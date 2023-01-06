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

namespace Splash\Tasking\Model\Jobs;

use Splash\Tasking\Model\AbstractJob;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage Task State Storage Inside Task Inputs
 */
trait InputsStateTrait
{
    /**
     * Define Initial State for a Batch Action
     *
     * @var array
     */
    protected static array $state = array(
        //==============================================================================
        //  General Status Flags
        'isCompleted' => false,
        'isListLoaded' => false,

        //==============================================================================
        //  Batch Counters
        'tasksCount' => 0,
        'jobsCount' => 0,
        'jobsCompleted' => 0,
        'jobsSuccess' => 0,
        'jobsError' => 0,

        //==============================================================================
        //  Batch Execution
        "currentJob" => 0,
    );

    /**
     * Check if batch actions are completed or task needs to be executed again (pagination)
     *
     * @return bool
     */
    public function isCompleted() : bool
    {
        return (bool) $this->getStateItem("isCompleted");
    }

    /**
     * Check if Errors have occurred during batch action
     *
     * @return bool
     */
    public function hasErrors() : bool
    {
        return (bool) $this->getStateItem("jobsError");
    }

    /**
     * Move Job User Inputs to "inputs" array
     *
     * @param array $inputs
     *
     * @return $this
     */
    public function setInputs(array $inputs): AbstractJob
    {
        $this->inputs["inputs"] = $inputs;

        return $this;
    }

    /**
     * Get Job User Inputs
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs["inputs"];
    }

    /**
     * Set Job Status
     *
     * @param array $state
     *
     * @return $this
     */
    public function setState(array $state): self
    {
        $current = $this->inputs["state"] ?? static::getDefaultState();
        //==============================================================================
        //  Init State Array using OptionResolver
        $resolver = (new OptionsResolver())->setDefaults(static::getDefaultState());
        //==============================================================================
        //  Update State Array using OptionResolver
        try {
            $this->inputs["state"] = $resolver->resolve($state);
            //==============================================================================
            //  Invalid Field Definition Array
        } catch (UndefinedOptionsException | InvalidOptionsException $ex) {
            $this->inputs["state"] = $current;
        }

        return $this;
    }

    /**
     * Get Job Status
     *
     * @return array
     */
    public function getState(): array
    {
        return $this->inputs["state"];
    }

    /**
     * Set Batch Action State Item
     *
     * @param string $index
     * @param mixed  $value
     *
     * @return self
     */
    public function setStateItem(string $index, $value): self
    {
        //==============================================================================
        // Read Full State Array
        $state = $this->getState();
        //==============================================================================
        // Update Item
        $state[$index] = $value;
        //==============================================================================
        // Update Full State Array
        $this->setState($state);

        return $this;
    }

    /**
     * Increment Batch Action State Item
     *
     * @param string $index
     * @param int    $offset
     *
     * @return self
     */
    public function incStateItem(string $index, int $offset = 1): self
    {
        $this->setStateItem(
            $index,
            (int) $this->getStateItem($index) + $offset
        );

        return $this;
    }
    /**
     * Get Batch Action State Item
     *
     * @param string $index
     *
     * @return null|scalar
     */
    public function getStateItem(string $index)
    {
        if (isset($this->inputs["state"][$index])) {
            return $this->inputs["state"][$index];
        }

        return null;
    }

    /**
     * Get Default State
     *
     * @return array
     */
    protected static function getDefaultState(): array
    {
        return static::$state;
    }
}
