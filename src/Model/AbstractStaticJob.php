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

namespace Splash\Tasking\Model;

use Splash\Tasking\Entity\Task;

/**
 * Base Class for Background Jobs Definition
 */
abstract class AbstractStaticJob extends AbstractJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Priority
     *
     * @var int
     */
    protected static int $priority = Task::DO_LOW;

    /**
     * Job Frequency
     * How often (in Minutes) shall this task be executed
     *
     * @var int
     */
    protected int $frequency = 60;

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Set Job Frequency in Minutes
     *
     * @param int $frequency
     *
     * @return $this
     */
    public function setFrequency(int $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get Job Frequency
     *
     * @return int
     */
    public function getFrequency(): int
    {
        return $this->frequency;
    }
}
