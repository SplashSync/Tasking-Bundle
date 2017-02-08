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
class TaskAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with('General', array('class' => 'col-md-6'))
                ->add('name')
//                ->add('user', 'sonata_type_model_list')
                ->add('running')
                ->add('finished')
            ->end()
            ->with('Action', array('class' => 'col-md-6'))
                ->add('serviceName')
                ->add('jobName')
                ->add('jobToken')
            ->end()                
            ->with('Parameters', array('class' => 'col-md-6'))
                ->add('jobParameters')
            ->end()                
            ->with('Static Tasks', array('class' => 'col-md-6'))
                ->add('jobIsStatic')
                ->add('jobFrequency')
                ->add('plannedAt')
            ->end()                
            ->with('Timing', array('class' => 'col-md-6'))
                ->add('createdAt')
                ->add('startedAt')
                ->add('finishedAt')
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
//            ->add('user')
            ->add('serviceName')                
            ->add('running')
            ->add('finished')
            ->add('jobIsStatic')
            ->add('try')
            ->add('finishedAt')
            ->add('fault_str')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
//            ->add('user')
            ->add('running')
            ->add('finished')
            ->add('serviceName')
            ->add('jobToken')
            ->add('jobIsStatic')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', array('class' => 'col-md-6'))
                ->add('name')
//                ->add('user', 'sonata_type_model_list')
                ->add('running')
                ->add('finished')
            ->end()
            ->with('Action', array('class' => 'col-md-6'))
                ->add('serviceName')
                ->add('jobName')
                ->add('jobToken')
            ->end()                
            ->with('Parameters', array('class' => 'col-md-6'))
                ->add('jobParameters')
            ->end()                
            ->with('Static Tasks', array('class' => 'col-md-6'))
                ->add('jobIsStatic')
                ->add('jobFrequency')
                ->add('plannedAt')
            ->end()                
            ->with('Timing', array('class' => 'col-md-6'))
                ->add('createdAt')
//                ->add('startedAt')
//                ->add('finishedAt')
            ->end()
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $object = parent::getNewInstance();

//        $inspection = new Inspection();
//        $inspection->setDate(new \DateTime());
//        $inspection->setComment("Initial inspection");

//        $object->addInspection($inspection);

        return $object;
    }
}
