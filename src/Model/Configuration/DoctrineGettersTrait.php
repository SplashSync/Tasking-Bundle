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

namespace BadPixxel\Tasking\Model\Configuration;

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Entity\Token;
use BadPixxel\Tasking\Entity\Worker;
use BadPixxel\Tasking\Repository\TaskRepository;
use BadPixxel\Tasking\Repository\TokenRepository;
use BadPixxel\Tasking\Repository\WorkerRepository;
use Doctrine\Persistence\ManagerRegistry as Registry;
use Doctrine\Persistence\ObjectManager;
use Webmozart\Assert\Assert;

/**
 * Access to Doctrine Services
 */
trait DoctrineGettersTrait
{
    /**
     * Tasking Doctrine Entity Manager
     *
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * Tasks Repository
     *
     * @var null|TaskRepository
     */
    private ?TaskRepository $taskRepository;

    /**
     * Worker Repository
     *
     * @var null|WorkerRepository
     */
    private ?WorkerRepository $workerRepository;

    /**
     * Token Repository
     *
     * @var null|TokenRepository
     */
    private ?TokenRepository $tokenRepository;

    /**
     * Get Entity Manager for Tasking
     *
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager
    {
        return $this->manager;
    }

    /**
     * Get Tasks Repository
     */
    public function getTasksRepository(): TaskRepository
    {
        if (!isset($this->taskRepository)) {
            $repository = $this->getEntityManager()->getRepository(Task::class);
            Assert::isInstanceOf(
                $repository,
                TaskRepository::class,
                "Unable to Load Tasks Repository"
            );

            return $this->taskRepository = $repository;
        }

        return $this->taskRepository;
    }

    /**
     * Get Worker Repository
     */
    public function getWorkerRepository(): WorkerRepository
    {
        if (!isset($this->workerRepository)) {
            $repository = $this->getEntityManager()->getRepository(Worker::class);
            Assert::isInstanceOf(
                $repository,
                WorkerRepository::class,
                "Unable to Load Worker Repository"
            );

            return $this->workerRepository = $repository;
        }

        return $this->workerRepository;
    }

    /**
     * Get Token Repository
     */
    public function getTokenRepository(): TokenRepository
    {
        if (!isset($this->tokenRepository)) {
            $repository = $this->getEntityManager()->getRepository(Token::class);
            Assert::isInstanceOf(
                $repository,
                TokenRepository::class,
                "Unable to Load Token Repository"
            );

            return $this->tokenRepository = $repository;
        }

        return $this->tokenRepository;
    }

    /**
     * Setup Entity Manager for Tasking
     */
    protected function setupEntityManager(Registry $registry): void
    {
        $this->manager = $registry->getManager($this->getEntityManagerName());
        $this->taskRepository = null;
        $this->tokenRepository = null;
        $this->workerRepository = null;
    }
}
