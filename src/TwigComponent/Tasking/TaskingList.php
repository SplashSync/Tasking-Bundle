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

namespace BadPixxel\Tasking\TwigComponent\Tasking;

use BadPixxel\Tasking\Model\Component\ComponentWithRefreshTrait;
use BadPixxel\Tasking\Services\Configuration;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Render Global Status of Tasking System
 */
#[AsLiveComponent(
    name:       "Tasking:List",
    template:   "@BadpixxelTasking/Component/Tasking/list.html.twig"
)]
class TaskingList
{
    use DefaultActionTrait;
    use ComponentWithRefreshTrait;

    /**
     * Task Status Summary
     *
     * @var array<string, array>
     */
    #[LiveProp]
    public array $statuses = array();

    /**
     * Filter on Task Index 1
     */
    #[LiveProp]
    public ?string $indexKey1 = null;

    /**
     * Filter on Task Index 2
     */
    #[LiveProp]
    public ?string $indexKey2 = null;

    /**
     * Limit Number of Tasks in List
     */
    #[LiveProp]
    public int $limit = 20;

    public function __construct(
        private readonly Configuration $configuration
    ) {
    }
    #[PostMount]
    public function __invoke(): void
    {
        //====================================================================//
        // Fetch List of Active Tasks
        $this->statuses = $this->configuration->getTasksRepository()
            ->getTasksStatus(
                key1: $this->indexKey1,
                key2: $this->indexKey2,
                limit: $this->limit,
            )
        ;
    }
}
