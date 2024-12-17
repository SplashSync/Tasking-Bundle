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
 * Interface for Multi-Run Jobs (Batch or Mass)
 */
interface RepeatableJobInterface extends JobInterface
{
    /**
     * Check if Job Must be Started Again
     */
    public function isCompleted(): bool;

    /**
     * Get Job Raw Inputs for Backup
     */
    public function getRawInputs(): array;
}
