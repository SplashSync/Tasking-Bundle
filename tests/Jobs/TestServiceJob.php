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

use Splash\Tasking\Model\AbstractServiceJob;

/**
 * Demonstartion fo Simple Background Jobs
 */
class TestServiceJob extends AbstractServiceJob
{
    /**
     * {@inheritdoc}
     */
    protected array $inputs = array(
        "Service" => "tasking.sampling.service",
        "Method" => "delayTask",
        "Inputs" => array("Delay" => 1),
    );

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Service Job",
        "description" => "Abstract Service Job Base",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    /**
     * {@inheritdoc}
     */
    protected ?string $token = "JOB_SERVICE";
}
