<?php
/* For licensing terms, see /license.txt */
/**
 * Script showing information about a user (name, e-mail, courses and sessions)
 * @author Bart Mollet
 * @package chamilo.admin
 */
/**
 * INIT SECTION
 */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
if (!isset($_GET['user_id'])) {
    api_not_allowed();
}
$user = api_get_user_info($_GET['user_id']);
$tool_name = $user['complete_name'].(empty($user['official_code'])?'':' ('.$user['official_code'].')');

/**
 * Display
 */
Display::display_header($tool_name);

$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'unsubscribe':
            if ( CourseManager::get_user_in_course_status($_GET['user_id'],$_GET['courseId']) == STUDENT) {
                CourseManager::unsubscribe_user($_GET['user_id'], $_GET['courseId']);
                Display::display_normal_message(get_lang('UserUnsubscribed'));
            } else {
                Display::display_error_message(get_lang('CannotUnsubscribeUserFromCourse'));
            }
            break;
    }
}
//only allow platform admins to login_as, or session admins only for students
// (not teachers nor other admins), and only if all options confirm it
// $_configuration['login_as_forbidden_globally'], defined in configuration.php,
// is the master key to these conditions
$statusname = api_get_status_langvars();
$login_as_icon = '';
if (empty($_configuration['login_as_forbidden_globally']) &&
    (api_is_global_platform_admin() ||
        (api_get_setting('login_as_allowed') === 'true' &&
            (api_is_platform_admin() ||
                (api_is_session_admin() &&
                    (api_is_session_admin() && $row['6'] == $statusname[STUDENT])
                )
            )
        )
    )
) {
    $login_as_icon = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&amp;user_id='.$user['user_id'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>';
}
echo '<div class="actions">
        <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.intval($_GET['user_id']).'" title="'.get_lang('Reporting').'">'.Display::return_icon('statistics.png', get_lang('Reporting'), '', ICON_SIZE_MEDIUM).'
        </a>
        '.$login_as_icon.'
        '.$editUser.'
    </div>';

echo Display::page_header($tool_name);

// Getting the user image
$sysdir_array = UserManager::get_user_picture_path_by_id($user['user_id'], 'system', false, true);
$sysdir = $sysdir_array['dir'];
$webdir_array = UserManager::get_user_picture_path_by_id($user['user_id'], 'web', false, true);
$webdir = $webdir_array['dir'];
$fullurl = $webdir.$webdir_array['file'];
$system_image_path = $sysdir.$webdir_array['file'];
list($width, $height, $type, $attr) = @getimagesize($system_image_path);
$resizing = (($height > 200) ? 'height="200"' : '');
$height += 30;
$width += 30;
$window_name = 'window'.uniqid('');
$onclick = $window_name."=window.open('".$fullurl."','".$window_name."','alwaysRaised=yes, alwaysLowered=no,alwaysOnTop=yes,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=".$width.",height=".$height.",left=200,top=20'); return false;";
echo '<a href="javascript: void(0);" onclick="'.$onclick.'" ><img src="'.$fullurl.'" '.$resizing.' /></a><br />';
echo '<p>'. ($user['status'] == 1 ? get_lang('Teacher') : get_lang('Student')).'</p>';
echo '<p>'.Display :: encrypted_mailto_link($user['mail'], $user['mail']).'</p>';
// Show info about who created this user and when
$creatorId = $user['creator_id'];
$creatorInfo = api_get_user_info($creatorId);
$registrationDate = $user['registration_date'];
echo '<p>'.sprintf(get_lang('CreatedByXYOnZ'), 'user_information.php?user_id='.$creatorId, $creatorInfo['username'], api_get_utc_datetime($registrationDate)).'</p>';

/**
 * Show the sessions and the courses in which this user is subscribed
 */

echo Display::page_subheader(get_lang('SessionList'));
$tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);

$user_id = $user['user_id'];

$sessions = SessionManager::get_sessions_by_user($user_id, true);

$personal_course_list = array();
if (count($sessions) > 0) {
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array (get_lang('Status'), true);
    $header[] = array ('', false);


    foreach ($sessions as $session_item) {

        $data = array ();
        $personal_course_list = array();
        $id_session = $session_item['session_id'];

        foreach ($session_item['courses'] as $my_course) {
            $courseInfo = api_get_course_info_by_id($my_course['id']);
            $row = array();
            $row[] = $courseInfo['code'];
            $row[] = $courseInfo['title'];
            //$row[] = $my_course['status'] == STUDENT ? get_lang('Student') : get_lang('Teacher');

            $roles = api_detect_user_roles($user['user_id'], $courseInfo['real_id'], $id_session);
            $row[] = api_get_roles_to_string($roles);
            $tools = '<a href="course_information.php?code='.$courseInfo['code'].'&id_session='.$id_session.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                    '<a href="'.api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>';

            $row[] = $tools;
            $data[] = $row;
        }
        $dates = SessionManager::parse_session_dates($session_item);
        echo Display::page_subheader($session_item['session_name'], ' '.$dates);

        $dates = array_filter(
            array($session_item['date_start'], $session_item['date_end'])
        );
        echo Display::page_subheader(
            '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$id_session.'">'.$session_item['session_name'].'</a>',
            ' '.implode(' - ', $dates)
        );

        Display :: display_sortable_table(
            $header,
            $data,
            array (),
            array(),
            array ('user_id' => intval($_GET['user_id']))
        );
    }
} else {
    Display::display_warning_message(get_lang('NoSessionsForThisUser'));
}


/**
 * Show the courses in which this user is subscribed
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c'.
    ' WHERE cu.user_id = '.$user['user_id'].' AND cu.c_id = c.id '.
    ' AND cu.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    $header=array();
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array (get_lang('Status'), true);
    $header[] = array ('', false);
    $data = array();
    while ($course = Database::fetch_object($res)) {
        $row = array ();
        $row[] = $course->code;
        $row[] = $course->title;

        //$row[] = $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher');
        $roles = api_detect_user_roles($user['user_id'], $course->id);
        $row[] = api_get_roles_to_string($roles);
        $tools = '<a href="course_information.php?code='.$course->code.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                '<a href="'.api_get_path(WEB_COURSE_PATH).$course->directory.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>' .
                '<a href="course_edit.php?courseId='.$course->c_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        if ( $course->status == STUDENT ) {
            $tools .= '<a href="user_information.php?action=unsubscribe&courseId='.$course->c_id.'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>';

        }
        $row[] = $tools;
        $data[] = $row;
    }
    echo Display::page_subheader(get_lang('Courses'));
    Display :: display_sortable_table(
        $header,
        $data,
        array (),
        array (),
        array ('user_id' => intval($_GET['user_id']))
    );
} else {
    Display::display_warning_message(get_lang('NoCoursesForThisUser'));
}
/**
 * Show the URL in which this user is subscribed
 */
if (api_is_multiple_url_enabled()) {
    $url_list= UrlManager::get_access_url_from_user($user['user_id']);
    if (count($url_list) > 0) {
        $header = array();
        $header[] = array ('URL', true);
        $data = array ();
        foreach ($url_list as $url) {
            $row = array();
            $row[] = Display::url($url['url'], $url['url']);
            $data[] = $row;
        }
        echo '<p><b>'.get_lang('URLList').'</b></p>';
        Display :: display_sortable_table(
            $header,
            $data,
            array (),
            array (),
            array ('user_id' => intval($_GET['user_id']))
        );
    } else {
        echo '<p>'.get_lang('NoUrlForThisUser').'</p>';
    }
}

Display::display_footer();
