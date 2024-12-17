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
use BadPixxel\Tasking\Dictionary\RepeatableJobOptions;
use BadPixxel\Tasking\Dictionary\TaskingTags;
use BadPixxel\Tasking\Dictionary\TaskPriority;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Register this Service a Batch Task
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsTaskingBatchJob extends AutoconfigureTag
{
    public function __construct(
        /**
         * Name of Task Action to Execute
         */
        string $action = "execute",
        /**
         * Number of tasks to start on each batch step
         */
        int $paginate = 1,
        /**
         * If Set, if one of the batch action return False, batch action is stopped
         */
        bool $stopOnError = true,
        /**
         * Default Task Priority
         */
        int $priority = TaskPriority::LOWEST,
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
            RepeatableJobOptions::PAGINATION => $paginate,
            RepeatableJobOptions::STOP_ON_ERROR => $stopOnError,
            JobOptions::PRIORITY => $priority,
            JobOptions::TOKEN => $token,
            JobOptions::INDEX_KEY_1 => $indexKey1,
            JobOptions::INDEX_KEY_2 => $indexKey2,
        ));
    }
}
