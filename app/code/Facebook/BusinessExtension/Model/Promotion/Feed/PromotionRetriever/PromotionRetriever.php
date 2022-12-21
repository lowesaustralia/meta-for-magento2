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

namespace Facebook\BusinessExtension\Model\Promotion\Feed\PromotionRetriever;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;

class PromotionRetriever
{
    const LIMIT = 2000;

    protected $storeId;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    protected $ruleCollection;

    /**
     * @param FBEHelper $fbeHelper
     */
    public function __construct(FBEHelper $fbeHelper, RuleCollection $ruleCollection
    )
    {
        $this->fbeHelper = $fbeHelper;
        $this->ruleCollection = $ruleCollection;

    }

    /**
     * @param $storeId
     * @return ProductRetrieverInterface|void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param int $limit
     * @return array
     */
    public function retrieve($limit = self::LIMIT): array
    {
        $catalogActiveRule = $this->ruleCollection->create()->addFieldToFilter('is_active', 1);
        return $catalogActiveRule->getItems();

    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
