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

/**
 * Manage Extended Inputs Storage for Batch & Mass Jobs
 */
trait ExtendedInputsTrait
{
    /**
     * Move Job User Inputs to "inputs" array
     *
     * @param array $inputs
     *
     * @return $this
     */
    public function setInputs(array $inputs): static
    {
        // First Init => Move Job Inputs to Input Key
        if (!isset($inputs["inputs"])) {
            $this->inputs = array("inputs" => $inputs);
        } else {
            $this->inputs = $inputs;
        }

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
}
