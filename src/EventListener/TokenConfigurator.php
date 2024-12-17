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

namespace BadPixxel\Tasking\EventListener;

use BadPixxel\Tasking\Entity\Token;
use BadPixxel\Tasking\Services\Configuration;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Listen to Doctrine Events to Configure Tokens
 */
#[AutoconfigureTag("doctrine.event_listener", array(
    "event" => Events::postPersist,
    "entity" => Token::class
))]
#[AutoconfigureTag("doctrine.event_listener", array(
    "event" => Events::postLoad,
    "entity" => Token::class
))]
class TokenConfigurator
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $this
            ->configure($args)
        ;
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this
            ->configure($args)
        ;
    }

    /**
     * Configure Token Parameters from Tasking Configuration
     *
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function configure(LifecycleEventArgs $args): static
    {
        $entity = $args->getObject();

        if ($entity instanceof Token) {
            $entity->setSelfReleaseDelay($this->configuration->getTokenSelfReleaseDelay());
        }

        return $this;
    }
}
