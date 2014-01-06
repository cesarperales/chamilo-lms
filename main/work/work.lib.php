<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	@package chamilo.work
 * 	@author Thomas, Hugues, Christophe - original version
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University -
 * ability for course admins to specify wether uploaded documents are visible or invisible by default.
 * 	@author Roan Embrechts, code refactoring and virtual course support
 * 	@author Frederic Vauthier, directories management
 *  @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 LOTS of bug fixes
 * 	@todo 	this lib should be convert in a static class and moved to magein/inc/lib
 */
/**
 * Initialization
 */
require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

if (isset($_configuration['add_document_to_work'])) {
    define('ADD_DOCUMENT_TO_WORK', $_configuration['add_document_to_work']);
} else {
    define('ADD_DOCUMENT_TO_WORK', false);
}

/**
 * Displays action links (for admins, authorized groups members and authorized students)
 * @param	string	Current dir
 * @param	integer	Whether to show tool options
 * @param	integer	Whether to show upload form option
 * @return	void
 */
function display_action_links($id, $cur_dir_path, $action)
{
	global $gradebook;

    $id = $my_back_id = intval($id);
    if ($action == 'list') {
        $my_back_id = 0;
    }

    $display_output = '';
    $origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';

    if (!empty($id)) {
        $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&id='.$my_back_id.'">'.Display::return_icon('back.png', get_lang('BackToWorksList'),'',ICON_SIZE_MEDIUM).'</a>';
    }

    if (api_is_allowed_to_edit(null, true) && $origin != 'learnpath') {
        // Create dir
        if (empty($id)) {
            $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=create_dir&origin='.$origin.'&gradebook='.$gradebook.'">';
            $display_output .= Display::return_icon('new_work.png', get_lang('CreateAssignment'),'',ICON_SIZE_MEDIUM).'</a>';
        }
        if (empty($id)) {
            // Options
            $display_output .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=settings&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'">';
            $display_output .= Display::return_icon('settings.png', get_lang('EditToolOptions'),'',ICON_SIZE_MEDIUM).'</a>';
        }
    }

    if (api_is_allowed_to_edit(null, true) && $origin != 'learnpath' && api_is_allowed_to_session_edit(false, true)) {
        // Delete all files
        if (api_get_setting('permanently_remove_deleted_files') == 'true'){
            $message = get_lang('ConfirmYourChoiceDeleteAllfiles');
        } else {
            $message = get_lang('ConfirmYourChoice');
        }
    }

	if ($display_output != '') {
		echo '<div class="actions">';
		echo $display_output;
		echo '</div>';
	}
}

/**
 * Displays all options for this tool.
 * These are
 * - make all files visible / invisible
 * - set the default visibility of uploaded files
 * @param $defaults
 */
function display_default_visibility_form($uploadvisibledisabled) {
	?>
	<div class="control-group">
		<label class="control-label">
		<?php echo get_lang('DefaultUpload'); ?>
		</label>
		<div class="controls">
            <label class="radio" for="uploadvisibledisabled_1">
                <input id="uploadvisibledisabled_1" class="checkbox" type="radio" name="uploadvisibledisabled" value="0"   <?php if ($uploadvisibledisabled == 0) echo 'checked'; ?> />
				<?php echo get_lang('NewVisible'); ?>
            </label>
        <label class="radio" for="uploadvisibledisabled_2">
            <input id="uploadvisibledisabled_2" class="checkbox" type="radio" name="uploadvisibledisabled" value="1" <?php if ($uploadvisibledisabled == 1) echo 'checked'; ?> />
            <?php echo get_lang('NewUnvisible'); ?>
        </label>
		</div>
	</div>
	<?php
}

/**
 * Display a part of the form to edit the settings of the tool
 * In this case weither the students are allowed to delete their own publication or not (by default not)
 *
 * @return html code
 * @since Dokeos 1.8.6.2
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function display_studentsdelete_form() {
	// by default api_get_course_setting returns -1 and the code only expects 0 or 1 so anything tha
	// is different than 1 will be converted into 0
	$current_course_setting_value = api_get_course_setting('student_delete_own_publication');
	if ($current_course_setting_value != 1) {
		$current_course_setting_value = 0;
	}
	?>
	<div class="control-group">
        <label class="control-label"><?php echo get_lang('StudentAllowedToDeleteOwnPublication'); ?></label>
	<div class="controls">
        <label class="radio" for="student_delete_own_publication_2">
            <input id="student_delete_own_publication_2" class="checkbox" type="radio" name="student_delete_own_publication" value="1" <?php if ($current_course_setting_value == 1) echo 'checked'; ?> />
                        <?php echo get_lang('Yes'); ?>
        </label>
        <label class="radio" for="student_delete_own_publication_1">
		<input id="student_delete_own_publication_1" class="checkbox" type="radio" name="student_delete_own_publication" value="0"
			<?php if ($current_course_setting_value == 0) echo 'checked'; ?> />
				<?php echo get_lang('No'); ?>
        </label>
		</div>
</div>
<?php
}

    $url = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'&action=settings';
    $form = new FormValidator('edit_settings', 'post', $url);
    $form->addElement('hidden', 'changeProperties', 1);
    $form->addElement('header', get_lang('EditToolOptions'));

    $group = array(
        $form->createElement('radio', 'show_score', null, get_lang('NewVisible'), 0),
        $form->createElement('radio', 'show_score', null, get_lang('NewUnvisible'), 1)
    );
    $form->addGroup($group, '', get_lang('DefaultUpload'));

    $group = array(
        $form->createElement('radio', 'student_delete_own_publication', null, get_lang('Yes'), 1),
        $form->createElement('radio', 'student_delete_own_publication', null, get_lang('No'), 0)
    );
    $form->addGroup($group, '', get_lang('StudentAllowedToDeleteOwnPublication'));
    $form->addElement('button', 'submit', get_lang('Save'));
    $form->setDefaults($defaults);
    return $form->return_form();
}

/**
 * converts 1-9 to 01-09
 */
function two_digits($number)
{
	$number = (int)$number;
	return ($number < 10) ? '0'.$number : $number;
}

/**
 * Converts 2008-10-06 12:45:00 to -> array('prefix' => array(year'=>2008, 'month'=>10, etc...)
 * @param string
 * @param string
 * @param array
 */
function convert_date_to_array($date, $group)
{
    $parts = explode(' ', $date);
    $date_parts = explode('-', $parts[0]);
    $date_parts_tmp = array();
    foreach ($date_parts as $item) {
        $date_parts_tmp[] = intval($item);
    }

    $time_parts = explode(':', $parts[1]);
    $time_parts_tmp = array();
    foreach ($time_parts as $item) {
        $time_parts_tmp[] = intval($item);
    }
    list($data[$group]['year'], $data[$group]['month'], $data[$group]['day']) = $date_parts_tmp;
    list($data[$group]['hour'], $data[$group]['minute']) = $time_parts_tmp;
	return $data;
}

/**
 * get date from a group of date
 */
function get_date_from_group($group)
{
	return $_POST[$group]['year'].'-'.two_digits($_POST[$group]['month']).'-'.two_digits($_POST[$group]['day']).' '.two_digits($_POST[$group]['hour']).':'.two_digits($_POST[$group]['minute']).':00';
}

/**
 * Create a group of select from a date
 * @param FormValidator $form
 * @param string $prefix
 * @return array
 */
function create_group_date_select($form, $prefix = '')
{
    $minute = range(10, 59);
    $d_year = date('Y');
    array_unshift($minute, '00', '01', '02', '03', '04', '05', '06', '07', '08', '09');

    $group_name = array(
        $form->createElement('select', $prefix.'day', '', array_combine(range(1, 31), range(1, 31))),
        $form->createElement('select', $prefix.'month', '', array_combine(range(1, 12), api_get_months_long())),
        $form->createElement('select', $prefix.'year', '', array($d_year => $d_year, $d_year + 1 => $d_year + 1)),
        $form->createElement('select', $prefix.'hour', '', array_combine(range(0, 23), range(0, 23))),
        $form->createElement('select', $prefix.'minute', '', $minute)
    );
    return $group_name;
}

function get_work_data_by_path($path) {
	$path = Database::escape_string($path);
    $course_id 	= api_get_course_int_id();
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT *  FROM  ".$work_table." WHERE url = '$path' AND c_id = $course_id ";
	$result = Database::query($sql);
	$return = array();
	if (Database::num_rows($result)) {
		$return = Database::fetch_array($result,'ASSOC');
	}
	return $return;
}

/**
 * @param int $id
 * @return array
 */
function get_work_data_by_id($id)
{
	$id = intval($id);
	$course_id 	= api_get_course_int_id();
	$work_table	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT * FROM  $work_table WHERE id = $id AND c_id = $course_id";
	$result = Database::query($sql);
	$return = array();
	if (Database::num_rows($result)) {
		$return = Database::fetch_array($result, 'ASSOC');
	}
	return $return;
}

/**
 * @param int $user_id
 * @param int $work_id
 * @return int
 */
function get_work_count_by_student($user_id, $work_id) {
	$user_id = intval($user_id);
	$work_id = intval($work_id);
	$course_id = api_get_course_int_id();
    $session_id = api_get_session_id();

	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql = "SELECT COUNT(*) as count FROM  $work_table
            WHERE
                c_id = $course_id AND
                parent_id = $work_id AND
                user_id = $user_id AND
                active = 1 AND
                session_id = $session_id ";
	$result = Database::query($sql);
	$return = 0;
	if (Database::num_rows($result)) {
		$return = Database::fetch_row($result,'ASSOC');
		$return = intval($return[0]);
	}
	return $return;
}

/**
 * @param int $id
 * @return array
 */
function get_work_assignment_by_id($id) {
	$id = intval($id);

	$table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
	$sql = "SELECT * FROM $table WHERE c_id = $courseId AND publication_id = $id";
	$result = Database::query($sql);
	$return = array();
	if (Database::num_rows($result)) {
		$return = Database::fetch_array($result,'ASSOC');
	}
	return $return;
}

/**
 * @param int $id
 * @param array $my_folder_data
 * @param string $add_in_where_query
 * @return array
 */
function getWorkList($id, $my_folder_data, $add_in_where_query)
{
    $work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

    $course_id          = api_get_course_int_id();
    $session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $group_id           = api_get_group_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    $linkInfo = is_resource_in_course_gradebook(api_get_course_id(), 3 , $id, api_get_session_id());

    if ($linkInfo) {
        $workInGradeBookLinkId = $linkInfo['id'];

        if ($workInGradeBookLinkId) {
            if ($is_allowed_to_edit) {
                if (intval($my_folder_data['qualification']) == 0) {
                    Display::display_warning_message(get_lang('MaxWeightNeedToBeProvided'));
                }
            }
        }
    }

    $contains_file_query = '';

    // Get list from database
    if ($is_allowed_to_edit) {
        $active_condition = ' active IN (0, 1)';
        $sql = "SELECT *  FROM  $work_table
                WHERE c_id = $course_id $add_in_where_query $condition_session AND $active_condition AND (parent_id = 0) $contains_file_query ";
        if (!empty($group_id)) {
            $sql .= " AND post_group_id = '".$group_id."' ";
        }
        $sql .= " ORDER BY sent_date DESC";
    } else {
        if (!empty($group_id)) {
            // set to select only messages posted by the user's group
            $group_query = " WHERE c_id = $course_id AND post_group_id = '".$group_id."' ";
            $subdirs_query = "AND parent_id = 0";
        } else {
            $group_query = " WHERE c_id = $course_id AND  post_group_id = '0' ";
            $subdirs_query = "AND parent_id = 0";
        }
        //@todo how we can active or not an assignment?
        $active_condition = ' AND active IN (1,0)';
        $sql = "SELECT * FROM  $work_table
                $group_query $subdirs_query $add_in_where_query $active_condition $condition_session
                ORDER BY title";
    }

    $work_parents = array();

    $sql_result = Database::query($sql);
    if (Database::num_rows($sql_result)) {
        while ($work = Database::fetch_object($sql_result)) {
            if ($work->parent_id == 0) {
                $work_parents[] = $work;
            }
        }
    }

    return $work_parents;
}

/**
 * @param int $userId
 * @return array
 */
