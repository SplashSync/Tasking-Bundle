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
 * Access to Token Tasking Parameters
 */
trait TokenParametersGettersTrait
{
    /**
     * @return int
     */
    public static function getTokenSelfReleaseDelay(): int
    {
        return (int) self::$config['token']['lock_ttl'];
    }

    /**
     * @return int
     */
    public static function getTokenDeleteDelay(): int
    {
        return (int) self::$config['token']['delete_ttl'];
    }

    /**
     * Complete Configuration for Tokens
     *
     * @param array $configuration
     *
     * @return array
     */
    private static function completeTokenConfiguration(array &$configuration): array
    {
        $configuration["token"] = array(
            // Compute Token Min Lock Delay
            "lock_ttl" => 10 * $configuration["watchdog_delay"],
            // Compute Token Delete Delay
            "delete_ttl" => 100 * $configuration["watchdog_delay"],
        );

        return $configuration;
    }
}
