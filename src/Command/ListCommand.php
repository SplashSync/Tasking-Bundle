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

namespace BadPixxel\Tasking\Command;

use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Dictionary\RepeatableJobOptions;
use BadPixxel\Tasking\Dictionary\TaskPriority;
use BadPixxel\Tasking\Services\Jobs\JobsManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * Console Command to List All Available Jobs
 */
class ListCommand extends Command
{
    /**
     * Command Constructor
     */
    public function __construct(
        private readonly JobsManager         $jobsManager,
        private readonly TranslatorInterface $translator,
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
     * @SuppressWarnings(UnusedFormalParameter)
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table
            ->setHeaders(array('Service ID', 'Priority', 'Mode', 'Name', 'Description'))
        ;
        //====================================================================//
        // Walk on Configured Jobs
        foreach ($this->jobsManager->getAllConfiguredJobs() as $serviceId => $jobConfiguration) {
            //====================================================================//
            // Add Job to List
            $table->addRow($this->getJobRow($serviceId, $jobConfiguration));
        }
        $table->render();

        return 0;
    }

    /**
     * Get Job Details String
     */
    protected function getJobRow(string $serviceId, array $jobConfiguration, string $mode = "info"): array
    {
        return array(
            // Job Service Name
            sprintf("<%s>%s</%s>", $mode, $serviceId, $mode),
            // Job Priority
            $this->getPriorityString($jobConfiguration),
            // Job Mode
            $this->getJobMode($serviceId, $jobConfiguration),
            // Job Name
            $this->jobsManager->getLabel($jobConfiguration["settings"]),
            // Job Description
            $this->jobsManager->getDescriptions($jobConfiguration["settings"]),
        );
    }

    /**
     * Get Job Priority as String
     */
    protected function getPriorityString(array $jobConfiguration): string
    {
        $priority = $jobConfiguration[JobOptions::PRIORITY] ?? TaskPriority::NORMAL;

        $label = $this->translator->trans(
            sprintf("priority.%d.title", $priority),
            array(),
            "TaskingBundle"
        );

        $mode = $this->translator->trans(
            sprintf("priority.%d.color", $priority),
            array(),
            "TaskingBundle"
        );

        return sprintf("[%d] <%s>%s</%s>", $priority, $mode, $label, $mode);
    }

    /**
     * Get Job Details String
     */
    protected function getJobMode(string $key, array $jobConfiguration): string
    {
        if ($this->jobsManager->isStatic($key)) {
            $frequency = $jobConfiguration[JobOptions::FREQUENCY] ?? null;
            Assert::integer($frequency);
            Assert::greaterThanEq($frequency, 1);

            return sprintf("<comment>Static, each %d Min</comment>", $frequency);
        }

        if ($this->jobsManager->isBatch($key)) {
            $paginate = $jobConfiguration[RepeatableJobOptions::PAGINATION] ?? null;
            Assert::integer($paginate);
            Assert::greaterThanEq($paginate, 1);

            return sprintf("<comment>Batch, %d per loop</comment>", $paginate);
        }

        if ($this->jobsManager->isMass($key)) {
            $paginate = $jobConfiguration[RepeatableJobOptions::PAGINATION] ?? null;
            Assert::integer($paginate);
            Assert::greaterThanEq($paginate, 1);

            return sprintf("<comment>Mass, %d per loop</comment>", $paginate);
        }

        return "Generic";
    }
}
