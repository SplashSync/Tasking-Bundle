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
class JobWithErrors extends AbstractJob
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
            "label" => "[TEST] Job with Errors",
            "description" => "Job that fail on execution !",
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
        // Return False if requested!
        return $this->doErrorReturn(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function prepare() : bool
    {
        //====================================================================//
        // Return False if requested!
        return $this->doErrorReturn(__FUNCTION__);
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
        // Return False if requested!
        return $this->doErrorReturn(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function finalize() : bool
    {
        //====================================================================//
        // Return False if requested!
        return $this->doErrorReturn(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function close() : bool
    {
        //====================================================================//
        // Return False if requested!
        return $this->doErrorReturn(__FUNCTION__);
    }

    /**
     * Return False (Error) if Requested by User
     */
    public function doErrorReturn(string $methodName): bool
    {
        echo sprintf("%s->%s() <br />", __CLASS__, $methodName);
        //====================================================================//
        // Trow exception if requested!
        if (!empty($this->getInputs()[$methodName])) {
            echo "You have requested an Error on ".$methodName." Method.";

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            // Simulate Errors
            "validate" => false,
            "prepare" => false,
            "execute" => false,
            "finalize" => false,
            "close" => false,
        ));
    }
}
