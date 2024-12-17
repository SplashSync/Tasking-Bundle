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

use BadPixxel\Tasking\Attribute\AsTaskingBatchJob;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Model\AbstractBatchJob;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demonstration for Simple Batch Jobs
 */
#[AsTaskingBatchJob(
    paginate: 7,
    token: self::class
)]
class BatchJob extends AbstractBatchJob
{
    /**
     * Build Job Options
     */
    public static function toOptions(
        ?int $nbTasks = null,
        ?int $msDelay = null,
        ?string $token = null,
    ): array {
        return array_filter(array(
            JobOptions::TOKEN => $token,
            JobOptions::INPUTS => array_filter(array(
                "nbTasks" => $nbTasks,
                "msDelay" => $msDelay,
            )),
        ));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Batch Job",
            "description" => "Demonstration of a Batch Job",
        );
    }

    //==============================================================================
    // Batch Task Execution
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    public function validate() : bool
    {
        $inputs = $this->getInputs();
        echo "Batch Job => Validate Inputs </br>";
        if (!is_integer($inputs["nbTasks"])) {
            return false;
        }
        echo "Batch Job => Nb Tasks is a Integer </br>";
        if (!is_integer($inputs["msDelay"])) {
            return false;
        }
        echo "Batch Job => Ms Delay is a Integer </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configure() : array
    {
        $inputs = $this->getInputs();
        $batchList = array();
        for ($i = 0; $i < ($inputs["nbTasks"] ?: 10); $i++) {
            $batchList[] = array(
                "name" => "Job ".$i,
                "msDelay" => $inputs["msDelay"] ?: 100,
            );
        }

        return $batchList;
    }

    /**
     * {@inheritdoc}
     */
    public function executeAction(array $inputs = array()): bool
    {
        $msDelay = (int) (1E3 * $inputs["msDelay"]);
        echo "Batch Job => Execute a ".$inputs["msDelay"]." Microsecond Pause </br>";
        usleep($msDelay);

        return true;
    }

    //==============================================================================
    // Tasking Job Configuration
    //==============================================================================

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            "nbTasks" => 20,
            "msDelay" => 500,
        ));
        $resolver->setAllowedTypes("nbTasks", "int");
        $resolver->setAllowedTypes("msDelay", "int");
    }
}
