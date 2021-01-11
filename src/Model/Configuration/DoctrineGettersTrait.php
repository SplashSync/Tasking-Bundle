<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Model\Configuration;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\TokenRepository;
use Splash\Tasking\Repository\WorkerRepository;

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
    private static $manager;

    /**
     * Tasks Repository
     *
     * @var null|TaskRepository
     */
    private static $taskRepository;

    /**
     * Worker Repository
     *
     * @var null|WorkerRepository
     */
    private static $workerRepository;

    /**
     * Token Repository
     *
     * @var null|TokenRepository
     */
    private static $tokenRepository;

    /**
     * Get Entity Manager for Tasking
     *
     * @return ObjectManager
     */
    public static function getEntityManager(): ObjectManager
    {
        return self::$manager;
    }

    /**
     * Get Tasks Repository
     *
     * @throws Exception
     *
     * @return TaskRepository
     */
    public static function getTasksRepository(): TaskRepository
    {
        if (!isset(self::$taskRepository)) {
            $repository = self::$manager->getRepository(Task::class);
            if (!($repository instanceof TaskRepository)) {
                throw new Exception("Unable to Load Tasks Repository");
            }

            return self::$taskRepository = $repository;
        }

        return self::$taskRepository;
    }

    /**
     * Get Worker Repository
     *
     * @throws Exception
     *
     * @return WorkerRepository
     */
    public static function getWorkerRepository(): WorkerRepository
    {
        if (!isset(self::$workerRepository)) {
            $repository = self::$manager->getRepository(Worker::class);
            if (!($repository instanceof WorkerRepository)) {
                throw new Exception("Unable to Load Worker Repository");
            }

            return self::$workerRepository = $repository;
        }

        return self::$workerRepository;
    }

    /**
     * Get Token Repository
     *
     * @throws Exception
     *
     * @return TokenRepository
     */
    public static function getTokenRepository(): TokenRepository
    {
        if (!isset(self::$tokenRepository)) {
            $repository = self::$manager->getRepository(Token::class);
            if (!($repository instanceof TokenRepository)) {
                throw new Exception("Unable to Load Token Repository");
            }

            return self::$tokenRepository = $repository;
        }

        return self::$tokenRepository;
    }

    /**
     * Setup Entity Manager for Tasking
     *
     * @param Registry $registry
     *
     * @return void
     */
    protected function setupEntityManager(Registry $registry): void
    {
        self::$manager = $registry->getManager(self::getEntityManagerName());
        self::$taskRepository = null;
        self::$tokenRepository = null;
        self::$workerRepository = null;
    }
}