function getWorkPerUser($userId)
{
    $works = getWorkList(null, null, null);
    $result = array();
    if (!empty($works)) {
        foreach ($works as $workData) {
            $workId = $workData->id;
            $result[$workId]['work'] = $workData;
            $result[$workId]['work']->user_results = get_work_user_list(0, 100, null, null, $workId, null, $userId);
        }
    }
    return $result;
}

/**
 * @param int $workId
 * @param int $groupId
 * @param int $course_id
 * @param int $sessionId
 * @param int $userId user id to filter
 * @param array $onlyUserList only parse this user list
 * @return mixed
 */
function getUniqueStudentAttempts($workId, $groupId, $course_id, $sessionId, $userId = null, $onlyUserList = array())
{
    $work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_table      = Database::get_main_table(TABLE_MAIN_USER);

    $course_id = intval($course_id);
    $workId = intval($workId);
    $sessionId = intval($sessionId);
    $groupId = intval($groupId);

    $studentCondition = null;

    if (!empty($onlyUserList)) {
        $onlyUserList = array_map('intval', $onlyUserList);
        $studentCondition = "AND u.user_id IN ('".implode("', '", $onlyUserList)."') ";
    } else {
        if (empty($userId)) {
            return 0;
        }
    }

    $sql_document = "SELECT count(*) FROM (
                        SELECT count(*)
                        FROM $work_table w INNER JOIN $user_table u ON w.user_id = u.user_id
                        WHERE   w.c_id = $course_id AND
                                w.session_id = $sessionId AND
                                w.parent_id = ".$workId." AND
                                w.post_group_id = ".$groupId." AND
                                w.active IN (0, 1) $studentCondition
                        ";

    if (!empty($userId)) {
        $userId = intval($userId);
        $sql_document .= " AND u.user_id = ".$userId;
    }
    $sql_document .= " GROUP BY u.user_id) as t";

    $res_document = Database::query($sql_document);
    $rowCount = Database::fetch_row($res_document);

    return $rowCount[0];
}

/**
 * Display the list of student publications, taking into account the user status
 * @deprecated
 * @param $id
 * @param $my_folder_data
 * @param $work_parents
 * @param $origin
 * @param array $userList
 */
