<?php

/*
 *  Copyright (C) BadPixxel <www.badpixxel.com>
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
 * Task Keep-Alive Event : Signal Current Task is Still Working
 *
 * Any code (job, service, listener) may dispatch it to extend the lifetime of the
 * running task, without depending on the Status Monitor. Outside a running task
 * (Http request, console command...), dispatching it is a no-op.
 */
class TaskKeepAliveEvent extends GenericEvent
{
    /**
     * Was the Requested Lifetime Granted
     */
    private bool $accepted = false;

    /**
     * @param null|int $nbSeconds Requested Lifetime in Seconds, defaults to Workers Watchdog Delay
     */
    public function __construct(
        private readonly ?int $nbSeconds = null
    ) {
        parent::__construct();
    }

    /**
     * Get Requested Lifetime in Seconds
     *
     * @return null|int
     */
    public function getNbSeconds(): ?int
    {
        return $this->nbSeconds;
    }

    /**
     * Check if Requested Lifetime was Granted
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * Set if Requested Lifetime was Granted
     *
     * @param bool $accepted
     *
     * @return static
     */
    public function setAccepted(bool $accepted): static
    {
        $this->accepted = $accepted;

        return $this;
    }
}
