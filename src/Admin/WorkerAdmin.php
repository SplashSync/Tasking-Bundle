<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
use Sonata\AdminBundle\Show\ShowMapper;

//use Sonata\Bundle\DemoBundle\Entity\Inspection;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class WorkerAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('Worker', array('class' => 'col-lg-3 col-md-5 col-sm-12'))
            ->add('nodeName')
            ->add('nodeIp')
            ->add('process')
            ->add('pID')
            ->end()
            ->with('Status', array('class' => 'col-lg-9 col-md-7 col-sm-12'))
            ->add('running')
            ->add('enabled')
            ->add('lastSeen')
            ->add('task')
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('__toString', null, array('route' => array('name' => 'show')))
//            ->add('nodeIp')
            ->add('enabled', null, array('editable' => true))
            ->add('pID')
            ->add('running')
            ->add('lastSeen')
            ->add('task')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('nodeName')
            ->add('running')
        ;
    }
}
