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

namespace Splash\Tasking\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Splash\Tasking\Repository\TaskRepository;

/**
 * Tasks Lists Displays Controller
 */
class ListController extends Controller
{
    /**
     * Display List of All Tasks
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return Response
     */
    public function allAction(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0): Response
    {
        //==============================================================================
        // Load Task Repository
        /** @var TaskRepository $repository */
        $repository = $this->get('doctrine')->getManager()
            ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $filters = $this->getIndexKeysFindBy($key2, $key1);
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks' => $repository->findBy($filters, $orderBy, $limit, $offset),
        ));
    }

    /**
     * Display List of All Waiting Tasks
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return Response
     */
    public function waitingAction(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0): Response
    {
        //==============================================================================
        // Load Task Repository
        $repository = $this->get('doctrine')->getManager()
            ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $filters = $this->getIndexKeysFindBy($key2, $key1);
        $filters["running"] = 0;
        $filters["finished"] = 0;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks' => $repository->findBy($filters, $orderBy, $limit, $offset),
        ));
    }

    /**
     * Display List of All Actives Tasks
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return Response
     */
    public function activeAction(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0): Response
    {
        //==============================================================================
        // Load Task Repository
        $repository = $this->get('doctrine')->getManager()
            ->getRepository('SplashTaskingBundle:Task');
        //==============================================================================
        // Compute Filters
        $filters = $this->getIndexKeysFindBy($key2, $key1);
        $filters["running"] = 1;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks' => $repository->findBy($filters, $orderBy, $limit, $offset),
        ));
    }

    /**
     * Display List of All Waiting Tasks
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return Response
     */
    public function completedAction(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0): Response
    {
        //==============================================================================
        // Compute Filters
        $filters = $this->getIndexKeysFindBy($key2, $key1);
        $filters["running"] = 0;
        $filters["finished"] = 1;
        //==============================================================================
        // Render All Tasks List
        return $this->render('SplashTaskingBundle:List:tasks.html.twig', array(
            'tasks' => $this->getTasksRepository()->findBy($filters, $orderBy, $limit, $offset),
        ));
    }

    /**
     * Display Summary of All Tasks with Indexes Filters
     *
     * @param string $key1 Your Custom Index Key 1
     * @param string $key2 Your Custom Index Key 2
     *
     * @return Response
     */
    public function summaryAction(string $key1 = null, string $key2 = null): Response
    {
        //==============================================================================
        // Render Tasks Sumary
        return $this->render('SplashTaskingBundle:List:summary.html.twig', array(
            'summary' => $this->getTasksRepository()->getTasksSummary($key1, $key2),
        ));
    }

    /**
     * Display Tasks Status List
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return Response
     */
    public function statusAction(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0): Response
    {
        //==============================================================================
        // Render Tasks Sumary
        return $this->render('SplashTaskingBundle:List:status.html.twig', array(
            'status' => $this->getTasksRepository()->getTasksStatus($key1, $key2, $orderBy, $limit, $offset),
        ));
    }

    /**
     * Create Index Keys FindBy Array
     *
     * @param string $indexKey1 Your Custom Index Key 1
     * @param string $indexKey2 Your Custom Index Key 2
     */
    private function getIndexKeysFindBy(string $indexKey1 = null, string $indexKey2 = null) : array
    {
        $filters = array();
        if (null != $indexKey1) {
            $filters["jobIndexKey1"] = $indexKey1;
        }
        if (null != $indexKey2) {
            $filters["jobIndexKey2"] = $indexKey2;
        }

        return $filters;
    }
    
    /**
     * Safe Load Tasks Repository
     *
     * @return TaskRepository
     */
    private function getTasksRepository(): TaskRepository
    {
        $repository = $this->get('doctrine')->getManager()
            ->getRepository('SplashTaskingBundle:Task');
        
        if(!($repository instanceof TaskRepository)) {
            throw new \Exception("Unable to Load Tasks Repository");
        }
        
        return $repository;
    }
    
}
