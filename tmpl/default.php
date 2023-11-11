<?php 
    // no direct access
    defined( '_JEXEC' ) or die( 'Restricted access' );
    defined('DS') or define("DS", DIRECTORY_SEPARATOR);
    require_once(JPATH_SITE.DS."modules".DS."mod_guru_courses".DS."helper.php");    

    $app = JFactory::getApplication();
    $lang = $app->getLanguage();
    $lang->load('com_guru');
    $class = new ModGuruCourses();
    $courses = $class->getCourses($params);
    $item_id = $app->input->get("Itemid", "0", "raw");
    $item_id_home = $class->getHomeMenuItem();
    $document = JFactory::getDocument();
    $document->addStyleSheet(JURI::root().'modules/mod_guru_courses/mod_guru_courses.css' );

    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

    $db = JFactory::getDbo();
    $config = guruHelper::getConfig();
    $guru_turnoffuikit = $config->get('guru_turnoffuikit');

    JHtml::_('behavior.core');
	echo '
		<script type="text/javascript">
			guru_site_host = "'.JURI::root().'";
		</script>
		
        <script type="text/javascript" src="'.JURI::root().'components/com_guru/js/ukconflict.js"></script>
		<script type="text/javascript" src="'.JURI::root().'components/com_guru/js/tooltip.min.js"></script>
	';

    if($guru_turnoffuikit == 1){
        echo '
            <script type="text/javascript" src="'.JURI::root().'components/com_guru/js/uikit.min.js"></script>
        ';
    }

    $orientation = $params->get("orientation", "1");

    if(!isset($orientation)){
        $orientation = 1;
    }

    if(intval($orientation) == 1){
        // vertical
?>
        <div id="guru-module-courses" class="guru-mod--courses <?php echo $moduleclass_sfx; ?>">
            <ul class="uk-list uk-list-line">
            <?php
            if(isset($courses) && count($courses) > 0){
                foreach($courses as $key=>$course){
            ?>
                <li class="guru-mod--courses__item">
                <?php
                    // course thumbnail
                    if($class->showCourseImage($params)){
                        if(trim($course["image_avatar"])){
                            $image_url = $course["image_avatar"];
                            $image_url = str_replace("thumbs/", "", $image_url);
                            $img_size = array();
                            $host = $_SERVER['HTTP_HOST'];
                            $myImg = str_replace("http://", "", $image_url);
                            $myImg = str_replace($host, "", $myImg);
                            if($myImg != $image_url){
                                $myImg =str_replace("/", DS."", $myImg);            
                                $img_size = @getimagesize(JPATH_SITE.DS.$myImg);                    
                            }
                            else{
                                $img_size = @getimagesize(urldecode($image_url));
                            }
                            
                            $width_old = isset($img_size["0"]) ? $img_size[0] : 0;
                            $height_old = isset($img_size["1"]) ? $img_size[0] : 0;

                            $width_th = "0";
                            $height_th = "0";

                            if($params->get("thumbsizetype", "1") == 0 && isset($img_size)){
                                if($width_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                                    //proportional by width
                                    $raport = $width_old/$height_old;
                                    $width_th = $params->get("thumbsize", "0");
                                    $height_th = intval($params->get("thumbsize", "0") / $raport);
                                    $width_bullet_margin = $params->get("thumbsize", "0");                  
                                }
                                else{
                                    $width_th = $width_old;
                                    $height_th = $height_old;                   
                                }
                            } else {
                                    if($height_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                                        //proportional by height            
                                    $raport = $height_old/$width_old;
                                    $height_th = $params->get("thumbsize", "0");                        
                                    $width_th  = intval($params->get("thumbsize", "0") / $raport);
                                    $width_bullet_margin = intval($params->get("thumbsize", "0") / $raport);                    
                                } else{
                                    $width_th = $width_old;
                                    $height_th = $height_old;                   
                                }
                            }
                            $src =  $class->create_module_thumbnails($image_url, 400, 150, 400, 150);
                            echo '<a class="guru-mod--courses__item-thumb" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'"><img src="'.$src.'" alt="" title=""></a>';
                        }

                        else{
                            $src = JUri::root() . 'components/com_guru/images/no_image.gif';
                            echo '<a class="guru-mod--courses__item-thumb" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'"><img src="'.$src.'" alt="" title=""></a>';
                        }
                    }

                    // course title
                    echo '<a class="guru-mod--courses__item-title" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'">'.$course["name"].'</a>';

                    // course details
                    if(($params->get("teachername", "1") == 1) || ($params->get("showamountstud", "1") == 1) || ($params->get("showcateg", "1") == 1)){
                        echo '<div class="guru-mod--courses__item-details">';
                            
                            if($params->get("teachername", "1") == 1){
                                $authors_urls = $class->getAuthor($course, $params);
                                
                                if($params->get("showteacherthumb", "1") == 1){
                                    echo "<span>".implode(", ", $authors_urls)."</span>";
                                }
                                else{
                                    echo "<span><i class='uk-icon-user'></i> ".implode(", ", $authors_urls)."</span>";
                                }
                            }

                            if($params->get("showcateg", "1") == 1){
                                $categories_urls = $class->getCategories($course, $params);

                                echo "<span>".implode(", ", $categories_urls)."</span>";
                            }

                            if($params->get("showamountstud", "1") == 1){
                                $nr_students = $class->getStudentsNumber($course, $params);

                                if($nr_students != 1){
                                    echo "<span><i class='uk-icon-users'></i>"." ".$nr_students." ".JText::_('GURU_MODULE_AMOUNT_STUDENTS_FRONT')."</span>";
                                }
                                else{
                                    echo "<span><i class='uk-icon-users'></i>"." ".$nr_students." ".JText::_('GURU_MODULE_AMOUNT_STUDENT_FRONT')."</span>";
                                }
                            }
                        echo '</div>';
                    }

                    if ($params->get('showprice', 1)) {
                        echo $class->getPriceDisplay($course['id']);
                    }

                    if ($params->get('showduration', 1)) {
                        echo $class->getDurationDisplay($course['id']);
                    }

                    if ($params->get('showlevel', 1)) {
                        echo $class->getLevelDisplay($course['level']);
                    }

                    if ($params->get('showcountlesson', 1)) {
                        echo $class->getCountLessonDisplay($course['id']);
                    }

                    if ($params->get('showrating', 1)) {
                        $reviewSummary = guruHelper::getReviewSummary($course['id']);
                        if ($reviewSummary->get('count')) {
                            echo guruHelper::renderRatingInline(['course_id' => $course['id']]);
                        }
                    }

                    // course description
                    if(($params->get("showdescription", "1") == 1)){
                        echo '<div class="guru-mod--courses__item-desc">';
                            if($params->get("showdescription", "1") == 1){
                                $description = $class->getDescription($course, $params);
                                echo '<p>'.$description.'</p>';
                            }
                            else{
                                echo '';
                            }
                        echo'</div>';
                    }
                ?>
                </li>
            <?php
                }
            }
            ?>
            </ul>
        </div>
<?php
    }
    else{
        // horizontal
?>
        <div id="guru-module-courses" class="guru-mod--courses guru-module-courses-horizontal <?php echo $moduleclass_sfx; ?>">
            <div class="guru-courses-list">
                <?php
                if(isset($courses) && count($courses) > 0){
                    foreach($courses as $key=>$course){
                ?>
                    <div class="guru-course-item">
                    <div class="guru-course-item-inner">                        
                    <?php
                        // course thumbnail
                        if($class->showCourseImage($params)){
                            echo '<div class="guru-course-media">';
                            if(trim($course["image_avatar"])){
                                $image_url = $course["image_avatar"];
                                $image_url = str_replace("thumbs/", "", $image_url);
                                $img_size = array();
                                $host = $_SERVER['HTTP_HOST'];
                                $myImg = str_replace("http://", "", $image_url);
                                $myImg = str_replace($host, "", $myImg);
                                
                                if($myImg != $image_url){
                                    $myImg =str_replace("/", DS."", $myImg);            
                                    $img_size = @getimagesize(JPATH_SITE.DS.$myImg);                    
                                }
                                else{
                                    $img_size = @getimagesize(urldecode($image_url));
                                }
                                
                                $width_old = $img_size["0"];
                                $height_old = $img_size["1"];

                                $width_th = "0";
                                $height_th = "0";
                                
                                if($params->get("thumbsizetype", "1") == 0 && isset($img_size)){
                                    if($width_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                                        //proportional by width
                                        $raport = $width_old/$height_old;
                                        $width_th = $params->get("thumbsize", "0");
                                        $height_th = intval($params->get("thumbsize", "0") / $raport);
                                        $width_bullet_margin = $params->get("thumbsize", "0");                  
                                    }
                                    else{
                                        $width_th = $width_old;
                                        $height_th = $height_old;                   
                                    }
                                }
                                else{
                                    if($height_old > $params->get("thumbsize", "0") && $params->get("thumbsize", "0") > 0){
                                        //proportional by height            
                                        $raport = $height_old/$width_old;
                                        $height_th = $params->get("thumbsize", "0");                        
                                        $width_th  = intval($params->get("thumbsize", "0") / $raport);
                                        $width_bullet_margin = intval($params->get("thumbsize", "0") / $raport);                    
                                    }
                                    else{
                                        $width_th = $width_old;
                                        $height_th = $height_old;                   
                                    }
                                }
                                $src =  $class->create_module_thumbnails($image_url, 400, 150, 400, 150);
                                echo '<a class="guru-mod--courses__item-thumb" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'"><img src="'.$src.'" alt="" title=""></a>';
                            } else {
                                $src = JUri::root() . 'components/com_guru/images/no_image.gif';
                                echo '<a class="guru-mod--courses__item-thumb" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'"><img src="'.$src.'" alt="" title=""></a>';
                            }

                            // Price
                            if ($params->get('showprice', 1)) {
                                echo $class->getPriceDisplay($course['id']);
                            }

                            echo '</div>';
                        }

                        // Course title
                        echo '<div class="guru-course-heading">';
                        echo '<h3><a class="guru-mod--courses__item-title" href="'.JRoute::_(guruHelper::getRoute('index.php?option=com_guru&view=guruPrograms&task=view&cid='.$course["id"]."-".$course["alias"])).'">'.$course["name"].'</a></h3>';
                        echo '</div>';
                        

                        // course details
                        if(($params->get("teachername", "1") == 1) || ($params->get("showamountstud", "1") == 1) || ($params->get("showcateg", "1") == 1)){
                            echo '<div class="guru-mod--courses__item-details">';
                                if($params->get("teachername", "1") == 1) {
                                    $authors_urls = $class->getAuthor($course, $params);
                                    
                                    if($params->get("showteacherthumb", "1") == 1) {
                                        echo implode(", ", $authors_urls);
                                    }
                                    else {
                                        echo "<span><i class='uk-icon-user'></i> ".implode(", ", $authors_urls)."</span>";
                                    }
                                }

                                if($params->get("showcateg", "1") == 1){
                                    $categories_urls = $class->getCategories($course, $params);

                                    echo "<span>".implode(", ", $categories_urls)."</span>";
                                }

                                if($params->get("showamountstud", "1") == 1){
                                    $nr_students = $class->getStudentsNumber($course, $params);

                                    if($nr_students != 1){
                                        echo "<span><i class='uk-icon-users'></i>"." ".$nr_students." ".JText::_('GURU_MODULE_AMOUNT_STUDENTS_FRONT')."</span>";
                                    }
                                    else{
                                        echo "<span><i class='uk-icon-users'></i>"." ".$nr_students." ".JText::_('GURU_MODULE_AMOUNT_STUDENT_FRONT')."</span>";
                                    }
                                }                                                       
                            echo '</div>';
                        }

                        if ($params->get('showrating', 1)) {
                            $reviewSummary = guruHelper::getReviewSummary($course['id']);
                            if ($reviewSummary->get('count')) {
                                echo guruHelper::renderRatingInline(['course_id' => $course['id']]);
                            }
                        }

                        // course description
                        if(($params->get("showdescription", "1") == 1)){
                            echo '<div class="guru-mod--courses__item-desc">';
                                if($params->get("showdescription", "1") == 1){
                                    $description = $class->getDescription($course, $params);
                                    echo '<p>'.$description.'</p>';
                                }
                                else{
                                    echo '';
                                }
                            echo'</div>';
                        }
                    ?>
                    </div>
                    </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
<?php
    }
?>