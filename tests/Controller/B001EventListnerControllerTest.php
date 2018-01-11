<?php

namespace Splash\Tasking\Tests\Controller;

use Splash\Tasking\Tests\Jobs\TestJob;
use Splash\Tasking\Tests\Jobs\TestStaticJob;
use Splash\Tasking\Entity\Task;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

class EventListnerControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    private $RandomStr;
    
    /**
     * @var \Symfony\Component\Console\Output\NullOutput
     */
    private $Output;
    
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Generate a Fake Output
        $this->Output       =   new NullOutput();
    }  
    
    /**
     * @abstract    Test of Task Event Listener Job Validate Function
     */    
    public function testJobValidate()
    {
        //====================================================================//
        // Link to Tasks Event Listener Services
        $Listener = static::$kernel->getContainer()->get('Tasking.EventListener');
        
        //====================================================================//
        // Test Standard Result
        $this->assertTrue($Listener->Validate(new TestJob()));
        
        //====================================================================//
        // Detect No Inputs
        $this->assertFalse($Listener->Validate(Null));
        
        //====================================================================//
        // Detect Wrong Action
        $this->assertFalse($Listener->Validate(
                (new TestJob())->setInputs(["Error-Wrong-Action" => True])
                ));
        
        //====================================================================//
        // Detect Wrong Priority
        $this->assertFalse($Listener->Validate(
                (new TestJob())->setInputs(["Error-Wrong-Priority" => True])
                ));
        
        //====================================================================//
        // Detect Wrong Inputs
        $this->assertFalse($Listener->Validate(
                (new TestJob())->setInputs(new \DateTime())
                ));
        
        //====================================================================//
        // Detect Wrong Token
        $this->assertFalse($Listener->Validate(
                (new TestJob())->setToken(new \DateTime())
                ));
        
    }    
        /**
     * @abstract    Test of Task Event Listener Job Validate Function
     */    
    public function testJobPrepare()
    {
        //====================================================================//
        // Link to Tasks Event Listener Services
        $Listener = static::$kernel->getContainer()->get('Tasking.EventListener');
        
        //====================================================================//
        // Convert Generic Job to Task
        $Job    =   new TestJob();
        $Task   =   $Listener->Prepare($Job);
        
        //====================================================================//
        // Verify Generic Job Result
        $this->assertInstanceOf( Task::class , $Task);
        $this->assertNotEmpty(  $Task->getName() );
        $this->assertEquals(    $Task->getJobClass()        , "\\" . get_class($Job) );
        $this->assertEquals(    $Task->getJobInputs()       , $Job->__get("inputs") );
        $this->assertEquals(    $Task->getJobPriority()     , $Job->getPriority() );
        $this->assertEquals(    $Task->getJobToken()        , $Job->getToken() );
        $this->assertEquals(    $Task->getSettings()        , $Job->getSettings() );
        $this->assertEquals(    $Task->getJobIndexKey1()    , $Job->getIndexKey1() );
        $this->assertEquals(    $Task->getJobIndexKey2()    , $Job->getIndexKey2() );
        $this->assertFalse(     $Task->getRunning() );
        $this->assertFalse(     $Task->getFinished() );
        $this->assertEquals(    0, $Task->getTry() );
        $this->assertEmpty(     $Task->getFaultStr() );
        
        
        //====================================================================//
        // Convert Static Job to Task
        $StaticJob    =   new TestStaticJob();
        $StaticTask   =   $Listener->Prepare($StaticJob);
        
        //====================================================================//
        // Verify Static Job Result
        $this->assertInstanceOf(Task::class , $StaticTask);
        $this->assertTrue(      $StaticTask->getJobIsStatic() );
        $this->assertNotEmpty(  $StaticTask->getJobFrequency() );
        
    }

        
}
