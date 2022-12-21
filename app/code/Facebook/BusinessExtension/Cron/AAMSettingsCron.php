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

namespace Facebook\BusinessExtension\Cron;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class AAMSettingsCron
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * AAMSettingsCron constructor
     *
     * @param FBEHelper $fbeHelper
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        FBEHelper $fbeHelper,
        SystemConfig $systemConfig
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->systemConfig = $systemConfig;
    }

    public function execute()
    {
        $pixelId = $this->systemConfig->getPixelId();
        $settingsAsString = null;
        if ($pixelId) {
            $settingsAsString = $this->fbeHelper->fetchAndSaveAAMSettings($pixelId);
            if (!$settingsAsString) {
                $this->fbeHelper->log('Error saving settings. Currently:', $settingsAsString);
            }
        }
        return $settingsAsString;
    }
}
