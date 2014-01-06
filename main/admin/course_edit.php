<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;

require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$course_table       = Database::get_main_table(TABLE_MAIN_COURSE);
$course_code = isset($_GET['course_code']) ? $_GET['course_code'] : $_POST['code'];

$noPHP_SELF = true;
$tool_name = get_lang('ModifyCourseInfo');
$interbreadcrumb[] = array ("url" => 'index.php',       "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => "course_list.php", "name" => get_lang('CourseList'));

// Get all course categories
$table_user = Database :: get_main_table(TABLE_MAIN_USER);

//Get the course infos
$course = api_get_course_info($course_code);
if (empty($course)) {
	header('Location: course_list.php');
	exit;
}

// Get course teachers
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$sql = "SELECT user.user_id,lastname,firstname FROM $table_user as user,$table_course_user as course_user
WHERE course_user.status='1' AND course_user.user_id=user.user_id AND course_user.c_id ='".$course['real_id']."'".$order_clause;
$res = Database::query($sql);
$course_teachers = array();
while ($obj = Database::fetch_object($res)) {
    $course_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Get all possible teachers without the course teachers
if (api_is_multiple_url_enabled()) {
	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$sql = "SELECT u.user_id,lastname,firstname FROM $table_user as u
			INNER JOIN $access_url_rel_user_table url_rel_user
			ON (u.user_id=url_rel_user.user_id) WHERE url_rel_user.access_url_id=".api_get_current_access_url_id()." AND status = 1 or status = 2 ".$order_clause;
} else {
	$sql = "SELECT user_id,lastname,firstname FROM $table_user WHERE status = 1 or status = 2 ".$order_clause;
}

$res = Database::query($sql);
$teachers = array();

$platform_teachers[0] = '-- '.get_lang('NoManager').' --';
while ($obj = Database::fetch_object($res)) {
    $allTeachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    if (!array_key_exists($obj->user_id, $course_teachers)) {
        $teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    }

	if (!array_key_exists($obj->user_id,$course_teachers)) {
		$teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
	}

	if (isset($course['tutor_name']) && isset($course_teachers[$obj->user_id]) && $course['tutor_name']== $course_teachers[$obj->user_id]) {
		$course['tutor_name']=$obj->user_id;
	}
	//We add in the array platform teachers
	$platform_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Case where there is no teacher in the course
if (count($course_teachers) == 0) {
    $sql='SELECT tutor_name FROM '.$course_table.' WHERE code="'.$course_code.'"';
    $res = Database::query($sql);
    $tutor_name = Database::result($res, 0, 0);
    $course['tutor_name'] = array_search($tutor_name, $platform_teachers);
}

// Build the form
$form = new FormValidator('update_course');
$form->addElement('header', get_lang('Course').'  #'.$course_info['real_id'].' '.$course_code);
$form->addElement('hidden', 'code', $course_code);

//title
$form->add_textfield('title', get_lang('Title'), true, array ('class' => 'span6'));
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$element = $form->addElement('text', 'real_code', array(get_lang('CourseCode'), get_lang('ThisValueCantBeChanged')));
$element->freeze();

// Id
$element = $form->addElement('text', 'real_id', 'id');
$element->freeze();

// visual code
$form->add_textfield('visual_code', array(get_lang('VisualCode'), get_lang('OnlyLettersAndNumbers'), get_lang('ThisValueIsUsedInTheCourseURL')), true, array('class' => 'span4'));

$form->applyFilter('visual_code', 'strtoupper');
$form->applyFilter('visual_code', 'html_filter');

$group = array(
    $form->createElement('select', 'platform_teachers', '', $teachers, ' id="platform_teachers" multiple=multiple size="4" style="width:300px;"'),
    $form->createElement('select', 'course_teachers', '',   $course_teachers, ' id="course_teachers" multiple=multiple size="4" style="width:300px;"')
);

$multiSelectTemplate = $form->getDoubleMultipleSelectTemplate();
$renderer = $form->defaultRenderer();
$renderer->setElementTemplate($multiSelectTemplate, 'group');
$form->addGroup(
    $group,
    'group',
    get_lang('CourseTeachers'),
    '</td><td width="80" align="center">'.
    '<input class="arrowr" style="width:30px;height:30px;padding-right:12px" type="button" onclick="moveItem(document.getElementById(\'platform_teachers\'), document.getElementById(\'course_teachers\'))" ><br><br>' .
	'<input class="arrowl" style="width:30px;height:30px;padding-left:13px" type="button" onclick="moveItem(document.getElementById(\'course_teachers\'), document.getElementById(\'platform_teachers\'))" ></td><td>'
);


$categories_select = $form->addElement('select', 'category_code', get_lang('CourseFaculty'), array() , array('style'=>'width:350px','id'=>'category_code_id', 'class'=>'chzn-select'));
$categories_select->addOption('-','');
CourseManager::select_and_sort_categories($categories_select);

            if (isset($teachers[$coachId])) {
                unset($teachers[$coachId]);
            }
        }

        $groupName =  'session_coaches['.$sessionId.']';
        $platformTeacherId = 'platform_teachers_by_session_'.$sessionId;
        $coachId = 'coaches_by_session_'.$sessionId;

        $platformTeacherName = 'platform_teachers_by_session';
        $coachName = 'coaches_by_session';

        $group = array(
            $form->createElement('select', $platformTeacherName, '', $teachers, ' id="'.$platformTeacherId.'" multiple=multiple size="4" style="width:300px;"'),
            $form->createElement('select', $coachName, '', $sessionTeachers, ' id="'.$coachId.'" multiple=multiple size="4" style="width:300px;"')
        );

        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate($element_template, $groupName);
        $sessionUrl = api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$sessionId;
        $form->addGroup($group, $groupName, Display::url($session['name'], $sessionUrl, array('target' => '_blank')).' - '.get_lang('Coaches'), '</td>
            <td width="80" align="center">
                <input class="arrowr" style="width:30px;height:30px;padding-right:12px" type="button" onclick="moveItem(document.getElementById(\''.$platformTeacherId.'\'), document.getElementById(\''.$coachId.'\'));">
                <br><br>
                <input class="arrowl" style="width:30px;height:30px;padding-left:13px" type="button" onclick="moveItem(document.getElementById(\''.$coachId.'\'), document.getElementById(\''.$platformTeacherId.'\'));">
            </td><td>'
        );
    }
}

