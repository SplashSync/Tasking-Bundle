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

use Splash\Tasking\Model\AbstractServiceJob;

/**
 * Demonstartion fo Simple Background Jobs
 */
class TestServiceJob extends AbstractServiceJob
{
    /**
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected $inputs = array(
        "Service" => "Tasking.Sampling.Service",
        "Method" => "delayTask",
        "Inputs" => array("Delay" => 1),
    );

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected $settings = array(
        "label" => "Service Job",
        "description" => "Abstract Service Job Base",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    /**
     * Job Token is Used for concurency Management
     * You can set it directly by overriding this constant
     * or by writing an array of parameters to setJobToken()
     *
     * @var string
     */
    protected $token = "JOB_SERVICE";
}
