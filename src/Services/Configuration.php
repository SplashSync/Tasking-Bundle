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

namespace BadPixxel\Tasking\Services;

use BadPixxel\Tasking\Model\Configuration as ConfigurationTraits;
use BadPixxel\Tasking\Paddock\Tracks\WorkersCheckerTrack;
use Doctrine\Persistence\ManagerRegistry as Registry;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Webmozart\Assert\Assert;

/**
 * Manage Tasking Bundle Configuration
 */
#[Autoconfigure(bind: array(
    '$configuration' => "%badpixxel_tasking%"
))]
class Configuration
{
    use ConfigurationTraits\CoreParametersGettersTrait;
    use ConfigurationTraits\ServerParametersGettersTrait;
    use ConfigurationTraits\DoctrineGettersTrait;
    use ConfigurationTraits\SupervisorParametersGettersTrait;
    use ConfigurationTraits\WorkersParametersGettersTrait;
    use ConfigurationTraits\TokenParametersGettersTrait;
    use ConfigurationTraits\TasksParametersGettersTrait;

    /**
     * Tasking Service Configuration Array
     *
     * @var array
     */
    protected array $config;

    /**
     * Class Constructor
     *
     * @param array    $configuration
     * @param Registry $registry
     *
     * @throws Exception
     */
    public function __construct(array $configuration, Registry $registry)
    {
        //====================================================================//
        // Complete & Store Configuration
        self::loadConfiguration($configuration);
        //====================================================================//
        // Setup Doctrine Services
        self::setupEntityManager($registry);
    }

    /**
     * Check if Configuration is ready
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isReady(): bool
    {
        if (!isset($this->config)) {
            throw new Exception("Tasking Bundle Configuration is NOT Loaded");
        }

        return true;
    }

    /**
     * Get Raw Configuration for Tasking
     */
    public function loadConfiguration(array $configuration): array
    {
        //====================================================================//
        // Validate Configuration
        self::validateConfiguration($configuration);
        //====================================================================//
        // Complete & Store Configuration
        $this->config = self::completeConfiguration($configuration);
        //====================================================================//
        // Setup Static Parameters
        WorkersCheckerTrack::setSupervisorMaxWorkers($this->getSupervisorMaxWorkers());

        return $this->config;
    }

    /**
     * Validate Initial Configuration for Tasking
     */
    private static function validateConfiguration(array $configuration): void
    {
        //====================================================================//
        // Validate Number of Workers
        Assert::greaterThan(
            $configuration['supervisor']['max_workers'] ?? 0,
            0,
            "Number of Workers must by above 0"
        );
        //====================================================================//
        // Validate Watchdog delay
        Assert::greaterThanEq(
            $configuration['watchdog_delay'],
            $configuration['refresh_delay'],
            "Watchdog delay MUST be greater than Refresh delay"
        );
    }

    /**
     * Complete Initial Configuration for Tasking
     */
    private static function completeConfiguration(array $configuration): array
    {
        //====================================================================//
        // Compute Tasks Parameters
        self::completeTasksConfiguration($configuration);
        //====================================================================//
        // Compute Token Parameters
        self::completeTokenConfiguration($configuration);

        return $configuration;
    }
}
