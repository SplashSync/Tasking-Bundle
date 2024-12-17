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

use BadPixxel\Tasking\Interfaces\BatchJobInterface;
use BadPixxel\Tasking\Interfaces\JobInterface;
use BadPixxel\Tasking\Interfaces\MassJobInterface;
use BadPixxel\Tasking\Interfaces\RepeatableJobInterface;
use BadPixxel\Tasking\Interfaces\StaticJobInterface;
use BadPixxel\Tasking\Resolver\TaskSettingsResolver;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * Manage Available Jobs
 */
class JobsManager
{
    /**
     * Command Constructor
     */
    public function __construct(
        private readonly JobLocator $jobLocator,
        private readonly JobConfigurator $jobConfigurator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Resolve Task Configuration using Job Static Configuration and Received Settings
     */
    public function getService(string $serviceIdOrClass): ?JobInterface
    {
        return $this->jobLocator->get($serviceIdOrClass);
    }

    /**
     * Resolve Task Configuration using Job Static Configuration and Received Settings
     */
    public function getConfiguration(string $serviceId, array $options = array()): ?array
    {
        return $this->jobConfigurator->getConfiguration($serviceId, $options);
    }

    //====================================================================//
    // ALL JOBS
    //====================================================================//

    /**
     * Get All Available Jobs Configurations
     *
     * @return array<string, array>
     */
    public function getAllConfiguredJobs(): array
    {
        $jobConfigurations = array();

        foreach ($this->jobLocator->getAll() as $serviceId => $jobService) {
            if ($jobConfiguration = $this->jobConfigurator->getRawConfiguration($serviceId, $jobService)) {
                $jobConfigurations[(string) $serviceId] = $jobConfiguration;
            }
        }

        return $jobConfigurations;
    }

    //====================================================================//
    // Job Informations
    //====================================================================//

    /**
     * Get Job Type String
     */
    public function getType(string $serviceId): string
    {
        if ($this->isStatic($serviceId)) {
            return "Static";
        }

        if ($this->isBatch($serviceId)) {
            return "Batch";
        }

        if ($this->isMass($serviceId)) {
            return "Mass";
        }

        return "Generic";
    }

    /**
     * Get Job Title from Settings
     */
    public function getLabel(array $settings): string
    {
        return $this->getTranslatedSetting($settings, "label");
    }

    /**
     * Get Job Descriptions from Settings
     */
    public function getDescriptions(array $settings): string
    {
        return $this->getTranslatedSetting($settings, "description");
    }

    //====================================================================//
    // REPEATABLE JOBS
    //====================================================================//

    /**
     * Check if a Job is a Repeatable Job
     */
    public function isRepeatable(string|JobInterface $serviceIdOrObject): ?RepeatableJobInterface
    {
        $jobService = is_string($serviceIdOrObject)
            ? $this->jobLocator->get($serviceIdOrObject)
            : $serviceIdOrObject
        ;

        return $jobService ? $this->jobLocator->isRepeatable($jobService) : null;
    }

    //====================================================================//
    // STATIC JOBS
    //====================================================================//

    /**
     * Get All Static Jobs
     *
     * @return JobInterface[]
     */
    public function getStaticJobs(): array
    {
        return $this->jobLocator->getStaticJobs();
    }

    /**
     * Check if a Job is a Static Job
     */
    public function isStatic(string|JobInterface $serviceIdOrObject): ?StaticJobInterface
    {
        $jobService = is_string($serviceIdOrObject)
            ? $this->jobLocator->get($serviceIdOrObject)
            : $serviceIdOrObject
        ;

        return $jobService ? $this->jobLocator->isStatic($jobService) : null;
    }

    //====================================================================//
    // BATCH JOBS
    //====================================================================//

    /**
     * Get All Batch Jobs
     *
     * @return BatchJobInterface[]
     */
    public function getBatchJobs(): array
    {
        return $this->jobLocator->getBatchJobs();
    }

    /**
     * Check if a Job is a Batch Job
     */
    public function isBatch(string|JobInterface $serviceIdOrObject): ?BatchJobInterface
    {
        $jobService = is_string($serviceIdOrObject)
            ? $this->jobLocator->get($serviceIdOrObject)
            : $serviceIdOrObject
        ;

        return $jobService ? $this->jobLocator->isBatch($jobService) : null;
    }

    //====================================================================//
    // MASS JOBS
    //====================================================================//

    /**
     * Get All Mass Jobs
     *
     * @return MassJobInterface[]
     */
    public function getMassJobs(): array
    {
        return $this->jobLocator->getMassJobs();
    }

    /**
     * Check if a Job is a Mass Job
     */
    public function isMass(string|JobInterface $serviceIdOrObject): ?MassJobInterface
    {
        $jobService = is_string($serviceIdOrObject)
            ? $this->jobLocator->get($serviceIdOrObject)
            : $serviceIdOrObject
        ;

        return $jobService ? $this->jobLocator->isMass($jobService) : null;
    }

    /**
     * Get Job Translated Setting
     */
    private function getTranslatedSetting(array $settings, string $key): string
    {
        static $resolver;

        $resolver ??= new TaskSettingsResolver();

        try {
            $resolved = $resolver->resolve($settings);
        } catch (Exception $exception) {
            return $exception->getMessage();
        }

        Assert::stringNotEmpty($settings[$key]);

        if ($resolved["translation_domain"]) {
            return $this->translator->trans(
                $settings[$key],
                $resolved["translation_params"],
                $resolved["translation_domain"]
            );
        }

        return $settings[$key];
    }
}