function display_student_publications_list($id, $my_folder_data, $work_parents, $origin, $userList = array())
{
	global $gradebook;

	// Database table names
	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$iprop_table     = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$work_assigment  = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

	$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

	$session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $course_id          = api_get_course_int_id();
    $course_info        = api_get_course_info(api_get_course_id());

	$sort_params = array();

	if (isset($_GET['column'])) {
		$sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
	}
	if (isset($_GET['page_nr'])) {
		$sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
	}
	if (isset($_GET['per_page'])) {
		$sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
	}
	if (isset($_GET['direction'])) {
		$sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
	}
	$sort_params    = implode('&amp;', $sort_params);
	$my_params      = $sort_params;
	$origin         = Security::remove_XSS($origin);

    $qualification_exists = false;
    if (!empty($my_folder_data['qualification']) && intval($my_folder_data['qualification']) > 0) {
        $qualification_exists = true;
    }

	$table_header = array();
	$table_has_actions_column = false;
	$table_header[] = array(get_lang('Type'), false, 'style="width:40px"');
	$table_header[] = array(get_lang('Title'), true);

	if (!empty($id)) {
		$table_header[] = array(get_lang('FirstName'), true);
		$table_header[] = array(get_lang('LastName'), true);
	}

    $table_header[] = array(get_lang('HandOutDateLimit'), true, 'style="width:200px"');

	if ($is_allowed_to_edit) {
        $table_header[] = array(get_lang('HandedOut'), false);
		$table_header[] = array(get_lang('Actions'), false, 'style="width:90px"', array('class'=>'td_actions'));
		$table_has_actions_column = true;

        if ($qualification_exists) {
            $table_header[] = array(get_lang('Qualification'), true);
        }

	} else {
        // All users
        if ($course_info['show_score'] == 0) {
            $table_header[] = array(get_lang('Others'), false);
        }
    }

	$table_data = array();

	// List of all folders if no id was provided

    $group_id = api_get_group_id();

	if (is_array($work_parents)) {
		foreach ($work_parents as $work_parent) {
			$sql_select_directory = "SELECT
			        title,
			        url,
			        prop.insert_date,
			        prop.lastedit_date,
			        work.id, author,
			        has_properties,
			        view_properties,
			        description,
			        qualification,
			        weight,
			        allow_text_assignment
                FROM ".$iprop_table." prop INNER JOIN ".$work_table." work ON (prop.ref=work.id AND prop.c_id = $course_id)
                WHERE active IN (0, 1) AND ";

			if (!empty($group_id)) {
				$sql_select_directory .= " work.post_group_id = '".$group_id."' "; // set to select only messages posted by the user's group
			} else {
				$sql_select_directory .= " work.post_group_id = '0' ";
			}
			$sql_select_directory .= " AND ".
			                           "  work.c_id = $course_id AND ".
			                           "  work.id  = ".$work_parent->id." AND ".
			                           "  work.filetype = 'folder' AND ".
			                           "  prop.tool='work' $condition_session";
			$result = Database::query($sql_select_directory);
			$row    = Database::fetch_array($result, 'ASSOC');

			if (!$row) {
				// the folder belongs to another session
				continue;
			}

            // form edit directory
            if (!empty($row['has_properties'])) {
                $sql = Database::query('SELECT * FROM '.$work_assigment.' WHERE c_id = '.$course_id.' AND id = "'.$row['has_properties'].'" LIMIT 1');
                $homework = Database::fetch_array($sql);
            }
            // save original value for later
            $utc_expiry_time = $homework['expires_on'];
			if ($is_allowed_to_edit && $locked == false) {

				if (!empty($edit_dir) && $edit_dir == $id2) {

					$form_folder = new FormValidator('edit_dir', 'post', api_get_self().'?origin='.$origin.'&gradebook='.$gradebook.'&edit_dir='.$id2);
                    $form_folder->addElement('text', 'dir_name', get_lang('Title'));
                    $form_folder->addElement('hidden', 'work_id', $id2);
                    $form_folder -> addRule('dir_name', get_lang('ThisFieldIsRequired'), 'required');

                    $my_title = !empty($row['title']) ? $row['title'] : basename($row['url']);

					$defaults = array('dir_name' => Security::remove_XSS($my_title), 'description' => Security::remove_XSS($row['description']));
					$form_folder->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'work', 'Width' => '80%', 'Height' => '200'));

					$there_is_a_end_date = false;
					$form_folder -> addElement('advanced_settings',
                        '<a href="javascript://" onclick="javascript: return plus();" >
                         <span id="plus">&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />
                         &nbsp;'.get_lang('AdvancedParameters').'
                         </span>
                         </a>'
                    );
					$form_folder->addElement('html', '<div id="options" style="display: none;">');

					if (empty($default)) {
						$default = api_get_local_time();
					}

					$parts = explode(' ', $default);

					list($d_year, $d_month, $d_day) = explode('-', $parts[0]);
					list($d_hour, $d_minute) = explode(':', $parts[1]);

                    $qualification_input[] = $form_folder->createElement('text', 'qualification');
                    $form_folder -> addGroup($qualification_input, 'qualification', get_lang('QualificationNumeric'));

                    if (Gradebook::is_active()) {
                        $link_info = is_resource_in_course_gradebook(api_get_course_id(), LINK_STUDENTPUBLICATION, $id2);
                        $form_folder -> addElement('checkbox', 'make_calification', null, get_lang('MakeQualifiable'), 'onclick="javascript: if(this.checked){document.getElementById(\'option3\').style.display = \'block\';}else{document.getElementById(\'option3\').style.display = \'none\';}"');

                        if (!empty($link_info)) {
                            $form_folder -> addElement('html', '<div id=\'option3\' style="display:block">');
                        } else {
                            $form_folder -> addElement('html', '<div id=\'option3\' style="display:none">');
                        }

                        //Loading gradebook select
                        load_gradebook_select_in_tool($form_folder);

                        $weight_input2[] = $form_folder->createElement('text', 'weight');
                        $form_folder -> addGroup($weight_input2, 'weight', get_lang('WeightInTheGradebook'), 'size="10"');
                        $form_folder -> addElement('html', '</div>');
                        $defaults['weight[weight]'] = $link_info['weight'];

                        if (!empty($link_info)) {
                            $defaults['category_id'] = $link_info['category_id'];
                            $defaults['make_calification'] = 1;
                        }
                    } else {
                        $defaults['category_id'] = '';
                    }

					if ($homework['expires_on'] != '0000-00-00 00:00:00') {
						$homework['expires_on'] = api_get_local_time($homework['expires_on']);
						$there_is_a_expire_date = true;
						$defaults['enableExpiryDate'] = true;

						$form_folder -> addElement('checkbox', 'enableExpiryDate',null,get_lang('EnableExpiryDate'), 'onclick="javascript: if(this.checked){document.getElementById(\'option1\').style.display = \'block\';}else{document.getElementById(\'option1\').style.display = \'none\';}"');
						$form_folder -> addElement('html', '<div id=\'option1\' style="display:block">');
						$form_folder -> addGroup(create_group_date_select(), 'expires', get_lang('ExpiresAt'));
						$form_folder -> addElement('html', '</div>');

					} else {
						$homework['expires_on'] = api_get_local_time();
						$expires_date_array = convert_date_to_array(api_get_local_time(), 'expires');
						$defaults 			= array_merge($defaults, $expires_date_array);

						$there_is_a_expire_date = false;

						$form_folder -> addElement('checkbox', 'enableExpiryDate',null,get_lang('EnableExpiryDate'), 'onclick="javascript: if(this.checked){document.getElementById(\'option1\').style.display = \'block\';}else{document.getElementById(\'option1\').style.display = \'none\';}"');
						$form_folder -> addElement('html', '<div id=\'option1\' style="display:none">');
						$form_folder -> addGroup(create_group_date_select(), 'expires', get_lang('ExpiresAt'));
						$form_folder -> addElement('html', '</div>');
					}

					if ($homework['ends_on'] != '0000-00-00 00:00:00') {
						$homework['ends_on'] = api_get_local_time($homework['ends_on']);
						$there_is_a_end_date = true;

						$defaults['enableEndDate'] = true;

						$form_folder -> addElement('checkbox', 'enableEndDate', null, get_lang('EnableEndDate'), 'onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display = \'block\';}else{document.getElementById(\'option2\').style.display = \'none\';}"');
						$form_folder -> addElement('html', '<div id=\'option2\' style="display:block">');
						$form_folder -> addGroup(create_group_date_select(), 'ends', get_lang('EndsAt'));
						$form_folder -> addElement('html', '</div>');
						$form_folder -> addRule(array('expires', 'ends'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');

					} else {
						$homework['ends_on'] = api_get_local_time();

						$expires_date_array = convert_date_to_array(api_get_local_time(), 'ends');
						$defaults 			= array_merge($defaults, $expires_date_array);

						$there_is_a_end_date = false;

						$form_folder -> addElement('checkbox', 'enableEndDate', null, get_lang('EnableEndDate'), 'onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display = \'block\';}else{document.getElementById(\'option2\').style.display = \'none\';}"');
						$form_folder -> addElement('html', '<div id=\'option2\' style="display:none">');
						$form_folder -> addGroup(create_group_date_select(), 'ends', get_lang('EndsAt'));
						$form_folder -> addElement('html', '</div>');
						$form_folder -> addRule(array('expires', 'ends'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');
					}

					if ($there_is_a_expire_date && $there_is_a_end_date) {
						$form_folder -> addRule(array('expires', 'ends'), get_lang('DateExpiredNotBeLessDeadLine'), 'comparedate');
					}

					$form_folder -> addElement('checkbox', 'allow_text_assignment', null, get_lang('AllowTextAssignments'));
                    $form_folder -> addElement('html', '</div>');
					$form_folder -> addElement('style_submit_button', 'submit', get_lang('ModifyDirectory'), 'class="save"');

					if ($there_is_a_end_date) {
						$end_date_array = convert_date_to_array($homework['ends_on'], 'ends');
						$defaults = array_merge($defaults, $end_date_array);
					}

					if ($there_is_a_expire_date) {
						$expires_date_array = convert_date_to_array($homework['expires_on'], 'expires');
						$defaults = array_merge($defaults, $expires_date_array);
					}

					if (!empty($row['qualification'])) {
						$defaults = array_merge($defaults, array('qualification[qualification]' => $row['qualification']));
					}

					$defaults['allow_text_assignment'] = $row['allow_text_assignment'];
					$form_folder -> setDefaults($defaults);
					$display_edit_form = true;

					if ($form_folder->validate()) {

						if ($_POST['enableExpiryDate'] == '1') {
							$there_is_a_expire_date = true;
						} else {
							$there_is_a_expire_date = false;
						}
						if ($_POST['enableEndDate'] == '1') {
							$there_is_a_end_date = true;
						} else {
							$there_is_a_end_date = false;
						}

						$values = $form_folder->exportValues();
                        $work_id = $values['work_id'];

						$dir_name = replace_dangerous_char($values['dir_name']);
						$dir_name = disable_dangerous_file($dir_name);

						$edit_check = false;

                        $work_data = get_work_data_by_id($work_id);

						if (!empty($work_data)) {
                            $edit_check = true;
					    } else {
							$edit_check = true;
						}

						if ($edit_check) {
							$TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);

							$expires_query = ' SET expires_on = '."'".($there_is_a_expire_date ? api_get_utc_datetime(get_date_from_group('expires')) : '0000-00-00 00:00:00')."'";
							Database::query('UPDATE '.$work_assigment.$expires_query.' WHERE c_id = '.$course_id.' AND id = '."'".$row['has_properties']."'");
							$sql_add_publication = "UPDATE ".$work_table." SET has_properties  = '".$row['has_properties'].  "', view_properties=1 WHERE c_id = $course_id AND id ='".$row['id']."'";
							Database::query($sql_add_publication);

							$ends_query = ' SET ends_on = '."'".($there_is_a_end_date ? api_get_utc_datetime(get_date_from_group('ends')) : '0000-00-00 00:00:00')."'";
							Database::query('UPDATE '.$work_assigment.$ends_query.' WHERE c_id = '.$course_id.' AND id = '."'".$row['has_properties']."'");
							$sql_add_publication = "UPDATE ".$work_table." SET has_properties  = '".$row['has_properties'].  "', view_properties=1 WHERE c_id = $course_id AND id ='".$row['id']."'";
							Database::query($sql_add_publication);

                            $qualification_value = isset($_POST['qualification']['qualification']) && !empty($_POST['qualification']['qualification']) ? intval($_POST['qualification']['qualification']) : 0;
                            $enable_qualification = !empty($qualification_value) ? 1 : 0;
                            $sql_add_publication = "UPDATE ".$work_assigment." SET enable_qualification  = '".$enable_qualification.  "' WHERE c_id = $course_id AND publication_id ='".$row['id']."'";
							Database::query($sql_add_publication);

                             $sql = 'UPDATE '.$work_table.' SET
                                                 allow_text_assignment = '."'".intval($_POST['allow_text_assignment'])."'".' ,
                                                 title = '."'".Database::escape_string($_POST['dir_name'])."'".',
                                                 description = '."'".Database::escape_string($_POST['description'])."'".',
                                                 qualification = '."'".Database::escape_string($_POST['qualification']['qualification'])."'".',
                                                 weight = '."'".Database::escape_string($_POST['weight']['weight'])."'".'
                                             WHERE c_id = '.$course_id.' AND id = '.$row['id'];
							Database::query($sql);

							require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';

                            $link_info = is_resource_in_course_gradebook(api_get_course_id(), LINK_STUDENTPUBLICATION, $row['id'], api_get_session_id());
                            $link_id = null;
                            if (!empty($link_info)) {
                                $link_id = $link_info['id'];
                            }

                            if (isset($_POST['make_calification']) && $_POST['make_calification'] == 1 && !empty($_POST['category_id'])) {
                                if (empty($link_id)) {
                                    add_resource_to_course_gradebook($_POST['category_id'], api_get_course_id(), LINK_STUDENTPUBLICATION, $row['id'], $_POST['dir_name'], (float)$_POST['weight']['weight'], (float)$_POST['qualification']['qualification'], $_POST['description'], 1, api_get_session_id(), $link_id);
                                } else {
                                    update_resource_from_course_gradebook($link_id, api_get_course_id(), $_POST['weight']['weight']);
                                }
                            } else {
                                //Delete everything of the gradebook
                                remove_resource_from_course_gradebook($link_id);
                            }

							update_dir_name($work_data, $dir_name, $values['dir_name']);

							$dir = $dir_name;
							$display_edit_form = false;

							// gets calendar_id from student_publication_assigment
							$sql = "SELECT add_to_calendar FROM $work_assigment WHERE c_id = $course_id AND publication_id ='".$row['id']."'";
							$res = Database::query($sql);
							$calendar_id = Database::fetch_row($res);
							$dir_name = sprintf(get_lang('HandingOverOfTaskX'), $dir_name);

							$end_date = $row['insert_date'];

							if ($_POST['enableExpiryDate'] == '1') {
								$end_date = Database::escape_string(api_get_utc_datetime(get_date_from_group('expires')));
							}

							// update from agenda if it exists
							if (!empty($calendar_id[0])) {
								$sql = "UPDATE ".$TABLEAGENDA."
										SET title='".$values['dir_name']."',
											content  = '".Database::escape_string($_POST['description'])."',
											start_date = '".$end_date."',
											end_date   = '".$end_date."'
										WHERE c_id = $course_id AND id='".$calendar_id[0]."'";
								Database::query($sql);
							}
							Display::display_confirmation_message(get_lang('FolderEdited'));
						} else {
							Display::display_warning_message(get_lang('FileExists'));
						}
					}
				}
			}

			$work_data = get_work_data_by_id($work_parent->id);

            $workId = $row['id'];

			$action = '';
			$row = array();
			$class = '';
			$course_id  = api_get_course_int_id();

			if (api_is_allowed_to_edit()) {
                $cant_files = get_count_work($work_data['id']);
			} else {
                $isSubscribed = userIsSubscribedToWork(api_get_user_id(), $work_data['id'], $course_id);
                if ($isSubscribed == false) {
                    continue;
                }
                $cant_files = get_count_work($work_data['id'], api_get_user_id());
			}

			$text_file = get_lang('FilesUpload');

			if ($cant_files == 1) {
				$text_file = api_strtolower(get_lang('FileUpload'));
			}

			$icon = Display::return_icon('work.png', get_lang('Assignment'), array(), ICON_SIZE_SMALL);

            $row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'">'.$icon.'</a>';

            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
            $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3, $workId, api_get_session_id());
            $link_id = $link_info['id'];
            $count  = 0;
            if ($link_info !== false) {
                $gradebook_data = get_resource_from_course_gradebook($link_id);
                $count = $gradebook_data['weight'];
            }
            if ($count > 0) {
                $add_to_name = Display::label(get_lang('IncludedInEvaluation'), 'info');
            } else {
                $add_to_name = '';
            }

				// Work name
                if ($cant_files > 0 ) {
                    $zip = '<a href="downloadfolder.inc.php?id='.$work_data['id'].'">'.Display::return_icon('save_pack.png', get_lang('Save'), array('style' => 'float:right;'), ICON_SIZE_SMALL).'</a>';
                }

            // Work name
            if ($cant_files > 0 ) {
                $zip = '<a href="downloadfolder.inc.php?id='.$work_data['id'].'">'.Display::return_icon('save_pack.png', get_lang('Save'), array('style' => 'float:right;'), ICON_SIZE_SMALL).'</a>';
            }

            $link = 'work_list.php';
            if (api_is_allowed_to_edit()) {
                $link = 'work_list_all.php';
            }

            $url = $zip.'<a href="'.api_get_path(WEB_CODE_PATH).'work/'.$link.'?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.Security::remove_XSS($_GET['gradebook']).'&id='.$work_data['id'].'"'.$class.'>'.
                    $work_title.'</a> '.$add_to_name.'<br />'.$cant_files.' '.$text_file;
            $row[] = $url;

            if (!empty($homework)) {
                // use original utc value saved previously to avoid doubling the utc-to-local conversion ($homework['expires_on'] might have been tainted)
                $row[] = !empty($utc_expiry_time) && $utc_expiry_time != '0000-00-00 00:00:00' ? api_get_local_time($utc_expiry_time): '-';
            } else {
                $row[] = '-';
            }

            if (!$is_allowed_to_edit) {
                if ($course_info['show_score'] == 0) {
                    $url = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id='.$work_parent->id;
                    $row[] = Display::url(Display::return_icon('group.png', get_lang('Others')), $url);
                }
            }

			if ($origin != 'learnpath') {
				if ($is_allowed_to_edit) {
			        $cant_files_per_user = getUniqueStudentAttempts($work_data['id'], $group_id, $course_id, api_get_session_id(), null, $userList);

                    $row[] = $cant_files_per_user.'/'.count($userList);
                    if (api_resource_is_locked_by_gradebook($workId, LINK_STUDENTPUBLICATION)) {
                        $action .= Display::return_icon('edit_na.png', get_lang('Edit'), array(), ICON_SIZE_SMALL);
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), array(), ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a href="'.api_get_path(WEB_CODE_PATH).'work/edit_work.php?cidReq='.api_get_course_id().'&origin='.$origin.'&gradebook='.$gradebook.'&id='.$workId.'">'.
                                    Display::return_icon('edit.png', get_lang('Modify'), array(), ICON_SIZE_SMALL).'</a>';
                        $action .= ' <a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'&delete_dir='.$workId.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('DirDelete').'"  >'.
                            Display::return_icon('delete.png',get_lang('DirDelete'),'',ICON_SIZE_SMALL).'</a>';
                    }
					$row[] = $action;
				} else {
					$row[] = '';
				}
			}
            $row[] = $work_data['title'];
			$table_data[] = $row;
		}
	}

	$sorting_options = array();
	$sorting_options['column'] = 1;

	// Here we change the way how the columns are going to be sorted
	// in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
	// because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"

	$column_order = array();
	$i=0;
	foreach ($table_header as $item) {
		$column_order[$i] = $i;
		$i++;
	}
    if (empty($my_folder_data)) {
		$column_order[1] = 5;
	} else {
		$column_order[2] = 2;
	}

	// An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
	$column_show = array();

	$column_show[] = 1; // type 0
	$column_show[] = 1; // title 1

	if (!empty($my_folder_data)) {
		$column_show[] = 1;  // 2
		$column_show[] = 1;  // 3
		if ($qualification_exists) {
			$column_show[] = 1;  // 4
		}
	}
	$column_show[] = 1; //date
	if ($table_has_actions_column) {
		$column_show[] = 1; // modify
	}
	$column_show[] = 1; //real date in correct format
    $column_show[] = 0; //real date in correct format

	$paging_options = array();
	if (isset($_GET['curdirpath'])) {
		$my_params = array ('curdirpath' => Security::remove_XSS($_GET['curdirpath']));
	}

	$my_params = array ('id' => isset($_GET['id']) ? $_GET['id'] : null);

	if (isset($_GET['edit_dir'])) {
		$my_params = array ('edit_dir' => intval($_GET['edit_dir']));
	}
	$my_params['origin'] = $origin;
	Display::display_sortable_config_table('work', $table_header, $table_data, $sorting_options, $paging_options, $my_params, $column_show, $column_order);
}

/**
 * Shows the work list (student view)
 * @return string
 */
function showStudentWorkGrid()
{
    $courseInfo = api_get_course_info();
    $columnModel = array(
        array('name'=>'type', 'index'=>'type', 'width'=>'30',   'align'=>'left', 'sortable' => 'false'),
        array('name'=>'title', 'index'=>'title', 'width'=>'80',   'align'=>'left'),
        array('name'=>'expires_on', 'index'=>'expires_on', 'width'=>'500',  'align'=>'left', 'sortable'=>'false')
    );

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_student&'.api_get_cidreq();

    $columns = array(
        get_lang('Type'),
        get_lang('Title'),
        get_lang('HandOutDateLimit')
    );

    if ($courseInfo['show_score'] == 0) {
        $columnModel[] = array(
            'name' => 'others',
            'index' => 'others',
            'width' => '80',
            'align' => 'left',
            'sortable' => 'false'
        );
        $columns[] = get_lang('Others');
    }

    $params = array(
        'autowidth' => 'true',
        'height' => 'auto'
    );

    $html = '<script>
    $(function() {
        '.Display::grid_js('workList', $url, $columns, $columnModel, $params, array(), array(), true).'
    });
    </script>';

    $html .= Display::grid_html('workList');
    return $html;
}

