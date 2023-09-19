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

use Splash\Tasking\Services\JobsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Console Command to List All Available Jobs
 */
class ListCommand extends Command
{
    /**
     * Command Constructor
     */
    public function __construct(
        private JobsManager $jobsManager,
        private TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tasking:list')
            ->setDescription('Tasking Service : List All Available Jobs')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table
            ->setHeaders(array('Service ID', 'Priority', 'Mode', 'Name', 'Description'))
        ;
        //====================================================================//
        // Walk on Tagged Jobs
        foreach ($this->jobsManager->getAll() as $key => $job) {
            //====================================================================//
            // Get Job Settings
            $settings = $job->getSettings();
            //====================================================================//
            // Add Job to List
            $table->addRow(array(
                // Job Service Name
                "<info>".$key."</info>",
                // Job Priority
                $job->getPriority(),
                // Job Mode
                $this->getJobMode($key),
                // Job Name
                $this->translator->trans(
                    $settings["label"],
                    $settings["translation_params"] ?? array(),
                    $settings["translation_domain"],
                ),
                // Job Description
                $this->translator->trans(
                    $settings["description"],
                    $settings["translation_params"] ?? array(),
                    $settings["translation_domain"],
                )
            ));
        }
        $table->render();

        return 0;
    }

    /**
     * Get Job Details String
     */
    protected function getJobMode(string $key): string
    {
        if ($job = $this->jobsManager->isStaticJobs($key)) {
            return sprintf("<comment>Static, each %d Min</comment>", $job->getFrequency());
        }

        if ($job = $this->jobsManager->isBatchJobs($key)) {
            return sprintf("<comment>Batch, %d per loop</comment>", $job->getPaginate());
        }

        if ($job = $this->jobsManager->isMassJobs($key)) {
            return "<comment>Mass Job</comment>";
        }

        return "";
    }
}
