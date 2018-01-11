<?php

namespace Splash\Tasking\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class ListController extends Controller
{
    
    /**
     * Display List of All Tasks
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * @param   array       $OrderBy        List Ordering
     * @param   int         $Limit          Limit Number of Items
     * @param   int         $Offset         Page Offset
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allAction( string $Key1 = Null, string $Key2 = Null, array $OrderBy = [], int $Limit = 10, int $Offset = 0 )
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $Filters = $this->getIndexKeysFindBy($Key2, $Key1);
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks'         =>  $Repository->findBy($Filters,$OrderBy,$Limit,$Offset)
        ));
        
    }
        
        
    /**
     * Display List of All Waiting Tasks
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * @param   array       $OrderBy        List Ordering
     * @param   int         $Limit          Limit Number of Items
     * @param   int         $Offset         Page Offset
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function waitingAction( string $Key1 = Null, string $Key2 = Null,array $OrderBy = [], int $Limit = 10, int $Offset = 0 )
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $Filters    =   $this->getIndexKeysFindBy($Key2, $Key1);
        $Filters["running"]     =   0;
        $Filters["finished"]    =   0;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks'         =>  $Repository->findBy($Filters,$OrderBy,$Limit,$Offset)
        ));
        
    }
    
    /**
     * Display List of All Actives Tasks
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * @param   array       $OrderBy        List Ordering
     * @param   int         $Limit          Limit Number of Items
     * @param   int         $Offset         Page Offset
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activeAction( string $Key1 = Null, string $Key2 = Null,array $OrderBy = [], int $Limit = 10, int $Offset = 0 )
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $Filters    =   $this->getIndexKeysFindBy($Key2, $Key1);
        $Filters["running"]     =   1;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks'         =>  $Repository->findBy($Filters,$OrderBy,$Limit,$Offset)
        ));
        
    }

         
    /**
     * Display List of All Waiting Tasks
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * @param   array       $OrderBy        List Ordering
     * @param   int         $Limit          Limit Number of Items
     * @param   int         $Offset         Page Offset
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function completedAction( string $Key1 = Null, string $Key2 = Null, array $OrderBy = [], int $Limit = 10, int $Offset = 0 )
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $Filters    =   $this->getIndexKeysFindBy($Key2, $Key1);
        $Filters["running"]     =   0;
        $Filters["finished"]    =   1;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks'         =>  $Repository->findBy($Filters,$OrderBy,$Limit,$Offset)
        ));
        
    }
    
   /**
     * Display Summary of All Tasks with Indexes Filters
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function summaryAction( string $Key1 = Null, string $Key2 = Null)
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Render Tasks Sumary
        return $this->render('SplashTaskingBundle:List:summary.html.twig', array(
            'summary'         =>  $Repository->getTasksSummary($Key1,$Key2)
        ));
    } 
    
   /**
     * Display Tasks Status List
     * 
     * @param   string      $Key1           Your Custom Index Key 1
     * @param   string      $Key2           Your Custom Index Key 2
     * @param   array       $OrderBy        List Ordering
     * @param   int         $Limit          Limit Number of Items
     * @param   int         $Offset         Page Offset
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function statusAction( string $Key1 = Null, string $Key2 = Null, array $OrderBy = [], int $Limit = 10, int $Offset = 0 )
    {
        //==============================================================================
        // Load Task Repository
        $Repository =   $this->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        
        //==============================================================================
        // Render Tasks Sumary
        return $this->render('SplashTaskingBundle:List:status.html.twig', array(
            'status'         =>  $Repository->getTasksStatus($Key1,$Key2,$OrderBy,$Limit,$Offset)
        ));
    } 
    
    
    
    /**
     * @abstract    Create Index Keys FindBy Array
     *  
     * @param   string      $IndexKey1          Your Custom Index Key 1
     * @param   string      $IndexKey2          Your Custom Index Key 2
     */        
    private function getIndexKeysFindBy(string $IndexKey1 = Null , string $IndexKey2 = Null) {
        
        $Filters = [];
        
        if ( !empty($IndexKey1) ) {
            $Filters["jobIndexKey1"] = $IndexKey1;
        }
        
        if ( !empty($IndexKey2) ) {
            $Filters["jobIndexKey2"] = $IndexKey2;
        }
        
        return $Filters;
    }        
    
}
