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

namespace Splash\Tasking\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

//use Sonata\Bundle\DemoBundle\Entity\Inspection;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TaskAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('Status', array('class' => 'col-md-12'))
            ->add('running')
            ->add('finished')
            ->add('try')
            ->add('faultStr')
            ->add('faultTrace')
            ->end()
            ->with('General', array('class' => 'col-md-6'))
            ->add('name')
            ->add('jobClass')
            ->add('jobName')
            ->add('jobToken')
            ->add('jobIsStatic')
            ->add('jobFrequency')
            ->add('plannedAt')
            ->end()
            ->with('Indexes', array('class' => 'col-md-6'))
            ->add('discriminator')
            ->add('jobIndexKey1')
            ->add('jobIndexKey2')
            ->end()
            ->with('Timing', array('class' => 'col-md-6'))
            ->add('createdAt')
            ->add('createdBy')
            ->add('startedAt')
            ->add('startedBy')
            ->add('finishedAt')
            ->add('duration')
            ->end()
            ->with('Inputs', array('class' => 'col-md-6'))
            ->add('jobInputsStr', null, array('safe' => true))
            ->end()
            ->with('Outputs', array('class' => 'col-md-6'))
            ->add('outputs', null, array('safe' => true))
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, array('route' => array('name' => 'show')))
            ->add('jobToken')
            ->add('duration')
            ->add('running', null, array('editable' => true))
            ->add('finished', null, array('editable' => true))
            ->add('jobIsStatic')
            ->add('try')
            ->add('faultStr')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
//            ->add('user')
            ->add('running')
            ->add('finished')
            ->add('jobClass')
            ->add('jobToken')
            ->add('jobIsStatic')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General', array('class' => 'col-md-6'))
            ->add('name')
//                ->add('user', 'sonata_type_model_list')
            ->add('running')
            ->add('finished')
            ->add('try')
            ->end()
            ->with('Action', array('class' => 'col-md-6'))
            ->add('jobClass')
            ->add('jobAction')
            ->add('jobToken')
            ->end()
            ->with('Parameters', array('class' => 'col-md-6'))
            ->add('jobInputs')
            ->end()
            ->with('Static Tasks', array('class' => 'col-md-6'))
            ->add('jobIsStatic')
            ->add('jobFrequency')
//                ->add('plannedAt', "datetime", [ "required" => False ])
            ->end()
            ->with('Indexes', array('class' => 'col-md-6'))
            ->add('jobIndexKey1')
            ->add('jobIndexKey2')
            ->end()

        ;
    }
}
