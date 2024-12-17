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

namespace BadPixxel\Tasking\Resolver;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Option Resolver for Task Settings
 *
 * @phpstan-type Options array{
 *     label: string,
 *     description: string,
 *     translation_domain: null|string,
 *     translation_params: array
 * }
 *
 * @method resolve(array $options = []): Options
 */
class TaskSettingsResolver extends OptionsResolver
{
    public function __construct()
    {
        $this->setDefaults(array(
            "label" => "Job Title",
            "description" => "Job Description",
            "translation_domain" => null,
            "translation_params" => array(),
        ));
        $this->setAllowedTypes("label", "string");
        $this->setAllowedTypes("description", "string");
        $this->setAllowedTypes("translation_domain", array("null", "string"));
        $this->setAllowedTypes("translation_params", "array");
    }
}
