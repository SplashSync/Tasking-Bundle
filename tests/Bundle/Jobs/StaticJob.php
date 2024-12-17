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

use BadPixxel\Tasking\Attribute\AsTaskingStaticJob;
use BadPixxel\Tasking\Interfaces\StaticJobInterface;
use BadPixxel\Tasking\Tests\Bundle\Model\AbstractDelayJob;

/**
 * Demonstration for Static Background Jobs
 */
#[AsTaskingStaticJob]
class StaticJob extends AbstractDelayJob implements StaticJobInterface
{
    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Static Job",
            "description" => "Static Job for Testing",
        );
    }
}
