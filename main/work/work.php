<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.work
 **/

/**
 * 	STUDENT PUBLICATIONS MODULE
 *
 * Note: for a more advanced module, see the dropbox tool.
 * This one is easier with less options.
 * This tool is better used for publishing things,
 * sending in assignments is better in the dropbox.
 *
 * GOALS
 * *****
 * Allow student to quickly send documents immediately visible on the Course
 *
 * The script does 5 things:
 *
 * 	1. Upload documents
 * 	2. Give them a name
 * 	3. Modify data about documents
 * 	4. Delete link to documents and simultaneously remove them
 * 	5. Show documents list to students and visitors
 *
 * On the long run, the idea is to allow sending realvideo . Which means only
 * establish a correspondence between RealServer Content Path and the user's
 * documents path.
 *
 *
 */

/* INIT SECTION */

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

require_once 'work.lib.php';

$course_id      = api_get_course_int_id();
$course_info    = api_get_course_info();
$user_id 	    = api_get_user_id();
$id_session     = api_get_session_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$work_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$my_folder_data = get_work_data_by_id($work_id);

$curdirpath = '';
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = to_javascript_work();
$htmlHeadXtra[] = '<script>
function setFocus() {
    $("#work_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

$_course = api_get_course_info();

/*	Constants and variables */

$tool_name = get_lang('StudentPublications');
$course_code = api_get_course_id();
$session_id = api_get_session_id();
$group_id = api_get_group_id();

$item_id 		        = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$parent_id 		        = isset($_REQUEST['parent_id']) ? Database::escape_string($_REQUEST['parent_id']) : '';
$origin 		        = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';
$submitGroupWorkUrl     = isset($_REQUEST['submitGroupWorkUrl']) ? Security::remove_XSS($_REQUEST['submitGroupWorkUrl']) : '';
$title 			        = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
$description 	        = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
$uploadvisibledisabled  = isset($_REQUEST['uploadvisibledisabled']) ? Database::escape_string($_REQUEST['uploadvisibledisabled']) : $course_info['show_score'];

//directories management
$sys_course_path 	= api_get_path(SYS_COURSE_PATH);
$course_dir 		= $sys_course_path . $_course['path'];
$base_work_dir 		= $course_dir . '/work';

$link_target_parameter = ""; // e.g. "target=\"_blank\"";

$display_list_users_without_publication = isset($_GET['list']) && Security::remove_XSS($_GET['list']) == 'without' ? true : false;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

//Download folder
if ($action == 'downloadfolder') {
    require 'downloadfolder.inc.php';
}

/*	More init stuff */

if (isset ($_POST['cancelForm']) && !empty ($_POST['cancelForm'])) {
    header('Location: '.api_get_self().'?origin='.$origin.'&amp;gradebook='.$gradebook);
    exit;
}

// If the POST's size exceeds 8M (default value in php.ini) the $_POST array is emptied
// If that case happens, we set $submitWork to 1 to allow displaying of the error message
// The redirection with header() is needed to avoid apache to show an error page on the next request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !sizeof($_POST)) {
    if (strstr($_SERVER['REQUEST_URI'], '?')) {
        header('Location: ' . $_SERVER['REQUEST_URI'] . '&submitWork=1');
        exit();
    } else {
        header('Location: ' . $_SERVER['REQUEST_URI'] . '?submitWork=1');
        exit();
    }
}

$group_id = api_get_group_id();

$display_upload_form = false;
if ($action == 'upload_form') {
    $display_upload_form = true;
}

/*	Header */
if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook =	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

if (!empty($group_id)) {
    $group_properties  = GroupManager::get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access($user_id, $group_id, GroupManager::GROUP_TOOL_WORK);
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array ('url' =>'work.php?gidReq='.$group_id,'name' => get_lang('StudentPublications'));
    $url_dir = 'work.php?&id=' . $work_id;
    $interbreadcrumb[] = array ('url' => $url_dir, 'name' =>  $my_folder_data['title']);

    if ($action == 'upload_form') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
    }

    if ($action == 'create_dir') {
        $interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('CreateAssignment'));
    }
} else {
    if (isset($origin) && $origin != 'learnpath') {

        if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));
        } else {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('StudentPublications'));
        }
        $url_dir = 'work.php?id=' . $work_id;
        $interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);

        if ($action == 'upload_form') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('UploadADocument'));
        }
        if ($action == 'settings') {
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditToolOptions'));
        }
        if ($action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => '#','name' => get_lang('CreateAssignment'));
        }
    }
}

// Stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit();
$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

/*	Display links to upload form and tool options */
if (!in_array($action, array('add', 'create_dir'))) {
    $token = Security::get_token();
}
$courseInfo = api_get_course_info();

display_action_links($work_id, $curdirpath, $action);

// for teachers

