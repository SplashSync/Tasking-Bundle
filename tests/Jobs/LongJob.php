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

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Tools\Status;

/**
 * Tests Of a Long Background Jobs
 */
class LongJob extends AbstractJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    protected array $inputs = array(
        // Allow Watchdog Renewal
        "Allow-Renewal" => true,
    );

    /**
     * {@inheritdoc}
     */
    protected ?string $token = "TEST_LONG_JOB";

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Long Job for Test",
        "description" => "Custom Long Job for Bundle Testing",
        "translation_domain" => false,
        "translation_params" => array(),
    );

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
                ? Status::requireLifetime(5)
                : Status::hasLifetime(5);
        } while ($hasLifetime);

        return true;
    }
}
