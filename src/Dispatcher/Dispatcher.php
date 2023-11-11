<?php

/**
 * @package     Jlms Courses Module
 * @subpackage  mod_jlms_courses
 *
 * @copyright   (C) 2023 Gonzalo R. Meneses A. <alakentu2003@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Alakentu\Module\Jlms\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_jlms_courses
 *
 * @since  5.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   5.0.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $data['list'] = $this->getHelperFactory()->getHelper('JlmsCoursesHelper')->getCourses($data['params'], $data['app']);

        return $data;
    }
}