// For teachers
switch ($action) {
    case 'settings':
        //if posts
        if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
            // Changing the tool setting: default visibility of an uploaded document
            // @todo
            $query = "UPDATE ".$main_course_table." SET show_score='" . $uploadvisibledisabled . "' WHERE code='" . api_get_course_id() . "'";
            $res = Database::query($query);

            /**
             * Course data are cached in session so we need to update both the database
             * and the session data
             */
            $_course['show_score'] = $uploadvisibledisabled;
            Session::write('_course', $course);

            // changing the tool setting: is a student allowed to delete his/her own document
            // database table definition
            $table_course_setting = Database :: get_course_table(TOOL_COURSE_SETTING);

            // counting the number of occurrences of this setting (if 0 => add, if 1 => update)
            $query = "SELECT * FROM " . $table_course_setting . " WHERE c_id = $course_id AND variable = 'student_delete_own_publication'";
            $result = Database::query($query);
            $number_of_setting = Database::num_rows($result);

            if ($number_of_setting == 1) {
                $query = "UPDATE " . $table_course_setting . " SET value='" . Database::escape_string($_POST['student_delete_own_publication']) . "'
                        WHERE variable='student_delete_own_publication' AND c_id = $course_id";
                Database::query($query);
            } else {
                $query = "INSERT INTO " . $table_course_setting . " (c_id, variable, value, category) VALUES
                ($course_id, 'student_delete_own_publication','" . Database::escape_string($_POST['student_delete_own_publication']) . "','work')";
                Database::query($query);
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        $studentDeleteOwnPublication = api_get_course_setting('student_delete_own_publication') == 1 ? 1 : 0;
        /*	Display of tool options */
        $content = settingsForm(
            array(
                'show_score' => $course_info['show_score'],
                'student_delete_own_publication' =>  $studentDeleteOwnPublication
            )
        );
        break;
    case 'add':
        //$check = Security::check_token('post');
        //show them the form for the directory name

        if ($is_allowed_to_edit && in_array($action, array('create_dir','add'))) {
            //create the form that asks for the directory name
            $form = new FormValidator('form1', 'post', api_get_self().'?action=create_dir&'. api_get_cidreq());

            $form->addElement('header', get_lang('CreateAssignment').$token);
            $form->addElement('hidden', 'action', 'add');
            $form->addElement('hidden', 'curdirpath', Security :: remove_XSS($curdirpath));
            // $form->addElement('hidden', 'sec_token', $token);

            $form->addElement('text', 'new_dir', get_lang('AssignmentName'));
            $form->addRule('new_dir', get_lang('ThisFieldIsRequired'), 'required');

            $form->add_html_editor('description', get_lang('Description'), false, false, getWorkDescriptionToolbar());

            $form->addElement('advanced_settings', '<a href="javascript: void(0);" onclick="javascript: return plus();"><span id="plus">'.Display::return_icon('div_show.gif',get_lang('AdvancedParameters'), array('style' => 'vertical-align:center')).' '.get_lang('AdvancedParameters').'</span></a>');

            $form->addElement('html', '<div id="options" style="display: none;">');

            //QualificationOfAssignment
            $form->addElement('text', 'qualification_value', get_lang('QualificationNumeric'));

            if (Gradebook::is_active()) {
                $form->addElement('checkbox', 'make_calification', null, get_lang('MakeQualifiable'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}"));
            } else {
                $message = Display::return_message(get_lang('CannotCreateDir'), 'error');
            }

            Session::write('message', $message);
            header('Location: '.$currentUrl);
            exit;
        } else {
            $content = $form->return_form();
        }
        break;
    case 'delete_dir':
        if ($is_allowed_to_edit) {
            $work_to_delete = get_work_data_by_id($_REQUEST['id']);
            $result = deleteDirWork($_REQUEST['id']);
            if ($result) {
                $message = Display::return_message(get_lang('DirDeleted') . ': '.$work_to_delete['title'], 'success');
                Session::write('message', $message);
                header('Location: '.$currentUrl);
                exit;
            }
        }
        break;
    case 'move':
        /*	Move file form request */
        if ($is_allowed_to_edit) {
            if (!empty($item_id)) {
                $content = generateMoveForm($item_id, $curdirpath, $course_info, $group_id, $session_id);
            }
        }
        break;
    case 'move_to':
        /* Move file command */
        if ($is_allowed_to_edit) {
            $move_to_path = get_work_path($_REQUEST['move_to_id']);

            if ($move_to_path==-1) {
                $move_to_path = '/';
            } elseif (substr($move_to_path, -1, 1) != '/') {
                $move_to_path = $move_to_path .'/';
            }

            // Security fix: make sure they can't move files that are not in the document table
            if ($path = get_work_path($item_id)) {
                if (move($course_dir.'/'.$path, $base_work_dir . $move_to_path)) {
                    // Update db
                    update_work_url($item_id, 'work' . $move_to_path, $_REQUEST['move_to_id']);
                    api_item_property_update($_course, 'work', $_REQUEST['move_to_id'], 'FolderUpdated', $user_id);

                    Display :: display_confirmation_message(get_lang('DirMv'));
                } else {
                    Display :: display_error_message(get_lang('Impossible'));
                }
            } else {
                Display :: display_error_message(get_lang('Impossible'));
            }
        }

        /*	Move file form request */
        if ($is_allowed_to_edit && $action == 'move') {
            if (!empty($item_id)) {
                $folders = array();
                $session_id = api_get_session_id();
                $session_id == 0 ? $withsession = " AND session_id = 0 " : $withsession = " AND session_id='".$session_id."'";
                $sql = "SELECT id, url, title FROM $work_table
                        WHERE c_id = $course_id AND active IN (0, 1) AND url LIKE '/%' AND post_group_id = '".$group_id."'".$withsession;
                $res = Database::query($sql);
                while ($folder = Database::fetch_array($res)) {
                    $folders[$folder['id']] = $folder['title'];
                }
                echo build_work_move_to_selector($folders, $curdirpath, $item_id);
            }
        }

        /*	MAKE VISIBLE WORK COMMAND */
        if ($is_allowed_to_edit && $action == 'make_visible') {
            if (!empty($item_id)) {
                if (isset($item_id) && $item_id == 'all') {
                } else {
                    $sql = "UPDATE " . $work_table . "	SET accepted = 1 WHERE c_id = $course_id AND id = '" . $item_id . "'";
                    Database::query($sql);
                    api_item_property_update($course_info, 'work', $item_id, 'visible', api_get_user_id());
                    Display::display_confirmation_message(get_lang('FileVisible'));
                }
            }
        }

        if ($is_allowed_to_edit && $action == 'make_invisible') {

            /*	MAKE INVISIBLE WORK COMMAND */
            if (!empty($item_id)) {
                if (isset($item_id) && $item_id == 'all') {
                } else {
                    $sql = "UPDATE  " . $work_table . " SET accepted = 0
                            WHERE c_id = $course_id AND id = '" . $item_id . "'";
                    Database::query($sql);
                    api_item_property_update($course_info, 'work', $item_id, 'invisible', api_get_user_id());
                    Display::display_confirmation_message(get_lang('FileInvisible'));
                }
            }
        }

        /*	Delete dir command */

        if ($is_allowed_to_edit && !empty($_REQUEST['delete_dir'])) {
            $delete_dir_id = intval($_REQUEST['delete_dir']);
            $locked = api_resource_is_locked_by_gradebook($delete_dir_id, LINK_STUDENTPUBLICATION);

            if ($locked == false) {

                $work_to_delete = get_work_data_by_id($delete_dir_id);
                del_dir($delete_dir_id);

                // gets calendar_id from student_publication_assigment
                $sql = "SELECT add_to_calendar FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_dir_id'";
                $res = Database::query($sql);
                $calendar_id = Database::fetch_row($res);

                // delete from agenda if it exists
                if (!empty($calendar_id[0])) {
                    $t_agenda   = Database::get_course_table(TABLE_AGENDA);
                    $sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id ='".$calendar_id[0]."'";
                    Database::query($sql);
                }
            } else {
                $message = Display::return_message(get_lang('Impossible'), 'error');
            }
            Session::write('message', $message);
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'list':

        /*	Display list of student publications */
        if (!empty($my_folder_data['description'])) {
            $content = '<p><div><strong>'.
                get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).
                '</p></div></p>';
        }

        $my_folder_data = get_work_data_by_id($work_id);

        $work_parents = array();
        if (empty($my_folder_data)) {
            $work_parents = getWorkList($work_id, $my_folder_data, $add_query);
        }

        if (api_is_allowed_to_edit()) {
            $userList = getWorkUserList($course_code, $session_id);

            // Work list
            $content .= '<div class="row">';
            $content .= '<div class="span9">';
            $content .= showTeacherWorkGrid();
            $content .= '</div>';
            $content .= '<div class="span3">';
            $content .= showStudentList($userList, $work_parents, $group_id, $course_id, $session_id);
            $content .= '</div>';
        } else {
            $content .= showStudentWorkGrid();
        }
    break;
}

if (isset($origin) && $origin != 'learnpath') {
    Display :: display_header(null);
} else {
    // We are in the learnpath tool
    Display::display_reduced_header();
}

Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

display_action_links($work_id, $curdirpath, $action);

            echo $table->toHtml();
            echo '</div>';
        } else {
            display_student_publications_list($work_id, $my_folder_data, $work_parents, $origin, $add_query, null);
        }
    break;
}
if ($origin != 'learnpath') {
    //we are not in the learning path tool
    Display :: display_footer();
}
