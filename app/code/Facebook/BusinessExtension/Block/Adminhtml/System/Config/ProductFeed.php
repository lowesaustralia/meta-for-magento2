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

namespace Facebook\BusinessExtension\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductFeed extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Facebook_BusinessExtension::system/config/product_feed.phtml';

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @todo move to helper
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('fbeadmin/ajax/productFeedUpload', ['store' => $this->getRequest()->getParam('store')]);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreNameForComment()
    {
        return $this->getRequest()->getParam('store')
            ? $this->_storeManager->getStore($this->getRequest()->getParam('store'))->getName()
            : $this->_storeManager->getDefaultStoreView()->getName();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        /** @var Button $button */
        $button = $this->getLayout()->createBlock(Button::class);
        return $button->setData(['id' => 'fb_feed_upload_btn', 'label' => __('Upload to Facebook')])
            ->toHtml();
    }
}
