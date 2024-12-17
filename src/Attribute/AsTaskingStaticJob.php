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

namespace BadPixxel\Tasking\Attribute;

use Attribute;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Dictionary\TaskingTags;
use BadPixxel\Tasking\Dictionary\TaskPriority;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Register this Service a Static Task
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsTaskingStaticJob extends AutoconfigureTag
{
    public function __construct(
        /**
         * Name of Task Action to Execute
         */
        string $action = "execute",
        /**
         * Default Task Priority
         */
        int $priority = TaskPriority::LOW,
        /**
         * Default Task Frequency
         */
        int $frequency = 1,
        /**
         * Default Task Token to Use
         * Job Token is Used for concurrency Management
         */
        ?string $token = null,
        /**
         * Indexing Key 1
         */
        ?string $indexKey1 = null,
        /**
         * Indexing Key 2
         */
        ?string $indexKey2 = null,
    ) {
        parent::__construct(TaskingTags::JOB, array(
            JobOptions::ACTION => $action,
            JobOptions::PRIORITY => $priority,
            JobOptions::FREQUENCY => $frequency,
            JobOptions::TOKEN => $token,
            JobOptions::INDEX_KEY_1 => $indexKey1,
            JobOptions::INDEX_KEY_2 => $indexKey2,
        ));
    }
}
