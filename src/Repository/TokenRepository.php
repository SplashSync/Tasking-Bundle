<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Repository;

use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PessimisticLockException;
use Exception;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Services\Configuration;
use Splash\Tasking\Tools\Status;
use Splash\Tasking\Tools\Timer;

/**
 * Task Tokens Repository
 *
 * Manage Acquire & Release of Tasks Tokens
 */
class TokenRepository extends EntityRepository
{
    /**
     * Token Acquire Mode => Normal => No concurrency management
     *
     * @var string
     */
    const MODE_NORMAL = "Normal";

    /**
     * Token Acquire Mode => Optimistic Locking
     *
     * @var string
     */
    const MODE_OPTIMISTIC = "Optimist";

    /**
     * Token Acquire Mode
     *
     * @var string
     */
    private $mode = self::MODE_OPTIMISTIC;

    /**
     * Verify this token is free and Acquire it
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws Exception
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     */
    public function acquire(string $tokenName): ?Token
    {
        switch ($this->mode) {
            case self::MODE_NORMAL:
                $token = $this->acquireNormal($tokenName);

                break;
            case self::MODE_OPTIMISTIC:
            default:
                $token = $this->acquireOptimistic($tokenName);

                break;
        }
        if ($token) {
            Status::setTokenAcquired($tokenName);

            return $token;
        }

        return null;
    }

    /**
     * Release this token
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PessimisticLockException
     *
     * @return bool
     */
    public function release(string $tokenName): bool
    {
        switch ($this->mode) {
            case self::MODE_NORMAL:
                $result = $this->releaseNormal($tokenName);

                break;
            case self::MODE_OPTIMISTIC:
            default:
                $result = $this->releaseOptimistic($tokenName);

                break;
        }
        if ($result) {
            Status::setTokenReleased();

            return true;
        }

        return false;
    }

    /**
     * Initialize a Specific Token before Task Creation
     *
     * @param string $tokenName Token Name
     *
     * @throws ORMException
     *
     * @return bool
     */
    public function validate(string $tokenName) : bool
    {
        //==============================================================================
        // Check If Token Exists
        /** @var null|Token $token */
        $token = $this->findOneBy(array("name" => $tokenName));

        //==============================================================================
        // Create token if necessary
        if (!$token) {
            $token = new Token($tokenName);
            $this->_em->persist($token);
            $this->_em->flush();
        }

        return ($token->getId() > 0);
    }

    /**
     * Delete a Token
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws ORMException
     *
     * @return bool
     */
    public function delete(string $tokenName) : bool
    {
        //==============================================================================
        // Check If this token Exists Token Key Name
        $token = $this->findOneBy(array("name" => $tokenName));
        //==============================================================================
        // Create token if necessary
        if (!$token) {
            return true;
        }
        //====================================================================//
        // Delete this Entity on EntityManager
        $this->_em->remove($token);
        //====================================================================//
        // Save Changes
        $this->_em->flush();

        return true;
    }

    /**
     * Delete all Token Unused for more than given delay
     *
     * @param null|int $maxAge Max Age for Tokens in Hours
     *
     * @throws Exception
     *
     * @return int Count of Deleted Tasks
     */
    public function clean(int $maxAge = null) : int
    {
        $maxAge = $maxAge ? $maxAge : Configuration::getTokenDeleteDelay();
        //==============================================================================
        // Prepare Max Age DateTime
        $maxDate = new DateTime("-".$maxAge."Hours");
        //==============================================================================
        // Count Old Finished Tasks
        return $this->createQueryBuilder("t")
            ->delete()
            ->where("t.locked != 1")
            ->andWhere("t.lockedAt < :maxage OR t.lockedAt IS NULL")
            ->setParameter(":maxage", $maxDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Verify this token is free and Acquire it
     * No Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws Exception
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     */
    private function acquireNormal(string $tokenName): ?Token
    {
        //==============================================================================
        // Check If this token Exists Token Key Name
        /** @var null|Token $token */
        $token = $this->findOneBy(array("name" => $tokenName));

        //==============================================================================
        // Create token if necessary
        if (!$token) {
            $token = new Token($tokenName);
            $this->_em->persist($token);
            $this->_em->flush();
        }
        //==============================================================================
        // Token is already locked => Exit
        if ($token->isLocked()) {
            return null;
        }
        //====================================================================//
        // Set Token As Locked
        $token->acquire();
        //====================================================================//
        // Save Changes
        $this->_em->flush();

        return $token;
    }

    /**
     * Release this token
     * No Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return bool False is Token could not be Released
     */
    private function releaseNormal(string $tokenName) : bool
    {
        //==============================================================================
        // Check If this token Exists Token Key Name
        /** @var null|Token $token */
        $token = $this->findOneBy(array("name" => $tokenName));
        //==============================================================================
        //If Token Doesn't Exists
        if (!$token) {
            return true;
        }
        //====================================================================//
        // Set Token As Unlocked
        $token->release();
        //====================================================================//
        // Save Changes
        $this->_em->flush();

        return true;
    }

    /**
     * Verify this token is free and Acquire it
     * Optimistic Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws Exception
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    private function acquireOptimistic(string $tokenName): ?Token
    {
        //==============================================================================
        // Check If this token Exists Token Key Name
        /** @var null|Token $token */
        $token = $this->findOneBy(array("name" => $tokenName));
        //==============================================================================
        // Create token if necessary
        if (!$token) {
            $token = new Token($tokenName);
            $this->_em->persist($token);
            $this->_em->flush();
        }
        $this->_em->refresh($token);
        //==============================================================================
        // Token is already locked => Exit
        if ($token->isLocked()) {
            return null;
        }

        //==============================================================================
        // Lock token in database
        //==============================================================================
        try {
            //====================================================================//
            // Lock this Entity on EntityManager
            $this->_em->lock($token, LockMode::OPTIMISTIC, $token->getVersion());
            //====================================================================//
            // Set Token As Locked
            $token->acquire();
            //====================================================================//
            // Save Changes
            $this->_em->flush();
        } catch (OptimisticLockException $e) {
            echo "Token Rejected (Optimistic) => ".$e->getMessage().PHP_EOL;
            die;
        }

        return $token;
    }

    /**
     * Release this token
     * Optimistic Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @throws Exception
     * @throws PessimisticLockException
     *
     * @return bool
     */
    private function releaseOptimistic(string $tokenName): bool
    {
        //==============================================================================
        // Check If this token Exists Token Key Name
        /** @var null|Token $token */
        $token = $this->findOneBy(array("name" => $tokenName));
        //==============================================================================
        //If Token Doesn't Exists
        if (!$token) {
            return true;
        }
        //==============================================================================
        // Token is already unlocked => Exit
        if (!$token->isLocked()) {
            return false;
        }

        while (1) {
            //==============================================================================
            // UnLock token in database
            //==============================================================================
            try {
                //====================================================================//
                // Lock this Entity on EntityManager
                $this->_em->lock($token, LockMode::OPTIMISTIC, $token->getVersion());
                //====================================================================//
                // Set Token As Unlocked
                $token->Release();
                //====================================================================//
                // Save Changes
                $this->_em->flush();

                return true;
            } catch (OptimisticLockException $e) {
                echo "Token Not Released (Optimistic) => ".$e->getMessage().PHP_EOL;
                Timer::msSleep(1);

                continue;
            }
        }
    }
}
