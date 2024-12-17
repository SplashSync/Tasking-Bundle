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

namespace BadPixxel\Tasking\Dictionary;

/**
 * Dictionary for Tasks Priority
 */
enum TaskPriority
{
    /** @var int */
    const HIGHEST = 10;

    /** @var int */
    const HIGH = 7;

    /** @var int */
    const NORMAL = 5;

    /** @var int */
    const LOW = 3;

    /** @var int */
    const LOWEST = 1;
}
