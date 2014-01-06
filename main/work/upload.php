<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;

$is_allowed_to_edit = api_is_allowed_to_edit();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$userInfo = api_get_user_info();
$session_id = api_get_session_id();
$course_code = api_get_course_id();
$course_info = api_get_course_info();
$group_id = api_get_group_id();

if (empty($work_id)) {
    api_not_allowed(true);
}

allowOnlySubscribedUser($user_id, $work_id, $course_id);

$parent_data = $my_folder_data = get_work_data_by_id($work_id);

if (empty($workInfo)) {
    api_not_allowed(true);
}

allowOnlySubscribedUser($user_id, $work_id, $course_id);

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code, $session_id);
$is_course_member = $is_course_member || api_is_platform_admin();

if ($is_course_member == false) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

//  @todo add an option to allow/block multiple attempts.
/*
if (!empty($workInfo) && !empty($workInfo['qualification'])) {
    $count =  get_work_count_by_student($user_id, $work_id);
    if ($count >= 1) {
        Display::display_header();
        if (api_get_course_setting('student_delete_own_publication') == '1') {
            Display::display_warning_message(get_lang('CantUploadDeleteYourPaperFirst'));
        } else {
            Display::display_warning_message(get_lang('YouAlreadySentAPaperYouCantUpload'));
        }
        Display::display_footer();
        exit;
    }
}*/

$has_expired = false;
$has_ended   = false;
$message = null;

if (!empty($my_folder_data)) {
    $homework = get_work_assignment_by_id($my_folder_data['id']);

    if ($homework['expires_on'] != '0000-00-00 00:00:00' || $homework['ends_on'] != '0000-00-00 00:00:00') {
        $time_now = time();

        if (!empty($homework['expires_on']) && $homework['expires_on'] != '0000-00-00 00:00:00') {
            $time_expires 	= api_strtotime($homework['expires_on'], 'UTC');
            $difference 	= $time_expires - $time_now;
            if ($difference < 0) {
                $has_expired = true;
            }
        }

        if (empty($homework['expires_on']) || $homework['expires_on'] == '0000-00-00 00:00:00') {
            $has_expired = false;
        }

        if (!empty($homework['ends_on']) && $homework['ends_on'] != '0000-00-00 00:00:00') {
            $time_ends 		= api_strtotime($homework['ends_on'], 'UTC');
            $difference2 	= $time_ends - $time_now;
            if ($difference2 < 0) {
                $has_ended = true;
            }
        }

        $ends_on 	= api_convert_and_format_date($homework['ends_on']);
        $expires_on = api_convert_and_format_date($homework['expires_on']);
    }

    if ($has_ended) {
        $message = Display::return_message(get_lang('EndDateAlreadyPassed').' '.$ends_on, 'error');
    } elseif ($has_expired) {
        $message = Display::return_message(get_lang('ExpiryDateAlreadyPassed').' '.$expires_on, 'warning');
    } else {
        if ($has_expired) {
            $message = Display::return_message(get_lang('ExpiryDateToSendWorkIs').' '.$expires_on);
        }
    }
}

$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(), 'name' => get_lang('StudentPublications'));
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$work_id, 'name' =>  $parent_data['title']);

// form title
$form_title = get_lang('UploadADocument');

$interbreadcrumb[] = array('url' => '#', 'name'  => $form_title);

$form = new FormValidator('form', 'POST', api_get_self()."?".api_get_cidreq()."&id=".$work_id."&gradebook=".Security::remove_XSS($_GET['gradebook'])."&origin=$origin", '', array('enctype' => "multipart/form-data"));
$form->addElement('header', $form_title);

$show_progress_bar = false;

if ($submitGroupWorkUrl) {
    // For user coming from group space to publish his work
    $realUrl = str_replace($_configuration['root_sys'], api_get_path(WEB_PATH), str_replace("\\", '/', realpath($submitGroupWorkUrl)));
    $form->addElement('hidden', 'newWorkUrl', $submitGroupWorkUrl);
    $text_document = $form->addElement('text', 'document', get_lang('Document'));
    $defaults['document'] = '<a href="' . format_url($submitGroupWorkUrl) . '">' . $realUrl . '</a>';
    $text_document->freeze();
} else {
    // else standard upload option
    $form->addElement('file', 'file', get_lang('UploadADocument'), 'size="40" onchange="updateDocumentTitle(this.value)"');
    $show_progress_bar = true;
}

$form = new FormValidator('form', 'POST', api_get_self()."?".api_get_cidreq()."&id=".$work_id, '', array('enctype' => "multipart/form-data"));
setWorkUploadForm($form);
$form->addElement('hidden', 'id', $work_id);
$form->addElement('hidden', 'contains_file', 0, array('id'=>'contains_file_id'));
$form->addElement('text', 'title', get_lang('Title'), array('id' => 'file_upload', 'class' => 'span4'));
$form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());

$form->addElement('hidden', 'active', 1);
$form->addElement('hidden', 'accepted', 1);
$form->addElement('hidden', 'sec_token', $token);

$text = get_lang('Send');
$class = 'upload';

// fix the Ok button when we see the tool in the learn path
if ($origin == 'learnpath') {
    $form->addElement('html', '<div style="margin-left:137px">');
    $form->addElement('style_submit_button', 'submitWork', $text, array('class'=> $class, 'value' => "submitWork"));
    $form->addElement('html', '</div>');
} else {
    $form->addElement('style_submit_button', 'submitWork', $text, array('class'=> $class, 'value' => "submitWork"));
}

if (!empty($_POST['submitWork']) || $item_id) {
    $form->addElement('style_submit_button', 'cancelForm', get_lang('Cancel'), 'class="cancel"');
}

if ($show_progress_bar) {
    $form->add_real_progress_bar('uploadWork', 'file');
}

$documentTemplateData = getDocumentTemplateFromWork($work_id, $course_info);
if (!empty($documentTemplateData)) {
    $defaults['title'] = $userInfo['complete_name'].'_'.$documentTemplateData['title'].'_'.substr(api_get_utc_datetime(), 0, 10);
    $defaults['description'] = $documentTemplateData['file_content'];
}

$form->setDefaults($defaults);
$error_message = null;

$succeed = false;
if ($form->validate()) {
    if ($student_can_edit_in_session && $check) {
        $values = $form->getSubmitValues();
        // Process work
        $error_message = processWorkForm($workInfo, $values, $course_info, $id_session, $group_id, $user_id);
        $script = 'work_list.php';
        if ($is_allowed_to_edit) {
            $script = 'work_list_all.php';
        }
        if (!empty($error_message)) {
            Session::write('error_message', $error_message);
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'work/'.$script.'?'.api_get_cidreq().'&id='.$work_id);
        exit;
    } else {
        // Bad token or can't add works
        $error_message = Display::return_message(get_lang('IsNotPosibleSaveTheDocument'), 'error');
    }
}

$htmlHeadXtra[] = to_javascript_work();
Display :: display_header(null);

if (!empty($work_id)) {

    echo $message;

    if ($is_allowed_to_edit) {
        if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
            echo Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
        } else {
            $form->display();
        }
    } elseif ($is_author) {
        if (empty($work_item['qualificator_id']) || $work_item['qualificator_id'] == 0) {
            $form->display();
        } else {
            Display::display_error_message(get_lang('ActionNotAllowed'));
        }
    } elseif ($student_can_edit_in_session && $has_ended == false) {

        $form->display();
    } else {
        Display::display_error_message(get_lang('ActionNotAllowed'));
    }
} else {
    Display::display_error_message(get_lang('ActionNotAllowed'));
}

Display :: display_footer();
