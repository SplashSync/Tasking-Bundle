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

namespace Splash\Tasking\Tests\Controller;

use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionClass;
use ReflectionException;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\TokenRepository;
use Splash\Tasking\Repository\WorkerRepository;
use Splash\Tasking\Services\Configuration;
use Splash\Tasking\Services\ProcessManager;
use Splash\Tasking\Services\Runner;
use Splash\Tasking\Services\TasksManager;
use Splash\Tasking\Services\WorkersManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base Test Controller for Tasking Bundle PhpUnit Tests
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var null|TasksManager
     */
    private ?TasksManager $tasks;

    /**
     * @var WorkersManager
     */
    private WorkersManager $worker;

    /**
     * @var Runner
     */
    private Runner $runner;

    /**
     * @var ProcessManager
     */
    private ProcessManager $process;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

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
        $this->entityManager = Configuration::getEntityManager();
        //====================================================================//
        // Link to Tasks Repository
        $this->tasksRepository = Configuration::getTasksRepository();
        //====================================================================//
        // Link to Token Repository
        $this->tokenRepository = Configuration::getTokenRepository();
        //====================================================================//
        // Link to Workers Repository
        $this->workersRepository = Configuration::getWorkerRepository();
        //====================================================================//
        // Generate a Fake Output
        $this->output = new NullOutput();
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
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
     *
     * @throws Exception
     *
     * @return TasksManager
     */
    protected function getTasksManager(): TasksManager
    {
        if (!isset($this->tasks)) {
            $tasksManager = static::getContainer()->get(TasksManager::class);
            if (!($tasksManager instanceof TasksManager)) {
                throw new Exception("Unable to Load Tasks Manager");
            }
            $this->tasks = $tasksManager;
        }

        return $this->tasks;
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
        if (!isset($this->worker)) {
            $workersManager = static::$container->get(WorkersManager::class);
            if (!($workersManager instanceof WorkersManager)) {
                throw new Exception("Unable to Load Worker Manager");
            }
            $this->worker = $workersManager;
        }

        return $this->worker;
    }

    /**
     * Get Tasks Runner
     *
     * @throws Exception
     *
     * @return Runner
     */
    protected function getTasksRunner(): Runner
    {
        if (!isset($this->runner)) {
            $tasksRunner = static::$container->get(Runner::class);
            if (!($tasksRunner instanceof Runner)) {
                throw new Exception("Unable to Load Tasks Runner");
            }
            $this->runner = $tasksRunner;
        }

        return $this->runner;
    }

    /**
     * Get Process Manager
     *
     * @throws Exception
     *
     * @return ProcessManager
     */
    protected function getProcessManager(): ProcessManager
    {
        if (!isset($this->process)) {
            $processManager = static::$container->get(ProcessManager::class);
            if (!($processManager instanceof ProcessManager)) {
                throw new Exception("Unable to Load Process Manager");
            }
            $this->process = $processManager;
        }

        return $this->process;
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
        if (!isset($this->dispatcher)) {
            //====================================================================//
            // Load Symfony Event Dispatcher
            $dispatcher = static::$container->get(EventDispatcherInterface::class);
            if (!($dispatcher instanceof EventDispatcherInterface)) {
                throw new Exception("Unable to Load Event Dispatcher");
            }
            $this->dispatcher = $dispatcher;
        }

        return $this->dispatcher;
    }
}
