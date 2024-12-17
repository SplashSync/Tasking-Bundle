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

namespace BadPixxel\Tasking;

use BadPixxel\Tasking\DependencyInjection\Compiler\TaggedJobsCompiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Badpixxel Tasking Bundle
 *
 * 100% PHP Advanced Tasks Scheduler for Symfony Applications
 */
class BadpixxelTaskingBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        //==============================================================================
        // Register Tagged Tasking Services
        $container->addCompilerPass(new TaggedJobsCompiler());
    }
}
