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

namespace BadPixxel\Tasking\Tests\Bundle\Model;

use BadPixxel\Tasking\Attribute\AsTaskingJob;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Model\AbstractJob;
use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Simple Background Jobs with Delays
 */
#[AsTaskingJob(
    token: self::class
)]
abstract class AbstractDelayJob extends AbstractJob
{
    /**
     * Build Job Options
     */
    public static function toOptions(
        ?int $delay = null,
        ?int $delayMs = null,
        ?string $token = null,
    ): array {
        return array_filter(array(
            JobOptions::TOKEN => $token,
            JobOptions::INPUTS => array_filter(array(
                "Delay-S" => $delay,
                "Delay-Ms" => $delayMs,
            )),
        ));
    }

    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Delay Job",
            "description" => "Delay Job for Testing",
        );
    }

    //==============================================================================
    // Task Execution
    //==============================================================================

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function execute() : bool
    {
        echo "Simple Job => Execute Requested Actions! </br>";
        //====================================================================//
        // Fetch Inputs
        $delayMs = $this->getInputs()["Delay-Ms"] ?? null;
        $delaySec = $this->getInputs()["Delay-S"] ?? null;
        if (!empty($delayMs)) {
            //====================================================================//
            // Milliseconds Delay
            Assert::integer($delayMs);
            Assert::greaterThanEq($delayMs, 30);
            echo "Simple Job => Wait for ".$delayMs." Ms </br>";
            usleep((int) 1E3 * $delayMs);
        } elseif (!empty($delaySec)) {
            //====================================================================//
            // Seconds Delay
            Assert::integer($delaySec);
            Assert::greaterThanEq($delaySec, 1);
            echo "Simple Job => Wait for ".$delaySec." Seconds </br>";
            sleep($delaySec);
        } else {
            sleep(1);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            "Delay-Ms" => 0,
            "Delay-S" => 1,
        ));
    }
}
