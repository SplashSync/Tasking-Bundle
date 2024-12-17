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

namespace BadPixxel\Tasking\Model;

use BadPixxel\Tasking\Dictionary\JobOptions;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Service Action for Background Jobs
 */
abstract class AbstractServiceJob extends AbstractJob
{
    /**
     * Service Job Constructor
     *
     * @param null|object $service Target Service
     */
    public function __construct(private readonly ?object $service = null)
    {
    }

    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * Build Job Options
     */
    public static function toOptions(
        string $method,
        array $args = array(),
        ?string $token = null,
    ): array {
        return array_filter(array(
            JobOptions::TOKEN => $token,
            JobOptions::INPUTS => array(
                "method" => $method,
                "args" => $args,
            ),
        ));
    }

    //==============================================================================
    //      Service Job Configurator
    //==============================================================================

    /**
     * @inheritdoc
     */
    public static function getDefaultSettings(): array
    {
        return array(
            "label" => "Service Job",
            "description" => "Execute Service Action",
        );
    }

    //==============================================================================
    //      Service Job Execution
    //==============================================================================

    /**
     * @inheritdoc
     */
    public function validate() : bool
    {
        //====================================================================//
        // Check target method is Defined
        Assert::stringNotEmpty($this->getMethod());
        //====================================================================//
        // Check Service is Configured
        Assert::notEmpty(
            $this->service,
            "Target Service not initialized. Did you forgot to register a configurator?"
        );
        //====================================================================//
        // Check Service Method Exists
        Assert::methodExists($this->service, $this->getMethod());

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
        $args = $this->getArgs();

        //====================================================================//
        // Execute Service Method
        return $this->service->{ $method }($args);
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Service Job Method Name
     */
    public function getMethod(): string
    {
        return $this->getInputs()["method"] ?? "";
    }

    /**
     * Get Service Job Action Args
     */
    public function getArgs(): array
    {
        $args = $this->getInputs()["args"] ?? null;

        return is_array($args) ? $args : array();
    }

    /**
     * @inheritDoc
     */
    protected function configureInputsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            "method" => null,
            "args" => array(),
        ));
        $resolver->setAllowedTypes("method", "string");
        $resolver->setAllowedTypes("args", "array");
    }
}
