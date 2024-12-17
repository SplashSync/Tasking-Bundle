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

namespace BadPixxel\Tasking\Resolver;

use BadPixxel\Tasking\Dictionary\JobOptions;

class StaticTaskOptionsResolver extends GenericTaskOptionsResolver
{
    public function __construct()
    {
        parent::__construct();
        //====================================================================//
        // Job Frequency
        $this->setDefault(JobOptions::FREQUENCY, 60);
        $this->setAllowedTypes(JobOptions::FREQUENCY, "int");
    }
}
