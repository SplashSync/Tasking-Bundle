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

use BadPixxel\Tasking\Entity\Token;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * Test of Tasks Tokens Repository
 */
class A002TokenRepositoryControllerTest extends AbstractTestController
{
    /**
     * Delete All Tokens
     */
    public function testDeleteAllTokens(): void
    {
        //====================================================================//
        // Delete All Tokens
        $this->tokenRepository->clean(0);

        //====================================================================//
        // Verify Delete All Tokens
        Assert::assertEquals(0, $this->tokenRepository->clean(0));
    }

    /**
     * Add Tokens
     */
    public function testAddRandomToken(): void
    {
        //====================================================================//
        // Delete All Tokens
        $this->tokenRepository->clean(0);

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Verify Token
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));

        //==============================================================================
        // Verify If Token Now Exists
        Assert::assertNotEmpty($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
    }

    /**
     * Delete Tokens
     */
    public function testDeleteRandomToken(): void
    {
        //====================================================================//
        // Delete All Tokens
        $this->tokenRepository->clean(0);
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
        //====================================================================//
        // Add Tokens
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));
        //==============================================================================
        // Verify If Token Now Exists
        Assert::assertNotEmpty($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
        //====================================================================//
        // Delete Tokens
        Assert::assertTrue($this->tokenRepository->delete($this->randomStr));
        //==============================================================================
        // Verify If Token Now Deleted
        Assert::assertNull($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
    }

    /**
     * Acquire & Release Tokens
     */
    public function testAcquireToken(): void
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
        //====================================================================//
        // Add Token
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));
        //==============================================================================
        // Verify If Token Now Exists
        Assert::assertNotEmpty($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
        //====================================================================//
        // Acquire Token
        $token = $this->tokenRepository->acquire($this->randomStr);
        Assert::assertInstanceOf(Token::class, $token);
        //====================================================================//
        // Verify Token
        Assert::assertNotEmpty($token->getCreatedAt());
        Assert::assertNotEmpty($token->getLockedAt());
        Assert::assertNotEmpty($token->getLockedAtTimeStamp());
        Assert::assertTrue($token->isLocked());
        Assert::assertFalse($token->isFree());
        Assert::assertEquals($this->randomStr, $token->getName());
        //====================================================================//
        // Acquire Token Again
        for ($i = 0; $i < 5; $i++) {
            Assert::assertNull($this->tokenRepository->acquire($this->randomStr));
        }
        //====================================================================//
        // Release Token
        Assert::assertTrue($this->tokenRepository->release($this->randomStr));
        //====================================================================//
        // Verify Token
        Assert::assertFalse($token->isLocked());
        Assert::assertTrue($token->isFree());
        Assert::assertEquals($this->randomStr, $token->getName());

        //====================================================================//
        // Acquire Token Again
        Assert::assertInstanceOf(
            Token::class,
            $this->tokenRepository->acquire($this->randomStr)
        );
        //====================================================================//
        // Delete Tokens
        Assert::assertTrue($this->tokenRepository->delete($this->randomStr));
    }

    /**
     * Test Token Self-Release Features
     *
     * @throws Exception
     */
    public function testSelfRelease(): void
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
        //====================================================================//
        // Create a New Token
        $token = new Token($this->randomStr);

        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $token->acquire();
        $minAge = new DateTime("-".($this->getConfiguration()->getTokenSelfReleaseDelay() - 2)." Seconds");
        $token->setLockedAt($minAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        //====================================================================//
        // Test Acquire a Token
        Assert::assertTrue($token->isLocked());
        Assert::assertNull($this->tokenRepository->acquire($this->randomStr));

        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $token->acquire();
        $maxAge = new DateTime("-".($this->getConfiguration()->getTokenSelfReleaseDelay() + 1)." Seconds");
        $token->setLockedAt($maxAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        //====================================================================//
        // Test Acquire a Token
        Assert::assertFalse($token->isLocked());
        $acquiredToken = $this->tokenRepository->acquire($this->randomStr);
        Assert::assertInstanceOf(Token::class, $acquiredToken);

        //====================================================================//
        // Test Acquire a Token
        for ($i = 0; $i < 5; $i++) {
            Assert::assertNull($this->tokenRepository->acquire($this->randomStr));
        }

        //====================================================================//
        // Test Release a Token
        Assert::assertTrue($this->tokenRepository->release($this->randomStr));

        //====================================================================//
        // Test Delete a Token
        $this->tokenRepository->delete($this->randomStr);
        Assert::assertNull($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
    }

    /**
     * Test a Lost Creation Race does Not Break the Entity Manager
     *
     * Replays the very same insert as the race winner: the constraint violation
     * must be survivable, i.e. it must NOT close the shared Entity Manager.
     */
    public function testLostRaceKeepsEntityManagerOpen(): void
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
        //====================================================================//
        // Create the Token => Here we are the Race Winner
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));

        //====================================================================//
        // Replay the Very Same Insert => Here we are the Race Loser
        try {
            $this->insertRawToken($this->randomStr);
            Assert::fail("Duplicated Token insert was expected to be rejected");
        } catch (UniqueConstraintViolationException) {
            //====================================================================//
            // Expected => This is the Collision we Need to Survive
        }
        //====================================================================//
        // Verify Entity Manager Survived the Constraint Violation
        Assert::assertTrue($this->getOrmManager()->isOpen());
        //====================================================================//
        // Verify Repository is Still Usable & No Duplicate was Created
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));
        Assert::assertSame(1, $this->countRawTokens($this->randomStr));
        //====================================================================//
        // Delete Token
        Assert::assertTrue($this->tokenRepository->delete($this->randomStr));
    }

    /**
     * Test Concurrent Creation of the Same Token
     *
     * Forks a set of processes that all validate the same brand new token at the
     * same instant: none may fail, and only one row may reach the database.
     */
    public function testConcurrentValidateIsRaceFree(): void
    {
        $workers = 6;
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();
        //====================================================================//
        // Verify Token Does Not Exist => All Children will Race to Create It
        Assert::assertNull($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
        //====================================================================//
        // Prepare Results Directory => Forked Processes Report Through Files
        $resultsDir = sys_get_temp_dir()."/tasking-token-race-".uniqid();
        Assert::assertTrue(mkdir($resultsDir, 0700, true));
        //====================================================================//
        // Close the Shared Connection BEFORE Forking
        // => Each Process Must Open its Own, None May Share a Socket
        $this->getOrmManager()->clear();
        $this->getDbalConnection()->close();
        //====================================================================//
        // Fork All Concurrent Workers, Aligned on a Common Start Time
        $startAt = microtime(true) + 1;
        $results = array();
        for ($index = 0; $index < $workers; $index++) {
            $results[$index] = $resultsDir."/".$index;
            $pid = pcntl_fork();
            Assert::assertNotSame(-1, $pid, "Unable to fork concurrent worker");
            //====================================================================//
            // Child Process => Race, Report, then Leave Without Any PhpUnit Noise
            if (0 == $pid) {
                $this->raceForToken($startAt, $results[$index]);
                posix_kill(posix_getpid(), SIGKILL);
            }
        }
        //====================================================================//
        // Wait for All Children
        $reaped = 0;
        while (pcntl_waitpid(0, $status) > 0) {
            //====================================================================//
            // Children Leave Through SIGKILL => Anything Else Means They Crashed
            Assert::assertTrue(pcntl_wifsignaled($status), "A concurrent worker died unexpectedly");
            $reaped++;
        }
        Assert::assertSame($workers, $reaped);
        //====================================================================//
        // Verify No Concurrent Worker Failed
        foreach ($results as $index => $resultFile) {
            Assert::assertFileExists($resultFile, sprintf("Worker %d produced no result", $index));
            Assert::assertSame(
                "OK",
                file_get_contents($resultFile),
                sprintf("Concurrent worker %d failed to validate the token", $index)
            );
            unlink($resultFile);
        }
        rmdir($resultsDir);
        //====================================================================//
        // Verify Exactly One Token Row was Created
        Assert::assertSame(1, $this->countRawTokens($this->randomStr));
        //====================================================================//
        // Delete Token
        Assert::assertTrue($this->tokenRepository->delete($this->randomStr));
    }

    /**
     * Validate the Test Token from a Forked Process & Report to a File
     *
     * @param float  $startAt    Common Start Time, to Maximize Collisions
     * @param string $resultFile Where to Report the Outcome
     *
     * @return void
     */
    private function raceForToken(float $startAt, string $resultFile): void
    {
        //====================================================================//
        // Wait for the Common Start Time
        while (microtime(true) < $startAt) {
            usleep(1000);
        }

        //====================================================================//
        // Race for the Token
        try {
            $result = $this->tokenRepository->validate($this->randomStr)
                ? "OK"
                : "KO => validate() returned false"
            ;
        } catch (Throwable $throwable) {
            $result = "KO => ".$throwable::class." => ".$throwable->getMessage();
        }

        file_put_contents($resultFile, $result);
    }

    /**
     * Insert a Token Row Through Raw Sql, Bypassing the Orm
     *
     * @param string $tokenName Token Name to Insert
     *
     * @return void
     */
    private function insertRawToken(string $tokenName): void
    {
        $this->getDbalConnection()->executeStatement(
            sprintf("INSERT INTO %s (Name, Locked, CreatedAt, version) VALUES (?, ?, ?, ?)", $this->getTokensTable()),
            array($tokenName, 0, (new DateTime())->format("Y-m-d H:i:s"), 1)
        );
    }

    /**
     * Count Token Rows Through Raw Sql, Bypassing the Orm
     *
     * @param string $tokenName Token Name to Count
     *
     * @return int
     */
    private function countRawTokens(string $tokenName): int
    {
        $count = $this->getDbalConnection()->fetchOne(
            sprintf("SELECT COUNT(*) FROM %s WHERE Name = ?", $this->getTokensTable()),
            array($tokenName)
        );
        Assert::assertIsNumeric($count);

        return (int) $count;
    }

    /**
     * Get Tokens Table Name
     *
     * @return string
     */
    private function getTokensTable(): string
    {
        return $this->getOrmManager()->getClassMetadata(Token::class)->getTableName();
    }

    /**
     * Get Raw Dbal Connection
     *
     * @return Connection
     */
    private function getDbalConnection(): Connection
    {
        return $this->getOrmManager()->getConnection();
    }

    /**
     * Get Entity Manager as an Orm Manager
     *
     * @return EntityManagerInterface
     */
    private function getOrmManager(): EntityManagerInterface
    {
        Assert::assertInstanceOf(EntityManagerInterface::class, $this->entityManager);

        return $this->entityManager;
    }
}
