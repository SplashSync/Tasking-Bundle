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

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\StaticJobInterface;

/**
 * Demonstration fo Simple Background Jobs
 */
class TestListenerJob extends TestJob implements StaticJobInterface
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    protected static int $priority = Task::DO_LOWEST;

    /**
     * {@inheritdoc}
     */
    protected int $frequency = 0;

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Listener Job for Test",
        "description" => "Listener Job for Bundle Testing",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    /**
     * Complete Job Information on Input
     *
     * @inheritdoc
     */
    public function setInputs(array $inputs): self
    {
        $this->settings["label"] = sprintf(
            "Static listener job %s",
            $inputs["index"] ?? "! unknown !"
        );
        $this->settings["description"] = sprintf(
            "Static job Index %s created form Listener",
            $inputs["index"] ?? "! unknown !"
        );
        $this->indexKey1 = $inputs["index"] ?? null;

        return parent::setInputs($inputs);
    }
}
