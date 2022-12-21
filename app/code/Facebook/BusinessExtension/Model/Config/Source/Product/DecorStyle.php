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

namespace Facebook\BusinessExtension\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class DecorStyle extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('Please Select')],
                ['value' => 'Bohemian', 'label' => __('Bohemian')],
                ['value' => 'Contemporary', 'label' => __('Contemporary')],
                ['value' => 'Industrial', 'label' => __('Industrial')],
                ['value' => 'Mid-Century', 'label' => __('Mid-Century')],
                ['value' => 'Modern', 'label' => __('Modern')],
                ['value' => 'Rustic', 'label' => __('Rustic')],
                ['value' => 'Vintage', 'label' => __('Vintage')],
            ];
        }
        return $this->_options;
    }
}
