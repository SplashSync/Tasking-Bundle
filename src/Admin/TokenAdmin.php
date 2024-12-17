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

namespace BadPixxel\Tasking\Admin;

use BadPixxel\Tasking\Entity\Token;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @extends  AbstractAdmin<Token>
 */
#[AutoconfigureTag("sonata.admin", array(
    "name" => "sonata.admin",
    "manager_type" => "orm",
    "model_class" => Token::class,
    "label" => "Token",
    "group" => "Tasking",
    "icon" => "<i class='fa fa-server'></i>",
))]
class TokenAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('name')
            ->add('locked')
            ->add('lockedAt')
            ->add('lockedBy')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('locked', null, array('editable' => true))
            ->add('lockedAt')
            ->add('lockedBy')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('locked')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name')
            ->add('locked')
            ->add('lockedAt')
            ->add('createdAt')
        ;
    }
}
