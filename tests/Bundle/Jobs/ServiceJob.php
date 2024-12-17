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
use BadPixxel\Tasking\Model\AbstractServiceJob;
use BadPixxel\Tasking\Tests\Bundle\Services\TasksSamplingService;

/**
 * Demonstration fo Simple Background Jobs
 */
#[AsTaskingJob(
    token: self::class
)]
class ServiceJob extends AbstractServiceJob
{
    /**
     * Service Job Constructor
     *
     * @param null|TasksSamplingService $service Target Service
     */
    public function __construct(?TasksSamplingService $service = null)
    {
        parent::__construct($service);
    }

    /**
     * Build Job Options
     */
    public static function toOptions(
        string $method = "delayTask",
        array $args = array(
            "Delay" => 1
        ),
        ?string $token = null,
    ): array {
        return parent::toOptions($method, $args, $token);
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Service Job",
            "description" => "Execute Sample Service Action",
        );
    }
}
