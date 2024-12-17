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

namespace BadPixxel\Tasking\Tests\Bundle\Jobs;

use BadPixxel\Tasking\Attribute\AsTaskingJob;
use BadPixxel\Tasking\Model\AbstractJob;
use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple Job for Testing Exceptions during Execution
 */
#[AsTaskingJob(
    token: self::class
)]
class JobWithExceptions extends AbstractJob
{
    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Job with Exception",
            "description" => "Job that throw exceptions on execution !",
        );
    }

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function validate() : bool
    {
        //====================================================================//
        // Throw exception if requested!
        return $this->doThrowException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function prepare() : bool
    {
        //====================================================================//
        // Throw exception if requested!
        return $this->doThrowException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function execute() : bool
    {
        sleep(1);

        //====================================================================//
        // Throw exception if requested!
        return $this->doThrowException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function finalize() : bool
    {
        //====================================================================//
        // Throw exception if requested!
        return $this->doThrowException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function close() : bool
    {
        //====================================================================//
        // Throw exception if requested!
        return $this->doThrowException(__FUNCTION__);
    }

    /**
     * Throw an Exception if Requested by User
     *
     * @throws Exception
     */
    public function doThrowException(string $methodName): bool
    {
        echo sprintf("%s->%s() <br />", __CLASS__, $methodName);
        //====================================================================//
        // Trow exception if requested!
        if (!empty($this->getInputs()[$methodName])) {
            throw new Exception(sprintf("You requested Job to Fail on %s Method.", $methodName));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            // Simulate Exceptions
            "validate" => false,
            "prepare" => false,
            "execute" => false,
            "finalize" => false,
            "close" => false,
        ));
    }
}
