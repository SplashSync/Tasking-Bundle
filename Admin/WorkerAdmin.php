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
            ->add('nodeName')
            ->add('process')
            ->add('pID')
            ->add('running')
            ->add('task')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('pID')
            ->add('nodeName')
            ->add('process')                
            ->add('running')
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

    /**
     * {@inheritdoc}
     */
//    protected function configureFormFields(FormMapper $formMapper)
//    {
//        $formMapper
//            ->with('General', array('class' => 'col-md-6'))
//                ->add('name')
//                ->add('user', 'sonata_type_model_list')
//                ->add('running')
//                ->add('finished')
////                ->add('IsActive')
////                ->add('deleted')
//            ->end()
//            ->with('Timing', array('class' => 'col-md-6'))
//                ->add('createdAt')
//                ->add('startedAt')
//                ->add('finishedAt')
//            ->end()
//            ->with('Action', array('class' => 'col-md-6'))
//                ->add('serviceName')
//                ->add('jobName')
////                ->add('jobParemeters')
//            ->end()                
////            ->with('Encoding', array('class' => 'col-md-6'))
////                ->add('crypt_mode')
////                ->add('crypt_key')
////            ->end()                
//////            ->with('inspections', array('class' => 'col-md-12'))
//////                ->add('inspections', 'sonata_type_collection', array(
//////                    'by_reference'       => false,
//////                    'cascade_validation' => true,
//////                ), array(
//////                    'edit' => 'inline',
//////                    'inline' => 'table'
//////                ))
//            ->end()
//        ;
//    }

}
