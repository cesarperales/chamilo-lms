<?php
/* For licensing terms, see /license.txt */
/**
 * Include file with functions for the announcements module.
 * @package chamilo.announcements
 * @todo use OOP
 */

/**
 * Announcements handler class
 * @author jmontoya
 */
class AnnouncementManager
{
    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * @return array
     */
    public static function get_tags()
    {
        return array('((user_name))', '((teacher_name))', '((teacher_email))', '((course_title))', '((course_link))');
    }

    /**
     * @param $content
     * @param $course_code
     * @return mixed
     */
    public static function parse_content($content, $course_code)
    {
        $reader_info = api_get_user_info(api_get_user_id());
        $course_info = api_get_course_info($course_code);
        $teacher_list = CourseManager::get_teacher_list_from_course_code($course_info['real_id']);

        $teacher_name = '';
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $teacher_data) {
                $teacher_name = api_get_person_name($teacher_data['firstname'], $teacher_data['lastname']);
                $teacher_email = $teacher_data['email'];
                break;
            }
        }
        $course_link = api_get_course_url();

        $data['username'] = $reader_info['username'];
        $data['teacher_name'] = $teacher_name;
        $data['teacher_email'] = $teacher_email;
        $data['course_title'] = $course_info['name'];
        $data['course_link'] = Display::url($course_link, $course_link);

        $content = str_replace(self::get_tags(), $data, $content);

        return $content;
    }

    /**
     * Gets all announcements from a course
     * @param    string course db
     * @param    int session id
     * @return    array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_course($course_info, $session_id = 0)
    {
        $session_id = intval($session_id);
        $course_id = $course_info['real_id'];

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT announcement.id, announcement.title, announcement.content
				FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
				WHERE   announcement.id = toolitemproperties.ref AND
				        toolitemproperties.tool='announcement' AND
				        announcement.session_id  = '$session_id' AND
				        announcement.c_id = $course_id AND
				        toolitemproperties.c_id = $course_id
				ORDER BY display_order DESC";
        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);
        $result = array();
        if ($num_rows > 0) {
            $list = array();
            while ($row = Database::fetch_array($rs)) {
                $list[] = $row;
            }

            return $list;
        }

        return false;
    }

    /**
     * This functions swithes the visibility a course resource
     * using the visibility field in 'item_property'
     * @param    array    the course array
     * @param    int     ID of the element of the corresponding type
     * @return   bool    False on failure, True on success
     */
    public static function change_visibility_announcement($_course, $id)
    {
        $session_id = api_get_session_id();
        $item_visibility = api_get_item_visibility($_course, TOOL_ANNOUNCEMENT, $id, $session_id);
        if ($item_visibility == '1') {
            api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'invisible', api_get_user_id());
        } else {
            api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'visible', api_get_user_id());
        }

        return true;
    }

    /**
     * Deletes an announcement
     * @param array the course array
     * @param int     the announcement id
     */
    public static function delete_announcement($_course, $id)
    {
        api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'delete', api_get_user_id());
    }

    /**
     * Deletes all announcements by course
     * @param array the course array
     */
    public static function delete_all_announcements($_course)
    {
        $announcements = self::get_all_annoucement_by_course($_course, api_get_session_id());


        foreach ($announcements as $annon) {
            api_item_property_update($_course, TOOL_ANNOUNCEMENT, $annon['id'], 'delete', api_get_user_id());
        }
    }

    /**
     * Displays one specific announcement
     * @param $announcement_id, the id of the announcement you want to display
     */
    public static function display_announcement($announcement_id)
    {
        global $stok;
        if ($announcement_id != strval(intval($announcement_id))) {
            return false;
        }

        global $charset;
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $course_id = api_get_course_int_id();

        if (self::user_can_edit_announcement()) {
            $sql_query = "  SELECT announcement.*, toolitemproperties.*
                            FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
                            WHERE
                                announcement.id = toolitemproperties.ref AND
                                announcement.id = '$announcement_id' AND
                                toolitemproperties.tool='announcement' AND
                                announcement.c_id = $course_id AND
                                toolitemproperties.c_id = $course_id
                            ORDER BY display_order DESC";
        } else {
            $visibility_condition = " toolitemproperties.visibility='1'";
            if (GroupManager::is_tutor_of_group(api_get_user_id(), api_get_group_id())) {
                $visibility_condition = " toolitemproperties.visibility IN ('0', '1') ";
            }

            if (api_get_user_id() != 0) {
                $sql_query = "	SELECT announcement.*, toolitemproperties.*
    							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
    							WHERE
                                    announcement.id = toolitemproperties.ref  AND
                                    announcement.id = '$announcement_id' AND
                                    toolitemproperties.tool='announcement' AND
                                    (
                                        (toolitemproperties.to_user_id='".api_get_user_id(
                )."'  AND toolitemproperties.to_group_id = ".api_get_group_id().") OR
                                         toolitemproperties.to_group_id IN ('".api_get_group_id()."') AND toolitemproperties.to_user_id = 0
                                    ) AND
                                    $visibility_condition AND
                                    announcement.c_id = $course_id AND
                                    toolitemproperties.c_id = $course_id
    							ORDER BY display_order DESC";
            } else {
                $sql_query = "	SELECT announcement.*, toolitemproperties.*
    							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
    							WHERE
                                    announcement.id = toolitemproperties.ref AND
                                    announcement.id = '$announcement_id' AND
                                    toolitemproperties.tool='announcement' AND
                                    toolitemproperties.to_group_id='0' AND
                                    toolitemproperties.visibility='1' AND
                                    announcement.c_id = $course_id AND
                                    toolitemproperties.c_id = $course_id";
            }
        }

        $sql_result = Database::query($sql_query);
        if (Database::num_rows($sql_result) > 0) {
            $result = Database::fetch_array($sql_result, 'ASSOC');
            $title = $result['title'];
            $content = self::parse_content($result['content'], api_get_course_id());

            echo "<table height=\"100\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" class=\"data_table\">";
            echo "<tr><td><h2>".$title."</h2></td></tr>";

            if (self::user_can_edit_announcement()) {
                $modify_icons = "<a href=\"".api_get_self()."?".api_get_cidreq(
                )."&action=modify&id=".$announcement_id."\">".Display::return_icon(
                    'edit.png',
                    get_lang('Edit'),
                    '',
                    ICON_SIZE_SMALL
                )."</a>";
                if ($result['visibility'] == 1) {
                    $image_visibility = "visible";
                    $alt_visibility = get_lang('Hide');
                } else {
                    $image_visibility = "invisible";
                    $alt_visibility = get_lang('Visible');
                }

                $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                )."&origin=".(!empty($_GET['origin']) ? Security::remove_XSS(
                    $_GET['origin']
                ) : '')."&action=showhide&id=".$announcement_id."&sec_token=".$stok."\">".
                    Display::return_icon($image_visibility.'.png', $alt_visibility, '', ICON_SIZE_SMALL)."</a>";

                if (self::user_can_edit_announcement()) {
                    $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                    )."&action=delete&id=".$announcement_id."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(
                        api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)
                    )."')) return false;\">".
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                        "</a>";
                }
                echo "<tr><th style='text-align:right'>$modify_icons</th></tr>";
            }

            echo "<tr><td>$content</td></tr>";

            echo "<tr><td class=\"announcements_datum\">".get_lang('LastUpdateDate')." : ".api_convert_and_format_date(
                $result['insert_date'],
                DATE_TIME_FORMAT_LONG
            )."</td></tr>";

            // User or group icon
            $sent_to_icon = '';
            if ($result['to_group_id'] !== '0' and $result['to_group_id'] !== 'NULL') {
                $sent_to_icon = Display::return_icon('group.gif', get_lang('AnnounceSentToUserSelection'));
            }
            $sent_to = self::sent_to('announcement', $announcement_id);

            $sent_to_form = self::sent_to_form($sent_to);

            echo Display::tag('td', get_lang('SentTo').' : '.$sent_to_form, array('class' => 'announcements_datum'));

            $attachment_list = self::get_attachment($announcement_id);

            if (count($attachment_list) > 0) {
                echo "<tr><td>";
                $realname = $attachment_list['path'];
                $user_filename = $attachment_list['filename'];
                $full_file_name = 'download.php?file='.$realname;
                echo '<br/>';
                echo Display::return_icon('attachment.gif', get_lang('Attachment'));
                echo '<a href="'.$full_file_name.' "> '.$user_filename.' </a>';
                echo ' - <span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
                if (api_is_allowed_to_edit(false, true)) {
                    echo Display::url(
                        Display::return_icon('delete.png', get_lang('Delete'), '', 16),
                        api_get_self()."?".api_get_cidreq(
                        )."&action=delete_attachment&id_attach=".$attachment_list['id']."&sec_token=".$stok
                    );
                }
                echo '</td></tr>';
            }
            echo "</table>";
        } else {
            return false;
        }
    }

    /**
     * Get last announcement order in the list (by max display_order)
     * @return int 0 or the integer display_order of the last announcement
     * @assert () === 0
     */
    public static function get_last_announcement_order()
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $course_id = api_get_course_int_id();
        $sql_max = "SELECT MAX(display_order) FROM $tbl_announcement WHERE c_id = $course_id ";
        $res_max = Database::query($sql_max);

        $order = 0;
        if (Database::num_rows($res_max)) {
            $row_max = Database::fetch_array($res_max);
            $order = intval($row_max[0]) + 1;
        }

        return $order;
    }
    /**
     * Get last announcement in a course by id (not iid)
     * @param int Course ID (will get from context if none provided)
     * @return int 0 or the integer id last announcement inserted
     * @assert () === 0
     */
    public static function get_last_announcement_id($course_id=null)
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = filter_var($course_id, FILTER_SANITIZE_NUMBER_INT);
        }
        $sql_max = "SELECT MAX(id) FROM $tbl_announcement WHERE c_id = $course_id";
        $res_max = Database::query($sql_max);
        $id = 0;
        if (Database::num_rows($res_max)) {
            $row_max = Database::fetch_array($res_max);
            $id = $row_max[0];
        }
        return $id;
    }
    /**
     * Get last announcement attachment in a course by id (not iid)
     * @param int Course ID (will get from context if none provided)
     * @return int 0 or the integer id last announcement attachment inserted
     * @assert () === 0
     */
    public static function get_last_announcement_attachment_id($course_id=null)
    {
        $tbl_announcement_att = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = filter_var($course_id, FILTER_SANITIZE_NUMBER_INT);
        }
        $sql_max = "SELECT MAX(id) FROM $tbl_announcement_att WHERE c_id = $course_id";
        $res_max = Database::query($sql_max);
        $id = 0;
        if (Database::num_rows($res_max)) {
            $row_max = Database::fetch_array($res_max);
            $id = $row_max[0];
        }
        return $id;
    }
    /**
     * Store an announcement in the database (including its attached file if any)
     * @param string    Announcement title (pure text)
     * @param string    Content of the announcement (can be HTML)
     * @param int       Display order in the list of announcements
     * @param array     Array of users and groups to send the announcement to
     * @param array     uploaded file $_FILES
     * @param string    Comment describing the attachment
     * @param bool $sendEmail
     * @return int      false on failure, ID of the announcement on success
     */
    public static function add_announcement(
        $emailTitle,
        $newContent,
        $sent_to,
        $file = array(),
        $file_comment = null,
        $end_date = null,
        $sendEmail = false
    ) {
        $_course = api_get_course_info();
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        // filter data
        $emailTitle = Database::escape_string($emailTitle);
        $newContent = Database::escape_string($newContent);

        if (empty($end_date)) {
            $end_date = api_get_utc_datetime();
        } else {
            $end_date = Database::escape_string($end_date);
        }

        $course_id = api_get_course_int_id();

        $order = self::get_last_announcement_order();
        $aid = self::get_last_announcement_id($course_id)+1;

        // store in the table announcement
        $sql = "INSERT INTO $tbl_announcement SET
                c_id 			= '$course_id',
                id                      = $aid,
                content 		= '$newContent',
                title 			= '$emailTitle',
                end_date        = '$end_date',
                display_order 	= '$order',
                session_id		= ".api_get_session_id();
        $result = Database::query($sql);
        if ($result === false) {
            return false;
        } else {
            //Store the attach file
            $last_id = $aid;
            if (!empty($file)) {
                self::add_announcement_attachment_file($last_id, $file_comment, $_FILES['user_upload']);
            }

            // store in item_property (first the groups, then the users)
            if (!is_null($sent_to)) {
                // !is_null($sent_to): when no user is selected we send it to everyone
                $send_to = self::separate_users_groups($sent_to);
                // storing the selected groups
                if (is_array($send_to['groups'])) {
                    foreach ($send_to['groups'] as $group) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            $group
                        );
                    }
                }

                // storing the selected users
                if (is_array($send_to['users'])) {
                    foreach ($send_to['users'] as $user) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            "AnnouncementAdded",
                            api_get_user_id(),
                            '',
                            $user
                        );
                    }
                }
            } else {
                // the message is sent to everyone, so we set the group to 0
                api_item_property_update(
                    $_course,
                    TOOL_ANNOUNCEMENT,
                    $last_id,
                    "AnnouncementAdded",
                    api_get_user_id(),
                    '0'
                );
            }

            if ($sendEmail && !empty($sendEmail)) {
                self::send_email($last_id);
            }

            return $last_id;
        }
    }

    /**
     * @param $emailTitle
     * @param $newContent
     * @param $to
     * @param $to_users
     * @param array $file
     * @param string $file_comment
     * @param bool $sendEmail
     * @return bool|int
     */
    public static function add_group_announcement(
        $emailTitle,
        $newContent,
        $to,
        $to_users,
        $file = array(),
        $file_comment = '',
        $sendEmail = false
    ) {
        $_course = api_get_course_info();

        // database definitions
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $emailTitle = Database::escape_string($emailTitle);
        $newContent = Database::escape_string($newContent);
        $order = self::get_last_announcement_order();

        $now = api_get_utc_datetime();
        $course_id = api_get_course_int_id();
        $aid = self::get_last_announcement_id($course_id)+1;

        // store in the table announcement
        $sql = "INSERT INTO $tbl_announcement SET
                c_id 			= '$course_id',
                id			= $aid,
                content 		= '$newContent',
                title 			= '$emailTitle',
                end_date 		= '$now',
                display_order 	= '$order',
                session_id		= ".api_get_session_id();

        $result = Database::query($sql);
        if ($result === false) {
            return false;
        }

        //store the attach file
        $last_id = $aid;

        if (!empty($file)) {
            self::add_announcement_attachment_file($last_id, $file_comment, $file);
        }

        // store in item_property (first the groups, then the users

        if (!isset($to_users)) {
            $send_to = self::separate_users_groups($to);
            // storing the selected groups
            if (is_array($send_to['groups'])) {
                foreach ($send_to['groups'] as $group) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $last_id,
                        "AnnouncementAdded",
                        api_get_user_id(),
                        $group
                    );
                }
            }
        } else {
            // the message is sent to everyone, so we set the group to 0
            // storing the selected users
            if (is_array($to_users)) {
                foreach ($to_users as $user) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $last_id,
                        "AnnouncementAdded",
                        api_get_user_id(),
                        api_get_group_id(),
                        $user
                    );
                }
            }
        }

        if ($sendEmail && !empty($sendEmail)) {
            self::send_email($last_id);
        }

        return $last_id;
    }

    /**
     * This function stores the announcement item in the announcement table
     * and updates the item_property table
     *
     * @param int     id of the announcement
     * @param string email
     * @param string content
     * @param array     users that will receive the announcement
     * @param mixed     attachment
     * @param string file comment
     * @param bool $sendEmail
     *
     */
    public static function edit_announcement($id, $emailTitle, $newContent, $to, $file = array(), $file_comment = '', $sendEmail = false)
    {
        $_course = api_get_course_info();

        $course_id = api_get_course_int_id();
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $emailTitle = Database::escape_string($emailTitle);
        $newContent = Database::escape_string($newContent);
        $id = intval($id);

        // store the modifications in the table announcement
        $sql = "UPDATE $tbl_announcement SET content = '$newContent', title = '$emailTitle' WHERE c_id = $course_id AND id='$id'";
        $result = Database::query($sql);

        // save attachment file
        $row_attach = self::get_attachment($id);
        $id_attach = intval($row_attach['id']);

        if (!empty($file)) {
            if (empty($id_attach)) {
                self::add_announcement_attachment_file($id, $file_comment, $file);
            } else {
                self::edit_announcement_attachment_file($id_attach, $file, $file_comment);
            }
        }

        // we remove everything from item_property for this
        $sql_delete = "DELETE FROM $tbl_item_property WHERE c_id = $course_id AND ref='$id' AND tool='announcement'";
        $result = Database::query($sql_delete);

        // store in item_property (first the groups, then the users

        if (!is_null($to)) {
            // !is_null($to): when no user is selected we send it to everyone

            $send_to = self::separate_users_groups($to);

            // storing the selected groups
            if (is_array($send_to['groups'])) {
                foreach ($send_to['groups'] as $group) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        "AnnouncementUpdated",
                        api_get_user_id(),
                        $group
                    );
                }
            }
            // storing the selected users
            if (is_array($send_to['users'])) {
                foreach ($send_to['users'] as $user) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        "AnnouncementUpdated",
                        api_get_user_id(),
                        0,
                        $user
                    );
                }
            }
        } else {
            // the message is sent to everyone, so we set the group to 0
            api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, "AnnouncementUpdated", api_get_user_id(), '0');
        }

        if ($sendEmail && !empty($sendEmail)) {
            self::send_email($id);
        }
    }

    /**
     * @param int $insert_id
     * @return bool
     */
    public static function update_mail_sent($insert_id)
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        if ($insert_id != strval(intval($insert_id))) {
            return false;
        }
        $insert_id = Database::escape_string($insert_id);
        $course_id = api_get_course_int_id();
        // store the modifications in the table tbl_annoucement
        $sql = "UPDATE $tbl_announcement SET email_sent='1' WHERE c_id = $course_id AND id='$insert_id'";
        Database::query($sql);
    }

    /**
     * Gets all announcements from a user by course
     * @param    string course db
     * @param    int user id
     * @return    array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_user_course($course_code, $user_id)
    {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (empty($user_id)) {
            return false;
        }
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        if (!empty($user_id) && is_numeric($user_id)) {
            $user_id = intval($user_id);
            $sql = "SELECT DISTINCT announcement.title, announcement.content
					FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
					WHERE
						announcement.c_id = $course_id AND
						toolitemproperties.c_id = $course_id AND
						announcement.id = toolitemproperties.ref AND
						toolitemproperties.tool='announcement' AND
						(toolitemproperties.insert_user_id='$user_id' AND (toolitemproperties.to_group_id='0' OR toolitemproperties.to_group_id is null))
						AND toolitemproperties.visibility='1'
						AND announcement.session_id  = 0
					ORDER BY display_order DESC";
            $rs = Database::query($sql);
            $num_rows = Database::num_rows($rs);
            $content = '';
            $i = 0;
            $result = array();
            if ($num_rows > 0) {
                while ($myrow = Database::fetch_array($rs)) {
                    $content .= '<strong>'.$myrow['title'].'</strong><br /><br />';
                    $content .= $myrow['content'];
                    $i++;
                }
                $result['content'] = $content;
                $result['count'] = $i;

                return $result;
            }

            return false;
        }

        return false;
    }


    /**
     * Returns announcement info from its id
     *
     * @param int $course_id
     * @param int $annoucement_id
     * @return array
     */
    public static function get_by_id($course_id, $annoucement_id)
    {
        $annoucement_id = intval($annoucement_id);
        $course_id = $course_id ? intval($course_id) : api_get_course_int_id();

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT announcement.*
                   FROM $tbl_announcement announcement INNER JOIN $tbl_item_property toolitemproperties
                   ON   announcement.id = toolitemproperties.ref AND
                        announcement.c_id = $course_id AND
                        toolitemproperties.c_id = $course_id
                   WHERE toolitemproperties.tool='announcement' AND
                         announcement.id = $annoucement_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::fetch_array($result);
        }

        return array();
    }

    /**
     * This tools loads all the users and all the groups who have received
     * a specific item (in this case an announcement item)
     */
    public static function load_edit_users($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = Database::escape_string($tool);
        $id = Database::escape_string($id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT to_group_id, to_user_id FROM $tbl_item_property WHERE c_id = $course_id AND tool='$tool' AND ref='$id'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $to_group = $row['to_group_id'];
            $to_user = $row['to_user_id'];

            if (empty($to_user) && empty($to_group)) {
                //return "everyone";
            }

            if (!empty($to_user) && !empty($to_group)) {
                $to[] = "USER:".$to_user;
            }

            if (empty($to_user) && !empty($to_group)) {
                $to[] = "GROUP:".$to_group;
            }

            if (!empty($to_user) && empty($to_group)) {
                $to[] = "USER:".$to_user;
            }
        }
        return $to;
    }

    /**
     * returns the javascript for setting a filter
     * this goes into the $htmlHeadXtra[] array
     */
    public static function user_group_filter_javascript()
    {
        return "<script language=\"JavaScript\" type=\"text/JavaScript\">
		<!--
		function jumpMenu(targ,selObj,restore)
		{
		  eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
		  if (restore) selObj.selectedIndex=0;
		}
		//-->
		</script>";
    }

    /*
      TO_JAVASCRIPT
     */

    /**
     * returns all the javascript that is required for easily
     * setting the target people/groups
     * this goes into the $htmlHeadXtra[] array
     */
    public static function to_javascript()
    {
        $www = api_get_path(WEB_PATH);
        $src = $www.'main/announcements/resources/js/main.js';
        $result = Javascript::tag($src);
        $root = Chamilo::url();
        $code = "var www = '$root';\n";
        $code .= Javascript::get_lang('FieldRequired', 'Send2All', 'AddAnAttachment', 'Everybody');
        $result .= Javascript::tag_code($code);

        return $result;
    }

    /*
      SENT_TO_FORM
     */

    /**
     * constructs the form to display all the groups and users the message has been sent to
     * input:     $sent_to_array is a 2 dimensional array containing the groups and the users
     *             the first level is a distinction between groups and users:
     *             $sent_to_array['groups'] * and $sent_to_array['users']
     *             $sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array
     *             containing all the id's of the groups (resp. users) who have received this message.
     * @author Patrick Cool <patrick.cool@>
     */
    public static function sent_to_form($sent_to_array)
    {
        // we find all the names of the groups
        $group_names = self::get_course_groups();

        // we count the number of users and the number of groups
        if (isset($sent_to_array['users'])) {
            $number_users = count($sent_to_array['users']);
        } else {
            $number_users = 0;
        }
        if (isset($sent_to_array['groups'])) {
            $number_groups = count($sent_to_array['groups']);
        } else {
            $number_groups = 0;
        }
        $total_numbers = $number_users + $number_groups;

        // starting the form if there is more than one user/group
        $output = array();
        if ($total_numbers > 1) {
            // outputting the name of the groups
            if (is_array($sent_to_array['groups'])) {
                foreach ($sent_to_array['groups'] as $group_id) {
                    $output[] = $group_names[$group_id]['name'];
                }
            }

            if (isset($sent_to_array['users'])) {
                if (is_array($sent_to_array['users'])) {
                    foreach ($sent_to_array['users'] as $user_id) {
                        $user_info = api_get_user_info($user_id);
                        $output[] = $user_info['complete_name_with_username'];
                    }
                }
            }
        } else {
            // there is only one user/group
            if (isset($sent_to_array['users']) and is_array($sent_to_array['users'])) {
                $user_info = api_get_user_info($sent_to_array['users'][0]);
                $output[] = api_get_person_name($user_info['firstname'], $user_info['lastname']);
            }
            if (isset($sent_to_array['groups']) and is_array(
                $sent_to_array['groups']
            ) and isset($sent_to_array['groups'][0]) and $sent_to_array['groups'][0] !== 0
            ) {
                $group_id = $sent_to_array['groups'][0];
                $output[] = "&nbsp;".$group_names[$group_id]['name'];
            }
            if (empty($sent_to_array['groups']) and empty($sent_to_array['users'])) {
                $output[] = "&nbsp;".get_lang('Everybody');
            }
        }


        if (!empty($output)) {
            $output = array_filter($output);

            if (count($output) > 0) {
                $output = implode(', ', $output);
            }

            return $output;
        }
    }

    /*
      SEPARATE_USERS_GROUPS
     */
    /**
     * This function separates the users from the groups
     * users have a value USER:XXX (with XXX the dokeos id
     * groups have a value GROUP:YYY (with YYY the group id)
     * @param    array   Array of strings that define the type and id of each destination
     * @return   array   Array of groups and users (each an array of IDs)
     */
    public static function separate_users_groups($to)
    {
        $grouplist = array();
        $userlist = array();

        foreach ($to as $to_item) {
            list($type, $id) = explode(':', $to_item);
            switch ($type) {
                case 'GROUP':
                    $grouplist[] = intval($id);
                    break;
                case 'USER':
                    $userlist[] = intval($id);
                    break;
            }
        }

        $send_to['groups'] = $grouplist;
        $send_to['users'] = $userlist;

        return $send_to;
    }

    /*
      SENT_TO()
     */

    /**
     * Returns all the users and all the groups a specific announcement item
     * has been sent to
     * @param    string  The tool (announcement, agenda, ...)
     * @param    int     ID of the element of the corresponding type
     * @return   array   Array of users and groups to whom the element has been sent
     */
    public static function sent_to($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = Database::escape_string($tool);
        $id = intval($id);

        $sent_to_group = array();
        $sent_to = array();
        $course_id = api_get_course_int_id();

        $sql = "SELECT to_group_id, to_user_id FROM $tbl_item_property WHERE c_id = $course_id AND tool = '$tool' AND ref=".$id;
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result)) {
            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            if (empty($row['to_user_id'])) {
                if ($row['to_group_id'] != 0) {
                    $sent_to_group[] = $row['to_group_id'];
                }
            }
            // if to_user_id <> 0 then it is sent to a specific user
            if ($row['to_user_id'] <> 0) {
                $sent_to_user[] = $row['to_user_id'];
            }
        }
        if (isset($sent_to_group)) {
            $sent_to['groups'] = $sent_to_group;
        }
        if (isset($sent_to_user)) {
            $sent_to['users'] = $sent_to_user;
        }

        return $sent_to;
    }

    /* 		ATTACHMENT FUNCTIONS	 */

    /**
     * Show a list with all the attachments according to the post's id
     * @param int announcement id
     * @return array with the post info
     * @author Arthur Portugal
     * @version November 2009, dokeos 1.8.6.2
     */
    public static function get_attachment($announcement_id)
    {
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $announcement_id = intval($announcement_id);
        $course_id = api_get_course_int_id();
        $row = array();
        $sql = 'SELECT id, path, filename, comment FROM '.$tbl_announcement_attachment.'
				WHERE c_id = '.$course_id.' AND announcement_id = '.$announcement_id.'';
        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result, 'ASSOC');
        }

        return $row;
    }

    /**
     * This function add a attachment file into announcement
     * @param int  announcement id
     * @param string file comment
     * @param array  uploaded file $_FILES
     * @return int  -1 if failed, 0 if unknown (should not happen), 1 if success
     */
    public static function add_announcement_attachment_file($announcement_id, $file_comment, $file)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $announcement_id = intval($announcement_id);
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            $courseDir = $_course['path'].'/upload/announcements'; // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = FileManager::add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file['name'];

            if (!FileManager::filter_extension($new_file_name)) {
                $return = -1;
                Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;
                $result = @move_uploaded_file($file['tmp_name'], $new_path);
                $safe_file_comment = Database::escape_string($file_comment);
                $safe_file_name = Database::escape_string($file_name);
                $safe_new_file_name = Database::escape_string($new_file_name);
                $aid = self::get_last_announcement_attachment_id($course_id)+1;
                // Storing the attachments if any
                $sql = "INSERT INTO $tbl_announcement_attachment (c_id, id, filename, comment, path, announcement_id, size) VALUES
                        ($course_id, $aid, '$safe_file_name', '$file_comment', '$safe_new_file_name' , '$announcement_id', '".intval(
                    $file['size']
                )."' )";
                Database::query($sql);
                $return = 1;
            }
        }

        return $return;
    }

    /**
     * This function edit a attachment file into announcement
     * @param int attach id
     * @param array uploaded file $_FILES
     * @param string file comment
     * @return int
     */
    public static function edit_announcement_attachment_file($id_attach, $file, $file_comment)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            $courseDir = $_course['path'].'/upload/announcements'; // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = FileManager::add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file ['name'];

            if (!FileManager::filter_extension($new_file_name)) {
                $return - 1;
                Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;
                $result = @move_uploaded_file($file['tmp_name'], $new_path);
                $safe_file_comment = Database::escape_string($file_comment);
                $safe_file_name = Database::escape_string($file_name);
                $safe_new_file_name = Database::escape_string($new_file_name);
                $id_attach = intval($id_attach);
                $sql = "UPDATE $tbl_announcement_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', size ='".intval(
                    $file['size']
                )."'
					 	WHERE c_id = $course_id AND id = '$id_attach'";
                $result = Database::query($sql);
                if ($result === false) {
                    $return = -1;
                    Display :: display_error_message(get_lang('UplUnableToSaveFile'));
                } else {
                    $return = 1;
                }
            }
        }

        return $return;
    }

    /**
     * This function delete a attachment file by id
     * @param integer attachment file Id
     *
     */
    public static function delete_announcement_attachment_file($id)
    {
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $id = intval($id);
        $course_id = api_get_course_int_id();
        $sql = "DELETE FROM $tbl_announcement_attachment WHERE c_id = $course_id AND id = $id";
        Database::query($sql);
        // update item_property
        //api_item_property_update($_course, 'announcement_attachment',  $id,'AnnouncementAttachmentDeleted', api_get_user_id());
    }

    public static function send_email($annoucement_id)
    {
        $email = AnnouncementEmail::create(null, $annoucement_id);
        $email->send();
    }

    public static function user_can_edit_announcement()
    {
        $group_id = api_get_group_id();

        return api_is_allowed_to_edit(false, true) OR
            (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous()) OR
            (!empty($group_id) AND GroupManager::user_has_access(
                api_get_user_id(),
                $group_id,
                GroupManager::GROUP_TOOL_ANNOUNCEMENT
            ) AND GroupManager::is_tutor_of_group(api_get_user_id(), $group_id));

    }
}
//end class
