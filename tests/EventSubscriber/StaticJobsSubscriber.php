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

namespace Splash\Tasking\Tests\EventSubscriber;

use Exception;
use Splash\Tasking\Events\StaticTasksListingEvent;
use Splash\Tasking\Tests\Jobs\TestListenerJob;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StaticJobsSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            StaticTasksListingEvent::class => "onStaticTasksListing",
        );
    }

    /**
     * Register Dummy Static Jobs
     *
     * @throws Exception
     */
    public function onStaticTasksListing(StaticTasksListingEvent $event): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $event->addStaticTask(
                TestListenerJob::class,
                "ListenerStaticJob",
                30,
                array(
                    "index" => $i,
                    "Delay-Ms" => 100
                )
            );
        }
    }
}
