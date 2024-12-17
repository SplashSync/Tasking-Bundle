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

namespace BadPixxel\Tasking\Tests\Controller;

use BadPixxel\Tasking\Repository\TaskRepository;
use BadPixxel\Tasking\Repository\TokenRepository;
use BadPixxel\Tasking\Repository\WorkerRepository;
use BadPixxel\Tasking\Services\Configuration;
use BadPixxel\Tasking\Services\Jobs\JobConfigurator;
use BadPixxel\Tasking\Services\ProcessManager;
use BadPixxel\Tasking\Services\Runner;
use BadPixxel\Tasking\Services\Tasks\TaskFactory;
use BadPixxel\Tasking\Services\TasksManager;
use BadPixxel\Tasking\Services\TokenManager;
use BadPixxel\Tasking\Services\WorkersManager;
use Doctrine\Persistence\ObjectManager;
use Exception;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base Test Controller for Tasking Bundle PhpUnit Tests
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 */
abstract class AbstractTestController extends WebTestCase
{
    /**
     * @var ObjectManager
     */
    protected ObjectManager $entityManager;

    /**
     * @var TaskRepository
     */
    protected TaskRepository $tasksRepository;

    /**
     * @var WorkerRepository
     */
    protected WorkerRepository $workersRepository;

    /**
     * @var TokenRepository
     */
    protected TokenRepository $tokenRepository;

    /**
     * @var NullOutput
     */
    protected $output;

    /**
     * @var string
     */
    protected string $randomStr;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        //====================================================================//
        // Link to entity manager Services
        $this->entityManager = $this->getConfiguration()->getEntityManager();
        //====================================================================//
        // Link to Tasks Repository
        $this->tasksRepository = $this->getConfiguration()->getTasksRepository();
        //====================================================================//
        // Link to Token Repository
        $this->tokenRepository = $this->getConfiguration()->getTokenRepository();
        //====================================================================//
        // Link to Workers Repository
        $this->workersRepository = $this->getConfiguration()->getWorkerRepository();
        //====================================================================//
        // Generate a Fake Output
        $this->output = new NullOutput();
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
    }

    /**
     * Return List of Repeatable Jobs Configurations
     *
     * @return array
     */
    public static function jobsRepeatableProvider() : array
    {
        return array(
            "small" => array(
                "nbTasks" => 5,
                "msDelay" => 500
            ),
            "medium" => array(
                "nbTasks" => 20,
                "msDelay" => 500
            ),
            "intense" => array(
                "nbTasks" => 100,
                "msDelay" => 150,
            ),
        );
    }

    /**
     * Safe Get a Random String
     *
     * @return string
     */
    protected static function randomStr(): string
    {
        return base64_encode((string) rand((int) 1E5, (int) 1E10));
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object $object     Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @throws ReflectionException
     *
     * @return mixed Method return.
     */
    protected function invokeMethod(object &$object, string $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get Tasks Manager
     */
    protected function getTasksManager(): TasksManager
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                TasksManager::class,
                $service = $this->getContainer()->get(TasksManager::class)
            );
        }

        return $service;
    }

    /**
     * Get Token Manager
     */
    protected function getTokenManager(): TokenManager
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                TokenManager::class,
                $service = $this->getContainer()->get(TokenManager::class)
            );
        }

        return $service;
    }

    /**
     * Get Worker Manager
     *
     * @throws Exception
     *
     * @return WorkersManager
     */
    protected function getWorkersManager(): WorkersManager
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                WorkersManager::class,
                $service = $this->getContainer()->get(WorkersManager::class)
            );
        }

        return $service;
    }

    /**
     * Get Tasks Runner
     */
    protected function getTasksRunner(): Runner
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                Runner::class,
                $service = $this->getContainer()->get(Runner::class)
            );
        }

        return $service;
    }

    /**
     * Get Process Manager
     */
    protected function getProcessManager(): ProcessManager
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                ProcessManager::class,
                $service = $this->getContainer()->get(ProcessManager::class)
            );
        }

        return $service;
    }

    /**
     * Get Event Dispatcher
     *
     * @throws Exception
     *
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher(): EventDispatcherInterface
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                EventDispatcherInterface::class,
                $service = $this->getContainer()->get(EventDispatcherInterface::class)
            );
        }

        return $service;
    }

    /**
     * Get Configuration Manager
     */
    protected function getConfiguration(): Configuration
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                Configuration::class,
                $service = $this->getContainer()->get(Configuration::class)
            );
        }

        return $service;
    }

    /**
     * Get Task Factory
     */
    protected function getTaskFactory(): TaskFactory
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                TaskFactory::class,
                $service = $this->getContainer()->get(TaskFactory::class)
            );
        }

        return $service;
    }

    /**
     * Get Job Configurator
     */
    protected function getJobConfigurator(): JobConfigurator
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                JobConfigurator::class,
                $service = $this->getContainer()->get(JobConfigurator::class)
            );
        }

        return $service;
    }
}
