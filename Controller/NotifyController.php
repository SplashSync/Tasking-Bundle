<?php

namespace Splash\Tasking\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class NotifyController extends Controller
{
    
    /**
     * Current User
     * @var User         User
     */    
    private $User;
    
    /**
     * User Tasks Reprository
     */    
    private $TasksRepository;
    
    /**
     * Class Initialisation
     * 
     * @return bool 
     */    
    public function initialize() {
        //==============================================================================
        // Load User Data
        $this->User = $this->getUser();
        //==============================================================================
        // Safety Check 
        if (is_null($this->User)) {
            return False;
        }
        //==============================================================================
        // Load Doctrine Repository
        $this->TasksRepository        =   $this->get('doctrine')->getManager()->getRepository('TaskingBundle:Task');
        return True;
    }    

    /**
     * Display Notifications for a Specified Level
     * 
     * @param string    $Level      Requested Notification Level
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function notifyAction()
    {
//        //==============================================================================
//        // Init & Safety Check 
//        if (!$this->initialize()) {
//            return "ERROR";
//        }                
        
        return $this->render('TaskingBundle:Notify:contents.html.twig', array(
            'summary'         =>  $this->get('doctrine')->getManager()->getRepository('SplashTaskingBundle:Task')->getUserSortedTasks($this->User)
        ));
    } 
}
