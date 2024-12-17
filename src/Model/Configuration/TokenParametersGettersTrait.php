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

namespace BadPixxel\Tasking\Model\Configuration;

use BadPixxel\Tasking\Entity\Token;

/**
 * Access to Token Tasking Parameters
 */
trait TokenParametersGettersTrait
{
    /**
     * Get Token Delay for Self-Release
     */
    public function getTokenSelfReleaseDelay(): int
    {
        return (int) $this->config['token']['lock_ttl'];
    }

    /**
     * Get Token delay for Delete
     */
    public function getTokenDeleteDelay(): int
    {
        return (int) $this->config['token']['delete_ttl'];
    }

    /**
     * Complete Configuration for Tokens
     */
    protected static function completeTokenConfiguration(array &$configuration): void
    {
        $configuration["token"] = array(
            // Compute Token Min Lock Delay
            "lock_ttl" => 10 * $configuration["watchdog_delay"],
            // Compute Token Delete Delay
            "delete_ttl" => 100 * $configuration["watchdog_delay"],
        );
    }
}