// Category code
$url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';
$categoryList = array();
if (!empty($course['category_code'])) {
    $data = getCategory($course['category_code']);
    $categoryList[] = array('id' => $data['code'], 'text' => $data['name']);
}

$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->applyFilter('select_language', 'html_filter');

$group = array();
$group[]= $form->createElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);
$form->addGroup($group, '', get_lang('CourseAccess'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[]= $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group, '', get_lang('Subscription'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[]= $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group, '', get_lang('Unsubscription'), '<br />');

$form->addElement('text','disk_quota',array(get_lang('CourseQuota'), null, get_lang('MB')));
$form->addRule('disk_quota', get_lang('ThisFieldIsRequired'),'required');
$form->addRule('disk_quota',get_lang('ThisFieldShouldBeNumeric'),'numeric');

//Extra fields
$extra_field = new ExtraField('course');
$extra = $extra_field->addElements($form, $course_code);

$htmlHeadXtra[] ='
<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addElement('style_submit_button', 'button', get_lang('ModifyCourseInfo'),'onclick="valide()"; class="save"');

// Set some default values
$course['disk_quota'] = round(DocumentManager::get_course_quota($course_code) /1024/1024, 1);
$course['title'] = api_html_entity_decode($course['title'], ENT_QUOTES, $charset);
$course['real_code'] = $course['code'];
$course['add_teachers_to_sessions_courses'] = isset($course['add_teachers_to_sessions_courses']) ? $course['add_teachers_to_sessions_courses'] : 0;

$form->setDefaults($course);

// Validate form
if ($form->validate()) {
	$course = $form->getSubmitValues();
    $visual_code = CourseManager::generate_course_code($course['visual_code']);
    // make sure to rebase the disk quota (shown in MB but stored in bytes)
    $course['disk_quota'] = $course['disk_quota']*1024*1024;
    CourseManager::update($course);

    // Check if the visual code is already used by *another* course
    $visual_code_is_used = false;

    $warn = get_lang('TheFollowingCoursesAlreadyUseThisVisualCode').':';
    if (!empty($visual_code)) {
        $list = CourseManager::get_courses_info_from_visual_code($visual_code);
        foreach ($list as $course_temp) {
            if ($course_temp['code'] != $course_code) {
               $visual_code_is_used = true;
               $warn .= ' '.$course_temp['title'].' ('.$course_temp['code'].'),';
            }
        }
        $warn = substr($warn, 0, -1);
    }
	if ($visual_code_is_used) {
	    header('Location: course_list.php?action=show_msg&warn='.urlencode($warn));
        exit;
	} else {
        header('Location: course_list.php');
        exit;
	}
}

Display::display_header($tool_name);

echo "<script>
function valide() {
    // Checking all multiple

    $('select').filter(function() {
        if ($(this).attr('multiple')) {
            $(this).find('option').each(function() {
                $(this).attr('selected', true);
            });
        }
    });
	//document.update_course.submit();
}
</script>";
// Display the form
$form->display();

Display :: display_footer();
