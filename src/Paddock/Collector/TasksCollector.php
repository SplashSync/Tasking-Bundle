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

namespace BadPixxel\Tasking\Paddock\Collector;

use BadPixxel\Paddock\Core\Collector\AbstractCollector;
use BadPixxel\Tasking\Services\Configuration;
use Symfony\Contracts\Cache\CacheInterface;

class TasksCollector extends AbstractCollector
{
    /**
     * Service Constructor
     */
    public function __construct(
        CacheInterface $paddockCollectors,
        private readonly Configuration $configuration,
    ) {
        parent::__construct($paddockCollectors);
    }
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
                $status = $this->configuration->getTasksRepository()->getTasksSummary();
            }

            return $status[$key] ?? 0;
        } catch (\Exception $ex) {
            $this->getLogger()->emergency($ex->getMessage());

            return 0;
        }
    }
}
