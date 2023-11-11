<?php

/**
 * @package     Jlms Courses Module
 * @subpackage  mod_jlms_courses
 *
 * @copyright   (C) 2023 Gonzalo R. Meneses A. <alakentu2003@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Alakentu\Module\Jlms\Site\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
jimport('joomla.html.html');
jimport('joomla.form.formfield');

class CategoriesField extends ListField
{
	/**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'Categories';
	
	public $return_array = array();

	public function getAllRows($parent, $level)
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->select(
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.description'),
                    $db->quoteName('a.name'),
                    $db->quoteName('r.child_id', 'cid'),
                    $db->quoteName('r.parent_id', 'pid'),
                    $db->quoteName('a.ordering'),
                    $db->quoteName('a.published'),
                ]
			)
			->from($db->quoteName('#__jlms_category', 'a'))
			->join('LEFT', $db->quoteName('#__jlms_categoryrel', 'r') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('r.child_id'))
			->where($db->quoteName('r.parent_id') . ' = :parent_id')
			->bind(':parent_id', $parent, ParameterType::INTEGER)
			->order($db->quoteName('a.ordering') . ' ASC');
		$db->setQuery($query);
		
		try {
            $result = $db->loadAssocList();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
		
		//$sql = "SELECT id, description, name, child_id as cid, parent_id as pid, ordering, published FROM #__jlms_category, #__jlms_categoryrel WHERE #__jlms_category.id = #__jlms_categoryrel.child_id and #__jlms_categoryrel.parent_id=" . intval($parent) . " ORDER BY `ordering` ASC";
		//$db->setQuery($sql);
		//$db->execute();
		//$result = $db->loadAssocList();

		if (isset($result) && is_array($result) && count($result) > 0)
		{
			$level++;
			foreach ($result as $key => $value) {
				$value["level"] = $level;
				$this->return_array[] = $value;
				$this->getAllRows($value["id"], $level);
			}
		}

		return $this->return_array;
	}

	protected function getOptions()
	{
		$options = array(
			array(
				'text' => Text::_('JOPTION_ALL_CATEGORIES'),
				'value' => 0,
			)
		);
		
		$params 	= $this->form->getValue('params');
		$category 	= "0";

		if (isset($params->category))
		{
			$category = $params->category;
		}

		$categories = $this->getAllRows(0, 0);

		foreach ($categories as $cat)
		{
			$options[] = array(
				'text' => str_repeat(' - ', $cat['level']) . $cat['name'],
				'value' => $cat['id'],
			);
		}

		return $options;
	}
}
