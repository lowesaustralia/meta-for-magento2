<?php

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
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Config\Source\FeedUploadMethod;
use Meta\Catalog\Model\Product\Feed\Builder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Use for Feed Api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FeedApi
{
    private const FEED_FILE_NAME = 'facebook_products%s.csv';
    private const FB_FEED_NAME = 'Magento Autogenerated Feed';

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private $graphApiAdapter;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var array
     */
    private $productRetrievers;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param Filesystem $filesystem
     * @param array $productRetrievers
     * @param Builder $builder
     * @param LoggerInterface $logger
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        Filesystem $filesystem,
        array $productRetrievers,
        Builder $builder,
        LoggerInterface $logger
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fileSystem = $filesystem;
        $this->productRetrievers = $productRetrievers;
        $this->builder = $builder;
        $this->builder->setUploadMethod(FeedUploadMethod::UPLOAD_METHOD_FEED_API);
        $this->logger = $logger;
    }

    /**
     * Get feed id
     *
     * @param int $feedId
     * @param array $catalogFeeds
     * @param string $feedName
     * @param int $catalogId
     * @return void
     * @throws GuzzleException
     */

    private function getFeedId($feedId, $catalogFeeds, $feedName, $catalogId)
    {
        if ($feedId) {
            $magentoFeeds = array_filter($catalogFeeds, function ($a) use ($feedId) {
                return $a['id'] === $feedId;
            });
            if (empty($magentoFeeds)) {
                $feedId = null;
            }
        }

        if (!$feedId) {
            $magentoFeeds = array_filter($catalogFeeds, function ($a) use ($feedName) {
                return $a['name'] === $feedName;
            });
            if (!empty($magentoFeeds)) {
                $feedId = $magentoFeeds[array_key_first($magentoFeeds)]['id'];
            }
        }

        if (!$feedId) {
            $feedId = $this->graphApiAdapter->createEmptyFeed($catalogId, $feedName);
            $maxAttempts = 5;
            $attempts = 0;
            do {
                $feedData = $this->graphApiAdapter->getFeed($feedId);
                if ($feedData !== false) {
                    break;
                }
                $attempts++;
                usleep(2000000);
            } while ($attempts < $maxAttempts);
        }
    }

    /**
     * Get FB Feed Id
     *
     * @return mixed|null
     * @throws GuzzleException
     */
    private function getFbFeedId()
    {
        $feedId = $this->systemConfig->getFeedId($this->storeId);
        $feedName = self::FB_FEED_NAME;
        $catalogId = $this->systemConfig->getCatalogId($this->storeId);
        $catalogFeeds = $this->graphApiAdapter->getCatalogFeeds($catalogId);
        $this->getFeedId($feedId, $catalogFeeds, $feedName, $catalogId);
        if ($feedId && $this->systemConfig->getFeedId($this->storeId) != $feedId) {
            $this->systemConfig
                ->saveConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID, $feedId, $this->storeId)
                ->cleanCache();
        }
        return $feedId;
    }

    /**
     * Write file
     *
     * @param WriteInterface $fileStream
     * @throws FileSystemException
     * @throws Exception
     */
    private function writeFile(WriteInterface $fileStream)
    {
        $fileStream->writeCsv($this->builder->getHeaderFields());

        $total = 0;
        foreach ($this->productRetrievers as $productRetriever) {
            $productRetriever->setStoreId($this->storeId);
            $offset = 0;
            $limit = $productRetriever->getLimit();
            do {
                $products = $productRetriever->retrieve($offset);
                $offset += $limit;
                if (empty($products)) {
                    break;
                }
                foreach ($products as $product) {
                    $entry = array_values($this->builder->buildProductEntry($product));
                    $fileStream->writeCsv($entry);
                    $total++;
                }
            } while (true);
        }

        $this->logger->debug(sprintf('Generated feed with %d products.', $total));
    }

    /**
     * Get file name with store code suffix for non-default store (no suffix for default one)
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getFeedFileName()
    {
        $defaultStoreId = $this->systemConfig->getStoreManager()->getDefaultStoreView()->getId();
        $storeCode = $this->systemConfig->getStoreManager()->getStore($this->storeId)->getCode();
        return sprintf(
            self::FEED_FILE_NAME,
            ($this->storeId && $this->storeId !== $defaultStoreId) ? ('_' . $storeCode) : ''
        );
    }

    /**
     * Generate product feed
     *
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    private function generateProductFeed()
    {
        $file = 'export/' . $this->getFeedFileName();
        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->create('export');

        //return $directory->getAbsolutePath($file);

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $this->writeFile($stream);
        $stream->unlock();

        return $directory->getAbsolutePath($file);
    }

    /**
     * Execute function
     *
     * @param int|null $storeId
     * @return bool|mixed
     * @throws Exception
     */
    public function execute($storeId = null)
    {
        $this->storeId = $storeId;
        $this->builder->setStoreId($this->storeId);
        $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        try {
            $feedId = $this->getFbFeedId();
            if (!$feedId) {
                throw new LocalizedException(__('Cannot fetch feed ID'));
            }
            $feed = $this->generateProductFeed();
            return $this->graphApiAdapter->pushProductFeed($feedId, $feed);
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw $e;
        }
    }
}
