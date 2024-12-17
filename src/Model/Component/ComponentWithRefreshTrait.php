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

namespace BadPixxel\Tasking\Model\Component;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Webmozart\Assert\Assert;

trait ComponentWithRefreshTrait
{
    /**
     * Refresh Delay in Seconds
     */
    #[LiveProp]
    public int $refresh = 1;

    /**
     * Get refresh Delay in Milliseconds
     */
    public function getDelayMs(): string
    {
        Assert::greaterThanEq($this->refresh, 1);

        return (string) ($this->refresh * 1000);
    }
}