/**
 * Shows the work list (teacher view)
 * @return string
 */
function showTeacherWorkGrid()
{
    $columnModel = array(
        array('name'=>'type', 'index'=>'type', 'width'=>'50', 'align'=>'left', 'sortable' => 'false'),
        array('name'=>'title', 'index'=>'title',  'width'=>'300',   'align'=>'left'),
        array('name'=>'sent_date', 'index'=>'sent_date', 'width'=>'150',  'align'=>'left'),
        array('name'=>'expires_on', 'index'=>'expires_on', 'width'=>'150',  'align'=>'left'),
        array('name'=>'end_on', 'index'=>'end_on', 'width'=>'150',  'align'=>'left'),
        array('name'=>'actions', 'index'=>'actions', 'width'=>'150', 'align'=>'left', 'sortable'=>'false')
    );
    $token = null;

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_teacher&'.api_get_cidreq();
    $deleteUrl = api_get_path(WEB_AJAX_PATH).'work.ajax.php?a=delete_work&'.api_get_cidreq();

    $columns = array(
        get_lang('Type'),
        get_lang('Title'),
        get_lang('SentDate'),
        get_lang('HandOutDateLimit'),
        get_lang('HandedOut'),
        get_lang('Actions')
    );

    $params = array(
        'multiselect' => true,
        'autowidth' => 'true',
        'height' => 'auto',
        //'beforeSelectRow' => 'function(rowid, e) { e.stopPropagation(); }'
    );

    $html = '<script>
    $(function() {
        '.Display::grid_js('workList', $url, $columns, $columnModel, $params, array(), null, true).'
        $("#workList").jqGrid(
            "navGrid",
            "#workList_pager",
            { edit: false, add: false, del: true },
            { height:280, reloadAfterSubmit:false }, // edit options
            { height:280, reloadAfterSubmit:false }, // add options
            { reloadAfterSubmit:false, url: "'.$deleteUrl.'" }, // del options
            { width:500 } // search options
        );
    });
    </script>';
    $html .= Display::grid_html('workList');
    return $html;
}

/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $group_dir
 * @return string html form
 */
// TODO: This function is a candidate for removal, it is not used anywhere.
function build_work_directory_selector($folders, $curdirpath, $group_dir = '')
{
	$form = '<form name="selector" action="'.api_get_self().'?'.api_get_cidreq().'" method="POST">';
	$form .= get_lang('CurrentDirectory').' <select name="curdirpath" onchange="javascript: document.selector.submit();">';
	//group documents cannot be uploaded in the root
	if ($group_dir == '') {
		$form .= '<option value="/">/ ('.get_lang('Root').')</option>';
		if (is_array($folders)) {
			foreach ($folders as $folder) {
				$selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
				$form .= '<option'.$selected.' value="'.$folder.'">'.$folder.'</option>'."\n";
			}
		}
	} else {
		foreach ($folders as $folder) {
			$selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
			$display_folder = substr($folder, strlen($group_dir));
			$display_folder = ($display_folder == '') ? '/ ('.get_lang('Root').')' : $display_folder;
			$form .= '<option'.$selected.' value="'.$folder.'">'.$display_folder.'</option>'."\n";
		}
	}

	$form .= '</select>';
	$form .= '<noscript><input type="submit" name="change_path" value="'.get_lang('Ok').'" /></noscript>';
	$form .= '</form>';

	return $form;
}

/**
 * Builds the form thats enables the user to
 * move a document from one directory to another
 * This function has been copied from the document/document.inc.php library
 *
 * @param array $folders
 * @param string $curdirpath
 * @param string $move_file
 * @param string $group_dir
 * @return string html form
 */
function build_work_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '')
{
    $course_id = api_get_course_int_id();
	$move_file	= intval($move_file);
	$tbl_work	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$sql 		= "SELECT title, url FROM $tbl_work WHERE c_id = $course_id AND id ='".$move_file."'";
	$result 	= Database::query($sql);
	$row = Database::fetch_array($result, 'ASSOC');
    $title = empty($row['title']) ? basename($row['url']) : $row['title'];

	global $gradebook;
    //@todo use formvalidator please!
	$form = '<form class="form-horizontal" name="move_to_form" action="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&curdirpath='.Security::remove_XSS($curdirpath).'" method="POST">';
	$form .= '<legend>'.get_lang('MoveFile').' - '.Security::remove_XSS($title).'</legend>';
	$form .= '<input type="hidden" name="item_id" value="'.$move_file.'" />';
	$form .= '<input type="hidden" name="action" value="move_to" />';
	$form .= '<div class="control-group">
				<label>
					<span class="form_required">*</span>'.get_lang('Select').'
				</label>
				<div class="controls">';
	$form .= ' <select name="move_to_id">';

	//group documents cannot be uploaded in the root
	if ($group_dir == '') {
		if ($curdirpath != '/') {
			//$form .= '<option value="0">/ ('.get_lang('Root').')</option>';
		}
		if (is_array($folders)) {
			foreach ($folders as $fid => $folder) {
				//you cannot move a file to:
				//1. current directory
				//2. inside the folder you want to move
				//3. inside a subfolder of the folder you want to move
				if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
					$form .= '<option value="'.$fid.'">'.$folder.'</option>';
				}
			}
		}
	} else {
		if ($curdirpath != '/') {
			$form .= '<option value="0">/ ('.get_lang('Root').')</option>';
		}
		foreach ($folders as $fid => $folder) {
			if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
				//cannot copy dir into his own subdir
				$display_folder = substr($folder, strlen($group_dir));
				$display_folder = ($display_folder == '') ? '/ ('.get_lang('Root').')' : $display_folder;
				$form .= '<option value="'.$fid.'">'.$display_folder.'</option>'."\n";
			}
		}
	}

	$form .= '</select>';
	$form .= '	</div>
			</div>';
	$form .= '<div class="control-group">
					<div class="controls">
						<button type="submit" class="save" name="move_file_submit">'.get_lang('MoveFile').'</button>
					</div>
				</div>';
	$form .= '</form>';
	$form .= '<div style="clear: both; margin-bottom: 10px;"></div>';
	return $form;
}

/**
 * Checks if the first given directory exists as a subdir of the second given directory
 * This function should now be deprecated by Security::check_abs_path()
 * @param	string	Subdir
 * @param	string	Base dir
 * @return	integer	-1 on error, 0 if not subdir, 1 if subdir
 */
// TODO: This function is a candidate for removal, it is not used anywhere.
function is_subdir_of($subdir, $basedir)
{
	if (empty($subdir) or empty($basedir)) {
		return -1;
	}
	if (substr($basedir, -1, 1) != '/') {
		$basedir = $basedir.'/';
	}
	if (substr($subdir, 0, 1) == '/') {
		$subdir = substr($subdir, 1);
	}
	return is_dir($basedir.$subdir) ? 1 : 0;
}

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 * (we could use unique_name() here...)
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 * @author Yannick Warnier <ywarnier@beeznest.org> Adaptation for work tool
 * @param	string	Base work dir (.../work)
 * @param 	string $desiredDirName complete path of the desired name
 * @return 	string actual directory name if it succeeds, boolean false otherwise
 */
function create_unexisting_work_directory($base_work_dir, $desired_dir_name)
{
	$nb = '';
	$base_work_dir = (substr($base_work_dir, -1, 1) == '/' ? $base_work_dir : $base_work_dir.'/');
	while (file_exists($base_work_dir.$desired_dir_name.$nb)) {
		$nb += 1;
	}
	if (@mkdir($base_work_dir.$desired_dir_name.$nb, api_get_permissions_for_new_directories())) {
		return $desired_dir_name.$nb;
	} else {
		return false;
	}
}

/**
 * Delete a work-tool directory
 * @param	string	Base "work" directory for this course as /var/www/chamilo/courses/ABCD/work/
 * @param	string	The directory name as the bit after "work/", without trailing slash
 * @return	integer	-1 on error
 */
function deleteDirWork($id)
{
    $locked = api_resource_is_locked_by_gradebook($id, LINK_STUDENTPUBLICATION);

    if ($locked == true) {
        Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
        return false;
    }

    $_course = api_get_course_info();
    $id = intval($id);
    $work_data = get_work_data_by_id($id);

    if (empty($work_data)) {
        return false;
    }

    $base_work_dir = api_get_path(SYS_COURSE_PATH) .$_course['path'].'/work';
    $work_data_url = $base_work_dir.$work_data['url'];
	$check = Security::check_abs_path($work_data_url.'/', $base_work_dir.'/');

	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $TSTDPUBASG = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
    $t_agenda   = Database::get_course_table(TABLE_AGENDA);

	$course_id = api_get_course_int_id();

	if (!empty($work_data['url'])) {

        if ($check) {

            // Deleting all contents inside the folder
            $sql = "UPDATE $table SET active = 2 WHERE c_id = $course_id AND filetype = 'folder' AND id = $id";
            Database::query($sql);

            $sql = "UPDATE $table SET active = 2 WHERE c_id = $course_id AND parent_id = $id";
            Database::query($sql);

            require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
    		$new_dir = $work_data_url.'_DELETED_'.$id;

    		if (api_get_setting('permanently_remove_deleted_files') == 'true'){
    			my_delete($work_data_url);
    		} else {
    			if (file_exists($work_data_url)) {
    				rename($work_data_url, $new_dir);
    			}
    		}

            // Gets calendar_id from student_publication_assigment
            $sql = "SELECT add_to_calendar FROM $TSTDPUBASG
                    WHERE c_id = $course_id AND publication_id = $id";
            $res = Database::query($sql);
            $calendar_id = Database::fetch_row($res);

            // delete from agenda if it exists
            if (!empty($calendar_id[0])) {
                $sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id = '".$calendar_id[0]."'";
                Database::query($sql);
            }
            $sql = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id = $id";
            Database::query($sql);

            $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3 , $id, api_get_session_id());
            $link_id = $link_info['id'];
            if ($link_info !== false) {
                remove_resource_from_course_gradebook($link_id);
            }
            return true;
        }
	}
}

/**
 * Get the path of a document in the student_publication table (path relative to the course directory)
 * @param	integer	Element ID
 * @return	string	Path (or -1 on error)
 */
function get_work_path($id)
{
	$table 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$course_id 	= api_get_course_int_id();
	$sql 		= 'SELECT url FROM '.$table.' WHERE c_id = '.$course_id.' AND id='.intval($id);
	$res 		= Database::query($sql);
	if (Database::num_rows($res)) {
		$row = Database::fetch_array($res);
		return $row['url'];
	}
	return -1;
}

/**
 * Update the url of a work in the student_publication table
 * @param	integer	ID of the work to update
 * @param	string	Destination directory where the work has been moved (must end with a '/')
 * @return	-1 on error, sql query result on success
 */
function updateWorkUrl($id, $new_path, $parent_id)
{
	if (empty($id)) return -1;
	$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$course_id = api_get_course_int_id();
    $id = intval($id);
    $parent_id = intval($parent_id);

	$sql = "SELECT * FROM $table WHERE c_id = $course_id AND id = $id";
	$res = Database::query($sql);
	if (Database::num_rows($res) != 1) {
		return -1;
	} else {
		$row = Database::fetch_array($res);
		$filename = basename($row['url']);
		$new_url = $new_path.$filename;
        $new_url = Database::escape_string($new_url);
        $sql2 = "UPDATE $table SET url = '$new_url', parent_id = '$parent_id' WHERE c_id = $course_id AND id = $id";
		$res2 = Database::query($sql2);
		return $res2;
	}
}

/**
 * Update the url of a dir in the student_publication table
 * @param   array work original data
 * @param   string new path
 * @return bool
 */
