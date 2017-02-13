<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Tasking\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
//use Sonata\Bundle\DemoBundle\Entity\Inspection;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class WorkerAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
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
                ->add('lastSeen')
                ->add('task')
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('_toString', null, array('route' => array('name' => 'show')))
            ->add('nodeIp')
            ->add('enabled', null , ['editable' => True])
            ->add('pID')                
            ->add('running')
            ->add('lastSeen')
            ->add('task')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('nodeName')
            ->add('running')
        ;
    }

}
