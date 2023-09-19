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

namespace Splash\Tasking;

use Splash\Tasking\Services\TasksManager;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Splash Tasking Bundle
 *
 * 100% PHP Advanced Tasks Scheduler for Symfony Applications
 */
class SplashTaskingBundle extends Bundle
{
    public function boot(): void
    {
        //==============================================================================
        // Force Loading of Tasks Manager
        if (isset($this->container)) {
            $this->container->get(TasksManager::class);
        }
    }
}
