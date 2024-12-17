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

return array(
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => array('all' => true),
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => array('all' => true),
    Symfony\Bundle\TwigBundle\TwigBundle::class => array('all' => true),
    Symfony\Bundle\MonologBundle\MonologBundle::class => array('all' => true),
    Symfony\Bundle\DebugBundle\DebugBundle::class => array('dev' => true, 'test' => true),
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => array('dev' => true, 'test' => true),
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => array('all' => true),
    Knp\Bundle\MenuBundle\KnpMenuBundle::class => array('all' => true),
    Sonata\AdminBundle\SonataAdminBundle::class => array('all' => true),
    Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle::class => array('all' => true),
    Sonata\BlockBundle\SonataBlockBundle::class => array('all' => true),
    Sonata\Form\Bridge\Symfony\SonataFormBundle::class => array('all' => true),
    Sonata\Twig\Bridge\Symfony\SonataTwigBundle::class => array('all' => true),
    BadPixxel\Tasking\BadpixxelTaskingBundle::class => array('all' => true),
    BadPixxel\Tasking\Tests\Bundle\BadPixxelTaskingTestBundle::class => array('dev' => true, 'test' => true),
    Symfony\UX\TwigComponent\TwigComponentBundle::class => array('all' => true),
    Symfony\UX\StimulusBundle\StimulusBundle::class => array('all' => true),
    Symfony\UX\LiveComponent\LiveComponentBundle::class => array('all' => true),
    Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => array('all' => true),
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => array('all' => true),
);
