<?php /** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Meta\Catalog\Model\Product\Feed\Method;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Builder;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Configurable as ConfigurableProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Simple as SimpleProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetriever\Other as OtherProductRetriever;
use Meta\Catalog\Model\Product\Feed\ProductRetrieverInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;

class BatchApi
{
    private const ATTR_METHOD = 'method';
    private const ATTR_UPDATE = 'UPDATE';
    private const ATTR_DELETE = 'DELETE';
    private const ATTR_DATA = 'data';

    // Process only the maximum allowed by API per request
    private const BATCH_MAX = 4999;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ProductRetrieverInterface[]
     */
    private $productRetrievers;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param FBEHelper $helper
     * @param GraphAPIAdapter $graphApiAdapter
     * @param SystemConfig $systemConfig
     * @param SimpleProductRetriever $simpleProductRetriever
     * @param ConfigurableProductRetriever $configurableProductRetriever
     * @param OtherProductRetriever $otherProductRetriever
     * @param Builder $builder
     */
    public function __construct(
        FBEHelper $helper,
        GraphAPIAdapter $graphApiAdapter,
        SystemConfig $systemConfig,
        SimpleProductRetriever $simpleProductRetriever,
        ConfigurableProductRetriever $configurableProductRetriever,
        OtherProductRetriever $otherProductRetriever,
        Builder $builder
    ) {
        $this->fbeHelper = $helper;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->systemConfig = $systemConfig;
        $this->productRetrievers = [
            $simpleProductRetriever,
            $configurableProductRetriever,
            $otherProductRetriever
        ];
        $this->builder = $builder;
    }

    /**
     * Build product request
     *
     * @param Product $product
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    private function buildProductRequest(Product $product, $method = self::ATTR_UPDATE)
    {
        return [
            self::ATTR_METHOD => $method,
            self::ATTR_DATA => $this->builder->buildProductEntry($product)
        ];
    }

    /**
     * Build request for individual product
     *
     * @param string $productIdentifier
     */
    public function buildDeleteProductRequest(string $productIdentifier): array
    {
        return [
            self::ATTR_METHOD => self::ATTR_DELETE,
            self::ATTR_DATA => ['id' => $productIdentifier]
        ];
    }

    /**
     * Build request for individual product
     *
     * @param Product $product
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    public function buildRequestForIndividualProduct(Product $product, $method = self::ATTR_UPDATE)
    {
        $this->builder->setStoreId($product->getStoreId());
        return $this->buildProductRequest($product, $method);
    }

    /**
     * Generate product request data
     *
     * @param int|null $storeId
     * @param mixed|null $accessToken
     * @param bool $inventoryOnly
     * @return array
     * @throws Exception
     */
    public function generateProductRequestData($storeId = null, $accessToken = null, $inventoryOnly = false)
    {
        $this->fbeHelper->log($storeId ? "Starting batch upload for store $storeId" : 'Starting batch upload');
        $this->prepareApiAdapter($storeId, $accessToken, $inventoryOnly);

        $catalogId = $this->systemConfig->getCatalogId($storeId);

        $currentBatch = 1;
        $requests = [];
        $responses = [];
        $exceptions = 0;
        foreach ($this->productRetrievers as $productRetriever) {
            $offset = 0;
            $productRetriever->setStoreId($storeId);
            $limit = $productRetriever->getLimit();
            do {
                $products = $productRetriever->retrieve($offset);
                $offset += $limit;

                foreach ($products as $product) {
                    try {
                        $requests[] = $this->buildProductRequest($product);
                    } catch (Exception $e) {
                        $exceptions++;
                        $this->handleProductBuildException($exceptions, $e, $storeId);
                    }

                    if (count($requests) === self::BATCH_MAX) {
                        $responses[] = $this->flushCatalogBatchRequest($catalogId, $requests, $currentBatch);
                        $requests = [];
                        $currentBatch++;
                    }
                }
            } while (!empty($products));
        }

        if (!empty($requests)) {
            $responses[] = $this->flushCatalogBatchRequest($catalogId, $requests, $currentBatch);
        }

        return $responses;
    }

    /**
     * Prepare API adapter
     *
     * @param int|null $storeId
     * @param mixed|null $accessToken
     * @param bool $inventoryOnly
     * @return void
     */
    private function prepareApiAdapter($storeId, $accessToken, $inventoryOnly): void
    {
        $this->storeId = $storeId;
        $this->builder->setStoreId($storeId)
            ->setInventoryOnly($inventoryOnly);
        $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($accessToken ?? $this->systemConfig->getAccessToken($storeId));
    }

    /**
     * Flush catalog batch request
     *
     * @param mixed|null $catalogId
     * @param array $requests
     * @param int $currentBatch
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function flushCatalogBatchRequest($catalogId, array $requests, int $currentBatch)
    {
        $this->fbeHelper->log(sprintf('Pushing batch %d with %d products', $currentBatch, count($requests)));
        $response = $this->graphApiAdapter->catalogBatchRequest($catalogId, $requests);
        $this->fbeHelper->log('Product push response ' . json_encode($response));
        return $response;
    }

    /**
     * Handle product build exception
     *
     * @param int $exceptions
     * @param Exception $e
     * @return void
     * @throws Exception
     */
    private function handleProductBuildException(int $exceptions, Exception $e, $storeId): void
    {
        // Don't overload the logs, log the first 3 exceptions
        if ($exceptions <= 3) {
            $context = [
                'store_id' => $storeId,
                'event' => 'batch_api',
                'event_type' => 'batch_api_product_build',
                'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
        }
        // If it looks like a systemic failure : stop feed generation
        if ($exceptions > 100) {
            throw $e;
        }
    }
}
