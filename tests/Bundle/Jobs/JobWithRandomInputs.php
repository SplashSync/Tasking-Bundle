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
use BadPixxel\Tasking\Dictionary\TaskPriority;
use BadPixxel\Tasking\Tests\Bundle\Model\AbstractDelayJob;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple Jobs with Random Input Value
 * To test multiple tasks insert
 */
#[AsTaskingJob(
    priority: TaskPriority::HIGH,
    token: self::class,
)]
class JobWithRandomInputs extends AbstractDelayJob
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
            "label" => "[TEST] Job with Random Inputs",
            "description" => "Job for Testing Job Queue",
        );
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            "random" => uniqid("RANDOM", true),
            "Delay-S" => 1,
            "Delay-Ms" => 0,
        ));
    }
}
