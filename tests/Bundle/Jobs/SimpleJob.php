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
use BadPixxel\Tasking\Tests\Bundle\Model\AbstractDelayJob;

/**
 * Tests Of Simple Background Jobs
 */
#[AsTaskingJob(
    token: self::class
)]
class SimpleJob extends AbstractDelayJob
{
    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Simple Job",
            "description" => "Simple Job for Testing",
        );
    }
}
