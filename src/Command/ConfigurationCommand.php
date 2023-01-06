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

namespace Splash\Tasking\Command;

use Splash\Tasking\Services\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Console Command to Display & Explain Tasking Bundle Configuration
 */
class ConfigurationCommand extends Command
{
    /**
     * Workers Manager Service
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $tableValues = array();

    /**
     * Class Constructor
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct(null);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tasking:config')
            ->setDescription('Tasking Service : Display & Explain Tasking Bundle Configuration')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Add First Table Line
        $this->tableValues[] = array(
            "<comment>".$this->translator->trans("config.title", array(), "SplashTaskingBundle")."</comment>",
        );
        //====================================================================//
        // Add Core Table Lines
        foreach (Configuration::getRawConfiguration() as $key => $value) {
            if (is_scalar($value)) {
                $this->addToTable($output, $key, $value);
            }
        }
        //====================================================================//
        // Add Sub-Configuration Table Lines
        foreach (Configuration::getRawConfiguration() as $key => $value) {
            if (is_array($value)) {
                $this->addToTable($output, $key, $value);
            }
        }
        //====================================================================//
        // Render Console Table
        $table = new Table($output);
        $table->setRows($this->tableValues);
        $table->render();

        $output->writeln("<comment>[C] : </comment> Computed parameters");

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string          $key
     * @param array|scalar    $value
     * @param null|string     $parentKey
     */
    private function addToTable(OutputInterface $output, string $key, $value, string $parentKey = null): void
    {
        $transkey = $parentKey ? "config.".$parentKey.".".$key : "config.".$key;
        //====================================================================//
        // Add Sub-Configuration
        if (is_array($value)) {
            $this->tableValues[] = new TableSeparator();
            $this->tableValues[] = array(
                "<comment>".$this->translator->trans($transkey.".title", array(), "SplashTaskingBundle")."</comment>",
            );

            foreach ($value as $childKey => $childValue) {
                $this->addToTable($output, $childKey, $childValue, $key);
            }

            return;
        }
        //====================================================================//
        // Prepare Value for Display
        $valueStr = (string) $value;
        if (is_bool($value)) {
            $valueStr = $value ? "True" : "False";
        }
        //====================================================================//
        // Display Value
        $this->tableValues[] = array(
            $this->translator->trans($transkey.".title", array(), "SplashTaskingBundle"),
            "<info>".$valueStr."</info>",
            $this->translator->trans($transkey.".desc", array(), "SplashTaskingBundle"),
        );
    }
}
