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

use BadPixxel\Tasking\Dictionary\RepeatableJobOptions;

class RepeatableTaskOptionsResolver extends GenericTaskOptionsResolver
{
    public function __construct()
    {
        parent::__construct();
        //====================================================================//
        // Batch Pagination
        $this->setDefault(RepeatableJobOptions::PAGINATION, 1);
        $this->setAllowedTypes(RepeatableJobOptions::PAGINATION, "int");
        //====================================================================//
        // Stop On Error Option
        $this->setDefault(RepeatableJobOptions::STOP_ON_ERROR, true);
        $this->setAllowedTypes(RepeatableJobOptions::STOP_ON_ERROR, "boolean");
    }
}
