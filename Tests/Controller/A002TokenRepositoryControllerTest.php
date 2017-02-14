<?php

namespace Splash\Tasking\Tests\Controller;

use Splash\Tasking\Entity\Token;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class A002TokenRepositoryControllerTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;    
    
    /**
     * @var \Splash\Tasking\Repository\TokenRepository
     */
    private $TokenRepository;    
    
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        
        //====================================================================//
        // Link to entity manager Services
        $this->_em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        //====================================================================//
        // Link to Token Reprository
        $this->TokenRepository = $this->_em->getRepository('SplashTaskingBundle:Token');         
        
    }        

    /**
     * @abstract    Delete All Tokens
     */    
    public function testDeleteAllTokens()
    {
        //====================================================================//
        // Delete All Tokens
        $this->TokenRepository->Clean(0);
        
        //====================================================================//
        // Verify Delete All Tokens
        $this->assertEquals(0, $this->TokenRepository->Clean(0));
        
    }      
    

    /**
     * @abstract    Add Tokens
     */    
    public function testAddRandomToken()
    {
        //====================================================================//
        // Delete All Tokens
        $this->TokenRepository->Clean(0);
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Verify Token
        $this->assertTrue($this->TokenRepository->Validate($this->RandomStr));
        
        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->TokenRepository->findOneByName( $this->RandomStr ));
        
    }      

    /**
     * @abstract    Delete Tokens
     */    
    public function testDeleteRandomToken()
    {
        //====================================================================//
        // Delete All Tokens
        $this->TokenRepository->Clean(0);
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        //====================================================================//
        // Add Tokens
        $this->assertTrue($this->TokenRepository->Validate($this->RandomStr));
        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->TokenRepository->findOneByName( $this->RandomStr ));
        //====================================================================//
        // Delete Tokens
        $this->assertTrue($this->TokenRepository->Delete($this->RandomStr));
        //==============================================================================
        // Verify If Token Now Deleted
        $this->assertNull($this->TokenRepository->findOneByName( $this->RandomStr ));
    }      

    /**
     * @abstract    Acquire & Release Tokens
     */    
    public function testAcquireToken()
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        //====================================================================//
        // Add Token
        $this->assertTrue($this->TokenRepository->Validate($this->RandomStr));
        //==============================================================================
        // Verify If Token Now Exists
        $this->assertNotEmpty($this->TokenRepository->findOneByName( $this->RandomStr ));
        //====================================================================//
        // Acquire Token
        $Token  =   $this->TokenRepository->Acquire($this->RandomStr);
        $this->assertInstanceOf( Token::class , $Token);
        //====================================================================//
        // Verify Token
        $this->assertNotEmpty($Token->getCreatedAt());
        $this->assertNotEmpty($Token->getLockedAt());
        $this->assertNotEmpty($Token->getLockedAtTimeStamp());
        $this->assertTrue($Token->isLocked());
        $this->assertFalse($Token->isFree());
        $this->assertEquals($this->RandomStr, $Token->getName());
        //====================================================================//
        // Acquire Token Again
        for( $i=0 ; $i < 5 ; $i++ ) {
            $this->assertFalse($this->TokenRepository->Acquire($this->RandomStr));
        }
        //====================================================================//
        // Release Token
        $this->assertTrue($this->TokenRepository->Release($this->RandomStr));
        //====================================================================//
        // Verify Token
        $this->assertFalse($Token->isLocked());
        $this->assertTrue($Token->isFree());
        $this->assertEquals($this->RandomStr, $Token->getName());

        //====================================================================//
        // Acquire Token Again
        $this->assertInstanceOf( 
                Token::class,
                $this->TokenRepository->Acquire($this->RandomStr)
                );
        //====================================================================//
        // Delete Tokens
        $this->assertTrue($this->TokenRepository->Delete($this->RandomStr));
    } 
    
    /**
     * @abstract    Test Token Self-Release Features
     */    
    public function testSelfRelease()
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        //====================================================================//
        // Create a New Token
        $Token = new Token($this->RandomStr);
        
        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $Token->Acquire();
        $MinAge = new \DateTime("-" . (Token::SELFRELEASE_DELAY - 2) . " Seconds");
        $Token->setLockedAt($MinAge);
        $this->_em->persist($Token);
        $this->_em->flush();
        //====================================================================//
        // Test Acquire a Token
        $this->assertFalse($this->TokenRepository->Acquire($this->RandomStr));
        
        //====================================================================//
        // Acquire Token and Change LockedAt Date
        //====================================================================//
        $Token->Acquire();
        $MaxAge = new \DateTime("-" . (Token::SELFRELEASE_DELAY + 1) . " Seconds");
        $Token->setLockedAt($MaxAge);
        $this->_em->persist($Token);
        $this->_em->flush();
        
        //====================================================================//
        // Test Acquire a Token
        $this->assertInstanceOf(Token::class , $this->TokenRepository->Acquire($this->RandomStr));
        
        //====================================================================//
        // Test Acquire a Token
        for( $i=0 ; $i < 5 ; $i++ ) {
            $this->assertFalse($this->TokenRepository->Acquire($this->RandomStr));
        }
        
        //====================================================================//
        // Test Relase a Token
        $this->assertTrue($this->TokenRepository->Release($this->RandomStr));
        
        //====================================================================//
        // Test Delete a Token
        $this->TokenRepository->Delete($this->RandomStr);
        $this->assertNull($this->TokenRepository->findOneByName($this->RandomStr));

    }   
    
}
