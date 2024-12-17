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

namespace BadPixxel\Tasking\Events;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Start Task Event : Add a new Task to Queue & Start Runners
 *
 * @method string getSubject()
 */
class StartTaskEvent extends GenericEvent
{
    /**
     * @param string $serviceIdOrClass Job Service ID or Class
     * @param array  $arguments        Task Configuration
     */
    public function __construct(string $serviceIdOrClass, array $arguments = array())
    {
        parent::__construct($serviceIdOrClass, $arguments);
    }
}
