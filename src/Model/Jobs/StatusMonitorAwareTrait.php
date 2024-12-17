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

namespace BadPixxel\Tasking\Model\Jobs;

use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use Webmozart\Assert\Assert;

trait StatusMonitorAwareTrait
{
    protected readonly StatusMonitor $statusMonitor;

    /**
     * @inheritdoc
     */
    public function getStatusMonitor(): StatusMonitor
    {
        Assert::notEmpty($this->statusMonitor);

        return $this->statusMonitor;
    }

    /**
     * @inheritdoc
     */
    public function requireLifetime(int $nbSeconds): bool
    {
        return $this->getStatusMonitor()->requireLifetime($nbSeconds);
    }

    /**
     * @inheritdoc
     */
    public function hasLifetime(int $nbSeconds): bool
    {
        return $this->getStatusMonitor()->hasLifetime($nbSeconds);
    }
}
