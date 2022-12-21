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

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class CategoryUpload extends AbstractAjax
{
    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     * @param CategoryCollection $categoryCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig,
        CategoryCollection $categoryCollection
    ) {
        parent::__construct($context, $resultJsonFactory, $fbeHelper, $systemConfig);
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @return array
     */
    public function executeForJson()
    {
        $response = [];

        if (!$this->systemConfig->getAccessToken()) {
            $response['success'] = false;
            $response['message'] = __('Before uploading categories, set up the extension.');
            return $response;
        }

        try {
            $feedPushResponse = $this->categoryCollection->pushAllCategoriesToFbCollections();
            $response['success'] = true;
            $response['feed_push_response'] = $feedPushResponse;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
