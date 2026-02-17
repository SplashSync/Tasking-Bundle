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

return array(
    'app' => array(
        'path' => './assets/app.js',
        'entrypoint' => true,
    ),
    '@symfony/stimulus-bundle' => array(
        'path' => '@symfony/stimulus-bundle/loader.js',
    ),
    '@symfony/ux-live-component' => array(
        'path' => '@symfony/ux-live-component/live_controller.js',
    ),
    '@hotwired/stimulus' => array(
        'version' => '3.2.2',
    ),
);
