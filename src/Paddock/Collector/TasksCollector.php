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

namespace Splash\Tasking\Paddock\Collector;

use BadPixxel\Paddock\Core\Collector\AbstractCollector;
use Splash\Tasking\Services\Configuration;

class TasksCollector extends AbstractCollector
{
    //====================================================================//
    // DEFINITION
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public static function getCode(): string
    {
        return "tasking-tasks";
    }

    /**
     * {@inheritDoc}
     */
    public static function getDescription(): string
    {
        return "[TASKING] Tasks Status Collector";
    }

    //====================================================================//
    // COLLECTOR
    //====================================================================//

    /**
     * {@inheritDoc}
     */
    public function get(string $key)
    {
        switch (strtolower($key)) {
            case "waiting":
            case "running":
            case "finished":
            case "total":
            case "token":
                return $this->getTasksCounter(ucfirst($key));
            default:
                $this->error("Requested key does not exists: ".$key);

                return null;
        }
    }

    //====================================================================//
    // PRIVATE METHODS
    //====================================================================//

    /**
     * Get a Tasks Status Counter
     *
     * @param string $key
     *
     * @return int
     */
    private function getTasksCounter(string $key): int
    {
        static $status;

        try {
            if (!isset($status)) {
                $status = Configuration::getTasksRepository()->getTasksSummary();
            }

            return $status[$key] ?? 0;
        } catch (\Exception $ex) {
            $this->getLogger()->emergency($ex->getMessage());

            return 0;
        }
    }
}
