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

namespace BadPixxel\Tasking\Tests\Bundle\Jobs;

use BadPixxel\Tasking\Attribute\AsTaskingJob;
use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Events\TaskKeepAliveEvent;
use BadPixxel\Tasking\Model\AbstractJob;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests Of a Long Background Job Signaling it is Still Alive
 *
 * Only dispatches generic keep-alive events: no dependency to the Status Monitor.
 */
#[AsTaskingJob(
    token: self::class
)]
class KeepAliveJob extends AbstractJob
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    //==============================================================================
    // Job Setup
    //==============================================================================

    /**
     * Build Job Options
     *
     * @param int         $duration Job Duration in Seconds
     * @param null|string $token    Job Token
     *
     * @return array
     */
    public static function toOptions(int $duration, ?string $token = null): array
    {
        return array_filter(array(
            JobOptions::TOKEN => $token,
            JobOptions::INPUTS => array(
                "Duration" => $duration,
            ),
        ));
    }

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    public function execute() : bool
    {
        $duration = $this->getInputs()["Duration"];
        //==============================================================================
        // Work & Signal we are Still Alive Every Second
        for ($second = 0; $second < $duration; $second++) {
            sleep(1);
            $event = $this->dispatcher->dispatch(new TaskKeepAliveEvent());
            if (!$event->isAccepted()) {
                echo "Keep-Alive Rejected at ".$second." Seconds".PHP_EOL;

                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "[TEST] Keep-Alive Job",
            "description" => "Long Job Emitting Keep-Alive Signals for Bundle Testing",
        );
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            // Job Duration in Seconds
            "Duration" => 1,
        ));
        $resolver->setAllowedTypes("Duration", "int");
    }
}
