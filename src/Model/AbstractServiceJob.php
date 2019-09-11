<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
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
    protected $inputs = array(
        "Service" => null,
        "Method" => null,
        "Inputs" => array(),
    );

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected $settings = array(
        "label" => "Service Job",
        "description" => "Abstract Service Job Base",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    //==============================================================================
    //      Service Job Execution
    //==============================================================================

    /**
     * Overide this function to validate you Input parameters
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate() : bool
    {
        //====================================================================//
        // Check Inputs Are Not Empty
        if ((null == $this->getService()) || (null == $this->getMethod())) {
            return false;
        }

        //====================================================================//
        // Check Service & Method Exists
        if (!$this->container->has($this->getService())) {
            throw new Exception(sprintf("Service %s not found", $this->getService()));
        }
        $service = $this->container->get($this->getService());
        if (!is_null($service) && !method_exists($service, $this->getMethod())) {
            throw new Exception(sprintf("Method %s not found", $this->getMethod()));
        }

        return true;
    }

    /**
     * Overide this function to perform your task
     *
     * @return bool
     */
    public function execute() : bool
    {
        //====================================================================//
        // Load Requested Service
        $service = $this->container->get($this->getService());
        $method = $this->getMethod();
        $inputs = $this->getInputs();
        //====================================================================//
        // Execute Service Method
        return $service->{ $method }($inputs);
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Service Job Service Name
     *
     * @return string
     */
    public function getService(): string
    {
        return isset($this->inputs["Service"]) ? $this->inputs["Service"] : "";
    }

    /**
     * Set Service Job Service Name
     *
     * @param string $service
     *
     * @return self
     */
    public function setService(string $service): self
    {
        $this->inputs["Service"] = $service;

        return $this;
    }

    /**
     * Get Service Job Method Name
     *
     * @return string
     */
    public function getMethod(): string
    {
        return isset($this->inputs["Method"]) ? $this->inputs["Method"] : "";
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
