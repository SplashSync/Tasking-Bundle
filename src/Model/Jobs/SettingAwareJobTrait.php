<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace BadPixxel\Tasking\Model\Jobs;

use BadPixxel\Tasking\Resolver\TaskSettingsResolver;

/**
 * Manage Tasks Settings
 */
trait SettingAwareJobTrait
{
    /**
     * Resolve Task Settings Resolver
     *
     * @throw InvalidArgumentException
     */
    public function resolveSettings(array $settings): array
    {
        //==============================================================================
        //  Merge Received Settings with Default Settings
        $mergedSettings = array_replace_recursive(
            static::getDefaultSettings(),
            $settings
        );

        //==============================================================================
        //  Resolve Merged Settings
        return static::getSettingsResolver()->resolve($mergedSettings);
    }

    /**
     * Get task default Settings
     */
    protected static function getDefaultSettings(): array
    {
        return array();
    }

    /**
     * Get Task Settings Resolver
     */
    protected static function getSettingsResolver(): TaskSettingsResolver
    {
        return new TaskSettingsResolver();
    }
}
