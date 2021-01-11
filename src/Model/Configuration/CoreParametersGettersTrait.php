<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Model\Configuration;

/**
 * Access to Core Tasking Parameters
 */
trait CoreParametersGettersTrait
{
    /**
     * @return string
     */
    public static function getEnvironmentName(): string
    {
        return (string) self::$config['environement'];
    }

    /**
     * @return string
     */
    public static function getEntityManagerName(): string
    {
        return (string) self::$config['entity_manager'];
    }

    /**
     * @return bool
     */
    public static function isMultiServer(): bool
    {
        return (bool) self::$config['multiserver'];
    }

    /**
     * @return null|string
     */
    public static function getMultiServerPath(): ?string
    {
        if (!self::isMultiServer()) {
            return null;
        }

        return (string) self::$config['multiserver_path'];
    }

    /**
     * Get Raw Configuration for Tasking
     *
     * @return array
     */
    public static function getRawConfiguration(): array
    {
        return self::$config;
    }
}
