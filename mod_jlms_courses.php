<?php

/**
 * @package     Jlms Courses Module
 * @subpackage  mod_jlms_courses
 *
 * @copyright   (C) 2023 Gonzalo R. Meneses A. <alakentu2003@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Alakentu\Module\Jlms\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Alakentu\Module\Jlms\Site\Helper\JlmsCoursesHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$app  = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('com_jlms');

HTMLHelper::_('jquery.framework');
HTMLHelper::stylesheet('components/com_jlms/css/guru.css');
HTMLHelper::stylesheet('components/com_jlms/css/custom.css');
HTMLHelper::stylesheet('components/com_jlms/css/tabs_css.css');
HTMLHelper::stylesheet('components/com_jlms/css/tabs.css');
HTMLHelper::stylesheet('modules/mod_jlms_course/assets/uikit-custom.css');
HTMLHelper::script('components/com_jlms/js/guru_modal.js');

require ModuleHelper::getLayoutPath('mod_jlms_courses', $params->get('layout', 'default'));
