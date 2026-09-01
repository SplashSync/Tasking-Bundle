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

namespace BadPixxel\Tasking\Repository;

use BadPixxel\Tasking\Entity\Token;
use BadPixxel\Tasking\Helper\Timer;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Task Tokens Repository
 *
 * Manage Acquire & Release of Tasks Tokens
 *
 * @template-extends EntityRepository<Token>
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
     * How Many Times we Try to Read or Create a Token before Giving Up
     *
     * @var int
     */
    private const CREATE_ATTEMPTS = 3;

    /**
     * Token Acquire Mode
     *
     * @var string
     */
    private string $mode = self::MODE_OPTIMISTIC;

    /**
     * Verify this token is free and Acquire it
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     *
     * @phpstan-impure
     */
    public function acquire(string $tokenName): ?Token
    {
        return match ($this->mode) {
            self::MODE_NORMAL => $this->acquireNormal($tokenName),
            default => $this->acquireOptimistic($tokenName),
        };
    }

    /**
     * Release this token
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @return bool
     */
    public function release(string $tokenName): bool
    {
        return match ($this->mode) {
            self::MODE_NORMAL => $this->releaseNormal($tokenName),
            default => $this->releaseOptimistic($tokenName),
        };
    }

    /**
     * Initialize a Specific Token before Task Creation
     *
     * @param string $tokenName Token Name
     *
     * @return bool
     */
    public function validate(string $tokenName) : bool
    {
        return ($this->ensureExists($tokenName)->getId() > 0);
    }

    /**
     * Delete a Token
     *
     * @param string $tokenName Token Name to Acquire
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
        $this->getEntityManager()->remove($token);
        //====================================================================//
        // Save Changes
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Delete all Token Unused for more than given delay
     *
     * @param int $maxAge Max Age for Tokens in Hours
     *
     * @return int Count of Deleted Tasks
     */
    public function clean(int $maxAge) : int
    {
        Assert::greaterThanEq($maxAge, 0);

        //==============================================================================
        // Prepare Max Age DateTime
        try {
            $maxDate = new DateTime("-".$maxAge."Hours");
        } catch (Exception $e) {
            return 0;
        }
        //==============================================================================
        // Clean && Count Old Finished Tasks
        $builder = $this->createQueryBuilder("t")
            ->delete()
            ->where("t.locked != 1")
            ->andWhere("t.lockedAt < :maxage OR t.lockedAt IS NULL")
            ->setParameter(":maxage", $maxDate)
        ;

        /** @phpstan-ignore-next-line */
        return $builder->getQuery()->execute();
    }

    /**
     * Ensure a Token Row Exists in Database => Concurrency Safe
     *
     * Losing the insert race is a success: the row we wanted now exists,
     * created by the winner. Exclusivity is NOT arbitrated here, it remains
     * the sole responsibility of acquire().
     *
     * @param string $tokenName Token Name to Create if Missing
     *
     * @throws Exception When the Token can Neither be Read nor Created
     *
     * @return Token
     */
    private function ensureExists(string $tokenName): Token
    {
        //==============================================================================
        // Tokens are Also Deleted Concurrently, so Winning or Losing the Insert Race
        // Guarantees Nothing on the Next Read => Give it More than One Round
        for ($attempt = 0; $attempt < self::CREATE_ATTEMPTS; $attempt++) {
            //==============================================================================
            // Token Already Exists => Nothing to Create
            /** @var null|Token $token */
            $token = $this->findOneBy(array("name" => $tokenName));
            if ($token) {
                return $token;
            }

            try {
                $this->insertToken($tokenName);
            } catch (UniqueConstraintViolationException) {
                //==============================================================================
                // Race Lost => Another Process Inserted it Between our Select & Insert
                // => This is Exactly the State we Wanted, Read it Back
            }
        }

        throw new Exception("Unable to Create Tasking Token: ".$tokenName);
    }

    /**
     * Insert a Token Row through the Dbal Connection, NOT through an Orm Flush
     *
     * Going through the connection is what makes a lost race harmless: a constraint
     * violation raised here never closes the shared Entity Manager.
     *
     * @param string $tokenName Token Name to Insert
     *
     * @throws UniqueConstraintViolationException When the Token was Created Concurrently
     *
     * @return void
     */
    private function insertToken(string $tokenName): void
    {
        $metadata = $this->getClassMetadata();

        $this->getEntityManager()->getConnection()->executeStatement(
            sprintf(
                "INSERT INTO %s (%s, %s, %s, %s) VALUES (?, ?, ?, ?)",
                $metadata->getTableName(),
                $metadata->getColumnName("name"),
                $metadata->getColumnName("locked"),
                $metadata->getColumnName("createdAt"),
                $metadata->getColumnName("version")
            ),
            array($tokenName, 0, (new DateTime())->format("Y-m-d H:i:s"), 1)
        );
    }

    /**
     * Verify this token is free and Acquire it
     * No Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     */
    private function acquireNormal(string $tokenName): ?Token
    {
        //==============================================================================
        // Check If this token Exists, Create it if Necessary
        $token = $this->ensureExists($tokenName);
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
        $this->getEntityManager()->flush();

        return $token;
    }

    /**
     * Release this token
     * No Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
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
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Verify this token is free and Acquire it
     * Optimistic Locking Mode
     *
     * @param string $tokenName Token Name to Acquire
     *
     * @return null|Token Null if Token not found or already Locked, $token Entity if Lock Acquired
     *
     * @SuppressWarnings(ExitExpression)
     */
    private function acquireOptimistic(string $tokenName): ?Token
    {
        //==============================================================================
        // Check If this token Exists, Create it if Necessary
        $token = $this->ensureExists($tokenName);

        try {
            $this->getEntityManager()->refresh($token);
        } catch (Throwable) {
            return null;
        }
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
            $this->getEntityManager()->lock($token, LockMode::OPTIMISTIC, $token->getVersion());
            //====================================================================//
            // Set Token As Locked
            $token->acquire();
            //====================================================================//
            // Save Changes
            $this->getEntityManager()->flush();
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
                $this->getEntityManager()->lock($token, LockMode::OPTIMISTIC, $token->getVersion());
                //====================================================================//
                // Set Token As Unlocked
                $token->Release();
                //====================================================================//
                // Save Changes
                $this->getEntityManager()->flush();

                return true;
            } catch (OptimisticLockException $e) {
                echo "Token Not Released (Optimistic) => ".$e->getMessage().PHP_EOL;
                Timer::msSleep(1);

                continue;
            }
        }
    }
}
