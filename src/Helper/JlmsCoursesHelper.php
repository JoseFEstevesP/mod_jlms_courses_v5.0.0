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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once JPATH_SITE. '/components/com_jlms/helpers/helper.php';

/**
 * Helper for mod_jlms_courses
 *
 * @since  5.0.0
 */
class JlmsCoursesHelper
{
    public function getDurationDisplay($pid)
    {
        return '<div class="jlms-course-duration">' . guruHelper::getCourseDurationText($pid) . '</div>';
    }

    public function getLevelDisplay($level)
    {
        return '<div class="jlms-course-level">' . guruHelper::getCourseLevelText($level) . '</div>';
    }

    public function getCountLessonDisplay($pid)
    {
        $count = guruHelper::countLesson($pid);
        return '<div class="jlms-lesson-count">' . $count . '</div>';
    }

    public $child_categories = array();

    public function getPriceDisplay($pid)
    {
        $config = guruHelper::getConfig();
        $guruHelper = new guruHelper();
        $price = "";

        $db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('pp.price')
			->from($db->quoteName('#__jlms_program', 'a'))
			->join('LEFT', $db->quoteName('#__jlms_program_plans', 'pp') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pp.product_id'))
			->join('LEFT', $db->quoteName('#__jlms_subplan', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('pp.plan_id'))
			->where($db->quoteName('p.id') . ' = :pid')
			->bind(':pid', $pid, ParameterType::INTEGER)
			->order($db->quoteName('s.ordering') . ' ASC');
		$db->setQuery($query);

        //$sql = "select pp.price from #__jlms_program p 
		//left join #__jlms_program_plans pp on p.id=pp.product_id 
		//left join #__jlms_subplan s on s.id=pp.plan_id 
		//where p.id=" . intval($pid) . " order by s.ordering asc";
        //$db->setQuery($sql);
        $price_array = $db->loadColumn();
        $price_array = array_filter($price_array);

        sort($price_array);

        if(count($price_array) > 1){
            if($config->get('currencypos') == 0){
                $price = '<span class="jlms-min-price">' . Text::_("GURU_CURRENCY_".$config->get('currency'))." ".$guruHelper->displayPrice($price_array["0"]).'</span><span class="jlms-price-separator"> - </span><span class="jlms-max-price">'.Text::_("GURU_CURRENCY_".$config->get('currency'))." ".$guruHelper->displayPrice($price_array[count($price_array)-1]) . '</span>';
            }
            else{
                $price = '<span class="jlms-min-price">' . $guruHelper->displayPrice($price_array["0"])." ".Text::_("GURU_CURRENCY_".$config->get('currency')).'</span><span class="jlms-price-separator"> - </span><span class="jlms-max-price">'.$guruHelper->displayPrice($price_array[count($price_array)-1])." ".Text::_("GURU_CURRENCY_".$config->get('currency')) . '</span>';
            }
        }
        else if (count($price_array) == 1){
            if($config->get('currencypos') == 0){
                $price = '<span class="jlms-min-price">' . Text::_("GURU_CURRENCY_".$config->get('currency'))." ".$guruHelper->displayPrice($price_array["0"]) . '</span>';
            }
            else{
                $price = '<span class="jlms-min-price">' . $guruHelper->displayPrice($price_array["0"])." ".Text::_("GURU_CURRENCY_".$config->get('currency')) . '</span>';
            }
        }
        
        $course_config 	= json_decode($config->get('psgpage'), true);
        $show_price 	= $course_config["course_price"];

        if (!$price_array) {
            $price = '<span class="jlms-free-price">' . Text::_("GURU_FREE") . '</span>';
        }

        $priceDisplay = '';
        if($show_price == 0){
            $priceDisplay = '<div class="jlms-course-price">'.$price.'</div>';
        }

        return $priceDisplay;
    }

    public function getAllChilds($parent)
	{
        $db = $this->getDatabase();
        $this->child_categories[] = intval($parent);

        $sql = "SELECT `child_id` FROM #__jlms_categoryrel WHERE parent_id=".intval($parent);
        $db->setQuery($sql);
        $result = $db->loadColumn();
        
        if(isset($result) && is_array($result) && isset($result["0"])){
            $this->child_categories[] = intval($result["0"]);

            if(intval($result["0"]) > 0){
                $this->getAllChilds($result["0"]);
            }
        }

        return $this->child_categories;
    }

    public function getCourses($params)
	{
        $db = $this->getDatabase();
        $sortby = $params->get("sortby", "0");
        $category = $params->get("category", "0");
        
        $and = "";

        if(intval($category) > 0){
            $categories_list = $this->getAllChilds(intval($category));

            if(is_array($categories_list) && count($categories_list) > 0){
                $and .= " AND `catid` in (".implode(",", $categories_list).")";
            }
        }

        switch($sortby){
            case "0" : { // most popular
                $sql = "select * from `#__jlms_program` where `published`='1' and `status`='1' and `startpublish` <= now() ".$and;
                $db->setQuery($sql);
                $courses = $db->loadAssocList();

                if(isset($courses) && count($courses) > 0){
                    $courses_temp = array();
                    
                    foreach($courses as $key=>$course){
                        $nr = $this->getStudentsNumber($course, null);
                        
                        if(count($courses_temp) == 0){
                            $courses_temp[] = array($course['id']=>$nr);
                        }
                        else{                           
                            foreach($courses_temp as $key=>$c_id_nr){
                                if(current($c_id_nr) <= $nr){
                                    array_splice($courses_temp, $key, 0, array(array($course['id']=>$nr)));
                                    break;
                                }
                                elseif(!isset($courses_temp[$key + 1])){
                                    array_splice($courses_temp, $key+1, 0, array(array($course['id']=>$nr)));
                                    break;
                                }
                            }
                        }
                    }

                    $courses = array();

                    foreach($courses_temp as $key=>$c_id_nr){
                        $sql = "select * from `#__jlms_program` where `id`=".intval(key($c_id_nr));
                        $db->setQuery($sql);
                        $course_temp = $db->loadAssocList();
                        $courses = array_merge($courses, $course_temp);
                    }
                }

                if(isset($courses) && count($courses) > 0){
                    $user = JFactory::getUser();
                    $user_groups = $user->groups;
                    $temp_courses = array();

                    foreach($courses as $key=>$course){
                        $sql = "select `groups` from #__jlms_category where `id`=".intval($course["catid"]);
                        $db->setQuery($sql);
                        $categ_groups = $db->loadColumn();

                        if(isset($categ_groups["0"]) && trim($categ_groups["0"]) != ""){
                            $acl_groups = json_decode(trim($categ_groups["0"]), true);
                            $intersect = array_intersect($user_groups, $acl_groups);

                            if(in_array(1, $acl_groups) || in_array(9, $acl_groups) || count($intersect) > 0){
                                $temp_courses[] = $course;
                            }
                            elseif(!isset($intersect) || count($intersect) == 0){
                                // do nothing
                            }
                        }
                        else{
                            $temp_courses[] = $course;
                        }
                    }

                    $courses = $temp_courses;
                }

                $courses = array_slice($courses, 0, $params->get("howManyC"));

                return $courses;
                break;
            }
            case "1" : { // most recent
                $and .= " ORDER BY `startpublish` DESC";
                break;
            }
            case "2" : { // random
                $and .= " ORDER BY RAND()";
                break;
            }
        }

        $sql = "select * from `#__jlms_program` where 1=1 and published=1 and status=1 and `startpublish` <= now() ".$and;
        $db->setQuery($sql);
        $courses = $db->loadAssocList();

        if(isset($courses) && count($courses) > 0){
            $user = JFactory::getUser();
            $user_groups = $user->groups;
            $temp_courses = array();

            foreach($courses as $key=>$course){
                $sql = "select `groups` from #__jlms_category where `id`=".intval($course["catid"]);
                $db->setQuery($sql);
                $categ_groups = $db->loadColumn();

                if(isset($categ_groups["0"]) && trim($categ_groups["0"]) != ""){
                    $acl_groups = json_decode(trim($categ_groups["0"]), true);
                    $intersect = array_intersect($user_groups, $acl_groups);

                    if(in_array(1, $acl_groups) || in_array(9, $acl_groups) || count($intersect) > 0){
                        $temp_courses[] = $course;
                    }
                    elseif(!isset($intersect) || count($intersect) == 0){
                        // do nothing
                    }
                }
                else{
                    $temp_courses[] = $course;
                }
            }

            $courses = $temp_courses;
        }

        $courses = array_slice($courses, 0, $params->get("howManyC"));

        return $courses;
    }
    
    public function showCourseImage($params)
	{
        if($params->get("showthumb", "1") == 1)
		{
            return true;
        }
        return false;
    }
    
    public function createThumb($course, $params)
	{
        return $course["image"];
    }
    
    public function getStudentsNumber($course, $params)
	{
        $db = $this->getDatabase();
        $sql = "SELECT count(distinct bc.`userid`) FROM #__jlms_buy_courses bc, #__users u , #__jlms_customer c, #__jlms_order o WHERE c.`id`=bc.`userid` and bc.`userid`=u.`id` and bc.`course_id`=".intval($course["id"])." and o.`userid`=c.`id` and o.`userid`=bc.`userid` and o.`status`='Paid'";
        $db->setQuery($sql);
        $result = $db->loadColumn();
        
        return @$result["0"];
    }
    
    public function getDescription($course, $params)
	{
        $return = "";
        $audio_p_desc_length = $params->get("desclength");
        $audio_p_desc_length_type = $params->get("desclengthtype");
        $description = strip_tags($course["description"]);
        
        if($audio_p_desc_length != '' && trim($description) != ""){
             $new_description = "";
             if($audio_p_desc_length_type == 0){
                //words
                $desc_array = explode(" ", $description);
                $desc = array();
                if(count($desc_array) > $audio_p_desc_length){
                    foreach($desc_array as $key => $val){                                   
                        if($key < $audio_p_desc_length){
                            $desc[] = $val;
                        }                                   
                     }
                    $new_description = implode(" ", $desc)."...";                               
                }
                else{
                    $new_description = $description;
                }                            
             }
             elseif($audio_p_desc_length_type == 1){
                //characters                            
                $descr_nr = strlen($description);
                if($descr_nr > $audio_p_desc_length){
                    $new_description = mb_substr(trim($description), 0, $audio_p_desc_length)."...";
                }
                else{
                    $new_description = $description;
                }
             }
             $return = $new_description;
        }
        return $return;
    }
    
    public function getAuthor($course, $params)
	{
        $db = $this->getDatabase();
        $user = Factory::getUser();
        $user_id = $user->id;
        $return = array();
        $item_id = Factory::getApplication()->input->get("Itemid", "0", "raw");
            
        $helper = new guruHelper();
        $itemid_seo = $helper->getSeoItemid();
        $itemid_seo = @$itemid_seo["guruprofile"];
        
        if(intval($itemid_seo) > 0){
            $item_id = intval($itemid_seo);
            
            $sql = "select `access` from #__menu where `id`=".intval($item_id);
            $db->setQuery($sql);
            $access = $db->loadResult();
            
            if(intval($access) == 3){
                // special
                $user_groups = $user->get("groups");
                if(!in_array(8, $user_groups)){
                    $item_id = Factory::getApplication()->input->get("Itemid", "0", "raw");
                }
            }
        }
        
        if(intval($item_id) > 0 && intval($user_id) == 0){
            $sql = "select `access` from #__menu where `id`=".intval($item_id);
            $db->setQuery($sql);
            $access = $db->loadResult();
            
            if(intval($access) != "1"){
                $item_id = 0;
            }
        }
        
        $authors = explode("|", $course["author"]);
        $authors = array_filter($authors);
        
        $sql = "SELECT `id`, `name` from `#__users` where `id` in (".implode(",", $authors).")";
        $db->setQuery($sql);
        $authors_details = $db->loadAssocList();
        
        if(isset($authors_details) && count($authors_details) > 0){
            foreach($authors_details as $key=>$value){
                $sql = "SELECT `id`, `images` from `#__jlms_authors` where `userid`=".intval($value["id"]);
                $db->setQuery($sql);
                $teacher_details = $db->loadAssoc();
                if (!$teacher_details) {
                    continue;
                }

                $teacher_id = $teacher_details["id"];
                $teacher_thumb = $teacher_details["images"];

                $url = '<a href="'.Route::_(guruHelper::getRoute('index.php?option=com_jlms&view=guruauthor&layout=view&cid='.intval($teacher_id)."-".JFilterOutput::stringURLSafe($value["name"]))).'">'.$value["name"]."</a>";

                if($params->get("showteacherthumb", "1") == 1){
                    if(trim($teacher_thumb) != ""){
                        $url = '<span class="teacher-img"><img class="mod-courses-teacher-thumb" src="'.Uri::root(true).$teacher_thumb.'" alt="'.$value["name"].'" /></span>'.$url;
                    }
                }

                $return[] = $url;
            }
        }
        
        return $return;
    }

    public function getCategories($course, $params)
	{
        $results = array();
        $category = guruHelper::getCategory($course['catid']);
        if (isset($category->id)) {
            $results[] = '<a href="'.Route::_(guruHelper::getRoute('index.php?option=com_jlms&view=gurupcategs&task=view&cid=' . $category->id . '-' . $category->alias)).'">' . htmlspecialchars($category->name) . '</a>';
        }
        return $results;
    }
    
    public function getAuthorID($course, $params)
	{
        $db = $this->getDatabase();
        $authorname = "SELECT id from #__jlms_authors where userid=".intval($course["author"]);
        $db->setQuery($authorname);
        $authorname = $db->loadResult();
        return $authorname;
    }
    
    public function create_module_thumbnails($images, $width, $height, $width_old, $height_old)
	{
            $image_path = Uri::root().$images;
            if(strpos($images, "http") !== FALSE){
                $image_path = $images;
            }
            $thumb_src = "modules/mod_jlms_courses/createthumbs.php?src=".$image_path."&amp;w=".$width."&amp;h=".$height;//."&zc=1";
            return $thumb_src;
        }
        
    public function getAudioDescription($audio, $params)
	{
        $return = "";
        $audio_p_desc_length = $params->get("desclength");
        $audio_p_desc_length_type = $params->get("desclengthtype");
        $description = $audio["description"];
        
        if($audio_p_desc_length != '' && trim($description) != ""){
             $new_description = "";
             if($audio_p_desc_length_type == 0){
                //words
                $desc_array = explode(" ", $description);
                $desc = array();
                if(count($desc_array) > $audio_p_desc_length){
                    foreach($desc_array as $key => $val){                                   
                        if($key < $audio_p_desc_length){
                            $desc[] = $val;
                        }                                   
                     }
                    $new_description = implode(" ", $desc)."...";                               
                }
                else{
                    $new_description = $description;
                }                            
             }
             elseif($audio_p_desc_length_type == 1){
                //characters                            
                $descr_nr = strlen($description);
                if($descr_nr > $audio_p_desc_length){
                    $new_description = mb_substr($description, 0, $audio_p_desc_length)."...";
                }
                else{
                    $new_description = $description;
                }
             }
             $return = $new_description;
        }
        return $return;
    }
	
    public function getCourseLevel($course, $params)
	{
        switch($course["level"]){
            case "0" : { 
                $return = Text::_("MOD_JLMS_COURSES_BEGINNERS");
                break;
            }
            case "1" : { 
                $return = Text::_("MOD_JLMS_COURSES_INTERMEDIATE");
                break;
            }
            case "2" : { 
                $return = Text::_("MOD_JLMS_COURSES_ADVANCED");
                break;
            }
        }
        return $return;
    
    }

    public function getHomeMenuItem()
	{
        $db = $this->getDatabase();

        $sql = "select `id` from #__menu where `home`='1' and `language`='*' limit 0, 1";
        $db->setQuery($sql);
        $menu_item = $db->loadResult();

        return intval($menu_item);
    }

    public function getCourseMenuItem($id)
	{
        $db = $this->getDatabase();

        $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=guruprograms&layout=view\' and `published`=\'1\' and `params` like \'%"cid":"'.intval($id).'"%\' order by `id` desc limit 0, 1';
        $db->setQuery($sql);
        $course_menu_id = $db->loadResult();

        if(intval($course_menu_id) == 0){
            $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=gurupcategs&layout=view\' and `published`=\'1\' order by `id` desc limit 0, 1';
            $db->setQuery($sql);
            $course_menu_id = $db->loadResult();
        }

        return intval($course_menu_id);
    }

    public function getTeacherMenuItem($id)
	{
        $db = $this->getDatabase();

        $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=guruauthor&layout=view\' and `published`=\'1\' and `params` like \'%"cid":"'.intval($id).'"%\' order by `id` desc limit 0, 1';

        $db->setQuery($sql);
        $teacher_menu_id = $db->loadResult();

        if(intval($teacher_menu_id) == 0){
            $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=guruauthor\' and `published`=\'1\' order by `id` desc limit 0, 1';

            $db->setQuery($sql);
            $teacher_menu_id = $db->loadResult();
        }

        return intval($teacher_menu_id);
    }

    public function getCategMenuItem($id)
	{
        $db = $this->getDatabase();
        $return = array();

        $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=gurupcategs&layout=view\' and `params` like \'%"cid":"'.intval($id).'"%\' and `published`=\'1\' order by `id` desc limit 0, 1';

        $db->setQuery($sql);
        $categ_menu_id = $db->loadResult();

        if(intval($categ_menu_id) == 0){
            $return["is_menu"] = false;

            $sql = 'select `id` from #__menu where `link`=\'index.php?option=com_jlms&view=gurupcategs\' and `published`=\'1\' order by `id` desc limit 0, 1';

            $db->setQuery($sql);
            $categ_menu_id = $db->loadResult();
        }
        else{
            $return["is_menu"] = true;
        }

        $return["id"] = intval($categ_menu_id);

        return $return;
    }
};
