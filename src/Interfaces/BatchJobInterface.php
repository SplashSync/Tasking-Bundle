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

namespace BadPixxel\Tasking\Interfaces;

/**
 * Interface for Batch Jobs
 */
interface BatchJobInterface extends RepeatableJobInterface
{
    /**
     * Generate list of your batch tasks inputs to threat
     *
     * @return array[]
     */
    public function configure(): array;

    /**
     * Execute Batch Job Step Action for Given Inputs
     */
    public function executeAction(array $inputs): bool;
}
