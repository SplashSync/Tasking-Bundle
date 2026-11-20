<?php

/*
 *  Copyright (C) BadPixxel <www.badpixxel.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace BadPixxel\Tasking\Model;

use BadPixxel\Tasking\Dictionary\TaskPriority;
use BadPixxel\Tasking\Interfaces\StaticJobInterface;

/**
 * Base Class for Background Jobs Definition
 */
abstract class AbstractStaticJob extends AbstractJob implements StaticJobInterface
{
    /**
     * Job Priority
     *
     * @var int
     */
    protected static int $priority = TaskPriority::LOW;
}
