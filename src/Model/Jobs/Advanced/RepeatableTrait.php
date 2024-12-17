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

use BadPixxel\Tasking\Dictionary\RepeatableJobOptions;
use BadPixxel\Tasking\Dictionary\RepeatableJobState as JobState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Repeatable Job Common Features
 */
trait RepeatableTrait
{
    use StateAwareTrait;

    //==============================================================================
    // Batch Job Inputs Resolution
    //==============================================================================

    /**
     * @inheritdoc
     */
    final public function resolveInputs(array $inputs): array
    {
        //==============================================================================
        // If Batch Already Started => Skip Resolution
        if (isset($inputs["inputs"])) {
            return $inputs;
        }
        $resolver = new OptionsResolver();
        //====================================================================//
        // Batch Pagination
        $resolver->setDefault(RepeatableJobOptions::PAGINATION, 1);
        //====================================================================//
        // Stop On Error Option
        $resolver->setDefault(RepeatableJobOptions::STOP_ON_ERROR, true);
        //==============================================================================
        // Configure Inputs Resolver from Job Service
        $this->configureInputsResolver($resolver);

        //==============================================================================
        //  Resolve Merged Inputs
        return $resolver->resolve($inputs);
    }

    /**
     * Update State from Job Result
     *
     * @param bool $jobResult
     *
     * @return bool If False, Loop Should be Stopped
     */
    protected function setJobResult(bool $jobResult): bool
    {
        //==============================================================================
        // Update State
        $this->incStateItem(JobState::EXECUTED);
        $this->incStateItem(($jobResult ? JobState::SUCCESS : JobState::ERROR));
        //==============================================================================
        // Manage Stop on Error
        if (!$jobResult && $this->isStropOnError()) {
            $this->setCompleted();

            return false;
        }

        return true;
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Batch Pagination Parameter
     */
    protected function getPaginate(): int
    {
        $paginate = $this->getInputs()[RepeatableJobOptions::PAGINATION] ?? 1;
        Assert::greaterThanEq($paginate, 1);

        return $paginate;
    }

    /**
     * Get Stop On Error Parameter
     */
    protected function isStropOnError(): bool
    {
        return $this->getInputs()[RepeatableJobOptions::STOP_ON_ERROR] ?? true;
    }
}
