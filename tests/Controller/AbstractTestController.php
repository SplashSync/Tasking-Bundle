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

namespace Splash\Tasking\Tests\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use ReflectionClass;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\TokenRepository;
use Splash\Tasking\Repository\WorkerRepository;
use Splash\Tasking\Services\ProcessManager;
use Splash\Tasking\Services\Runner;
use Splash\Tasking\Services\TasksManager;
use Splash\Tasking\Services\WorkersManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    protected $entityManager;

    /**
     * @var TaskRepository
     */
    protected $tasksRepository;

    /**
     * @var WorkerRepository
     */
    protected $workersRepository;

    /**
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var TasksManager
     */
    protected $tasks;

    /**
     * @var WorkersManager
     */
    protected $worker;

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @var ProcessManager
     */
    protected $process;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var NullOutput
     */
    protected $output;

    /**
     * @var string
     */
    protected $randomStr;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();

        //====================================================================//
        // Load Symfony Services Container
        $container = static::$kernel->getContainer();
        if (null == $container) {
            throw new Exception("Unable to Load Symfony Container");
        }

        //====================================================================//
        // Link to Task Manager Services
        $this->tasks = $container->get('splash.tasking.tasks');
        //====================================================================//
        // Link to Worker Manager Services
        $this->worker = $container->get('splash.tasking.workers');
        //====================================================================//
        // Link to Task Runner Services
        $this->runner = $container->get('splash.tasking.runner');
        //====================================================================//
        // Link to Task Process Manager
        $this->process = $container->get('splash.tasking.process');
        //====================================================================//
        // Link to entity manager Services
        $this->entityManager = $this->tasks->getManager();
        //====================================================================//
        // Link to Tasks Repository
        $this->tasksRepository = $this->tasks->getTasksRepository();
        //====================================================================//
        // Link to Token Repository
        $this->tokenRepository = $this->tasks->getTokenRepository();
        //====================================================================//
        // Link to Workers Repository
        $this->workersRepository = $this->tasks->getWorkerRepository();
        //====================================================================//
        // Link to Event Dispatcher
        $this->dispatcher = $container->get('event_dispatcher');
        //====================================================================//
        // Generate a Fake Output
        $this->output = new NullOutput();
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
    }

    /**
     * Safe Get Container
     *
     * @throws Exception
     *
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        //====================================================================//
        // Load Symfony Services Container
        $container = static::$kernel->getContainer();
        if (null == $container) {
            throw new Exception("Unable to Load Symfony Container");
        }

        return $container;
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
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
