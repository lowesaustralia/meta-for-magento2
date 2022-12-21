<?php
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

namespace Facebook\BusinessExtension\Model\Config\Source;

class FeedUploadMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    const UPLOAD_METHOD_FEED_API = 'feed_api';
    const UPLOAD_METHOD_CATALOG_BATCH_API = 'catalog_batch_api';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::UPLOAD_METHOD_FEED_API, 'label' => __('Feed API')],
            ['value' => self::UPLOAD_METHOD_CATALOG_BATCH_API, 'label' => __('Catalog Batch API')],
        ];
    }
}
