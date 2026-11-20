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

namespace BadPixxel\Tasking\EventListener;

use BadPixxel\Tasking\Events\TaskKeepAliveEvent;
use BadPixxel\Tasking\Services\Configuration;
use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

/**
 * Keep Running Tasks Alive on Demand
 *
 * Resets the watchdog, then marks the task & its worker as alive in database,
 * so the task is neither replayed nor its worker restarted while it works.
 */
class TaskKeepAliveSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StatusMonitor $statusMonitor,
        private readonly Configuration $configuration,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            TaskKeepAliveEvent::class => "onKeepAlive",
        );
    }

    /**
     * Keep Current Task Alive
     *
     * @param TaskKeepAliveEvent $event
     *
     * @return void
     */
    public function onKeepAlive(TaskKeepAliveEvent $event): void
    {
        //==============================================================================
        // Outside a Running Task => No-Op
        if (!$this->statusMonitor->isJobRunning()) {
            return;
        }
        //==============================================================================
        // Mark Task & Worker as Alive in Database (Throttled)
        if ($this->statusMonitor->isJobAliveRefreshNeeded()) {
            $this->markAlive();
        }
        //==============================================================================
        // Extend Current Job Lifetime
        $event->setAccepted($this->statusMonitor->keepAlive(
            $event->getNbSeconds() ?? $this->configuration->getWorkerWatchdogDelay()
        ));
    }

    /**
     * Mark Current Task & Worker as Alive in Database
     *
     * Job lifetime only slides once the database is up to date: the process never
     * believes it is alive longer than what the replay rule sees.
     *
     * @return void
     */
    private function markAlive(): void
    {
        $now = new DateTime();

        try {
            //==============================================================================
            // Refresh Worker Last Seen
            if ($workerId = $this->statusMonitor->getWorkerId()) {
                $this->configuration->getWorkerRepository()->touchLastSeen($workerId, $now);
            }
            //==============================================================================
            // Refresh Task Last Alive
            if ($taskId = $this->statusMonitor->getTaskId()) {
                $this->configuration->getTasksRepository()->touchAlive($taskId, $now->getTimestamp());
                $this->statusMonitor->setJobAlive();
            }
        } catch (Throwable $throwable) {
            //==============================================================================
            // A Keep-Alive Signal must Never Break the Running Job
            $this->logger->error("Keep-Alive: Unable to mark task as alive: ".$throwable->getMessage());
        }
    }
}
