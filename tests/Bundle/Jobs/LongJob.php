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

namespace BadPixxel\Tasking\Tests\Bundle\Jobs;

use BadPixxel\Tasking\Attribute\AsTaskingJob;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Interfaces\StatusMonitorAwareJobInterface;
use BadPixxel\Tasking\Model\AbstractJob;
use BadPixxel\Tasking\Model\Jobs\StatusMonitorAwareTrait;
use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Tests Of a Long Background Jobs
 */
#[AsTaskingJob(
    token: self::class
)]
class LongJob extends AbstractJob implements StatusMonitorAwareJobInterface
{
    use StatusMonitorAwareTrait;

    public function __construct(
        protected readonly StatusMonitor $statusMonitor
    ) {
    }

    //==============================================================================
    // Job Setup
    //==============================================================================

    /**
     * Build Job Options
     */
    public static function toOptions(
        bool $renewal = false,
        ?string $token = null,
    ): array {
        return array_filter(array(
            JobOptions::TOKEN => $token,
            JobOptions::INPUTS => array(
                "Allow-Renewal" => $renewal,
            ),
        ));
    }

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    public function execute() : bool
    {
        //==============================================================================
        // Loop until Task Delay is Reached
        do {
            sleep(1);
            $hasLifetime = $this->getInputs()["Allow-Renewal"]
                ? $this->requireLifetime(5)
                : $this->hasLifetime(5)
            ;
        } while ($hasLifetime);

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Long Job",
            "description" => "Custom Long Job for Bundle Testing",
        );
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            // Allow Watchdog Renewal
            "Allow-Renewal" => false,
        ));
    }
}
