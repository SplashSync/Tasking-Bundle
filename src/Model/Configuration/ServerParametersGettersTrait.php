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
 * Access to Server Tasking Parameters
 */
trait ServerParametersGettersTrait
{
    /**
     * @return bool
     */
    public static function isServerForceCrontab(): bool
    {
        return (bool) self::$config['server']['force_crontab'];
    }

    /**
     * @return string
     */
    public static function getServerPhpVersion(): string
    {
        return (string) self::$config['server']['php_version'];
    }
}
