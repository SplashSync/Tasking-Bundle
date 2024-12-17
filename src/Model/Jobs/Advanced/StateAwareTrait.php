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

namespace BadPixxel\Tasking\Model\Jobs\Advanced;

use BadPixxel\Tasking\Dictionary\RepeatableJobState;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage Task State Storage Inside Task Inputs
 */
trait StateAwareTrait
{
    /**
     * Check if batch actions are completed or task needs to be executed again (pagination)
     */
    public function isCompleted() : bool
    {
        return (bool) $this->getStateItem(RepeatableJobState::COMPLETED);
    }

    /**
     * Set repeatable actions as completed
     */
    public function setCompleted() : void
    {
        $this->setStateItem(RepeatableJobState::COMPLETED, true);
    }

    /**
     * Check if batch actions was initiated
     */
    public function isBooted() : bool
    {
        return (bool) $this->getStateItem(RepeatableJobState::BOOTED);
    }

    /**
     * Set repeatable actions as booted
     */
    public function setBooted() : void
    {
        $this->setStateItem(RepeatableJobState::BOOTED, true);
    }

    /**
     * Check if Errors have occurred during batch action
     *
     * @return bool
     */
    public function hasErrors() : bool
    {
        return (bool) $this->getStateItem(RepeatableJobState::ERROR);
    }

    /**
     * Get Repeatable Job State
     */
    public function getState(): array
    {
        return $this->inputs["state"] ?? array();
    }

    /**
     * Set Batch Action State Item
     */
    public function setStateItem(string $index, bool|int $value): static
    {
        $this->setState(array_replace_recursive(
            $this->getState(),
            array($index => $value)
        ));

        return $this;
    }

    /**
     * Increment Batch Action State Item
     */
    public function incStateItem(string $index, int $offset = 1): static
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
     * Set Repeatable Job State
     */
    private function setState(array $state): static
    {
        //==============================================================================
        //  Update State Array using OptionResolver
        try {
            $this->inputs["state"] = self::getStateResolver()->resolve($state);
            //==============================================================================
            //  Invalid Field Definition Array
        } catch (UndefinedOptionsException | InvalidOptionsException $ex) {
            $this->inputs["state"] = self::getStateResolver()->resolve($this->getState());
        }

        return $this;
    }

    /**
     * Get Job State Resolver
     */
    private static function getStateResolver(): OptionsResolver
    {
        static $resolver;

        //==============================================================================
        // Init State OptionResolver
        if (!isset($resolver)) {
            $resolver = new OptionsResolver();
            $resolver->setDefaults(array(
                //==============================================================================
                //  General Status Flags
                RepeatableJobState::COMPLETED => false,
                RepeatableJobState::BOOTED => false,

                //==============================================================================
                //  Batch Counters
                RepeatableJobState::COUNT => 0,
                RepeatableJobState::EXECUTED => 0,
                RepeatableJobState::SUCCESS => 0,
                RepeatableJobState::ERROR => 0,

                //==============================================================================
                //  Batch Execution
                RepeatableJobState::CURRENT => 0,
            ));
        }

        return $resolver;
    }
}
