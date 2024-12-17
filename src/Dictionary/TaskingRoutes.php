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
 * Dictionary for Tasking Bundle Routes
 */
enum TaskingRoutes
{
    /**
     * Check / Start Tasking Workers from remote
     */
    const START = "badpixxel_tasking_api_start";

    /**
     * Get Tasking Status as Json Response
     */
    const STATUS = "badpixxel_tasking_status";

    /**
     * Get Tasking Admin & Demo Dashboard
     */
    const DASHBOARD = "badpixxel_tasking_dashboard";
}
