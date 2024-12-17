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

namespace BadPixxel\Tasking\Services\Jobs;

use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Helper\JobValidator;
use BadPixxel\Tasking\Interfaces\JobInterface;
use BadPixxel\Tasking\Resolver\GenericTaskOptionsResolver;
use BadPixxel\Tasking\Resolver\RepeatableTaskOptionsResolver;
use BadPixxel\Tasking\Resolver\StaticTaskOptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Manage Jobs Configuration
 */
class JobConfigurator
{
    /**
     * Command Constructor
     */
    public function __construct(
        private readonly array $configurations,
        private readonly JobLocator $jobLocator,
    ) {
    }

    /**
     * Get All Available Job Configurations
     *
     * @return array[]
     */
    public function getAll(): array
    {
        return $this->configurations;
    }

    /**
     * Resolve Task Configuration using Job Static Configuration and Received Settings
     */
    public function getConfiguration(string $serviceId, array $options = array()): ?array
    {
        //====================================================================//
        // Identify Job Service
        if (!$jobService = $this->jobLocator->get($serviceId)) {
            return null;
        }
        //====================================================================//
        // Resolve Task Configuration
        $taskOptions = $this->getRawConfiguration($serviceId, $jobService, $options);
        //====================================================================//
        // Resolve Task Inputs
        Assert::isArray($taskOptions[JobOptions::INPUTS] ?? null);
        $taskOptions[JobOptions::INPUTS] = $jobService->resolveInputs($taskOptions[JobOptions::INPUTS]);

        return $taskOptions;
    }

    /**
     * Resolve Task Configuration using Job Static Configuration and Received Settings
     */
    public function getRawConfiguration(string $serviceId, JobInterface $jobService, array $options = array()): ?array
    {
        //====================================================================//
        // Detect Options Resolver to Use
        $resolver = $this->getTaskOptionResolver($jobService);
        //====================================================================//
        // Resolve Task Options
        $taskOptions = $resolver->resolve(array_replace_recursive(
            $this->configurations[$serviceId] ?? array(),
            $options
        ));
        //====================================================================//
        // Resolve Task Settings
        Assert::isArray($taskOptions[JobOptions::SETTINGS]);
        $taskOptions[JobOptions::SETTINGS] = $jobService->resolveSettings($taskOptions[JobOptions::SETTINGS]);
        //====================================================================//
        // Validate Core Options
        JobValidator::validate(get_class($jobService), $taskOptions);

        return $taskOptions;
    }

    /**
     * Detect Options Resolver to Use for this Task Type
     */
    private function getTaskOptionResolver(JobInterface $jobService): GenericTaskOptionsResolver
    {
        if ($this->jobLocator->isStatic($jobService)) {
            return new StaticTaskOptionsResolver();
        }
        if ($this->jobLocator->isRepeatable($jobService)) {
            return new RepeatableTaskOptionsResolver();
        }

        return new GenericTaskOptionsResolver();
    }
}
