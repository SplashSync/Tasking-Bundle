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

namespace BadPixxel\Tasking\Tests\Bundle\Resources\config;

use BadPixxel\Tasking\Actions\Start;
use BadPixxel\Tasking\Dictionary\TaskingRoutes;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Routing for Tasking Bundle Remote API
 */
return function (RoutingConfigurator $routes): void {
    $routes
        ->add(TaskingRoutes::START, '/start')->controller(Start::class)
    ;
};
