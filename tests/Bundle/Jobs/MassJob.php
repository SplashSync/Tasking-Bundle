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

use BadPixxel\Tasking\Attribute\AsTaskingMassJob;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Dictionary\RepeatableJobState;
use BadPixxel\Tasking\Model\AbstractMassJob;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demonstration for Simple Batch Jobs
 */
#[AsTaskingMassJob(
    paginate: 7,
    token: self::class
)]
class MassJob extends AbstractMassJob
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
            "label" => "[TEST] Mass Job",
            "description" => "Demonstration of a Mass Job",
        );
    }

    //==============================================================================
    // Mass Job Execution Management
    //==============================================================================

    /**
     * @inheritdoc
     */
    public function count(array $inputs = array()) : int
    {
        $inputs = $this->getInputs();

        return (int) $inputs["nbTasks"] - (int) $this->getStateItem(RepeatableJobState::EXECUTED);
    }

    /**
     * {@inheritdoc}
     */
    public function executeAction(): bool
    {
        $inputs = $this->getInputs();

        $msDelay = (int) (1E3 * $inputs["msDelay"]);
        echo "Mass Job => Execute a ".$inputs["msDelay"]." Microsecond Pause </br>";
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
