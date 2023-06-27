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

namespace Splash\Tasking\Model;

use Exception;

/**
 * Service Action for Background Jobs
 */
abstract class AbstractServiceJob extends AbstractJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected array $inputs = array(
        "Method" => null,
        "Inputs" => array(),
    );

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected array $settings = array(
        "label" => "Service Job",
        "description" => "Abstract Service Job Base",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    //==============================================================================
    //      Service Job Configurator
    //==============================================================================

    /**
     * Service Job Constructor
     *
     * @param null|object $service Target Service
     */
    public function __construct(private ?object $service = null)
    {
    }

    //==============================================================================
    //      Service Job Execution
    //==============================================================================

    /**
     * Override this function to validate you Input parameters
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate() : bool
    {
        //====================================================================//
        // Check target method is Defined
        if (empty($this->getMethod())) {
            return false;
        }
        //====================================================================//
        // Check Service is Configured
        if (!isset($this->service)) {
            throw new Exception(
                "Target Service not initialized. Did you forgot to register a configurator?"
            );
        }
        //====================================================================//
        // Check Service Method Exists
        if (!method_exists($this->service, $this->getMethod())) {
            throw new Exception(sprintf(
                "Method %s not found on service %s",
                $this->getMethod(),
                get_class($this->service)
            ));
        }

        return true;
    }

    /**
     * Override this function to perform your task
     *
     * @return bool
     */
    public function execute() : bool
    {
        //====================================================================//
        // Check Service is Configured
        if (!isset($this->service)) {
            return false;
        }
        //====================================================================//
        // Load Requested Service
        $method = $this->getMethod();
        $inputs = $this->getInputs();
        //====================================================================//
        // Execute Service Method
        return $this->service->{ $method }($inputs);
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Service Job Method Name
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->inputs["Method"] ?? "";
    }

    /**
     * Set Service Job Service Name
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->inputs["Method"] = $method;

        return $this;
    }

    /**
     * Set Job Inputs
     *
     * @param array $inputs
     *
     * @return $this
     */
    public function setInputs(array $inputs): AbstractJob
    {
        $this->inputs["Inputs"] = $inputs;

        return $this;
    }

    /**
     * Get Job Inputs
     *
     * @return array
     */
    public function getInputs(): array
    {
        if (isset($this->inputs["Inputs"])) {
            return $this->inputs["Inputs"];
        }

        return array();
    }
}
