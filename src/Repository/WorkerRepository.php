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

namespace Splash\Tasking\Repository;

use Doctrine\ORM\EntityRepository;
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
        return  $this->findOneBy(array(
            "nodeName" => $system["nodename"],
            "pID" => getmypid(),
        ));
    }

    /**
     * Identify Worker on this machine using it's Process Number
     *
     * @param int $processId Worker Process Id
     *
     * @return null|Worker
     */
    public function findOneByProcess(int $processId): ?Worker
    {
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //====================================================================//
        // Retrieve Server Local Supervisor
        return  $this->findOneBy(array(
            "nodeName" => $system["nodename"],
            "process" => $processId,
        ));
    }

    /**
     * Count Number of Active Workers
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
}