function updateDirName($work_data, $newPath)
{
	$course_id = $work_data['c_id'];
    $courseInfo = api_get_course_info_by_id($course_id);

	$work_id = intval($work_data['id']);
    $oldPath = $work_data['url'];
    $path  = $work_data['url'];
    $originalNewPath = Database::escape_string($newPath);
    $newPath = Database::escape_string($newPath);
    $newPath = replace_dangerous_char($newPath);
    $newPath = disable_dangerous_file($newPath);

    if ($oldPath == '/'.$newPath) {
        return true;
    }

	if (!empty($newPath)) {
        $base_work_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work';
		my_rename($base_work_dir.$oldPath, $newPath);
		$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

		//update all the files in the other directories according with the next query
		$sql = "SELECT id, url FROM $table WHERE c_id = $course_id AND parent_id = $work_id";
		$result = Database::query($sql);
        $work_len = strlen('work/'.$path);

		while ($work = Database :: fetch_array($result)) {
			$new_dir = $work['url'];
			$name_with_directory = substr($new_dir, $work_len, strlen($new_dir));
            $name = Database::escape_string('work/'.$newPath.'/'.$name_with_directory);
			$sql = 'UPDATE '.$table.' SET url= "'.$name.'" WHERE c_id = '.$course_id.' AND id = '.$work['id'];
			Database::query($sql);
		}

        $sql = "UPDATE $table SET url= '/".$newPath."', title = '".$originalNewPath."'
                WHERE c_id = $course_id AND id = $work_id";
        Database::query($sql);
	}
}

/**
 * Return an array with all the folder's ids that are in the given path
 * @param	string Path of the directory
 * @return	array The list of ids of all the directories in the path
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */

function get_parent_directories($id)
{
	$course_id = api_get_course_int_id();
	$work_table      = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $id = intval($id);
    $sql = "SELECT id FROM $work_table WHERE c_id = $course_id AND parent_id = $id";
	$result = Database::query($sql);
    $list_id = array();
    if (Database::num_rows($result)) {
	   while ($row = Database::fetch_array($result)) {
		  $list_id[] = $row['id'];
	   }
    }
	return $list_id;
}

/**
 * Transform an all directory structure (only directories) in an array
 * @param	string path of the directory
 * @return	array the directory structure into an array
 * @author 	Julio Montoya Dokeos
 * @version April 2008
 */
