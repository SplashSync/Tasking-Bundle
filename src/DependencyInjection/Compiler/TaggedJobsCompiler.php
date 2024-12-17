<?php

declare(strict_types=1);

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

namespace BadPixxel\Tasking\DependencyInjection\Compiler;

use BadPixxel\Tasking\Dictionary\TaskingTags;
use BadPixxel\Tasking\Helper\JobValidator;
use BadPixxel\Tasking\Services\Jobs\JobConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

class TaggedJobsCompiler implements CompilerPassInterface
{
    /**
     * Compiled Array of Tasking Jobs Configurations
     *
     * @var array[]
     */
    private array $configurations = array();

    public function process(ContainerBuilder $container): void
    {
        //==============================================================================
        // Build List of Flex Services
        foreach ($container->findTaggedServiceIds(TaskingTags::JOB) as $id => $tags) {
            //==============================================================================
            // Verify Only One Tag is Registered
            Assert::count($tags, 1, "Tasking jobs must be registered only once");
            foreach ($tags as $tag) {
                Assert::classExists($class = (string) $container->getDefinition($id)->getClass());
                //==============================================================================
                // Verify Tag Configuration
                JobValidator::validate($class, $tag);
                //==============================================================================
                // Register Tag Configuration
                $this->configurations[$id] ??= $tag;
            }
        }
        //==============================================================================
        // Configure Flex Services for Resolver
        $container->getDefinition(JobConfigurator::class)->setArgument('$configurations', $this->configurations);
    }
}
