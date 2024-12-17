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

use BadPixxel\Tasking\Dictionary\TaskPriority;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenericTaskOptionsResolver extends OptionsResolver
{
    public function __construct()
    {
        //====================================================================//
        // Job Action Method Name
        $this->setDefault("action", "execute");
        $this->setAllowedTypes("action", "string");
        //====================================================================//
        // Job Priority
        $this->setDefault("priority", TaskPriority::NORMAL);
        $this->setAllowedTypes("priority", "integer");
        //====================================================================//
        // Job Token
        $this->setDefault("token", null);
        $this->setAllowedTypes("token", array("null", "string"));
        //====================================================================//
        // Job Settings
        $this->setDefault("settings", array());
        $this->setAllowedTypes("settings", "array");
        //====================================================================//
        // Job Index Key 1
        $this->setDefault("indexKey1", null);
        $this->setAllowedTypes("indexKey1", array("null", "string"));
        //====================================================================//
        // Job Index Key 2
        $this->setDefault("indexKey2", null);
        $this->setAllowedTypes("indexKey2", array("null", "string"));
        //====================================================================//
        // Job Inputs
        $this->setDefault("inputs", array());
        $this->setAllowedTypes("inputs", "array");
    }
}
