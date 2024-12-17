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

namespace BadPixxel\Tasking\Model;

use BadPixxel\Tasking\Interfaces\JobInterface;
use BadPixxel\Tasking\Model\Jobs\InputsAwareJobTrait;
use BadPixxel\Tasking\Model\Jobs\SettingAwareJobTrait;

/**
 * Base Class for Background Jobs Definition
 */
abstract class AbstractJob implements JobInterface
{
    use SettingAwareJobTrait;
    use InputsAwareJobTrait;

    //==============================================================================
    // Task Execution (To Override)
    //==============================================================================

    /**
     * Override this function to validate you Input parameters
     *
     * @return bool
     */
    public function validate() : bool
    {
        return true;
    }

    /**
     * Override this function to prepare your class for it's execution
     *
     * @return bool
     */
    public function prepare() : bool
    {
        return true;
    }

    /**
     * Override this function to perform your task
     *
     * @return bool
     */
    public function execute() : bool
    {
        return true;
    }

    /**
     * Override this function to validate results of your task or perform post-actions
     *
     * @return bool
     */
    public function finalize() : bool
    {
        return true;
    }

    /**
     * Override this function to close your task
     *
     * @return bool
     */
    public function close() : bool
    {
        return true;
    }
}
