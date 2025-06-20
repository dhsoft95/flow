<?php

namespace App\Models;

class Timesheets_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'project_time';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $tasks_table = $this->db->prefixTable('tasks');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');
        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $timesheet_table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $timesheet_table.project_id=$project_id";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $timesheet_table.user_id=$user_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status === "none_open") {
            $where .= " AND $timesheet_table.status !='open'";
        } else if ($status) {
            $where .= " AND $timesheet_table.status='$status'";
        }

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $where .= " AND $timesheet_table.task_id=$task_id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $timesheet_table.project_id IN(SELECT $projects_table.id FROM $projects_table WHERE $projects_table.client_id=$client_id)";
        }

        $offset = convert_seconds_to_time_format(get_timezone_offset());

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.start_time,'$offset'))>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.end_time,'$offset'))<='$end_date'";
        }


        $allowed_members = $this->_get_clean_value($options, "allowed_members");
        if (is_array($allowed_members) && count($allowed_members)) {
            $allowed_members = join(",", $allowed_members);
            $where .= " AND $timesheet_table.user_id IN($allowed_members)";
        } else if (!$project_id && ($allowed_members === "own_project_members" || $allowed_members === "own_project_members_excluding_own")) {
            $where .= $this->get_timesheet_own_project_memeber_only_query($timesheet_table);
        }

        //prepare custom fild binding query
        $custom_fields = get_array_value($options, "custom_fields");
        $custom_field_filter = get_array_value($options, "custom_field_filter");
        $custom_field_query_info = $this->prepare_custom_field_query_string("timesheets", $custom_fields, $timesheet_table, $custom_field_filter);
        $select_custom_fieds = get_array_value($custom_field_query_info, "select_string");
        $join_custom_fieds = get_array_value($custom_field_query_info, "join_string");
        $custom_fields_where = get_array_value($custom_field_query_info, "where_string");

        $limit_offset = "";
        $limit = $this->_get_clean_value($options, "limit");
        if ($limit) {
            $skip = $this->_get_clean_value($options, "skip");
            $offset = $skip ? $skip : 0;
            $limit_offset = " LIMIT $limit OFFSET $offset ";
        }

        $available_order_by_list = array(
            "member_name" => "logged_by_user",
            "task_title" => "task_title",
            "start_time" => $timesheet_table . ".start_time",
            "end_time" => $timesheet_table . ".end_time",
            "project" => "project_title",
            "client" => "timesheet_client_company_name",
        );

        $order_by = get_array_value($available_order_by_list, $this->_get_clean_value($options, "order_by"));

        $order = "";

        if ($order_by) {
            $order_dir = $this->_get_clean_value($options, "order_dir");
            $order = " ORDER BY $order_by $order_dir ";
        }

        $search_by = get_array_value($options, "search_by");
        if ($search_by) {
            $search_by = $this->db->escapeLikeString($search_by);

            $where .= " AND (";
            $where .= " $tasks_table.title LIKE '%$search_by%' ESCAPE '!' ";
            $where .= " OR $timesheet_table.start_time LIKE '%$search_by%' ESCAPE '!' ";
            $where .= " OR $timesheet_table.end_time LIKE '%$search_by%' ESCAPE '!' ";
            $where .= " OR $timesheet_table.note LIKE '%$search_by%' ESCAPE '!' ";
            $where .= " OR $projects_table.title LIKE '%$search_by%' ESCAPE '!' ";
            $where .= " OR CONCAT($users_table.first_name, ' ', $users_table.last_name) LIKE '%$search_by%' ESCAPE '!' ";
            $where .= $this->get_custom_field_search_query($timesheet_table, "timesheets", $search_by);

            $where .= " )";
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS $timesheet_table.*,  CONCAT($users_table.first_name, ' ',$users_table.last_name) AS logged_by_user, $users_table.image as logged_by_avatar,
            $tasks_table.title AS task_title, $projects_table.title AS project_title,
            $projects_table.client_id AS timesheet_client_id, (SELECT $clients_table.company_name FROM $clients_table WHERE $clients_table.id=$projects_table.client_id AND $clients_table.deleted=0) AS timesheet_client_company_name $select_custom_fieds
        FROM $timesheet_table
        LEFT JOIN $users_table ON $users_table.id= $timesheet_table.user_id
        LEFT JOIN $tasks_table ON $tasks_table.id= $timesheet_table.task_id
        LEFT JOIN $projects_table ON $projects_table.id= $timesheet_table.project_id
        $join_custom_fieds
        WHERE $timesheet_table.deleted=0 $where $custom_fields_where 
        $order $limit_offset";

        $raw_query = $this->db->query($sql);

        $total_rows = $this->db->query("SELECT FOUND_ROWS() as found_rows")->getRow();

        if ($limit) {
            return array(
                "data" => $raw_query->getResult(),
                "recordsTotal" => $total_rows->found_rows,
                "recordsFiltered" => $total_rows->found_rows,
            );
        } else {
            return $raw_query;
        }
    }

    function get_summary_details($options = array()) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $tasks_table = $this->db->prefixTable('tasks');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');
        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $timesheet_table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $timesheet_table.project_id=$project_id";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $timesheet_table.user_id=$user_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status === "none_open") {
            $where .= " AND $timesheet_table.status !='open'";
        } else if ($status) {
            $where .= " AND $timesheet_table.status='$status'";
        }

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $where .= " AND $timesheet_table.task_id=$task_id";
        }

        $offset = convert_seconds_to_time_format(get_timezone_offset());

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.start_time,'$offset'))>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.end_time,'$offset'))<='$end_date'";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $timesheet_table.project_id IN(SELECT $projects_table.id FROM $projects_table WHERE $projects_table.client_id=$client_id)";
        }

        $allowed_members = $this->_get_clean_value($options, "allowed_members");
        if (is_array($allowed_members) && count($allowed_members)) {
            $allowed_members = join(",", $allowed_members);
            $where .= " AND $timesheet_table.user_id IN($allowed_members)";
        } else if (!$project_id && ($allowed_members === "own_project_members" || $allowed_members === "own_project_members_excluding_own")) {
            $where .= $this->get_timesheet_own_project_memeber_only_query($timesheet_table);
        }


        //group by
        $group_by_option = "$timesheet_table.user_id, $timesheet_table.task_id, $timesheet_table.project_id";
        $group_by = $this->_get_clean_value($options, "group_by");

        if ($group_by === "member") {
            $group_by_option = "$timesheet_table.user_id";
        } else if ($group_by === "task") {
            $group_by_option = "$timesheet_table.task_id";
        } else if ($group_by === "project") {
            $group_by_option = "$timesheet_table.project_id";
        }

        $custom_field_filter = $this->_get_clean_value($options, "custom_field_filter");
        $custom_field_query_info = $this->prepare_custom_field_query_string("timesheets", "", $timesheet_table, $custom_field_filter);
        $custom_fields_where = $this->_get_clean_value($custom_field_query_info, "where_string");

        $sql = "SELECT new_summary_table.user_id, new_summary_table.total_duration, CONCAT($users_table.first_name, ' ',$users_table.last_name) AS logged_by_user, $users_table.image as logged_by_avatar,
                       $tasks_table.id AS task_id,  $tasks_table.title AS task_title,  $projects_table.id AS project_id,  $projects_table.title AS project_title,
                       $projects_table.client_id AS timesheet_client_id, (SELECT $clients_table.company_name FROM $clients_table WHERE $clients_table.id=$projects_table.client_id AND $clients_table.deleted=0) AS timesheet_client_company_name
                FROM (SELECT MAX($timesheet_table.project_id) AS project_id, MAX($timesheet_table.user_id) AS user_id, MAX($timesheet_table.task_id) AS task_id, (SUM(TIMESTAMPDIFF(SECOND, $timesheet_table.start_time, $timesheet_table.end_time)) + SUM(ROUND(($timesheet_table.hours * 60), 0) * 60)) AS total_duration
                        FROM $timesheet_table
                        WHERE $timesheet_table.deleted=0 $where $custom_fields_where
                        GROUP BY $group_by_option) AS new_summary_table
                LEFT JOIN $users_table ON $users_table.id= new_summary_table.user_id
                LEFT JOIN $tasks_table ON $tasks_table.id= new_summary_table.task_id
                LEFT JOIN $projects_table ON $projects_table.id= new_summary_table.project_id            
                ";
        return $this->db->query($sql);
    }

    function get_timer_info($project_id, $user_id) {
        return parent::get_all_where(array("project_id" => $project_id, "user_id" => $user_id, "status" => "open", "deleted" => 0));
    }

    function get_task_timer_info($task_id, $user_id) {
        return parent::get_all_where(array("task_id" => $task_id, "user_id" => $user_id, "status" => "open", "deleted" => 0));
    }

    function process_timer($data) {
        $status = $this->_get_clean_value($data, "status"); //user wants to set this status
        $project_id = $this->_get_clean_value($data, "project_id");
        $user_id = $this->_get_clean_value($data, "user_id");
        $note = $this->_get_clean_value($data, "note");
        $task_id = $this->_get_clean_value($data, "task_id");

        //check if timer record already exists
        $where = array("project_id" => $project_id, "user_id" => $user_id, "status" => "open", "deleted" => 0);

        if ($task_id) {
            //check if there already has any task
            $task_where = array_merge($where, array("task_id" => $task_id));
            $task_timer_info = parent::get_one_where($task_where);
            if ($task_timer_info->id) {
                $where["task_id"] = $task_id;
            }
        }

        $timer_info = parent::get_one_where($where);

        $now = get_current_utc_time();
        if ($status === "start") {
            //no record found, create a new record 
            $timer_data = array(
                "project_id" => $project_id,
                "user_id" => $user_id,
                "status" => "open",
                "start_time" => $now,
                "task_id" => $task_id
            );
            return parent::ci_save($timer_data);
        } else if ($status === "stop" && $timer_info->id) {
            //timer is running
            //calculate the total time and stop the timer
            $timer_data = array(
                "end_time" => $now,
                "status" => "logged",
                "note" => $note,
                "task_id" => $task_id,
            );
            return parent::ci_save($timer_data, $timer_info->id);
        }
    }

    function get_open_timers($user_id = 0) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $projects_table = $this->db->prefixTable('projects');
        $tasks_table = $this->db->prefixTable('tasks');
        $user_id = $this->_get_clean_value($user_id);

        $sql = "SELECT $timesheet_table.*, $projects_table.title AS project_title, $tasks_table.title AS task_title
        FROM $timesheet_table
        LEFT JOIN $projects_table ON $projects_table.id= $timesheet_table.project_id
        LEFT JOIN $tasks_table ON $tasks_table.id= $timesheet_table.task_id
        WHERE $timesheet_table.deleted=0 AND $timesheet_table.user_id=$user_id AND $timesheet_table.status='open'";
        return $this->db->query($sql);
    }

    function get_timesheet_statistics($options = array()) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $users_table = $this->db->prefixTable('users');

        $info = new \stdClass();

        $where = "";
        $offset = convert_seconds_to_time_format(get_timezone_offset());

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.start_time,'$offset'))>='$start_date'";
        }
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND DATE(ADDTIME($timesheet_table.start_time,'$offset'))<='$end_date'";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $timesheet_table.user_id=$user_id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $timesheet_table.project_id=$project_id";
        }

        $allowed_members = $this->_get_clean_value($options, "allowed_members");
        if (is_array($allowed_members) && count($allowed_members)) {
            $allowed_members = join(",", $allowed_members);
            $where .= " AND $timesheet_table.user_id IN($allowed_members)";
        } else if (!$project_id && ($allowed_members === "own_project_members" || $allowed_members === "own_project_members_excluding_own")) {
            $where .= $this->get_timesheet_own_project_memeber_only_query($timesheet_table);
        }

        //ignor sql mode here 
        try {
            $this->db->query("SET sql_mode = ''");
        } catch (\Exception $e) {
            
        }

        $timeZone = new \DateTimeZone(get_setting("timezone"));
        $dateTime = new \DateTime("now", $timeZone);
        $offset_in_gmt = $dateTime->format('P');

        $select_tz_start_time = "CONVERT_TZ($timesheet_table.start_time,'+00:00','$offset_in_gmt')";
        $select_tz_end_time = "CONVERT_TZ($timesheet_table.end_time,'+00:00','$offset_in_gmt')";

        $timesheets_data = "SELECT DATE_FORMAT($select_tz_start_time,'%d') AS day, (SUM(TIME_TO_SEC(TIMEDIFF($select_tz_end_time,$select_tz_start_time))) + SUM(ROUND(($timesheet_table.hours * 60), 0) * 60)) total_sec
                FROM $timesheet_table 
                WHERE $timesheet_table.deleted=0 AND $timesheet_table.status='logged' $where
                GROUP BY DATE($select_tz_start_time)";

        $timesheet_users_data = "SELECT $timesheet_table.user_id, (SUM(TIME_TO_SEC(TIMEDIFF($select_tz_end_time,$select_tz_start_time))) + SUM(ROUND(($timesheet_table.hours * 60), 0) * 60)) total_sec,
                CONCAT($users_table.first_name, ' ',$users_table.last_name) AS user_name, $users_table.image as user_avatar
                FROM $timesheet_table 
                LEFT JOIN $users_table ON $users_table.id = $timesheet_table.user_id
                WHERE $timesheet_table.deleted=0 AND $timesheet_table.status='logged' $where
                GROUP BY $timesheet_table.user_id";

        $info->timesheets_data = $this->db->query($timesheets_data)->getResult();
        $info->timesheet_users_data = $this->db->query($timesheet_users_data)->getResult();
        return $info;
    }

    function active_members_on_projects() {
        $users_table = $this->db->prefixTable('users');
        $projects_table = $this->db->prefixTable('projects');
        $project_time_table = $this->db->prefixTable('project_time');

        $sql = "SELECT CONCAT($users_table.first_name, ' ',$users_table.last_name) AS member_name, $users_table.image, $users_table.id,
                    (SELECT GROUP_CONCAT($project_time_table.project_id, '--::--', $projects_table.title, '--::--', $project_time_table.start_time)
                    FROM $project_time_table 
                    LEFT JOIN $projects_table ON $projects_table.id = $project_time_table.project_id
                    WHERE $project_time_table.user_id=$users_table.id AND $project_time_table.status='open' AND $project_time_table.deleted=0
                    ) AS projects_list
                FROM $users_table
                WHERE $users_table.deleted=0 AND $users_table.id IN(
                        SELECT $project_time_table.user_id 
                        FROM $project_time_table
                        WHERE $project_time_table.deleted=0 AND $project_time_table.status='open')
                ORDER BY $users_table.first_name";

        return $this->db->query($sql);
    }

    function user_has_any_timer_except_this_project($project_id, $user_id) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $project_id = $this->_get_clean_value($project_id);
        $user_id = $this->_get_clean_value($user_id);

        $sql = "SELECT COUNT($timesheet_table.id) AS total_timers FROM $timesheet_table WHERE $timesheet_table.deleted=0 AND $timesheet_table.user_id=$user_id AND $timesheet_table.project_id!=$project_id AND $timesheet_table.status='open'";

        return $this->db->query($sql)->getRow()->total_timers;
    }

    function user_has_any_timer($user_id) {
        $timesheet_table = $this->db->prefixTable('project_time');
        $user_id = $this->_get_clean_value($user_id);

        $sql = "SELECT COUNT($timesheet_table.id) AS total_timers FROM $timesheet_table WHERE $timesheet_table.deleted=0 AND $timesheet_table.user_id=$user_id AND $timesheet_table.status='open'";

        return $this->db->query($sql)->getRow()->total_timers;
    }

    function count_total_time($options = array()) {
        $attendnace_table = $this->db->prefixTable('attendance');
        $timesheet_table = $this->db->prefixTable('project_time');

        $attendance_where = "";
        $timesheet_where = "";

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $attendance_where .= " AND $attendnace_table.user_id=$user_id";
            $timesheet_where .= " AND $timesheet_table.user_id=$user_id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        $allowed_members = $this->_get_clean_value($options, "allowed_members");
        if ($project_id) {
            $timesheet_where .= " AND $timesheet_table.project_id=$project_id";

            if (is_array($allowed_members) && count($allowed_members)) {
                $allowed_members = join(",", $allowed_members);
                $timesheet_where .= " AND $timesheet_table.user_id IN($allowed_members)";
            }
        } else if ($allowed_members === "own_project_members" || $allowed_members === "own_project_members_excluding_own") {
            $timesheet_where .= $this->get_timesheet_own_project_memeber_only_query($timesheet_table);
        }

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $timesheet_where .= " AND $timesheet_table.task_id=$task_id";
        }

        $info = new \stdClass();

        $attendance_sql = "SELECT  SUM(TIME_TO_SEC(TIMEDIFF($attendnace_table.out_time,$attendnace_table.in_time))) total_sec
                FROM $attendnace_table 
                WHERE $attendnace_table.deleted=0 AND $attendnace_table.status!='incomplete' $attendance_where";
        $info->timecard_total = $this->db->query($attendance_sql)->getRow()->total_sec;

        $timesheet_sql = "SELECT (SUM(TIME_TO_SEC(TIMEDIFF($timesheet_table.end_time,$timesheet_table.start_time))) + SUM((ROUND(($timesheet_table.hours * 60), 0)) * 60)) total_sec
                FROM $timesheet_table 
                WHERE $timesheet_table.deleted=0 AND $timesheet_table.status='logged' $timesheet_where";

        $info->timesheet_total = $this->db->query($timesheet_sql)->getRow()->total_sec;

        return $info;
    }

    private function get_timesheet_own_project_memeber_only_query($timesheet_table) {
        $users_model = model("App\Models\Users_model", false);
        $login_user_id = $users_model->login_user_id();

        if ($login_user_id) {
            $project_members_table = $this->db->prefixTable('project_members');
            return " AND $timesheet_table.project_id IN(SELECT $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.deleted=0 AND $project_members_table.user_id=$login_user_id)";
        }
    }
    
    function user_has_any_open_timer_on_this_task($task_id, $user_id) {
        $timesheet_table = $this->db->prefixTable('project_time');

        $task_id = $this->_get_clean_value($task_id);
        $user_id = $this->_get_clean_value($user_id);

        $sql = "SELECT COUNT($timesheet_table.id) AS total_timers FROM $timesheet_table WHERE $timesheet_table.deleted=0 AND $timesheet_table.user_id=$user_id AND $timesheet_table.task_id=$task_id AND $timesheet_table.status='open'";

        return $this->db->query($sql)->getRow()->total_timers;
    }

}
