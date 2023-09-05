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

namespace Meta\Catalog\Cron;

use Exception;
use Meta\Catalog\Model\Product\Feed\Uploader;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;

class UploadInventory
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param SystemConfig $systemConfig
     * @param Uploader $uploader
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        SystemConfig $systemConfig,
        Uploader $uploader,
        FBEHelper $fbeHelper
    ) {
        $this->systemConfig = $systemConfig;
        $this->uploader = $uploader;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Upload inventory for store
     *
     * @param int $storeId
     * @return $this
     * @throws LocalizedException
     */
    private function uploadForStore($storeId)
    {
        if ($this->isUploadEnabled($storeId)) {
            $this->uploader->uploadInventory($storeId);
        }

        return $this;
    }

    /**
     * Check configuration state of upload
     *
     * @param int $storeId Store ID to check.
     * @return bool
     */
    private function isUploadEnabled($storeId)
    {
        return $this->systemConfig->isCatalogSyncEnabled($storeId);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->systemConfig->getStoreManager()->getStores() as $store) {
            try {
                $this->uploadForStore($store->getId());
            } catch (Exception $e) {
                $context = [
                    'store_id' => $store->getId(),
                    'event' => 'inventory_sync',
                    'event_type' => 'upload_inventory_cron',
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
            }
        }
    }
}
