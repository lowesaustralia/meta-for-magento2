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

namespace Meta\Conversion\Block\Pixel;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Escaper;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * @api
 */
class Search extends Common
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Search constructor
     *
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param SystemConfig $systemConfig
     * @param Escaper $escaper
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        Escaper $escaper,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $fbeHelper,
            $magentoDataHelper,
            $systemConfig,
            $escaper,
            $checkoutSession,
            $data
        );
        $this->escaper = $escaper;
    }
    /**
     * Get search query
     *
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->escaper->escapeHtml($this->getRequest()->getParam('q'));
    }

    /**
     * Get event to observer name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_search';
    }
}