function directory_to_array($directory)
{
	$array_items = array();
	if ($handle = @opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if (is_dir($directory. '/' . $file)) {
					$array_items = array_merge($array_items, directory_to_array($directory. '/' . $file));
					$file = $directory . '/' . $file;
					$array_items[] = preg_replace("/\/\//si", '/', $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}

/**
 * Insert into the DB of the course all the directories
 * @param	string path of the /work directory of the course
 * @return	-1 on error, sql query result on success
 * @author 	Julio Montoya
 * @version April 2008
 * @param string $base_work_dir
 */

function insert_all_directory_in_course_table($base_work_dir)
{
	$dir_to_array = directory_to_array($base_work_dir, true);
	$only_dir = array();

	for ($i = 0; $i < count($dir_to_array); $i++) {
		$only_dir[] = substr($dir_to_array[$i], strlen($base_work_dir), strlen($dir_to_array[$i]));
	}
	$course_id = api_get_course_int_id();
    $group_id  = api_get_group_id();

	for($i = 0; $i < count($only_dir); $i++) {
		global $work_table;
        $url = Database::escape_string($only_dir[$i]);
		$sql = "INSERT INTO " . $work_table . " SET
               c_id 		= '$course_id',
               url 			= '".$url."',
               title        = '',
               description 	= '',
               author      	= '',
               active		= '0',
               accepted		= '1',
               filetype		= 'folder',
               post_group_id = '".$group_id."',
               sent_date	= '0000-00-00 00:00:00' ";
		Database::query($sql);
	}
}

/**
 * This function displays the number of files contained in a directory
 *
 * @param	string the path of the directory
 * @param	boolean true if we want the total quantity of files include in others child directorys , false only  files in the directory
 * @return	array the first element is an integer with the number of files in the folder, the second element is the number of directories
 * @author 	Julio Montoya
 * @version	April 2008
 */
function count_dir($path_dir, $recurse)
{
	$count = 0;
	$count_dir = 0;
	$d = dir($path_dir);
	while ($entry = $d->Read()) {
		if (!(($entry == '..') || ($entry == '.'))) {
			if (is_dir($path_dir.'/'.$entry)) {
				$count_dir++;
				if ($recurse) {
					$count += count_dir($path_dir . '/' . $entry, $recurse);
				}
			} else {
				$count++;
			}
		}
	}
	$return_array = array();
	$return_array[] = $count;
	$return_array[] = $count_dir;
	return $return_array;
}

/**
 * returns all the javascript that is required for easily
 * validation when you create a work
 * this goes into the $htmlHeadXtra[] array
 */
function to_javascript_work()
{
    $origin = isset($_REQUEST['origin']) && !empty($_REQUEST['origin']) ? api_get_tools_lists($_REQUEST['origin']) : '';

	$js = '<script>
			function plus() {
				if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif', get_lang('Hide', ''), array('style' => 'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif', get_lang('Show', ''), array('style' => 'vertical-align:middle')).'&nbsp;'.addslashes(get_lang('AdvancedParameters', '')).'\';
				}
			}

			function updateDocumentTitle(value) {
				var temp = value.indexOf("/");
				//linux path
				if(temp!=-1){
					var temp=value.split("/");
				} else {
					var temp=value.split("\\\");
				}
				document.getElementById("file_upload").value=temp[temp.length-1];
				$("#contains_file_id").attr("value", 1);
			}

			function checkDate(month, day, year) {
			  var monthLength =
			    new Array(31,28,31,30,31,30,31,31,30,31,30,31);

			  if (!day || !month || !year)
			    return false;

			  // check for bisestile year
			  if (year/4 == parseInt(year/4))
			    monthLength[1] = 29;

			  if (month < 1 || month > 12)
			    return false;

			  if (day > monthLength[month-1])
			    return false;

			  return true;
			}

			function mktime() {

			    var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
			    d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);

			    var dateManip = {
			        0: function(tt){ return d.setHours(tt); },
			        1: function(tt){ return d.setMinutes(tt); },
			        2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
			        3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
			        4: function(tt){ return d.setDate(tt+mb); },
			        5: function(tt){ return d.setYear(tt+ma); }
			    };

			    for( i = 0; i < argc; i++ ){
			        no = parseInt(argv[i]*1);
			        if (isNaN(no)) {
			            return false;
			        } else {
			            // arg is number, lets manipulate date object
			            if(!dateManip[i](no)){
			                // failed
			                return false;
			            }
			        }
			    }
			    return Math.floor(d.getTime()/1000);
			}

			function validate() {
				var expires_day = document.form1.expires_day.value;
				var expires_month = document.form1.expires_month.value;
				var expires_year = document.form1.expires_year.value;
				var expires_hour = document.form1.expires_hour.value;
				var expires_minute = document.form1.expires_minute.value;
				var expires_date = mktime(expires_hour,expires_minute,0,expires_month,expires_day,expires_year)

				var ends_day = document.form1.ends_day.value;
				var ends_month = document.form1.ends_month.value;
				var ends_year = document.form1.ends_year.value;
				var ends_hour = document.form1.ends_hour.value;
				var ends_minute = document.form1.ends_minute.value;
				var ends_date = mktime(ends_hour,ends_minute,0,ends_month,ends_day,ends_year);

				var new_dir = document.form1.new_dir.value;

				msg_id1 = document.getElementById("msg_error1");
				msg_id2 = document.getElementById("msg_error2");
				msg_id3 = document.getElementById("msg_error3");
				msg_id4 = document.getElementById("msg_error4");
				msg_id5	= document.getElementById("msg_error_weight");

				if (new_dir=="") {
					msg_id1.style.display ="block";
					msg_id1.innerHTML="'.get_lang('FieldRequired', '').'";
					msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				} else if(document.form1.enableExpiryDate.checked && document.form1.enableEndDate.checked && expires_date > ends_date) {
						msg_id2.style.display ="block";
						msg_id2.innerHTML="'.get_lang('EndDateCannotBeBeforeTheExpireDate', '').'";
						msg_id1.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				} else if (!checkDate(expires_month,expires_day,expires_year)) {
					msg_id3.style.display ="block";
					msg_id3.innerHTML="'.get_lang('InvalidDate', '').'";
					msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id4.innerHTML="";msg_id5.innerHTML="";
				} else if (!checkDate(ends_month,ends_day,ends_year)) {
					msg_id4.style.display ="block";
					msg_id4.innerHTML="'.get_lang('InvalidDate', '').'";
					msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id5.innerHTML="";
				} else {
					if (document.form1.make_calification.checked) {
					 	var weight = document.form1.weight.value;
						 	if(weight=="") {
								msg_id5.style.display ="block";
								msg_id5.innerHTML="'.get_lang('WeightNecessary', '').'";
								msg_id1.innerHTML="";msg_id2.innerHTML="";msg_id3.innerHTML="";msg_id4.innerHTML="";
							    return false;
                        }
				 	}
					document.form1.action = "work.php?'.  api_get_cidreq().'&origin='.$origin.'&gradebook='.(empty($_GET['gradebook'])?'':'view').'";
					document.form1.submit();
				}
			}
			</script>';
    return $js;
}

/**
 * Gets the id of a student publication with a given path
 * @param string $path
 * @return true if is found / false if not found
 */
// TODO: The name of this function does not fit with the kind of information it returns. Maybe check_work_id() or is_work_id()?
function get_work_id($path)
{
	$TBL_STUDENT_PUBLICATION = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$TBL_PROP_TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$course_id = api_get_course_int_id();
    $path = Database::escape_string($path);

	if (api_is_allowed_to_edit()) {
		$sql = "SELECT work.id FROM $TBL_STUDENT_PUBLICATION AS work, $TBL_PROP_TABLE AS props
				WHERE
				    props.c_id = $course_id AND
				    work.c_id = $course_id AND
				    props.tool='work' AND
				    work.id=props.ref AND
				    work.url LIKE 'work/".$path."%' AND
				    work.filetype='file' AND
				    props.visibility<>'2'";
	} else {
		$sql = "SELECT work.id FROM $TBL_STUDENT_PUBLICATION AS work,$TBL_PROP_TABLE AS props
				WHERE
                    props.c_id = $course_id AND
                    work.c_id = $course_id AND
                    props.tool='work' AND
                    work.id=props.ref AND
                    work.url LIKE 'work/".$path."%' AND
                    work.filetype='file' AND
                    props.visibility<>'2' AND
                    props.lastedit_user_id = '".api_get_user_id()."'";
	}
	$result = Database::query($sql);
	$num_rows = Database::num_rows($result);

	if ($result && $num_rows > 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * @param int $work_id
 * @param int $onlyMeUserId show only my works
 * @param int $notMeUserId show works from everyone except me
 * @return int
 */
function get_count_work($work_id, $onlyMeUserId = null, $notMeUserId = null)
{
    $work_table 	 = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $iprop_table     = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $user_table      = Database::get_main_table(TABLE_MAIN_USER);

    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    $session_id     = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);

    $course_id      = api_get_course_int_id();
    $group_id       = api_get_group_id();
    $course_info    = api_get_course_info(api_get_course_id());
    $work_id       = intval($work_id);

    if (!empty($group_id)) {
        $extra_conditions = " work.post_group_id = '".intval($group_id)."' "; // set to select only messages posted by the user's group
    } else {
        $extra_conditions = " work.post_group_id = '0' ";
    }

    if ($is_allowed_to_edit) {
        $extra_conditions .= ' AND work.active IN (0, 1) ';
    } else {
        $extra_conditions .= ' AND work.active = 1 AND accepted = 1';
        if (isset($course_info['show_score']) && $course_info['show_score'] == 1) {
            $extra_conditions .= " AND work.user_id = ".api_get_user_id()." ";
        } else {
            $extra_conditions .= '';
        }
    }

    $extra_conditions .= " AND parent_id  = ".$work_id."  ";

    $where_condition = null;

    if (!empty($notMeUserId)) {
        $where_condition .= " AND u.user_id <> ".intval($notMeUserId);
    }

    if (!empty($onlyMeUserId)) {
        $where_condition .= " AND u.user_id =  ".intval($onlyMeUserId);
    }

    $sql = "SELECT count(*) as count ".
           " FROM ".$iprop_table." prop INNER JOIN ".$work_table." work ".
           " ON (prop.ref=work.id AND prop.c_id = $course_id ".
           " AND prop.tool='work' AND work.active = 1 ".
           " AND prop.visibility <> 2 AND work.c_id = $course_id ) ".
           "   INNER JOIN $user_table u  ON (work.user_id = u.user_id) ".
           " WHERE $extra_conditions $where_condition $condition_session ";
    $result = Database::query($sql);

    $users_with_work = 0;
    if (Database::num_rows($result)) {
        $result = Database::fetch_array($result);
        $users_with_work = $result['count'];
    }
    return $users_with_work;
}

/**
 * @param int $start
 * @param int $limit
 * @param int $column
 * @param string $direction
 * @param int $work_id
 * @param array $where_condition
 * @param int $studentId
 * @return array
 */
function get_work_user_list($start, $limit, $column, $direction, $work_id, $where_condition, $studentId = null)
{
    $workTable         = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment  = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $course_id          = api_get_course_int_id();
    $session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $group_id           = api_get_group_id();

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $column = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start = intval($start);
    $limit = intval($limit);

    // Get list from database

    if (!empty($group_id)) {
        $group_query = " WHERE w.c_id = $course_id AND post_group_id = '".$group_id."' ";
        $subdirs_query = "AND parent_id = 0";
    } else {
        $group_query = " WHERE w.c_id = $course_id AND post_group_id = '0' ";
        $subdirs_query = "AND parent_id = 0";
    }

    $active_condition = ' AND active IN (1, 0)';

        if ($is_allowed_to_edit) {
            $extra_conditions .= ' AND work.active IN (0, 1) ';
        } else {

            if (isset($course_info['show_score']) &&  $course_info['show_score'] == 1) {
                $extra_conditions .= " AND (u.user_id = ".api_get_user_id()." AND work.active IN (0, 1) OR work.active = 1) ";
            } else {
                $extra_conditions .= ' AND work.active = 1 ';
            }
        }

    $sql .= " ORDER BY $column $direction ";
    $sql .= " LIMIT $start, $limit";

    $result = Database::query($sql);

    if ($getCount) {
        $row = Database::fetch_array($result);
        return $row['count'];
    }

    $works = array();
    $url = api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq();
    $urlOthers = api_get_path(WEB_CODE_PATH).'work/work_list_others.php?'.api_get_cidreq().'&id=';
    while ($work = Database::fetch_array($result, 'ASSOC')) {

        $isSubscribed = userIsSubscribedToWork(api_get_user_id(), $work['id'], $course_id);

        if ($isSubscribed == false) {
            continue;
        }

        $sql = "SELECT $select
                FROM $work_condition  $user_condition
                WHERE  $extra_conditions $where_condition $condition_session ";

}

/**
 * @param int $start
 * @param int $limit
 * @param string $column
 * @param string $direction
 * @param string $where_condition
 * @param bool $getCount
 * @return array
 */
function getWorkListTeacher($start, $limit, $column, $direction, $where_condition, $getCount = false)
{
    $workTable         = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workTableAssignment  = Database::get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

    $course_id          = api_get_course_int_id();
    $session_id         = api_get_session_id();
    $condition_session  = api_get_session_condition($session_id);
    $group_id           = api_get_group_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $column         = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start          = intval($start);
    $limit          = intval($limit);

    // Get list from database
    if ($is_allowed_to_edit) {
        $active_condition = ' active IN (0, 1)';
        if ($getCount) {
            $select = " SELECT count(w.id) as count";
        } else {
            $select = " SELECT w.*, a.expires_on, expires_on, ends_on, enable_qualification ";
        }
        $sql = " $select
                FROM $workTable w
                LEFT JOIN $workTableAssignment a ON (a.publication_id = w.id AND a.c_id = w.c_id)
                WHERE w.c_id = $course_id
                    $condition_session AND
                    $active_condition AND
                    (parent_id = 0) $where_condition ";

        $sql .= " AND post_group_id = '".$group_id."' ";

        $sql .= " ORDER BY $column $direction ";
        $sql .= " LIMIT $start, $limit";

        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);
            return $row['count'];
        }
        $works = array();
        $url = api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq();
        while ($work = Database::fetch_array($result, 'ASSOC')) {
            $workId = $work['id'];
            $work['type'] = Display::return_icon('work.png');
            $work['expires_on'] = $work['expires_on']  == '0000-00-00 00:00:00' ? null : api_get_local_time($work['expires_on']);
            $work['ends_on'] = $work['ends_on']  == '0000-00-00 00:00:00' ? null : api_get_local_time($work['ends_on']);

            if (empty($work['title'])) {
                $work['title'] = basename($work['url']);
            }
            $work['title'] = Display::url($work['title'], $url.'&id='.$workId);

            $work['title'] .= ' '.Display::label(get_count_work($work['id']), 'success');
            $work['sent_date'] = date_to_str_ago($work['sent_date']).' <br />'.api_get_local_time($work['sent_date']);

            $editLink = Display::url(
                Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
                api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq()
            );

            $downloadLink = Display::url(
                Display::return_icon('save_pack.png', get_lang('Save'), array(), ICON_SIZE_SMALL),
                api_get_path(WEB_CODE_PATH).'work/downloadfolder.inc.php?id='.$workId.'&'.api_get_cidreq()
            );
            $deleteUrl = api_get_path(WEB_CODE_PATH).'work/work.php?id='.$workId.'&action=delete_dir&'.api_get_cidreq();
            $deleteLink = '<a href="#" onclick="showConfirmationPopup(this, \''.$deleteUrl.'\' ) " >'.
                Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';

            $work['actions'] = $downloadLink.$editLink.$deleteLink;
            $works[] = $work;
        }
    }
    return $works;
}

/**
 * @param int $start
 * @param int $limit
 * @param string $column
 * @param string $direction
 * @param int $workId
 * @param int $studentId
 * @param string $whereCondition
 * @param bool $getCount
 * @return array
 */
function get_work_user_list_from_documents(
    $start,
    $limit,
    $column,
    $direction,
    $workId,
    $studentId = null,
    $whereCondition,
    $getCount = false
) {

    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }

    if ($getCount) {
        $select1 = " SELECT count(u.user_id) as count ";
        $select2 = " SELECT count(u.user_id) as count ";
    } else {
        $select1 = " SELECT DISTINCT u.firstname, u.lastname, u.user_id, w.title, w.parent_id, w.document_id document_id, w.id, qualification, qualificator_id";
        $select2 = " SELECT DISTINCT u.firstname, u.lastname, u.user_id, d.title, w.parent_id, d.id document_id, 0, 0, 0";
    }

    $documentTable = Database::get_course_table(TABLE_DOCUMENT);
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $workRelDocument = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $userTable = Database::get_main_table(TABLE_MAIN_USER);

    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();

    if (empty($studentId)) {
        $studentId = api_get_user_id();
    }
    $studentId = intval($studentId);
    $workId = intval($workId);

    $userCondition = " AND u.user_id = $studentId ";
    $sessionCondition = " AND w.session_id = $sessionId ";
    $workCondition = " AND w_rel.work_id = $workId";
    $workParentCondition  = " AND w.parent_id = $workId";

    $sql = "    (
                    $select1 FROM $userTable u
                    INNER JOIN $workTable w ON (u.user_id = w.user_id AND w.active = 1 AND w.filetype = 'file')
                    WHERE
                        w.c_id = $courseId
                        $userCondition
                        $sessionCondition
                        $whereCondition
                        $workParentCondition

                ) UNION (
                    $select2 FROM $workTable w
                    INNER JOIN $workRelDocument w_rel ON (w_rel.work_id = w.id AND w.active = 1)
                    INNER JOIN $documentTable d ON (w_rel.document_id = d.id AND d.c_id = w.c_id)
                    INNER JOIN $userTable u ON (u.user_id = $studentId)
                    WHERE
                        w.c_id = $courseId
                        $workCondition
                        $sessionCondition AND
                        d.id NOT IN
                            (SELECT w.document_id id FROM $workTable w
                            WHERE
                                user_id = $studentId AND
                                c_id = $courseId AND
                                filetype = 'file' AND
                                active = 1
                                $sessionCondition
                                $workParentCondition
                            )
                )
            ";

    $start = intval($start);
    $limit = intval($limit);

    $direction = in_array(strtolower($direction), array('desc', 'asc')) ? $direction : 'desc';
    $column = Database::escape_string($column);

    if ($getCount) {
        $result = Database::query($sql);
        $result = Database::fetch_array($result);
        return $result['count'];
    }

    $sql .= " ORDER BY $column $direction";
    $sql .= " LIMIT $start, $limit";

    $result = Database::query($sql);

    $currentUserId = api_get_user_id();
    $work_data = get_work_data_by_id($workId);

    $qualificationExists = false;
    if (!empty($work_data['qualification']) && intval($work_data['qualification']) > 0) {
        $qualificationExists = true;
    }

    $workList = array();

    $urlAdd = api_get_path(WEB_CODE_PATH).'work/upload_from_template.php?'.api_get_cidreq();
    $urlEdit = api_get_path(WEB_CODE_PATH).'work/edit.php?'.api_get_cidreq();
    $urlDelete = api_get_path(WEB_CODE_PATH).'work/work_list.php?action=delete&'.api_get_cidreq();
    $urlView = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq();

    $editIcon = Display::return_icon('edit.png', get_lang('Edit'));
    $addIcon = Display::return_icon('add.png', get_lang('Add'));
    $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'));
    $viewIcon = Display::return_icon('default.png', get_lang('View'));

    $allowEdition = api_get_course_setting('student_delete_own_publication');
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $userId = $row['user_id'];
        $documentId = $row['document_id'];
        $itemId = $row['id'];

        $addLinkShowed = false;

        if (empty($documentId)) {
            $url = $urlEdit.'&item_id='.$row['id'].'&id='.$workId;
            $editLink = Display::url($editIcon, $url);
            if ($allowEdition == false) {
                $editLink = null;
            }
        } else {
            $documentToWork = getDocumentToWorkPerUser($documentId, $workId, $courseId, $sessionId, $userId);
            if (empty($documentToWork)) {
                $url = $urlAdd.'&document_id='.$documentId.'&id='.$workId;
                $editLink = Display::url($addIcon, $url);
                $addLinkShowed = true;
            } else {

                $row['title'] = $documentToWork['title'];
                $row['sent_date'] = $documentToWork['sent_date'];
                $newWorkId = $documentToWork['id'];
                $url = $urlEdit.'&item_id='.$newWorkId.'&id='.$workId;
                $editLink = Display::url($editIcon, $url);

                if ($allowEdition == false) {
                    $editLink = null;
                }
            }
        }

        if ($allowEdition && !empty($itemId)) {
            $deleteLink  = Display::url($deleteIcon, $urlDelete.'&item_id='.$itemId.'&id='.$workId);
        } else {
            $deleteLink = null;
        }

        $viewLink = null;

        if (!empty($itemId)) {
            $viewLink = Display::url($viewIcon, $urlView.'&id='.$itemId);
        }

        $row['type'] = build_document_icon_tag('file', $row['file']);

        if ($qualificationExists) {
            if (empty($row['qualificator_id'])) {
                $status = Display::label(get_lang('NotRevised'), 'warning');
            } else {
                $status = Display::label(get_lang('Revised'), 'success');
            }
            $row['qualificator_id'] = $status;
        }

        if (!empty($row['qualification'])) {
            $row['qualification'] = Display::label($row['qualification'], 'info');
        }

        if (!empty($row['sent_date'])) {
            $row['sent_date'] = api_get_local_time($row['sent_date']);
        }

        if ($userId == $currentUserId) {
            $row['actions'] = $viewLink.$editLink.$deleteLink;
        }

        if ($addLinkShowed) {
            $row['qualification'] = '';
            $row['qualificator_id'] = '';
        }

        $workList[] = $row;
    }

    return $workList;
}

/**
 * @param int $start
 * @param int $limit
 * @param int $column
 * @param string $direction
 * @param int $work_id
 * @param array $where_condition
 * @param int $studentId
 * @param bool $getCount
 * @return array
 */
