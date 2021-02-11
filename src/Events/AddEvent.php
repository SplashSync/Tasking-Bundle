<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Events;

use Splash\Tasking\Model\AbstractJob;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * New Add Event : Add a new Task to Queue
 */
class AddEvent extends GenericEvent
{
    /**
     * Encapsulate an event with $subject and $args.
     *
     * @param AbstractJob $subject   The subject of the event, usually an object or a callable
     * @param array       $arguments Arguments to store in the event
     */
    public function __construct(AbstractJob $subject, array $arguments = array())
    {
        parent::__construct($subject, $arguments);
    }
}
