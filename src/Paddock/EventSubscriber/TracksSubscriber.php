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

namespace Splash\Tasking\Paddock\EventSubscriber;

use BadPixxel\Paddock\Core\Events\GetTracksEvent;
use Exception;
use Splash\Tasking\Paddock\Tracks;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TracksSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            GetTracksEvent::class => "registerTracks",
        );
    }

    /**
     * Add Bundle Tracks to Paddock
     *
     * @param GetTracksEvent $event
     *
     * @throws Exception
     */
    public function registerTracks(GetTracksEvent $event): void
    {
        $event->add(Tracks\TaskingCheckerTrack::class);
        $event->add(Tracks\WorkersCheckerTrack::class);
    }
}
