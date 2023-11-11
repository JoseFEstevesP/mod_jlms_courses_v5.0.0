<?php

/**
 * @package     Jlms Courses Module
 * @subpackage  mod_jlms_courses
 *
 * @copyright   (C) 2023 Gonzalo R. Meneses A. <alakentu2003@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The jlms courses module service provider.
 *
 * @since  5.0.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Alakentu\\Module\\JlmsCourses'));
        $container->registerServiceProvider(new HelperFactory('\\Alakentu\\Module\\JlmsCourses\\Site\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
