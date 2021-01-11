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

use Exception;
use Psr\Log\LoggerInterface;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;

/**
 * Token Management Service
 */
class TokenManager
{
    //==============================================================================
    //  Variables Definition
    //==============================================================================

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
     * @param LoggerInterface $logger
     *
     * @throws Exception
     */
    public function __construct(LoggerInterface $logger)
    {
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
    }

    //====================================================================//
    //  Tasks Tokens Management
    //====================================================================//

    /**
     * Take Lock on a Specific Token
     *
     * @param Task $task Task Object
     *
     * @throws Exception
     *
     * @return bool
     */
    public function acquire(Task $task): bool
    {
        //==============================================================================
        // Safety Check - If Task Counter is Over => Close Directly This Task
        // This means task was aborted due to a uncached fatal error
        if ($task->getTry() > Configuration::getTasksMaxRetry()) {
            $task->setFaultStr("Fatal Error: Task Counter is Over!");
            $this->logger->notice("Token Manager: Task Counter is Over!");

            return false;
        }

        //==============================================================================
        // Check Token is not Empty => Skip
        $jobToken = $task->getJobToken();
        if (null == $jobToken) {
            return true;
        }
        //==============================================================================
        // Check If we have an Active Token
        if (!is_null($this->currentToken)) {
            //==============================================================================
            // Check If Token is Already Took
            if ($this->currentToken == $jobToken) {
                $this->logger->info('Token Manager: Token Already Took! ('.$this->currentToken.')');

                return true;
            }
            //==============================================================================
            // CRITICAL - Release Current Token before Asking for a new one
            $this->release();
            $this->logger
                ->error('Token Manager: Token Not Released before Acquiring a New One! ('.$this->currentToken.')');

            return true;
        }
        //==============================================================================
        // Try Acquire this Token
        $acquiredToken = Configuration::getTokenRepository()->acquire($jobToken);

        //==============================================================================
        // Check If token is Available
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
     * @throws Exception
     *
     * @return bool Return True only if Current Token was Released
     */
    public function release() : bool
    {
        //==============================================================================
        // Check If we currently have a token
        if (is_null($this->currentToken)) {
            return false;
        }
        //==============================================================================
        // Release Token
        $release = Configuration::getTokenRepository()->release($this->currentToken);
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
     * @throws Exception
     *
     * @return bool
     */
    public function validate(Task $task): bool
    {
        $token = $task->getJobToken();
        if (null === $token) {
            return true;
        }

        return Configuration::getTokenRepository()->validate($token);
    }
}
