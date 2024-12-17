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

namespace BadPixxel\Tasking\Helper;

use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Interfaces\JobInterface;
use Webmozart\Assert\Assert;

class JobValidator
{
    /**
     * Validate Job Configuration
     *
     * @param string $jobClass
     * @param array  $jobConfig
     *
     * @return bool
     */
    public static function validate(string $jobClass, array $jobConfig): bool
    {
        //====================================================================//
        // Validate Job Class
        Assert::implementsInterface(
            $jobClass,
            JobInterface::class,
            'Invalid Job: Expected a sub-class of %2$s. Got: %s'
        );
        //====================================================================//
        // Validate Job Action
        Assert::stringNotEmpty($jobConfig[JobOptions::ACTION], "Job Action cannot be empty.");
        Assert::methodExists($jobClass, $jobConfig[JobOptions::ACTION], "Job Action doesn't exist.");
        //====================================================================//
        // Validate Job Token
        Assert::nullOrStringNotEmpty($jobConfig[JobOptions::TOKEN], "Job token is invalid.");
        //====================================================================//
        // Validate Job Priority
        Assert::positiveInteger($jobConfig[JobOptions::PRIORITY], "Job Priority must be an integer.");

        return true;
    }
}
