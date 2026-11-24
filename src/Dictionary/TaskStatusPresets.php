<?php

/*
 *  Copyright (C) BadPixxel <www.badpixxel.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace BadPixxel\Tasking\Dictionary;

use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;
use Sonata\Form\Type\BooleanType;

/**
 * Predefined Tasks List Filters for Sonata Admin
 *
 * Each preset is a set of standard Sonata datagrid filter values.
 */
class TaskStatusPresets
{
    /**
     * Never started tasks, waiting for a worker.
     */
    const WAITING = array(
        "label" => "Waiting",
        "icon" => "fa fa-hourglass-half text-muted",
        "filter" => array(
            "running" => array("value" => BooleanType::TYPE_NO),
            "finished" => array("value" => BooleanType::TYPE_NO),
            "try" => array("type" => NumberOperatorType::TYPE_EQUAL, "value" => 0),
        ),
    );

    /**
     * Tasks executed right now.
     */
    const RUNNING = array(
        "label" => "Running",
        "icon" => "fa fa-play text-orange",
        "filter" => array(
            "running" => array("value" => BooleanType::TYPE_YES),
            "finished" => array("value" => BooleanType::TYPE_NO),
        ),
    );

    /**
     * Failed tasks: retrying, or abandoned once max tries reached.
     */
    const ERRORS = array(
        "label" => "Errors",
        "icon" => "fa fa-exclamation-triangle text-red",
        "filter" => array(
            "running" => array("value" => BooleanType::TYPE_NO),
            "finished" => array("value" => BooleanType::TYPE_NO),
            "try" => array("type" => NumberOperatorType::TYPE_GREATER_EQUAL, "value" => 1),
        ),
    );

    /**
     * Successfully completed tasks.
     */
    const FINISHED = array(
        "label" => "Finished",
        "icon" => "fa fa-check text-green",
        "filter" => array(
            "finished" => array("value" => BooleanType::TYPE_YES),
        ),
    );

    /**
     * Static (scheduled) tasks.
     */
    const STATIC = array(
        "label" => "Static",
        "icon" => "fa fa-calendar text-aqua",
        "filter" => array(
            "jobIsStatic" => array("value" => BooleanType::TYPE_YES),
        ),
    );

    /**
     * All Presets, in display order.
     */
    const ALL = array(
        self::WAITING,
        self::RUNNING,
        self::ERRORS,
        self::FINISHED,
        self::STATIC,
    );
}
