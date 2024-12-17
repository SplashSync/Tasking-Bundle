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

/**
 * Access to Core Tasking Parameters
 */
trait CoreParametersGettersTrait
{
    /**
     * Get Name of Environment Used by Worker
     */
    public function getEnvironmentName(): string
    {
        return (string) ($this->config['environment'] ?? "prod");
    }

    /**
     * Get Name of Doctrine Entity Manager Used by Tasking Bundle
     */
    public function getEntityManagerName(): string
    {
        return (string) $this->config['entity_manager'];
    }

    /**
     * Is Multi-Server Mode Activated ?
     */
    public function isMultiServer(): bool
    {
        return (bool) $this->config['multiserver'];
    }

    /**
     * Get Multi-Server Path
     *
     * @return null|string
     */
    public function getMultiServerPath(): ?string
    {
        if (!$this->isMultiServer()) {
            return null;
        }

        return (string) $this->config['multiserver_path'];
    }

    /**
     * Get Raw Configuration for Tasking
     *
     * @return array
     */
    public function getRawConfiguration(): array
    {
        return $this->config;
    }
}
