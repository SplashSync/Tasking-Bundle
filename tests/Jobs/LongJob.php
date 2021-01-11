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

namespace Splash\Tasking\Tests\Jobs;

use Exception;
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
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected $inputs = array(
        // Allow Watchdog Renewal
        "Allow-Renewal" => true,
    );

    /**
     * Job Token is Used for concurrency Management
     * You can set it directly by overriding this constant
     * or by writing an array of parameters to setJobToken()
     *
     * @var string
     */
    protected $token = "TEST_LONG_JOB";

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected $settings = array(
        "label" => "Long Job for Test",
        "description" => "Custom Long Job for Bundle Testing",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * Override this function to perform your task
     *
     * @throws Exception
     *
     * @return bool
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
