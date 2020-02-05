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

use DateTime;
use PHPUnit\Framework\Assert;
use Splash\Tasking\Entity\Token;

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
        $minAge = new DateTime("-".(Token::SELFRELEASE_DELAY - 2)." Seconds");
        $token->setLockedAt($minAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        //====================================================================//
        // Test Acquire a Token
        Assert::assertNull($this->tokenRepository->acquire($this->randomStr));

        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $token->acquire();
        $maxAge = new DateTime("-".(Token::SELFRELEASE_DELAY + 1)." Seconds");
        $token->setLockedAt($maxAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        //====================================================================//
        // Test Acquire a Token
        Assert::assertInstanceOf(Token::class, $this->tokenRepository->acquire($this->randomStr));

        //====================================================================//
        // Test Acquire a Token
        for ($i = 0; $i < 5; $i++) {
            Assert::assertNull($this->tokenRepository->acquire($this->randomStr));
        }

        //====================================================================//
        // Test Relase a Token
        Assert::assertTrue($this->tokenRepository->release($this->randomStr));

        //====================================================================//
        // Test Delete a Token
        $this->tokenRepository->delete($this->randomStr);
        Assert::assertNull($this->tokenRepository->findOneBy(array("name" => $this->randomStr)));
    }
}
