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
 * Dictionary for Job Input Arguments Names
 */
enum JobOptions
{
    /**
     * Action Method for this Job
     */
    const ACTION = "action";

    /**
     * Task Priority
     */
    const PRIORITY = "priority";

    /**
     * Task Settings
     */
    const SETTINGS = "settings";

    /**
     * Task INPUTS
     */
    const INPUTS = "inputs";

    /**
     * Task Token
     */
    const TOKEN = "token";

    /**
     * Task Indexing Key 1
     */
    const INDEX_KEY_1 = "indexKey1";

    /**
     * Task Indexing Key 2
     */
    const INDEX_KEY_2 = "indexKey2";

    /**
     * Task Frequency
     */
    const FREQUENCY = "frequency";
}
