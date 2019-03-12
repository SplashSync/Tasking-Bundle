<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Tests\Controller;

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
        $this->tokenRepository->Clean(0);

        //====================================================================//
        // Verify Delete All Tokens
        $this->assertEquals(0, $this->tokenRepository->Clean(0));
    }

    /**
     * Add Tokens
     */
    public function testAddRandomToken(): void
    {
        //====================================================================//
        // Delete All Tokens
        $this->tokenRepository->Clean(0);

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Verify Token
        $this->assertTrue($this->tokenRepository->Validate($this->randomStr));

        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->tokenRepository->findOneByName($this->randomStr));
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
        $this->assertTrue($this->tokenRepository->Validate($this->randomStr));
        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->tokenRepository->findOneByName($this->randomStr));
        //====================================================================//
        // Delete Tokens
        $this->assertTrue($this->tokenRepository->Delete($this->randomStr));
        //==============================================================================
        // Verify If Token Now Deleted
        $this->assertNull($this->tokenRepository->findOneByName($this->randomStr));
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
        $this->assertTrue($this->tokenRepository->Validate($this->randomStr));
        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->tokenRepository->findOneByName($this->randomStr));
        //====================================================================//
        // Acquire Token
        $token = $this->tokenRepository->Acquire($this->randomStr);
        $this->assertInstanceOf(Token::class, $token);
        //====================================================================//
        // Verify Token
        $this->assertNotEmpty($token->getCreatedAt());
        $this->assertNotEmpty($token->getLockedAt());
        $this->assertNotEmpty($token->getLockedAtTimeStamp());
        $this->assertTrue($token->isLocked());
        $this->assertFalse($token->isFree());
        $this->assertEquals($this->randomStr, $token->getName());
        //====================================================================//
        // Acquire Token Again
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->tokenRepository->Acquire($this->randomStr));
        }
        //====================================================================//
        // Release Token
        $this->assertTrue($this->tokenRepository->Release($this->randomStr));
        //====================================================================//
        // Verify Token
        $this->assertFalse($token->isLocked());
        $this->assertTrue($token->isFree());
        $this->assertEquals($this->randomStr, $token->getName());

        //====================================================================//
        // Acquire Token Again
        $this->assertInstanceOf(
            Token::class,
            $this->tokenRepository->Acquire($this->randomStr)
        );
        //====================================================================//
        // Delete Tokens
        $this->assertTrue($this->tokenRepository->Delete($this->randomStr));
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
        $token->Acquire();
        $minAge = new \DateTime("-".(Token::SELFRELEASE_DELAY - 2)." Seconds");
        $token->setLockedAt($minAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        //====================================================================//
        // Test Acquire a Token
        $this->assertNull($this->tokenRepository->Acquire($this->randomStr));

        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $token->Acquire();
        $maxAge = new \DateTime("-".(Token::SELFRELEASE_DELAY + 1)." Seconds");
        $token->setLockedAt($maxAge);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        //====================================================================//
        // Test Acquire a Token
        $this->assertInstanceOf(Token::class, $this->tokenRepository->Acquire($this->randomStr));

        //====================================================================//
        // Test Acquire a Token
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->tokenRepository->Acquire($this->randomStr));
        }

        //====================================================================//
        // Test Relase a Token
        $this->assertTrue($this->tokenRepository->Release($this->randomStr));

        //====================================================================//
        // Test Delete a Token
        $this->tokenRepository->Delete($this->randomStr);
        $this->assertNull($this->tokenRepository->findOneByName($this->randomStr));
    }
}
