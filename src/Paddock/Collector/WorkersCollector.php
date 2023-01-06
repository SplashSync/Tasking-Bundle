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

namespace Splash\Tasking\Paddock\Collector;

use BadPixxel\Paddock\Core\Collector\AbstractCollector;
use Splash\Tasking\Services\Configuration;

class WorkersCollector extends AbstractCollector
{
    //====================================================================//
    // DEFINITION
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public static function getCode(): string
    {
        return "tasking-workers";
    }

    /**
     * {@inheritDoc}
     */
    public static function getDescription(): string
    {
        return "[TASKING] Workers Status Collector";
    }

    //====================================================================//
    // COLLECTOR
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public function get(string $key)
    {
        switch ($key) {
            case "total":
            case "workers":
            case "supervisor":
            case "running":
            case "disabled":
            case "sleeping":
                return $this->getWorkerCounter($key);
            default:
                $this->error("Requested key does not exists: ".$key);

                return null;
        }
    }

    //====================================================================//
    // PRIVATE METHODS
    //====================================================================//

    /**
     * Get a Worker Status Counter
     *
     * @param string $key
     *
     * @return int
     */
    private function getWorkerCounter(string $key): int
    {
        static $status;

        try {
            if (!isset($status)) {
                $status = Configuration::getWorkerRepository()->getWorkersStatus();
            }

            return $status[$key] ?? 0;
        } catch (\Exception $ex) {
            $this->getLogger()->emergency($ex->getMessage());

            return 0;
        }
    }
}
