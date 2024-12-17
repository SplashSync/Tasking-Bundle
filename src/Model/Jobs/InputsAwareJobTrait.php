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

namespace BadPixxel\Tasking\Model\Jobs;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage Tasks Settings
 */
trait InputsAwareJobTrait
{
    /**
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected array $inputs = array();

    /**
     * @inheritdoc
     */
    public function resolveInputs(array $inputs): array
    {
        $resolver = new OptionsResolver();
        //==============================================================================
        // Configure Inputs Resolver from Job Service
        $this->configureInputsResolver($resolver);

        //==============================================================================
        //  Resolve Merged Inputs
        return $resolver->resolve($inputs);
    }

    //==============================================================================
    //      Getters & Setters
    //==============================================================================

    /**
     * @inheritdoc
     */
    public function setInputs(array $inputs): static
    {
        $this->inputs = $inputs;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @inheritdoc
     */
    final public function getRawInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Configure Inputs Resolver
     */
    abstract protected function configureInputsResolver(OptionsResolver $resolver): void;
}
