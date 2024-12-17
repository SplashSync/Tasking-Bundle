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

namespace BadPixxel\Tasking\TwigComponent\Workers;

use BadPixxel\Tasking\Entity\Worker;
use BadPixxel\Tasking\Model\Component\ComponentWithRefreshTrait;
use BadPixxel\Tasking\Services\Configuration;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Render Status of Tasking Workers
 */
#[AsLiveComponent(
    name: "Tasking:Workers:Status",
    template: "@BadpixxelTasking/Component/Workers/status.html.twig"
)]
class WorkersStatus
{
    use ComponentWithRefreshTrait;

    /**
     * List of Available Workers
     *
     * @var Worker[]
     */
    public array $workers = array();

    public function __construct(
        private readonly Configuration $configuration
    ) {
    }

    #[PostMount]
    public function __invoke(): void
    {
        $this->workers = $this->configuration->getWorkerRepository()->findAll();
    }
}
