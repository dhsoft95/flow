<?php

namespace App\Models;

class Projects_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'projects';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $project_members_table = $this->db->prefixTable('project_members');
        $clients_table = $this->db->prefixTable('clients');
        $tasks_table = $this->db->prefixTable('tasks');
        $project_status_table = $this->db->prefixTable('project_status');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $projects_table.id=$id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $projects_table.client_id=$client_id AND $projects_table.project_type='client_project'";
        }

        $status_id = $this->_get_clean_value($options, "status_id");
        if ($status_id) {
            $where .= " AND $projects_table.status_id='$status_id'";
        }

        $status_ids = $this->_get_clean_value($options, "status_ids");
        if ($status_ids) {
            $where .= " AND (FIND_IN_SET($projects_table.status_id, '$status_ids')) ";
        }


        $project_label = $this->_get_clean_value($options, "project_label");
        if ($project_label) {
            $where .= " AND (FIND_IN_SET('$project_label', $projects_table.labels)) ";
        }


        $deadline = $this->_get_clean_value($options, "deadline");
        $for_events_table = $this->_get_clean_value($options, "for_events_table");
        if ($deadline && !$for_events_table) {
            $now = get_my_local_time("Y-m-d");
            if ($deadline === "expired") {
                $where .= " AND ($projects_table.deadline IS NOT NULL AND $projects_table.deadline<'$now')";
            } else {
                $where .= " AND ($projects_table.deadline IS NOT NULL AND $projects_table.deadline<='$deadline')";
            }
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        $start_date_for_events = $this->_get_clean_value($options, "start_date_for_events");
        if ($start_date && $deadline) {
            if ($start_date_for_events) {
                $where .= " AND ($projects_table.start_date BETWEEN '$start_date' AND '$deadline') ";
            } else {
                $where .= " AND ($projects_table.deadline BETWEEN '$start_date' AND '$deadline') ";
            }
        }


        $start_date_from = $this->_get_clean_value($options, "start_date_from");
        $start_date_to = $this->_get_clean_value($options, "start_date_to");
        if ($start_date_from && $start_date_to) {
            $where .= " AND ($projects_table.start_date BETWEEN '$start_date_from' AND '$start_date_to') ";
        }


        $extra_join = "";
        $extra_where = "";
        $user_id = $this->_get_clean_value($options, "user_id");

        $starred_projects = $this->_get_clean_value($options, "starred_projects");
        if ($starred_projects) {
            $where .= " AND FIND_IN_SET(':$user_id:',$projects_table.starred_by) ";
        }

        if (!$client_id && $user_id && !$starred_projects) {
            $extra_join = " LEFT JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.user_id=$user_id AND $project_members_table.deleted=0 GROUP BY $project_members_table.project_id) AS project_members_table ON project_members_table.project_id= $projects_table.id ";
            $extra_where = " AND project_members_table.user_id=$user_id";
        }

        $select_labels_data_query = $this->get_labels_data_query();

        //prepare custom fild binding query
        $custom_fields = get_array_value($options, "custom_fields");
        $custom_field_filter = get_array_value($options, "custom_field_filter");
        $custom_field_query_info = $this->prepare_custom_field_query_string("projects", $custom_fields, $projects_table, $custom_field_filter);
        $select_custom_fieds = get_array_value($custom_field_query_info, "select_string");
        $join_custom_fieds = get_array_value($custom_field_query_info, "join_string");
        $custom_fields_where = get_array_value($custom_field_query_info, "where_string");

        $this->db->query('SET SQL_BIG_SELECTS=1');

        $sql = "SELECT $projects_table.*, $clients_table.company_name, $clients_table.currency_symbol,  total_points_table.total_points, completed_points_table.completed_points, $project_status_table.key_name AS status_key_name, $project_status_table.title_language_key, $project_status_table.title AS status_title,  $project_status_table.icon AS status_icon, $select_labels_data_query $select_custom_fieds
        FROM $projects_table
        LEFT JOIN $clients_table ON $clients_table.id= $projects_table.client_id
        LEFT JOIN (SELECT project_id, SUM(points) AS total_points FROM $tasks_table WHERE deleted=0 GROUP BY project_id) AS  total_points_table ON total_points_table.project_id= $projects_table.id
        LEFT JOIN (SELECT project_id, SUM(points) AS completed_points FROM $tasks_table WHERE deleted=0 AND status_id=3 GROUP BY project_id) AS  completed_points_table ON completed_points_table.project_id= $projects_table.id
        LEFT JOIN $project_status_table ON $projects_table.status_id = $project_status_table.id 
        $extra_join   
        $join_custom_fieds    
        WHERE $projects_table.deleted=0 $where $extra_where $custom_fields_where
        ORDER BY $projects_table.start_date DESC";
        return $this->db->query($sql);
    }

    function get_label_suggestions() {
        $projects_table = $this->db->prefixTable('projects');
        $sql = "SELECT GROUP_CONCAT(labels) as label_groups
        FROM $projects_table
        WHERE $projects_table.deleted=0";
        return $this->db->query($sql)->getRow()->label_groups;
    }

    function count_project_status($options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $project_members_table = $this->db->prefixTable('project_members');

        $extra_join = "";
        $extra_where = "";
        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $extra_join = " LEFT JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.user_id=$user_id AND $project_members_table.deleted=0 GROUP BY $project_members_table.project_id) AS project_members_table ON project_members_table.project_id= $projects_table.id ";
            $extra_where = " AND project_members_table.user_id=$user_id";
        }

        $sql = "SELECT $projects_table.status_id, COUNT($projects_table.id) as total
        FROM $projects_table
        $extra_join
        WHERE $projects_table.deleted=0 AND ($projects_table.status_id=1 OR  $projects_table.status_id=2 OR $projects_table.status_id=3) $extra_where
        GROUP BY $projects_table.status_id";
        $result = $this->db->query($sql)->getResult();

        $info = new \stdClass();
        $info->open = 0;
        $info->completed = 0;
        $info->hold = 0;

        foreach ($result as $value) {
            if ($value->status_id == 1) {
                $info->open = $value->total;
            } else if ($value->status_id == 2) {
                $info->completed = $value->total;
            } else if ($value->status_id == 3) {
                $info->hold = $value->total;
            }
        }
        return $info;
    }

    function get_gantt_data($options = array()) {
        $tasks_table = $this->db->prefixTable('tasks');
        $milestones_table = $this->db->prefixTable('milestones');
        $users_table = $this->db->prefixTable('users');
        $task_status_table = $this->db->prefixTable('task_status');
        $project_members_table = $this->db->prefixTable('project_members');
        $projects_table = $this->db->prefixTable('projects');

        $where = "";

        $milestone_id = $this->_get_clean_value($options, "milestone_id");
        if ($milestone_id) {
            $where .= " AND $tasks_table.milestone_id=$milestone_id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $tasks_table.project_id=$project_id";
        } else {
            //show only opened project's tasks on global view
            $where .= " AND $tasks_table.project_id IN(SELECT $projects_table.id FROM $projects_table WHERE $projects_table.deleted=0 AND $projects_table.status_id=1)";
        }

        $assigned_to = $this->_get_clean_value($options, "assigned_to");
        if ($assigned_to) {
            $where .= " AND $tasks_table.assigned_to=$assigned_to";
        }

        $status_id = $this->_get_clean_value($options, "status_id");
        if ($status_id) {
            $where .= " AND $tasks_table.status_id=$status_id";
        }

        $status_ids = $this->_get_clean_value($options, "status_ids");
        if ($status_ids) {
            $where .= " AND $tasks_table.status_id IN($status_ids)";
        }

        $exclude_status = $this->_get_clean_value($options, "exclude_status");
        if ($exclude_status) {
            $where .= " AND $tasks_table.status_id!=$exclude_status";
        }


        $extra_join = "";
        $extra_where = "";
        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $extra_join = " LEFT JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.user_id=$user_id AND $project_members_table.deleted=0 GROUP BY $project_members_table.project_id) AS project_members_table ON project_members_table.project_id= $tasks_table.project_id ";
            $extra_where = " AND project_members_table.user_id=$user_id";
        }

        $show_assigned_tasks_only_user_id = $this->_get_clean_value($options, "show_assigned_tasks_only_user_id");
        if ($show_assigned_tasks_only_user_id) {
            $where .= " AND ($tasks_table.assigned_to=$show_assigned_tasks_only_user_id OR FIND_IN_SET('$show_assigned_tasks_only_user_id', $tasks_table.collaborators))";
        }

        $sql = "SELECT $tasks_table.id AS task_id, $tasks_table.title AS task_title, $tasks_table.status_id, $tasks_table.start_date, $tasks_table.deadline AS end_date, $tasks_table.parent_task_id,
             $milestones_table.id AS milestone_id, $milestones_table.title AS milestone_title, $milestones_table.due_date AS milestone_due_date, $tasks_table.assigned_to, CONCAT($users_table.first_name, ' ', $users_table.last_name ) AS assigned_to_name, $tasks_table.project_id, CONCAT($projects_table.title) AS project_name,
             $task_status_table.title AS status_title, $task_status_table.color AS status_color, $tasks_table.blocked_by, $tasks_table.blocking
                FROM $tasks_table
                LEFT JOIN $milestones_table ON $milestones_table.id= $tasks_table.milestone_id
                LEFT JOIN $users_table ON $users_table.id= $tasks_table.assigned_to
                LEFT JOIN $task_status_table ON $task_status_table.id =  $tasks_table.status_id
                LEFT JOIN $projects_table ON $projects_table.id= $tasks_table.project_id
                $extra_join
        WHERE $tasks_table.deleted=0 $where $extra_where
        ORDER BY $tasks_table.parent_task_id ASC, $tasks_table.start_date ASC";
        return $this->db->query($sql)->getResult();
    }

    function add_remove_star($project_id, $user_id, $type = "add") {
        $projects_table = $this->db->prefixTable('projects');

        $project_id = $this->_get_clean_value($project_id);
        $user_id = $this->_get_clean_value($user_id);

        $action = " CONCAT($projects_table.starred_by,',',':$user_id:') ";
        $where = " AND FIND_IN_SET(':$user_id:',$projects_table.starred_by) = 0"; //don't add duplicate

        if ($type != "add") {
            $action = " REPLACE($projects_table.starred_by, ',:$user_id:', '') ";
            $where = "";
        }

        $sql = "UPDATE $projects_table SET $projects_table.starred_by = $action
        WHERE $projects_table.id=$project_id $where";
        return $this->db->query($sql);
    }

    function get_starred_projects($user_id) {
        $projects_table = $this->db->prefixTable('projects');
        $project_status_table = $this->db->prefixTable('project_status');

        $user_id = $this->_get_clean_value($user_id);

        $sql = "SELECT $projects_table.*, $project_status_table.icon
        FROM $projects_table
        LEFT JOIN $project_status_table ON $project_status_table.id = $projects_table.status_id
        WHERE $projects_table.deleted=0 AND FIND_IN_SET(':$user_id:',$projects_table.starred_by)
        ORDER BY $projects_table.title ASC";
        return $this->db->query($sql);
    }

    function delete_project_and_sub_items($project_id) {
        $projects_table = $this->db->prefixTable('projects');
        $tasks_table = $this->db->prefixTable('tasks');
        $milestones_table = $this->db->prefixTable('milestones');
        $project_files_table = $this->db->prefixTable('project_files');
        $project_comments_table = $this->db->prefixTable('project_comments');
        $activity_logs_table = $this->db->prefixTable('activity_logs');
        $notifications_table = $this->db->prefixTable('notifications');

        $project_id = $this->_get_clean_value($project_id);

        //get project files info to delete the files from directory 
        $project_files_sql = "SELECT * FROM $project_files_table WHERE $project_files_table.deleted=0 AND $project_files_table.project_id=$project_id; ";
        $project_files = $this->db->query($project_files_sql)->getResult();

        //get project comments info to delete the files from directory 
        $project_comments_sql = "SELECT * FROM $project_comments_table WHERE $project_comments_table.deleted=0 AND $project_comments_table.project_id=$project_id; ";
        $project_comments = $this->db->query($project_comments_sql)->getResult();

        //delete the project and sub items
        $delete_project_sql = "UPDATE $projects_table SET $projects_table.deleted=1 WHERE $projects_table.id=$project_id; ";
        $this->db->query($delete_project_sql);

        $delete_tasks_sql = "UPDATE $tasks_table SET $tasks_table.deleted=1 WHERE $tasks_table.project_id=$project_id; ";
        $this->db->query($delete_tasks_sql);

        $delete_milestones_sql = "UPDATE $milestones_table SET $milestones_table.deleted=1 WHERE $milestones_table.project_id=$project_id; ";
        $this->db->query($delete_milestones_sql);

        $delete_files_sql = "UPDATE $project_files_table SET $project_files_table.deleted=1 WHERE $project_files_table.project_id=$project_id; ";
        $this->db->query($delete_files_sql);

        $delete_comments_sql = "UPDATE $project_comments_table SET $project_comments_table.deleted=1 WHERE $project_comments_table.project_id=$project_id; ";
        $this->db->query($delete_comments_sql);

        $delete_activity_logs_sql = "UPDATE $activity_logs_table SET $activity_logs_table.deleted=1 WHERE $activity_logs_table.log_for='project' AND $activity_logs_table.log_for_id=$project_id; ";
        $this->db->query($delete_activity_logs_sql);

        $delete_notifications_sql = "UPDATE $notifications_table SET $notifications_table.deleted=1 WHERE $notifications_table.project_id=$project_id; ";
        $this->db->query($delete_notifications_sql);

        //delete the comment files from directory
        $comment_file_path = get_setting("timeline_file_path");
        foreach ($project_comments as $comment_info) {
            if ($comment_info->files && $comment_info->files != "a:0:{}") {
                $files = unserialize($comment_info->files);
                foreach ($files as $file) {
                    delete_app_files($comment_file_path, array($file));
                }
            }
        }



        //delete the project files from directory
        $file_path = get_setting("project_file_path") . $project_id . "/";
        foreach ($project_files as $file) {
            delete_app_files($file_path, array(make_array_of_file($file)));
        }

        return true;
    }

    function get_search_suggestion($search = "", $options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $project_members_table = $this->db->prefixTable('project_members');

        $where = "";
        $extra_join = "";

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $extra_join = " LEFT JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.user_id=$user_id AND $project_members_table.deleted=0 GROUP BY $project_members_table.project_id) AS project_members_table ON project_members_table.project_id= $projects_table.id ";
            $where = " AND project_members_table.user_id=$user_id";
        }

        if ($search) {
            $search = $this->db->escapeLikeString($search);
        }

        $sql = "SELECT $projects_table.id, $projects_table.title
        FROM $projects_table  
        $extra_join
        WHERE $projects_table.deleted=0 AND $projects_table.title LIKE '%$search%' ESCAPE '!' $where
        ORDER BY $projects_table.title ASC
        LIMIT 0, 10";

        return $this->db->query($sql);
    }

    function count_task_points($options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $project_members_table = $this->db->prefixTable('project_members');
        $tasks_table = $this->db->prefixTable('tasks');

        $where = "";
        $extra_join = "";

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $extra_join = " LEFT JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id FROM $project_members_table WHERE $project_members_table.user_id=$user_id AND $project_members_table.deleted=0 GROUP BY $project_members_table.project_id) AS project_members_table ON project_members_table.project_id= $projects_table.id ";
            $where = " AND project_members_table.user_id=$user_id";
        }

        $sql = "SELECT SUM(total_points_table.total_points) AS total_points, SUM(completed_points_table.completed_points) AS completed_points
        FROM $projects_table
        LEFT JOIN (SELECT project_id, SUM(points) AS total_points FROM $tasks_table WHERE deleted=0 GROUP BY project_id) AS  total_points_table ON total_points_table.project_id= $projects_table.id
        LEFT JOIN (SELECT project_id, SUM(points) AS completed_points FROM $tasks_table WHERE deleted=0 AND status_id=3 GROUP BY project_id) AS  completed_points_table ON completed_points_table.project_id= $projects_table.id  
        $extra_join
        WHERE $projects_table.deleted=0 AND status_id=1 $where";
        return $this->db->query($sql)->getRow();
    }

    function get_team_members_summary($options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $project_members_table = $this->db->prefixTable('project_members');
        $users_table = $this->db->prefixTable('users');
        $timesheet_table = $this->db->prefixTable('project_time');
        $tasks_table = $this->db->prefixTable('tasks');

        $timeZone = new \DateTimeZone(get_setting("timezone"));
        $dateTime = new \DateTime("now", $timeZone);
        $offset_in_gmt = $dateTime->format('P');

        $select_tz_start_time = "CONVERT_TZ($timesheet_table.start_time,'+00:00','$offset_in_gmt')";
        $select_tz_end_time = "CONVERT_TZ($timesheet_table.end_time,'+00:00','$offset_in_gmt')";

        try {
            $this->db->query("SET sql_mode = ''");
        } catch (\Exception $e) {
            
        }

        $projects_where = "";

        $start_date_from = $this->_get_clean_value($options, "start_date_from");
        $start_date_to = $this->_get_clean_value($options, "start_date_to");
        if ($start_date_from && $start_date_to) {
            $projects_where .= " AND ($projects_table.start_date BETWEEN '$start_date_from' AND '$start_date_to') ";
        }

        $sql = "SELECT  $users_table.id as team_member_id, CONCAT($users_table.first_name, ' ', $users_table.last_name) AS team_member_name, $users_table.image, 
                SUM(project_details.open_tasks) AS open_tasks, SUM(project_details.completed_tasks) AS completed_tasks,
                SUM(project_details.open_project) AS open_projects, SUM(project_details.completed_project) AS completed_projects , SUM(project_details.hold_project) AS hold_projects,
                SUM(project_details.total_secconds_worked) AS total_secconds_worked
                FROM $users_table
                INNER JOIN (SELECT $project_members_table.user_id, $project_members_table.project_id, 
                    tasks_table.open_tasks, tasks_table.completed_tasks, timesheet_table.total_secconds_worked,
                    $projects_table.start_date, IF($projects_table.status_id=1,1,0) AS open_project,  IF($projects_table.status_id=2,1,0) AS completed_project,  IF($projects_table.status_id=3,1,0) AS hold_project
                FROM  $project_members_table
                LEFT JOIN (SELECT SUM(IF($tasks_table.status_id=3,1,0)) AS completed_tasks, SUM(IF($tasks_table.status_id!=3,1,0)) AS open_tasks, $tasks_table.project_id, $tasks_table.assigned_to FROM $tasks_table WHERE $tasks_table.deleted=0 AND $tasks_table.assigned_to!=0 AND $tasks_table.project_id!=0 GROUP BY $tasks_table.project_id, $tasks_table.assigned_to
                           ) AS tasks_table ON tasks_table.project_id = $project_members_table.project_id AND tasks_table.assigned_to = $project_members_table.user_id
                LEFT JOIN (SELECT SUM(TIME_TO_SEC(TIMEDIFF($select_tz_end_time,$select_tz_start_time))) + SUM(ROUND(($timesheet_table.hours * 60), 0) * 60) AS total_secconds_worked, $timesheet_table.project_id, $timesheet_table.user_id FROM $timesheet_table WHERE $timesheet_table.deleted=0 GROUP BY $timesheet_table.project_id, $timesheet_table.user_id 
                           ) AS timesheet_table ON timesheet_table.project_id = $project_members_table.project_id AND timesheet_table.user_id = $project_members_table.user_id 
                               
                INNER JOIN $projects_table ON $projects_table.id = $project_members_table.project_id AND $projects_table.deleted=0 $projects_where) AS project_details ON project_details.user_id=$users_table.id
                WHERE $users_table.deleted = 0 AND $users_table.status='active' AND $users_table.user_type='staff'
                GROUP BY $users_table.id
                ";

        return $this->db->query($sql);
    }

    function get_clients_summary($options = array()) {
        $projects_table = $this->db->prefixTable('projects');
        $clients_table = $this->db->prefixTable('clients');
        $timesheet_table = $this->db->prefixTable('project_time');
        $tasks_table = $this->db->prefixTable('tasks');

        $timeZone = new \DateTimeZone(get_setting("timezone"));
        $dateTime = new \DateTime("now", $timeZone);
        $offset_in_gmt = $dateTime->format('P');

        $select_tz_start_time = "CONVERT_TZ($timesheet_table.start_time,'+00:00','$offset_in_gmt')";
        $select_tz_end_time = "CONVERT_TZ($timesheet_table.end_time,'+00:00','$offset_in_gmt')";

        try {
            $this->db->query("SET sql_mode = ''");
        } catch (\Exception $e) {
            
        }

        $projects_where = "";

        $start_date_from = $this->_get_clean_value($options, "start_date_from");
        $start_date_to = $this->_get_clean_value($options, "start_date_to");
        if ($start_date_from && $start_date_to) {
            $projects_where .= " AND ($projects_table.start_date BETWEEN '$start_date_from' AND '$start_date_to') ";
        }

        $sql = "SELECT  $clients_table.id as client_id, $clients_table.company_name AS client_name,
                project_details.open_tasks, project_details.completed_tasks,
                project_details.open_projects, project_details.completed_projects , project_details.hold_projects,
                project_details.total_secconds_worked
                FROM $clients_table
                INNER JOIN (SELECT $projects_table.client_id,
                    SUM(tasks_table.open_tasks) AS open_tasks, SUM(tasks_table.completed_tasks) AS completed_tasks, SUM(timesheet_table.total_secconds_worked) AS total_secconds_worked,
                    SUM(IF($projects_table.status_id=1,1,0)) AS open_projects,  SUM(IF($projects_table.status_id=2,1,0)) AS completed_projects,  SUM(IF($projects_table.status_id=3,1,0)) AS hold_projects
                FROM  $projects_table
                LEFT JOIN (SELECT SUM(IF($tasks_table.status_id=3,1,0)) AS completed_tasks, SUM(IF($tasks_table.status_id!=3,1,0)) AS open_tasks, $tasks_table.project_id FROM $tasks_table WHERE $tasks_table.deleted=0 AND $tasks_table.project_id!=0 GROUP BY $tasks_table.project_id
                           ) AS tasks_table ON tasks_table.project_id = $projects_table.id
                LEFT JOIN (SELECT SUM(TIME_TO_SEC(TIMEDIFF($select_tz_end_time,$select_tz_start_time))) + SUM(ROUND(($timesheet_table.hours * 60), 0) * 60) AS total_secconds_worked, $timesheet_table.project_id FROM $timesheet_table WHERE $timesheet_table.deleted=0 GROUP BY $timesheet_table.project_id
                           ) AS timesheet_table ON timesheet_table.project_id = $projects_table.id
                WHERE $projects_table.deleted=0 $projects_where
                GROUP BY $projects_table.client_id    
                ) AS project_details ON project_details.client_id=$clients_table.id
                WHERE $clients_table.deleted=0
                GROUP BY $clients_table.id
                ";

        return $this->db->query($sql);
    }

    function get_projects_id_and_name() {
        $projects_table = $this->db->prefixTable('projects');

        $sql = "SELECT id, title
        FROM $projects_table 
        WHERE $projects_table.deleted=0 AND $projects_table.status_id=1";
        return $this->db->query($sql);
    }

}
