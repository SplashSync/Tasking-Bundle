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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Splash\Tasking\Entity\Worker;

/**
 * Workers Repository
 */
class WorkerRepository extends EntityRepository
{
    /**
     * Identify Current Worker on this machine using it's PID
     *
     * @return null|Worker
     */
    public function findOneByLinuxPid(): ?Worker
    {
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //====================================================================//
        // Retrieve Server Local Supervisor
        $worker = $this->findOneBy(array(
            "nodeName" => is_array($system) ? $system["nodename"] : "Unknown",
            "pID" => getmypid(),
        ));

        return ($worker instanceof Worker) ? $worker : null;
    }

    /**
     * Identify Worker on this machine using it's Process Number
     *
     * @param int $processId Worker Process Id
     *
     * @throws ORMException
     *
     * @return null|Worker
     */
    public function findOneByProcess(int $processId): ?Worker
    {
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //====================================================================//
        // Retrieve Server Local Worker
        $worker = $this->findOneBy(array(
            "nodeName" => is_array($system) ? $system["nodename"] : "Unknown",
            "process" => $processId,
        ));
        //====================================================================//
        // Ensure Sync with Database
        if ($worker instanceof Worker) {
            $this->_em->refresh($worker);

            return $worker;
        }

        return null;
    }

    /**
     * Count Number of Active Workers
     *
     * @throws MappingException
     *
     * @return int
     */
    public function countActiveWorkers() : int
    {
        //====================================================================//
        // Clear EntityManager
        $this->_em->clear();
        //====================================================================//
        // Load List of All Currently Setuped Workers
        $workers = $this->findAll();
        //====================================================================//
        // Count Actives Workers
        $activesWorkers = 0;
        /** @var Worker $worker */
        foreach ($workers as $worker) {
            //====================================================================//
            // Worker Is Inactive => Nothing to Do
            if (false == $worker->isRunning()) {
                continue;
            }
            $activesWorkers++;
        }

        return $activesWorkers;
    }

    /**
     * Get Worker Status Array
     *
     * @return array
     */
    public function getWorkersStatus(): array
    {
        //====================================================================//
        // Get List of Workers
        $this->_em->clear();
        $workers = $this->findAll();
        //====================================================================//
        // Init Counters
        $status = array(
            "total" => 0,
            "workers" => 0,
            "supervisor" => 0,
            "running" => 0,
            "disabled" => 0,
            "sleeping" => 0,
        );
        //====================================================================//
        // Update Workers Counters
        /** @var Worker $worker */
        foreach ($workers as $worker) {
            //====================================================================//
            // Workers is Supervisor
            if ((0 == $worker->getProcess()) && $worker->isRunning()) {
                $status["supervisor"]++;
            }
            if ((0 == $worker->getProcess())) {
                continue;
            }
            $status["total"]++;
            //====================================================================//
            // Workers is Running
            if ($worker->isRunning()) {
                $status["running"]++;

                continue;
            }
            //====================================================================//
            // Workers is Disabled
            if (!$worker->isEnabled()) {
                $status["disabled"]++;

                continue;
            }
            //====================================================================//
            // Workers is Sleeping
            $status["sleeping"]++;
        }

        return $status;
    }
}
