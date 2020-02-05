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

namespace Splash\Tasking\Services;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Repository\TokenRepository;

/**
 * Token Management Service
 */
class TokenManager
{
    //==============================================================================
    //  Variables Definition
    //==============================================================================

    /**
     * Doctrine Entity Manager
     *
     * @var EntityManagerInterface
     */
    public $entityManager;

    /**
     * Tasking Service Configuration Array
     *
     * @var ArrayObject
     */
    protected $config;

    /**
     * Token Repository
     *
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Current Acquired Token
     *
     * @var null|string
     */
    private $currentToken;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param array                  $config
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, array $config)
    {
        //====================================================================//
        // Link to entity manager Service
        $this->entityManager = $entityManager;
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
        //====================================================================//
        // Link to Token Repository
        $tokenRepository = $entityManager->getRepository(Token::class);
        if (!($tokenRepository instanceof TokenRepository)) {
            throw new Exception("Wrong repository class");
        }
        $this->tokenRepository = $tokenRepository;
        //====================================================================//
        // Init Parameters
        $this->config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
    }

    //====================================================================//
    //  Tasks Tokens Management
    //====================================================================//

    /**
     * Take Lock on a Specific Token
     *
     * @param Task $task Task Object
     *
     * @return bool
     */
    public function acquire(Task $task): bool
    {
        //==============================================================================
        // Safety Check - If Task Counter is Over => Close Directly This Task
        // This means task was aborded due to a uncatched fatal error
        if ($task->getTry() > $this->config->tasks["try_count"]) {
            $task->setFaultStr("Fatal Error: Task Counter is Over!");
            $this->logger->notice("Token Manager: Task Counter is Over!");

            return false;
        }

        //==============================================================================
        // Ckeck Token is not Empty => Skip
        $jobToken = $task->getJobToken();
        if (null == $jobToken) {
            return true;
        }
        //==============================================================================
        // Ckeck If we have an Active Token
        if (!is_null($this->currentToken)) {
            //==============================================================================
            // Ckeck If Token is Already Took
            if ($this->currentToken == $jobToken) {
                $this->logger->info('Token Manager: Token Already Took! ('.$this->currentToken.')');

                return true;
            }
            //==============================================================================
            // CRITICAL - Release Current Token before Asking for a new one
            $this->release();
            $this->logger->error('Token Manager: Token Not Released before Acquiring a New One! ('.$this->currentToken.')');

            return true;
        }
        //==============================================================================
        // Try Acquire this Token
        $acquiredToken = $this->tokenRepository->acquire($jobToken);

        //==============================================================================
        // Ckeck If token is Available
        if ($acquiredToken instanceof Token) {
            $this->currentToken = $acquiredToken->getName();
            $this->logger->info('Token Manager: Token Acquired! ('.$jobToken.')');

            return true;
        }

        //==============================================================================
        // Token Rejected
        $this->currentToken = null;
        $this->logger->info('Token Manager: Token Rejected! ('.$jobToken.')');

        return false;
    }

    /**
     * Release Lock on a Specific Token
     *
     * @return bool Return True only if Current Token was Released
     */
    public function release() : bool
    {
        //==============================================================================
        // Ckeck If we currently have a token
        if (is_null($this->currentToken)) {
            return false;
        }
        //==============================================================================
        // Release Token
        $release = $this->tokenRepository->release($this->currentToken);
        //==============================================================================
        // Token Released => Clear Current Token
        if (true === $release) {
            $this->logger->info('Token Manager: Token Released! ('.$this->currentToken.')');
            $this->currentToken = null;
        }

        return $release;
    }

    /**
     * Validate/Create a Token before insertion of a new Task
     *
     * @param Task $task
     *
     * @return bool
     */
    public function validate(Task $task): bool
    {
        $token = $task->getJobToken();
        if (null === $token) {
            return true;
        }

        return $this->tokenRepository->validate($token);
    }
}