function get_work_user_list($start, $limit, $column, $direction, $work_id, $where_condition, $studentId = null, $getCount = false)
{
    $work_table         = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $iprop_table        = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $user_table         = Database::get_main_table(TABLE_MAIN_USER);

    $session_id     = api_get_session_id();
    $course_id      = api_get_course_int_id();
    $group_id       = api_get_group_id();
    $course_info    = api_get_course_info(api_get_course_id());

    $work_id       = intval($work_id);
    $column         = !empty($column) ? Database::escape_string($column) : 'sent_date';
    $start          = intval($start);
    $limit          = intval($limit);

    if (!in_array($direction, array('asc','desc'))) {
        $direction = 'desc';
    }

    $work_data          = get_work_data_by_id($work_id);
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
    $condition_session  = api_get_session_condition($session_id);

    $locked = api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION);

    if (!empty($work_data)) {

        if (!empty($group_id)) {
            $extra_conditions = " work.post_group_id = '".intval($group_id)."' "; // set to select only messages posted by the user's group
        } else {
            $extra_conditions = " work.post_group_id = '0' ";
        }

        if ($is_allowed_to_edit) {
            $extra_conditions .= ' AND work.active IN (0, 1) ';
        } else {
            if (isset($course_info['show_score']) &&  $course_info['show_score'] == 1) {
                $extra_conditions .= " AND (u.user_id = ".api_get_user_id()." AND work.active IN (0, 1) OR work.active = 1) ";
            } else {
                $extra_conditions .= ' AND work.active = 1 ';
            }
        }

        $extra_conditions .= " AND parent_id  = ".$work_id."  ";

        $select = 'SELECT DISTINCT u.user_id, work.id as id, title as title, description, url, sent_date, contains_file, has_properties, view_properties,
                    qualification, weight, allow_text_assignment, u.firstname, u.lastname, u.username, parent_id, accepted, qualificator_id';

        if ($getCount) {
            $select = "SELECT DISTINCT count(u.user_id) as count ";
        }

        $user_condition = "INNER JOIN $user_table u  ON (work.user_id = u.user_id) ";
        $work_condition = "$iprop_table prop INNER JOIN $work_table work ON (prop.ref = work.id AND prop.c_id = $course_id AND work.c_id = $course_id ) ";

        $work_assignment = get_work_assignment_by_id($work_id);

        if (!empty($studentId)) {
            $where_condition.= " AND u.user_id = ".intval($studentId);
        }

        $sql = " $select
                FROM $work_condition  $user_condition
                WHERE $extra_conditions $where_condition $condition_session ";
        $sql .= " ORDER BY $column $direction ";
        $sql .= " LIMIT $start, $limit";

        $result = Database::query($sql);
        $works = array();

        if ($getCount) {
            $work = Database::fetch_array($result, 'ASSOC');
            return $work['count'];
        }

        while ($work = Database::fetch_array($result, 'ASSOC')) {
            $item_id = $work['id'];

            // Get the author ID for that document from the item_property table
			$is_author  = false;
            $can_read   = false;

            $owner_id = $work['user_id'];

            /* Because a bug found when saving items using the api_item_property_update()
               the field $item_property_data['insert_user_id'] is not reliable. */

			// $item_property_data = api_get_item_property_info(api_get_course_int_id(), 'work', $item_id, api_get_session_id());
			//if (!$is_allowed_to_edit && $item_property_data['insert_user_id'] == api_get_user_id()) {
            if (!$is_allowed_to_edit && $owner_id == api_get_user_id()) {
				$is_author = true;
			}

            if ($course_info['show_score'] == 0) {
                $can_read = true;
            }

            if ($work['accepted'] == '0') {
                $class = 'invisible';
            } else {
                $class = '';
            }

            $qualification_exists = false;
            if (!empty($work_data['qualification']) && intval($work_data['qualification']) > 0) {
                $qualification_exists = true;
            }

            $qualification_string = '';

            if ($qualification_exists) {
                if ($work['qualification'] == '') {
                    $qualification_string = Display::label('-');
                } else {
                    $qualification_string = Display::label($work['qualification'], 'info');
                }
            }

            $add_string = '';
            $time_expires = api_strtotime($work_assignment['expires_on'], 'UTC');

            if (!empty($work_assignment['expires_on']) && $work_assignment['expires_on'] != '0000-00-00 00:00:00' && $time_expires && ($time_expires < api_strtotime($work['sent_date'], 'UTC'))) {
                $add_string = Display::label(get_lang('Expired'), 'important');
            }

            if (
                ($can_read && $work['accepted'] == '1') ||
                ($is_author && in_array($work['accepted'], array('1','0'))) ||
                $is_allowed_to_edit
            ) {

                // Firstname, lastname, username
                $work['firstname'] = Display::div($work['firstname'], array('class' => $class));
                $work['lastname'] = Display::div($work['lastname'], array('class' => $class));

                if (strlen($work['title']) > 30) {
                    $short_title = substr($work['title'], 0, 27).'...';
                    $work['title'] = Display::span($short_title, array('class' => $class, 'title' => $work['title']));
                } else {
                    $work['title'] = Display::div($work['title'], array('class' => $class));
                }

                // Type.
                $work['type'] = build_document_icon_tag('file', $work['file']);

                // File name.
                $link_to_download = null;

                if ($work['contains_file']) {
                    $link_to_download = '<a href="download.php?id='.$item_id.'">'.Display::return_icon('save.png', get_lang('Save'),array(), ICON_SIZE_SMALL).'</a> ';
                } else {
                   //$link_to_download = '<a href="view.php?id='.$item_id.'">'.Display::return_icon('save_na.png', get_lang('Save'),array(), ICON_SIZE_SMALL).'</a> ';
                }

                $send_to = Portfolio::share('work', $work['id'],  array('style' => 'white-space:nowrap;'));

                $work['qualification'] = $qualification_string;

                // Date.
                $work_date = api_convert_and_format_date($work['sent_date']);
                $work['sent_date'] = date_to_str_ago(api_get_local_time($work['sent_date'])).' '.$add_string.'<br />'.$work_date;

                // Actions.
                $url = api_get_path(WEB_CODE_PATH).'work/';
                $action = '';
                if ($is_allowed_to_edit) {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.
                        Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a> ';

                    if ($locked) {
                        if ($qualification_exists) {
                            $action .= Display::return_icon('rate_work_na.png', get_lang('CorrectAndRate'),array(), ICON_SIZE_SMALL);
                        } else {
                            $action .= Display::return_icon('edit_na.png', get_lang('Comment'),array(), ICON_SIZE_SMALL);
                        }
                    } else {
                        if ($qualification_exists) {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'" title="'.get_lang('Edit').'"  >'.
                            Display::return_icon('rate_work.png', get_lang('CorrectAndRate'),array(), ICON_SIZE_SMALL).'</a>';
                        } else {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'&gradebook='.Security::remove_XSS($_GET['gradebook']).'" title="'.get_lang('Modify').'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($work['contains_file']) {
                        if ($locked) {
                            $action .= Display::return_icon('move_na.png', get_lang('Move'),array(), ICON_SIZE_SMALL);
                        } else {
                            $action .= '<a href="'.$url.'work.php?'.api_get_cidreq().'&action=move&item_id='.$item_id.'" title="'.get_lang('Move').'">'.Display::return_icon('move.png', get_lang('Move'),array(), ICON_SIZE_SMALL).'</a>';
                        }
                    }

                    if ($work['accepted'] == '1') {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_invisible&item_id='.$item_id.'" title="'.get_lang('Invisible').'" >'.Display::return_icon('visible.png', get_lang('Invisible'),array(), ICON_SIZE_SMALL).'</a>';
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=make_visible&item_id='.$item_id.'" title="'.get_lang('Visible').'" >'.Display::return_icon('invisible.png', get_lang('Visible'),array(), ICON_SIZE_SMALL).'</a> ';
                    }

                    if ($locked) {
                        $action .= Display::return_icon('delete_na.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
                    } else {
                        $action .= '<a href="'.$url.'work_list_all.php?'.api_get_cidreq().'&id='.$work_id.'&action=delete&amp;item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'" >'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
                    }
                } elseif ($is_author && (empty($work['qualificator_id']) || $work['qualificator_id'] == 0)) {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a>';

                    if (api_get_course_setting('student_delete_own_publication') == 1) {
                        if (api_is_allowed_to_session_edit(false, true)) {
                            $action .= '<a href="'.$url.'edit.php?'.api_get_cidreq().'&item_id='.$item_id.'&id='.$work['parent_id'].'&gradebook='.Security::remove_XSS($_GET['gradebook']).'" title="'.get_lang('Modify').'">'.
                                Display::return_icon('edit.png', get_lang('Comment'),array(), ICON_SIZE_SMALL).'</a>';
                        }
                        $action .= ' <a href="'.$url.'work.php?'.api_get_cidreq().'&action=delete&amp;item_id='.$item_id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'"  >'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
                    } else {
                        $action .= Display::return_icon('edit_na.png', get_lang('Modify'),array(), ICON_SIZE_SMALL);
                    }
                } else {
                    $action .= '<a href="'.$url.'view.php?'.api_get_cidreq().'&id='.$item_id.'" title="'.get_lang('View').'">'.Display::return_icon('default.png', get_lang('View'),array(), ICON_SIZE_SMALL).'</a>';
                    $action .= Display::return_icon('edit_na.png', get_lang('Modify'),array(), ICON_SIZE_SMALL);
                }

                // Status.
                if (empty($work['qualificator_id'])) {
                    $qualificator_id = Display::label(get_lang('NotRevised'), 'warning');
                } else {
                    $qualificator_id = Display::label(get_lang('Revised'), 'success');
                }
                $work['qualificator_id'] = $qualificator_id;
                $work['actions'] = $send_to.$link_to_download.$action;
                $works[] = $work;
            }
        }
        return $works;
    }
}

/**
 * Send reminder to users who have not given the task
 *
 * @param int
 * @return array
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 */
function send_reminder_users_without_publication($task_data)
{
	global $_course;
	$task_id = $task_data['id'];
	$task_title = !empty($task_data['title']) ? $task_data['title'] : basename($task_data['url']);
	$subject = '[' . api_get_setting('siteName') . '] ';

	// The body can be as long as you wish, and any combination of text and variables
	$content = get_lang('ReminderToSubmitPendingTask')."\n".get_lang('CourseName').' : '.$_course['name']."\n";
	$content .= get_lang('WorkName').' : '.$task_title."\n";

	$list_users = get_list_users_without_publication($task_id);

    $mails_sent_to = array();
	foreach ($list_users as $user) {
		$name_user = api_get_person_name($user[1], $user[0], null, PERSON_NAME_EMAIL_ADDRESS);
        $dear_line = get_lang('Dear')." ".api_get_person_name($user[1], $user[0]) .", \n\n";
        $body      = $dear_line.$content;
        MessageManager::send_message($user[3], $subject, $body);
        $mails_sent_to[] = $name_user;
	}
    return $mails_sent_to;
}

/**
 * Sends an email to the students of a course when a homework is created
 *
 * @param string course_id
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 * @author Julio Montoya <gugli100@gmail.com> Adding session support - 2011
 */
function send_email_on_homework_creation($course_id)
{
	// Get the students of the course
	$session_id = api_get_session_id();
	if (empty($session_id)) {
		$students = CourseManager::get_student_list_from_course_code($course_id);
	} else {
		$students = CourseManager::get_student_list_from_course_code($course_id, true, $session_id);
	}
	$emailsubject = '[' . api_get_setting('siteName') . '] '.get_lang('HomeworkCreated');
	$currentUser = api_get_user_info(api_get_user_id());
	if (!empty($students)) {
		foreach($students as $student) {
			$user_info = api_get_user_info($student["user_id"]);
			if(!empty($user_info["mail"])) {
				$name_user = api_get_person_name($user_info["firstname"], $user_info["lastname"], null, PERSON_NAME_EMAIL_ADDRESS);
				$emailbody = get_lang('Dear')." ".$name_user.",\n\n";
				$emailbody .= get_lang('HomeworkHasBeenCreatedForTheCourse')." ".$course_id.". "."\n\n".get_lang('PleaseCheckHomeworkPage');
				$emailbody .= "\n\n".api_get_person_name($currentUser["firstname"], $currentUser["lastname"]);
				@api_mail($name_user, $user_info["mail"], $emailsubject, $emailbody, api_get_person_name($currentUser["firstname"], $currentUser["lastname"], null, PERSON_NAME_EMAIL_ADDRESS), $currentUser["mail"]);
			}
		}
	}
}

/**
 * @param string $url
 * @return bool
 */
function is_work_exist_by_url($url) {
	$work_table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$url = Database::escape_string($url);
	$sql = "SELECT id FROM $work_table WHERE url='$url'";
	$result = Database::query($sql);
	if (Database::num_rows($result)> 0) {
		$row = Database::fetch_row($result);
		if (empty($row)) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

/**
 * @param $name
 * @param $values
 * @param string $checked
 * @return string
 */
function make_select($name, $values, $checked = '')
{
	$output = '<select name="'.$name.'" id="'.$name.'">';
	foreach ($values as $key => $value) {
		$output .= '<option value="'.$key.'" '.(($checked==$key) ? 'selected="selected"' : '').'>'.$value.'</option>';
	}
	$output .= '</select>';
	return $output;
}

/**
 * @param $name
 * @param string $checked
 * @param null $label
 * @return string
 */
function make_checkbox($name, $checked = '', $label = null)
{
	$check = '<input id ="'.$name.'" type="checkbox" value="1" name="'.$name.'" '.((!empty($checked))?'checked="checked"':'').'/>';
	if (!empty($label)) {
		$check .="<label for ='$name'>$label</label>";
	}
	return $check;
}

/**
 * @param $prefix
 * @param string $default
 * @return string
 */
function draw_date_picker($prefix, $default = '')
{
	if (empty($default)) {
		$default = api_get_local_time();
	}
	$parts = explode(' ', $default);
	list($d_year, $d_month, $d_day) = explode('-', $parts[0]);
	list($d_hour, $d_minute) = explode(':', $parts[1]);

	$minute = range(10, 59);
	array_unshift($minute, '00', '01', '02', '03', '04', '05', '06', '07', '08', '09');
	$date_form = make_select($prefix.'_day', array_combine(range(1, 31), range(1, 31)), $d_day);
	$date_form .= make_select($prefix.'_month', array_combine(range(1, 12), api_get_months_long()), $d_month);
	$date_form .= make_select($prefix.'_year', array($d_year => $d_year, $d_year + 1 => $d_year + 1), $d_year).'&nbsp;&nbsp;&nbsp;&nbsp;';
	$date_form .= make_select($prefix.'_hour', array_combine(range(0, 23), range(0, 23)), $d_hour).' : ';
	$date_form .= make_select($prefix.'_minute', $minute, $d_minute);
	return $date_form;
}

function get_date_from_select($prefix)
{
	return $_POST[$prefix.'_year'].'-'.two_digits($_POST[$prefix.'_month']).'-'.two_digits($_POST[$prefix.'_day']).' '.two_digits($_POST[$prefix.'_hour']).':'.two_digits($_POST[$prefix.'_minute']).':00';
}

/**
 * Check if a user is the author of the item
 * @param int $item_id
 * @param int  $user_id
 * @return bool
 */
function user_is_author($item_id, $user_id = null)
{
    if (empty($item_id)) {
        return false;
    }

    if (empty($userId)) {
        $userId = api_get_user_id();
    }

    $isAuthor = false;
    $is_allowed_to_edit = api_is_allowed_to_edit();

    if ($is_allowed_to_edit) {
        $isAuthor = true;
    } else {

        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $data = api_get_item_property_info($courseId, 'work', $itemId, $sessionId);
        if ($data['insert_user_id'] == $userId) {
            $isAuthor = true;
        }

        $workData = get_work_data_by_id($itemId);
        if ($workData['user_id'] == $userId) {
            $isAuthor = true;
        }
    }

    if (!$is_author) {
        //api_not_allowed();
        return false;
    }

/**
 * Get list of users who have not given the task
 * @param int
 * @param int
 * @return array
 * @author cvargas
 * @author Julio Montoya <gugli100@gmail.com> Fixing query
 */
function get_list_users_without_publication($task_id, $studentId = null)
{
	$work_table 			 = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$table_course_user 		 = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$table_user 			 = Database::get_main_table(TABLE_MAIN_USER);
	$session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

	//condition for the session
	$session_id    = api_get_session_id();
	$course_id     = api_get_course_int_id();

	$task_id = intval($task_id);

	if ($session_id == 0) {
		$sql = "SELECT user_id as id FROM $work_table WHERE c_id = $course_id AND parent_id='$task_id' AND active = 1";
	} else {
		$sql = "SELECT user_id as id FROM $work_table WHERE c_id = $course_id AND parent_id='$task_id' and session_id='".$session_id."' AND active = 1";
	}

	$result = Database::query($sql);
	$users_with_tasks = array();
	while($row = Database::fetch_array($result)) {
		$users_with_tasks[] = $row['id'];
	}

	if ($session_id == 0) {
		$sql_users = "SELECT cu.user_id, u.lastname, u.firstname, u.email FROM $table_course_user AS cu, $table_user AS u
		              WHERE u.status != 1 and cu.course_code='".api_get_course_id()."' AND u.user_id = cu.user_id";
	} else {
		$sql_users = "SELECT cu.id_user, u.lastname, u.firstname, u.email FROM $session_course_rel_user AS cu, $table_user AS u
		              WHERE u.status != 1 and cu.course_code='".api_get_course_id()."' AND u.user_id = cu.id_user and cu.id_session = '".$session_id."'";
	}

    if (!empty($studentId)) {
        $sql_users.= " AND u.user_id = ".intval($studentId);
    }

    $group_id = api_get_group_id();

    $new_group_user_list = array();

    if ($group_id) {
        $group_user_list = GroupManager::get_subscribed_users($group_id);
        if (!empty($group_user_list)) {
            foreach($group_user_list as $group_user) {
                $new_group_user_list[] = $group_user['user_id'];
            }
        }
    }

	$result_users = Database::query($sql_users);
	$users_without_tasks = array();
	while ($row_users = Database::fetch_row($result_users)) {

		if (in_array($row_users[0], $users_with_tasks)) {
            continue;
        }

		if ($group_id && !in_array($row_users[0], $new_group_user_list)) {
            continue;
        }
        $userId = $row_users[0];
        $row_users[0] = $row_users[1];
        $row_users[1] = $row_users[2];
		$row_users[2] = Display::encrypted_mailto_link($row_users[3]);
        $row_users[3] = $userId;
		$users_without_tasks[] = $row_users;
	}
	return $users_without_tasks;
}

/**
 * Display list of users who have not given the task
 *
 * @param int task id
 * @param int $studentId
 * @return array
 * @author cvargas carlos.vargas@beeznest.com cfasanando, christian.fasanado@beeznest.com
 * @author Julio Montoya <gugli100@gmail.com> Fixes
 */
function display_list_users_without_publication($task_id, $studentId = null)
{
	global $origin;
	$table_header[] = array(get_lang('LastName'), true);
	$table_header[] = array(get_lang('FirstName'), true);
	$table_header[] = array(get_lang('Email'), true);

	$data = get_list_users_without_publication($task_id);

	$sorting_options = array();
	$sorting_options['column'] = 1;
	$paging_options = array();
	$my_params = array();

	if (isset($_GET['curdirpath'])) {
		$my_params['curdirpath'] = Security::remove_XSS($_GET['curdirpath']);
	}
	if (isset($_GET['edit_dir'])) {
		$my_params['edit_dir'] = Security::remove_XSS($_GET['edit_dir']);
	}
	if (isset($_GET['list'])) {
		$my_params['list'] = Security::remove_XSS($_GET['list']);
	}
	$my_params['origin'] = $origin;
    $my_params['id'] = intval($_GET['id']);

	//$column_show
	$column_show[] = 1;
	$column_show[] = 1;
	$column_show[] = 1;
	Display::display_sortable_config_table('work', $table_header, $data, $sorting_options, $paging_options, $my_params, $column_show);
}

// Document to work
/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function addDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id' => $documentId,
        'work_id' => $workId,
        'c_id' => $courseId
    );
    Database::insert($table, $params);
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id = ? and work_id = ? and c_id = ?' => array($documentId, $workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getAllDocumentToWork($workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return array();
    }
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'work_id = ? and c_id = ?' => array($workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $documentId
 * @param int $workId
 * @param int $courseId
 */
function deleteDocumentToWork($documentId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_DOCUMENT);
    $params = array(
        'document_id = ? and work_id = ? and c_id = ?' => array($documentId, $workId, $courseId)
    );
    Database::delete($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 */
function addUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'user_id' => $userId,
        'work_id' => $workId,
        'c_id' => $courseId
    );
    Database::insert($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'user_id = ? and work_id = ? and c_id = ?' => array($userId, $workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

/**
 * @param int $workId
 * @param int $courseId
 * @return array
 */
function getAllUserToWork($workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'work_id = ? and c_id = ?' => array($workId, $courseId)
    );
    return Database::select('*', $table, array('where' => $params));
}

function userAddedToWork($userId, $workId, $courseId)
{
    /*$table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'user_id = ? and work_id = ? and c_id = ?' => array($userId, $workId, $courseId)
    );
    $result = Database::select('count(*)', $table, array('where' => $params));*/
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 */
function deleteUserToWork($userId, $workId, $courseId)
{
    $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION_REL_USER);
    $params = array(
        'user_id = ? and work_id = ? and c_id = ?' => array($userId, $workId, $courseId)
    );
    Database::delete($table, $params);
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return bool
 */
function userIsSubscribedToWork($userId, $workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return true;
    }
    $subscribedUsers = getAllUserToWork($workId, $courseId);

    if (empty($subscribedUsers)) {
        return true;
    } else {
        $subscribedUsersIdList = array();
        foreach ($subscribedUsers as $item) {
            $subscribedUsersIdList[] = $item['user_id'];
        }
        if (in_array($userId, $subscribedUsersIdList)) {
            return true;
        }
    }
    return false;
}

/**
 * @param int $userId
 * @param int $workId
 * @param int $courseId
 * @return bool
 */
function allowOnlySubscribedUser($userId, $workId, $courseId)
{
    if (ADD_DOCUMENT_TO_WORK == false) {
        return true;
    }
    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        return true;
    }
    if (userIsSubscribedToWork($userId, $workId, $courseId) == false) {
        api_not_allowed(true);
    }
}

/**
 * @param int $workId
 * @param array $courseInfo
 * @return array
 */
function getDocumentTemplateFromWork($workId, $courseInfo)
{
    $documents = getAllDocumentToWork($workId, $courseInfo['real_id']);
    if (!empty($documents)) {
        foreach ($documents as $doc) {
            $docData = DocumentManager::get_document_data_by_id($doc['document_id'], $courseInfo['code']);
            $fileInfo = pathinfo($docData['path']);
            if ($fileInfo['extension'] == 'html') {
                if (file_exists($docData['absolute_path']) && is_file($docData['absolute_path'])) {
                    $docData['file_content'] = file_get_contents($docData['absolute_path']);
                    return $docData;
                }
            }
        }
    }
    return array();
}

/**
 * @param $workId
 * @param $courseInfo
 */
function getAllDocumentsFromWorkToString($workId, $courseInfo)
{
    $documents = getAllDocumentToWork($workId, $courseInfo['real_id']);
    if (!empty($documents)) {
        $docContent = '<ul class="nav nav-list well">';
        $docContent .= '<li class="nav-header">'.get_lang('Documents').'</li>';
        foreach ($documents as $doc) {
            $docData = DocumentManager::get_document_data_by_id($doc['document_id'], $courseInfo['code']);
            if ($docData) {
                $docContent .= '<li><a target="_blank" href="'.$docData['url'].'">'.$docData['title'].'</a></li>';
            }
        }
        $docContent .= '</ul><br />';
        echo $docContent;
    }
}

/**
 * Returns fckeditor toolbar
 * @return array
 */
function getWorkDescriptionToolbar()
{
    return array('ToolbarSet' => 'Work', 'Width' => '100%', 'Height' => '400');
}
