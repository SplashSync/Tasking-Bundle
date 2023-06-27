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
use Splash\Tasking\Tests\Services\TasksSamplingService;

/**
 * Demonstration fo Simple Background Jobs
 */
class TestServiceJob extends AbstractServiceJob
{
    /**
     * {@inheritdoc}
     */
    protected array $inputs = array(
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

    /**
     * Service Job Constructor
     *
     * @param null|TasksSamplingService $service Target Service
     */
    public function __construct(?TasksSamplingService $service = null)
    {
        parent::__construct($service);
    }
}
