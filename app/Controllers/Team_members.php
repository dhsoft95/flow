<?php

namespace App\Controllers;

use App\Libraries\Excel_import;

class Team_members extends Security_Controller {

    use Excel_import;

    private $roles_id_by_title = array();

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();
    }

    private function can_view_team_members_contact_info() {
        if ($this->login_user->user_type == "staff") {
            if ($this->login_user->is_admin) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_view_team_members_contact_info") == "1") {
                return true;
            }
        }
    }

    private function can_view_team_members_social_links() {
        if ($this->login_user->user_type == "staff") {
            if ($this->login_user->is_admin) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_view_team_members_social_links") == "1") {
                return true;
            }
        }
    }

    private function update_only_allowed_members($user_id) {
        if ($this->can_update_team_members_info($user_id)) {
            return true; //own profile
        } else {
            app_redirect("forbidden");
        }
    }

    //only admin can change other user's info
    //none admin users can only change his/her own info
    //allowed members can update other members info    
    private function can_update_team_members_info($user_id) {
        $access_info = $this->get_access_info("team_member_update_permission");

        if ($this->login_user->id === $user_id) {
            return true; //own profile
        } else if ($access_info->access_type == "all") {
            return true; //has access to change all user's profile
        } else if ($user_id && in_array($user_id, $access_info->allowed_members)) {
            return true; //has permission to update this user's profile
        } else {

            return false;
        }
    }

    //only admin/permitted users can change other user's info
    //other users can only change his/her own info
    private function can_access_user_settings($user_id) {
        if ($user_id && ($this->login_user->is_admin || $this->login_user->id === $user_id || get_array_value($this->login_user->permissions, "can_manage_user_role_and_permissions"))) {
            return true;
        } else {
            app_redirect("forbidden");
        }
    }

    private function _can_activate_deactivate_team_member($member_info) {

        if ($member_info && !$this->is_own_id($member_info->id) && ($this->login_user->is_admin || (get_array_value($this->login_user->permissions, "can_activate_deactivate_team_members") && $member_info->is_admin != 1))) {
            return true;
        }
        return false;
    }

    private function _can_delete_team_member($member_info) {

        //can't delete own user
        //only admin can delete other admin users.
        //non-admin users can delete other users but can't delete admin user. 
        if ($member_info && !$this->is_own_id($member_info->id) && ($this->login_user->is_admin || (get_array_value($this->login_user->permissions, "can_delete_team_members") && $member_info->is_admin != 1))) {
            return true;
        }
        return false;
    }

    public function index() {
        if (!$this->can_view_team_members_list()) {
            app_redirect("forbidden");
        }

        $view_data["show_contact_info"] = $this->can_view_team_members_contact_info();

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("team_members", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("team_members", $this->login_user->is_admin, $this->login_user->user_type);

        return $this->template->rander("team_members/index", $view_data);
    }

    private function access_only_admin_or_member_creator() {
        if (!($this->login_user->is_admin || get_array_value($this->login_user->permissions, "can_add_or_invite_new_team_members"))) {
            app_redirect("forbidden");
        }
    }

    /* open new member modal */

    function modal_form() {
        $this->access_only_admin_or_member_creator();

        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $view_data['role_dropdown'] = $this->_get_roles_dropdown();

        $id = $this->request->getPost('id');
        $options = array(
            "id" => $id,
        );

        $view_data['model_info'] = $this->Users_model->get_details($options)->getRow();

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("team_members", 0, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        return $this->template->view('team_members/modal_form', $view_data);
    }

    /* save new member */

    function add_team_member() {
        $this->access_only_admin_or_member_creator();

        //check duplicate email address, if found then show an error message
        if ($this->Users_model->is_email_exists($this->request->getPost('email'))) {
            echo json_encode(array("success" => false, 'message' => app_lang('duplicate_email')));
            exit();
        }

        $this->validate_submitted_data(array(
            "email" => "required|valid_email",
            "first_name" => "required",
            "last_name" => "required",
            "job_title" => "required",
            "role" => "required"
        ));

        $password = $this->request->getPost("password");

        $user_data = array(
            "email" => $this->request->getPost('email'),
            "first_name" => $this->request->getPost('first_name'),
            "last_name" => $this->request->getPost('last_name'),
            "is_admin" => $this->request->getPost('is_admin'),
            "address" => $this->request->getPost('address'),
            "phone" => $this->request->getPost('phone'),
            "gender" => $this->request->getPost('gender'),
            "job_title" => $this->request->getPost('job_title'),
            "phone" => $this->request->getPost('phone'),
            "gender" => $this->request->getPost('gender'),
            "user_type" => "staff",
            "created_at" => get_current_utc_time()
        );

        if ($password) {
            $user_data["password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        //make role id or admin permission 
        $role = $this->request->getPost('role');
        $role_id = $role;

        if ($this->login_user->is_admin && $role === "admin") {
            $user_data["is_admin"] = 1;
            $user_data["role_id"] = 0;
        } else {
            $user_data["is_admin"] = 0;
            $user_data["role_id"] = $role_id;
        }


        //add a new team member
        $user_id = $this->Users_model->ci_save($user_data);
        if ($user_id) {
            //user added, now add the job info for the user
            $job_data = array(
                "user_id" => $user_id,
                "salary" => $this->request->getPost('salary') ? $this->request->getPost('salary') : 0,
                "salary_term" => $this->request->getPost('salary_term'),
                "date_of_hire" => $this->request->getPost('date_of_hire')
            );
            $this->Users_model->save_job_info($job_data);

            save_custom_fields("team_members", $user_id, $this->login_user->is_admin, $this->login_user->user_type);

            //send login details to user
            if ($this->request->getPost('email_login_details')) {

                //get the login details template
                $email_template = $this->Email_templates_model->get_final_template("login_info"); //use default template

                $parser_data["SIGNATURE"] = $email_template->signature;
                $parser_data["USER_FIRST_NAME"] = $user_data["first_name"];
                $parser_data["USER_LAST_NAME"] = $user_data["last_name"];
                $parser_data["USER_LOGIN_EMAIL"] = $user_data["email"];
                $parser_data["USER_LOGIN_PASSWORD"] = $this->request->getPost('password');
                $parser_data["DASHBOARD_URL"] = base_url();
                $parser_data["LOGO_URL"] = get_logo_url();
                $parser_data["RECIPIENTS_EMAIL_ADDRESS"] = $user_data["email"];

                $message = $this->parser->setData($parser_data)->renderString($email_template->message);
                $subject = $this->parser->setData($parser_data)->renderString($email_template->subject);

                send_app_mail($this->request->getPost('email'), $subject, $message);
            }
        }

        if ($user_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($user_id), 'id' => $user_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* open invitation modal */

    function invitation_modal() {
        $this->access_only_admin_or_member_creator();

        $role_dropdown = array(
            "0" => app_lang('team_member')
        );

        $roles = $this->Roles_model->get_all()->getResult();
        foreach ($roles as $role) {
            $role_dropdown[$role->id] = $role->title;
        }

        $view_data['role_dropdown'] = $role_dropdown;

        return $this->template->view('team_members/invitation_modal', $view_data);
    }

    //send a team member invitation to an email address
    function send_invitation() {
        $this->access_only_admin_or_member_creator();

        $this->validate_submitted_data(array(
            "email.*" => "required|valid_email"
        ));

        $email_array = $this->request->getPost('email');
        $email_array = array_unique($email_array);

        //get the send invitation template 
        $email_template = $this->Email_templates_model->get_final_template("team_member_invitation"); //use default template

        $parser_data["INVITATION_SENT_BY"] = $this->login_user->first_name . " " . $this->login_user->last_name;
        $parser_data["SIGNATURE"] = $email_template->signature;
        $parser_data["SITE_URL"] = get_uri();
        $parser_data["LOGO_URL"] = get_logo_url();

        $send_email = array();

        $role_id = $this->request->getPost('role');

        foreach ($email_array as $email) {
            $verification_data = array(
                "type" => "invitation",
                "code" => make_random_string(),
                "params" => serialize(array(
                    "email" => $email,
                    "type" => "staff",
                    "expire_time" => time() + (24 * 60 * 60), //make the invitation url with 24hrs validity
                    "role_id" => $role_id
                ))
            );

            $save_id = $this->Verification_model->ci_save($verification_data);
            $verification_info = $this->Verification_model->get_one($save_id);

            $parser_data['INVITATION_URL'] = get_uri("signup/accept_invitation/" . $verification_info->code);

            //send invitation email
            $message = $this->parser->setData($parser_data)->renderString($email_template->message);
            $subject = $this->parser->setData($parser_data)->renderString($email_template->subject);

            $send_email[] = send_app_mail($email, $subject, $message);
        }

        if (!in_array(false, $send_email)) {
            if (count($send_email) != 0 && count($send_email) == 1) {
                echo json_encode(array('success' => true, 'message' => app_lang("invitation_sent")));
            } else {
                echo json_encode(array('success' => true, 'message' => app_lang("invitations_sent")));
            }
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
        }
    }

    //prepere the data for members list
    function list_data() {
        if (!$this->can_view_team_members_list()) {
            app_redirect("forbidden");
        }

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("team_members", $this->login_user->is_admin, $this->login_user->user_type);
        $options = array(
            "status" => $this->request->getPost("status"),
            "user_type" => "staff",
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("team_members", $this->login_user->is_admin, $this->login_user->user_type)
        );

        $list_data = $this->Users_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data, $custom_fields);
        }
        echo json_encode(array("data" => $result));
    }

    //get a row data for member list
    function _row_data($id) {
        validate_numeric_value($id);
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("team_members", $this->login_user->is_admin, $this->login_user->user_type);
        $options = array(
            "id" => $id,
            "custom_fields" => $custom_fields
        );

        $data = $this->Users_model->get_details($options)->getRow();
        return $this->_make_row($data, $custom_fields);
    }

    //prepare team member list row
    private function _make_row($data, $custom_fields) {
        $image_url = get_avatar($data->image);
        $user_avatar = "<span class='avatar avatar-xs'><img src='$image_url' alt='...'></span>";
        $full_name = $data->first_name . " " . $data->last_name . " ";

        //check contact info view permissions
        $show_cotact_info = $this->can_view_team_members_contact_info();

        $row_data = array(
            $user_avatar,
            get_team_member_profile_link($data->id, $full_name),
            $data->job_title,
            $show_cotact_info ? $data->email : "",
            $show_cotact_info && $data->phone ? $data->phone : "-"
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $delete_link = "";
        if ($this->_can_delete_team_member($data)) {
            $delete_link = js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_team_member'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("team_members/delete"), "data-action" => "delete-confirmation"));
        }

        $row_data[] = $delete_link;

        return $row_data;
    }

    //delete a team member
    function delete() {


        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $user_info = $this->Users_model->get_one($id);
        if (!$this->_can_delete_team_member($user_info)) {
            app_redirect("forbidden");
        }

        if ($this->Users_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    //show team member's details view
    function view($id = 0, $tab = "") {
        if ($id * 1) {
            validate_numeric_value($id);

            //if team member's list is disabled, but the user can see his/her own profile.
            if (!$this->can_view_team_members_list() && $this->login_user->id != $id) {
                app_redirect("forbidden");
            }



            //we have an id. view the team_member's profie
            $options = array("id" => $id, "user_type" => "staff");
            $user_info = $this->Users_model->get_details($options)->getRow();
            if ($user_info) {

                //check which tabs are viewable for current logged in user
                $view_data['show_timeline'] = get_setting("module_timeline") ? true : false;

                $can_update_team_members_info = $this->can_update_team_members_info($id);

                $view_data['show_general_info'] = $can_update_team_members_info;
                $view_data['show_job_info'] = false;

                if ($this->login_user->is_admin || $user_info->id === $this->login_user->id || $this->has_job_info_manage_permission()) {
                    $view_data['show_job_info'] = true;
                }

                $view_data['show_account_settings'] = false;

                $show_attendance = false;
                $show_leave = false;

                $expense_access_info = $this->get_access_info("expense");
                $view_data["show_expense_info"] = (get_setting("module_expense") == "1" && $expense_access_info->access_type == "all") ? true : false;

                //admin can access all members attendance and leave
                //none admin users can only access to his/her own information 

                if ($this->login_user->is_admin || $user_info->id === $this->login_user->id || get_array_value($this->login_user->permissions, "can_manage_user_role_and_permissions")) {
                    $show_attendance = true;
                    $show_leave = true;
                    $view_data['show_account_settings'] = true;
                } else {
                    //none admin users but who has access to this team member's attendance and leave can access this info
                    $access_timecard = $this->get_access_info("attendance");
                    if ($access_timecard->access_type === "all" || in_array($user_info->id, $access_timecard->allowed_members)) {
                        $show_attendance = true;
                    }

                    $access_leave = $this->get_access_info("leave");
                    if ($access_leave->access_type === "all" || in_array($user_info->id, $access_leave->allowed_members)) {
                        $show_leave = true;
                    }
                }


                //check module availability
                $view_data['show_attendance'] = $show_attendance && get_setting("module_attendance") ? true : false;
                $view_data['show_leave'] = $show_leave && get_setting("module_leave") ? true : false;

                //check contact info view permissions
                $show_cotact_info = $this->can_view_team_members_contact_info();
                $show_social_links = $this->can_view_team_members_social_links();

                //own info is always visible
                if ($id == $this->login_user->id) {
                    $show_cotact_info = true;
                    $show_social_links = true;
                }

                $view_data['show_cotact_info'] = $show_cotact_info;
                $view_data['show_social_links'] = $show_social_links;

                //show projects tab to admin
                $view_data['show_projects'] = false;
                if ($this->login_user->is_admin) {
                    $view_data['show_projects'] = true;
                }

                $view_data['show_projects_count'] = false;
                if ($this->can_manage_all_projects() && !$this->has_all_projects_restricted_role()) {
                    $view_data['show_projects_count'] = true;
                }

                $view_data['tab'] = clean_data($tab); //selected tab
                $view_data['user_info'] = $user_info;
                $view_data['social_link'] = $this->Social_links_model->get_one($id);

                $hide_send_message_button = true;
                $this->init_permission_checker("message_permission");
                if ($this->check_access_on_messages_for_this_user() && $this->validate_sending_message($id)) {
                    $hide_send_message_button = false;
                }
                $view_data['hide_send_message_button'] = $hide_send_message_button;

                $view_data["show_notes"] = false;
                if ($this->can_access_team_members_note($user_info->id)) {
                    $view_data["show_notes"] = true;
                }

                $view_data["show_timesheets"] = false;
                $access_timesheets = $this->get_access_info("timesheet_manage_permission");
                if (get_setting("module_project_timesheet") == "1" && ($this->login_user->is_admin || ($this->login_user->id === $user_info->id) || ($access_timesheets->access_type && !$this->has_all_projects_restricted_role()))) {
                    $view_data["show_timesheets"] = true;
                }

                return $this->template->rander("team_members/view", $view_data);
            } else {
                show_404();
            }
        } else {

            if (!$this->can_view_team_members_list()) {
                app_redirect("forbidden");
            }

            //we don't have any specific id to view. show the list of team_member
            $view_data['team_members'] = $this->Users_model->get_details(array("user_type" => "staff", "status" => "active"))->getResult();
            return $this->template->rander("team_members/profile_card", $view_data);
        }
    }

    //show the job information of a team member
    function job_info($user_id) {

        validate_numeric_value($user_id);
        if (!($this->login_user->is_admin || $this->login_user->id === $user_id || $this->has_job_info_manage_permission())) {
            app_redirect("forbidden");
        }

        $options = array("id" => $user_id);
        $user_info = $this->Users_model->get_details($options)->getRow();

        $view_data['user_id'] = $user_id;
        $view_data['job_info'] = $this->Users_model->get_job_info($user_id);
        $view_data['job_info']->job_title = $user_info->job_title;

        $view_data['can_manage_team_members_job_information'] = $this->has_job_info_manage_permission();

        return $this->template->view("team_members/job_info", $view_data);
    }

    private function has_job_info_manage_permission() {
        return get_array_value($this->login_user->permissions, "job_info_manage_permission");
    }

    //save job information of a team member
    function save_job_info() {
        if (!($this->login_user->is_admin || $this->has_job_info_manage_permission())) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data(array(
            "user_id" => "required|numeric"
        ));

        $user_id = $this->request->getPost('user_id');

        $job_data = array(
            "user_id" => $user_id,
            "salary" => unformat_currency($this->request->getPost('salary')),
            "salary_term" => $this->request->getPost('salary_term'),
            "date_of_hire" => $this->request->getPost('date_of_hire')
        );

        //we'll save the job title in users table
        $user_data = array(
            "job_title" => $this->request->getPost('job_title')
        );

        $this->Users_model->ci_save($user_data, $user_id);
        if ($this->Users_model->save_job_info($job_data)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_updated')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //show general information of a team member
    function general_info($user_id) {
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);

        $view_data['user_info'] = $this->Users_model->get_one($user_id);
        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("team_members", $user_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        return $this->template->view("team_members/general_info", $view_data);
    }

    //save general information of a team member
    function save_general_info($user_id) {
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);

        $this->validate_submitted_data(array(
            "first_name" => "required",
            "last_name" => "required"
        ));

        $user_data = array(
            "first_name" => $this->request->getPost('first_name'),
            "last_name" => $this->request->getPost('last_name'),
            "address" => $this->request->getPost('address'),
            "phone" => $this->request->getPost('phone'),
            "skype" => $this->request->getPost('skype'),
            "gender" => $this->request->getPost('gender'),
            "alternative_address" => $this->request->getPost('alternative_address'),
            "alternative_phone" => $this->request->getPost('alternative_phone'),
            "dob" => $this->request->getPost('dob'),
            "ssn" => $this->request->getPost('ssn')
        );

        $user_data = clean_data($user_data);

        $user_info_updated = $this->Users_model->ci_save($user_data, $user_id);

        save_custom_fields("team_members", $user_id, $this->login_user->is_admin, $this->login_user->user_type);

        if ($user_info_updated) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_updated')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //show social links of a team member
    function social_links($user_id) {
        //important! here id=user_id
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);

        $view_data['user_id'] = $user_id;
        $view_data['model_info'] = $this->Social_links_model->get_one($user_id);
        return $this->template->view("users/social_links", $view_data);
    }

    //save social links of a team member
    function save_social_links($user_id) {
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);

        $id = 0;
        $has_social_links = $this->Social_links_model->get_one($user_id);
        if (isset($has_social_links->id)) {
            $id = $has_social_links->id;
        }

        $social_link_data = array(
            "facebook" => $this->request->getPost('facebook'),
            "twitter" => $this->request->getPost('twitter'),
            "linkedin" => $this->request->getPost('linkedin'),
            "digg" => $this->request->getPost('digg'),
            "youtube" => $this->request->getPost('youtube'),
            "pinterest" => $this->request->getPost('pinterest'),
            "instagram" => $this->request->getPost('instagram'),
            "github" => $this->request->getPost('github'),
            "tumblr" => $this->request->getPost('tumblr'),
            "vine" => $this->request->getPost('vine'),
            "whatsapp" => $this->request->getPost('whatsapp'),
            "user_id" => $user_id,
            "id" => $id ? $id : $user_id
        );

        $social_link_data = clean_data($social_link_data);

        $this->Social_links_model->ci_save($social_link_data, $id);
        echo json_encode(array("success" => true, 'message' => app_lang('record_updated')));
    }

    //show account settings of a team member
    function account_settings($user_id) {
        validate_numeric_value($user_id);
        $this->can_access_user_settings($user_id);

        $view_data['user_info'] = $this->Users_model->get_one($user_id);
        if ($view_data['user_info']->is_admin) {
            $view_data['user_info']->role_id = "admin";
        }
        $view_data['role_dropdown'] = $this->_get_roles_dropdown();
        $view_data['can_activate_deactivate_team_members'] = $this->_can_activate_deactivate_team_member($view_data['user_info']);

        return $this->template->view("users/account_settings", $view_data);
    }

    //show my preference settings of a team member
    function my_preferences() {
        $view_data["user_info"] = $this->Users_model->get_one($this->login_user->id);

        //language dropdown
        $view_data['language_dropdown'] = array();
        if (!get_setting("disable_language_selector_for_team_members")) {
            $view_data['language_dropdown'] = get_language_list();
        }

        $view_data["hidden_topbar_menus_dropdown"] = $this->get_hidden_topbar_menus_dropdown();
        $view_data["recently_meaning_dropdown"] = $this->get_recently_meaning_dropdown();

        return $this->template->view("team_members/my_preferences", $view_data);
    }

    function save_my_preferences() {
        //setting preferences
        $settings = array("notification_sound_volume", "disable_push_notification", "hidden_topbar_menus", "disable_keyboard_shortcuts", "recently_meaning", "reminder_sound_volume", "reminder_snooze_length");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            $value = clean_data($value);

            $this->Settings_model->save_setting("user_" . $this->login_user->id . "_" . $setting, $value, "user");
        }

        //there was 3 settings in users table.
        //so, update the users table also

        $user_data = array(
            "enable_web_notification" => $this->request->getPost("enable_web_notification"),
            "enable_email_notification" => $this->request->getPost("enable_email_notification"),
        );

        if (!get_setting("disable_language_selector_for_team_members")) {
            $user_data["language"] = $this->request->getPost("personal_language");
        }

        $user_data = clean_data($user_data);

        $this->Users_model->ci_save($user_data, $this->login_user->id);

        try {
            app_hooks()->do_action("app_hook_team_members_my_preferences_save_data");
        } catch (\Exception $ex) {
            log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function save_personal_language($language) {
        if (!get_setting("disable_language_selector_for_team_members") && ($language || $language === "0")) {

            $language = clean_data($language);
            $data["language"] = strtolower($language);

            $this->Users_model->ci_save($data, $this->login_user->id);
        }
    }

    //save account settings of a team member
    function save_account_settings($user_id) {
        validate_numeric_value($user_id);
        $this->can_access_user_settings($user_id);

        if ($this->Users_model->is_email_exists($this->request->getPost('email'), $user_id)) {
            echo json_encode(array("success" => false, 'message' => app_lang('duplicate_email')));
            exit();
        }

        $account_data = array(
            "email" => $this->request->getPost('email')
        );

        $role = $this->request->getPost('role');
        $user_info = $this->Users_model->get_one($user_id);

        if (!$this->is_own_id($user_id) && ($this->login_user->is_admin || (!$user_info->is_admin && $this->has_role_manage_permission() && !$this->is_admin_role($role)))) {
            //only admin user/eligible user has permission to update team member's role
            //but admin user/eligible user can't update his/her own role 
            //eligible user can't update admin user's role or can't give admin role to anyone
            $role_id = $role;

            if ($this->login_user->is_admin && $role === "admin") {
                $account_data["is_admin"] = 1;
                $account_data["role_id"] = 0;
            } else {
                $account_data["is_admin"] = 0;
                $account_data["role_id"] = $role_id;
            }

            if ($this->_can_activate_deactivate_team_member($user_info)) {
                $account_data['disable_login'] = $this->request->getPost('disable_login');
                $account_data['status'] = $this->request->getPost('status') === "inactive" ? "inactive" : "active";
            }
        }

        //don't reset password if user doesn't entered any password
        if ($this->request->getPost('password') && ($this->login_user->is_admin || $this->is_own_id($user_id))) {
            $account_data['password'] = password_hash($this->request->getPost("password"), PASSWORD_DEFAULT);
        }

        if ($this->Users_model->ci_save($account_data, $user_id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_updated')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //save profile image of a team member
    function save_profile_image($user_id = 0) {
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);
        $user_info = $this->Users_model->get_one($user_id);

        //process the the file which has uploaded by dropzone
        $profile_image = str_replace("~", ":", $this->request->getPost("profile_image"));

        if ($profile_image) {
            $profile_image = serialize(move_temp_file("avatar.png", get_setting("profile_image_path"), "", $profile_image));

            //delete old file
            delete_app_files(get_setting("profile_image_path"), array(@unserialize($user_info->image)));

            $image_data = array("image" => $profile_image);

            $this->Users_model->ci_save($image_data, $user_id);
            echo json_encode(array("success" => true, 'message' => app_lang('profile_image_changed')));
        }

        //process the the file which has uploaded using manual file submit
        if ($_FILES) {
            $profile_image_file = get_array_value($_FILES, "profile_image_file");
            $image_file_name = get_array_value($profile_image_file, "tmp_name");
            $image_file_size = get_array_value($profile_image_file, "size");
            if ($image_file_name) {
                if (!$this->check_profile_image_dimension($image_file_name)) {
                    echo json_encode(array("success" => false, 'message' => app_lang('profile_image_error_message')));
                    exit();
                }

                $profile_image = serialize(move_temp_file("avatar.png", get_setting("profile_image_path"), "", $image_file_name, "", "", false, $image_file_size));

                //delete old file
                if ($user_info->image) {
                    delete_app_files(get_setting("profile_image_path"), array(@unserialize($user_info->image)));
                }

                $image_data = array("image" => $profile_image);
                $this->Users_model->ci_save($image_data, $user_id);
                echo json_encode(array("success" => true, 'message' => app_lang('profile_image_changed'), "reload_page" => true));
            }
        }
    }

    //show projects list of a team member
    function projects_info($user_id) {
        if ($user_id) {
            validate_numeric_value($user_id);
            $view_data['user_id'] = $user_id;
            $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);
            $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("projects", $this->login_user->is_admin, $this->login_user->user_type);
            $view_data['project_statuses'] = $this->Project_status_model->get_details()->getResult();
            return $this->template->view("team_members/projects_info", $view_data);
        }
    }

    //show attendance list of a team member
    function attendance_info($user_id) {
        if ($user_id) {
            validate_numeric_value($user_id);
            $view_data['user_id'] = $user_id;
            return $this->template->view("team_members/attendance_info", $view_data);
        }
    }

    //show weekly attendance list of a team member
    function weekly_attendance() {
        return $this->template->view("team_members/weekly_attendance");
    }

    //show weekly attendance list of a team member
    function custom_range_attendance() {
        return $this->template->view("team_members/custom_range_attendance");
    }

    //show attendance summary of a team member
    function attendance_summary($user_id) {
        validate_numeric_value($user_id);
        $view_data["user_id"] = $user_id;
        return $this->template->view("team_members/attendance_summary", $view_data);
    }

    //show leave list of a team member
    function leave_info($applicant_id) {
        if ($applicant_id) {
            validate_numeric_value($applicant_id);
            $view_data['applicant_id'] = $applicant_id;
            return $this->template->view("team_members/leave_info", $view_data);
        }
    }

    //show yearly leave list of a team member
    function yearly_leaves() {
        return $this->template->view("team_members/yearly_leaves");
    }

    //show yearly leave list of a team member
    function expense_info($user_id) {
        validate_numeric_value($user_id);
        $view_data["user_id"] = $user_id;
        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("expenses", $this->login_user->is_admin, $this->login_user->user_type);
        return $this->template->view("team_members/expenses", $view_data);
    }

    /* load files tab */

    function files($user_id) {
        validate_numeric_value($user_id);
        $this->update_only_allowed_members($user_id);

        $options = array("user_id" => $user_id);
        $view_data['files'] = $this->General_files_model->get_details($options)->getResult();
        $view_data['user_id'] = $user_id;
        return $this->template->view("team_members/files/index", $view_data);
    }

    /* file upload modal */

    function file_modal_form() {
        $view_data['model_info'] = $this->General_files_model->get_one($this->request->getPost('id'));
        $user_id = $this->request->getPost('user_id') ? $this->request->getPost('user_id') : $view_data['model_info']->user_id;

        $this->update_only_allowed_members($user_id);

        $view_data['user_id'] = $user_id;
        return $this->template->view('team_members/files/modal_form', $view_data);
    }

    /* save file data and move temp file to parmanent file directory */

    function save_file() {


        $this->validate_submitted_data(array(
            "id" => "numeric",
            "user_id" => "required|numeric"
        ));

        $user_id = $this->request->getPost('user_id');
        $this->update_only_allowed_members($user_id);

        $files = $this->request->getPost("files");
        $success = false;
        $now = get_current_utc_time();

        $target_path = getcwd() . "/" . get_general_file_path("team_members", $user_id);

        //process the fiiles which has been uploaded by dropzone
        if ($files && get_array_value($files, 0)) {
            foreach ($files as $file) {
                $file_name = $this->request->getPost('file_name_' . $file);
                $file_info = move_temp_file($file_name, $target_path);
                if ($file_info) {
                    $data = array(
                        "user_id" => $user_id,
                        "file_name" => get_array_value($file_info, 'file_name'),
                        "file_id" => get_array_value($file_info, 'file_id'),
                        "service_type" => get_array_value($file_info, 'service_type'),
                        "description" => $this->request->getPost('description_' . $file),
                        "file_size" => $this->request->getPost('file_size_' . $file),
                        "created_at" => $now,
                        "uploaded_by" => $this->login_user->id
                    );
                    $success = $this->General_files_model->ci_save($data);
                } else {
                    $success = false;
                }
            }
        }


        if ($success) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* list of files, prepared for datatable  */

    function files_list_data($user_id = 0) {
        validate_numeric_value($user_id);
        $options = array("user_id" => $user_id);

        $this->update_only_allowed_members($user_id);

        $list_data = $this->General_files_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_file_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_file_row($data) {
        $file_icon = get_file_icon(strtolower(pathinfo($data->file_name, PATHINFO_EXTENSION)));

        $image_url = get_avatar($data->uploaded_by_user_image);
        $uploaded_by = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span> $data->uploaded_by_user_name";

        $uploaded_by = get_team_member_profile_link($data->uploaded_by, $uploaded_by);

        $description = "<div class='float-start'>" .
            js_anchor(remove_file_prefix($data->file_name), array('title' => "", "data-toggle" => "app-modal", "data-sidebar" => "0", "data-url" => get_uri("team_members/view_file/" . $data->id), "class" => "text-break-space"));

        if ($data->description) {
            $description .= "<div>" . $data->description . "</div></div>";
        } else {
            $description .= "</div>";
        }

        $options = anchor(get_uri("team_members/download_file/" . $data->id), "<i data-feather='download-cloud' class='icon-16'></i>", array("title" => app_lang("download")));

        if ($this->login_user->is_admin || $data->uploaded_by == $this->login_user->id) {
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_file'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("team_members/delete_file"), "data-action" => "delete-confirmation"));
        }

        return array(
            $data->id,
            "<div data-feather='$file_icon' class='mr10 float-start'></div>" . $description,
            convert_file_size($data->file_size),
            $uploaded_by,
            format_to_datetime($data->created_at),
            $options
        );
    }

    function view_file($file_id = 0) {
        validate_numeric_value($file_id);
        $file_info = $this->General_files_model->get_details(array("id" => $file_id))->getRow();

        if ($file_info) {

            if (!$file_info->user_id) {
                app_redirect("forbidden");
            }

            $this->update_only_allowed_members($file_info->user_id);

            $view_data['can_comment_on_files'] = false;

            $view_data["file_url"] = get_source_url_of_file(make_array_of_file($file_info), get_general_file_path("team_members", $file_info->user_id));
            $view_data["is_image_file"] = is_image_file($file_info->file_name);
            $view_data["is_iframe_preview_available"] = is_iframe_preview_available($file_info->file_name);
            $view_data["is_google_preview_available"] = is_google_preview_available($file_info->file_name);
            $view_data["is_viewable_video_file"] = is_viewable_video_file($file_info->file_name);
            $view_data["is_google_drive_file"] = ($file_info->file_id && $file_info->service_type == "google") ? true : false;
            $view_data["is_iframe_preview_available"] = is_iframe_preview_available($file_info->file_name);

            $view_data["file_info"] = $file_info;
            $view_data['file_id'] = $file_id;
            return $this->template->view("team_members/files/view", $view_data);
        } else {
            show_404();
        }
    }

    /* download a file */

    function download_file($id) {

        $file_info = $this->General_files_model->get_one($id);

        if (!$file_info->user_id) {
            app_redirect("forbidden");
        }
        $this->update_only_allowed_members($file_info->user_id);

        //serilize the path
        $file_data = serialize(array(make_array_of_file($file_info)));

        return $this->download_app_files(get_general_file_path("team_members", $file_info->user_id), $file_data);
    }

    /* delete a file */

    function delete_file() {

        $id = $this->request->getPost('id');
        $info = $this->General_files_model->get_one($id);

        if (!$info->user_id) {
            app_redirect("forbidden");
        }

        if ($info->user_id && ($this->login_user->is_admin || $this->login_user->id === $info->uploaded_by)) {
            if ($this->General_files_model->delete($id)) {

                //delete the files
                delete_app_files(get_general_file_path("team_members", $info->user_id), array(make_array_of_file($info)));

                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        } else {
            app_redirect("forbidden");
        }
    }

    /* show keyboard shortcut modal form */

    function keyboard_shortcut_modal_form() {
        return $this->template->view('team_members/keyboard_shortcut_modal_form');
    }

    private function get_recently_meaning_dropdown() {
        return array(
            "2_hours" => app_lang("in") . " 2 " . strtolower(app_lang("hours")),
            "5_hours" => app_lang("in") . " 5 " . strtolower(app_lang("hours")),
            "8_hours" => app_lang("in") . " 8 " . strtolower(app_lang("hours")),
            "1_days" => app_lang("in") . " 1 " . strtolower(app_lang("day")),
            "2_days" => app_lang("in") . " 2 " . strtolower(app_lang("days")),
            "3_days" => app_lang("in") . " 3 " . strtolower(app_lang("days")),
            "5_days" => app_lang("in") . " 5 " . strtolower(app_lang("days")),
            "7_days" => app_lang("in") . " 7 " . strtolower(app_lang("days")),
            "15_days" => app_lang("in") . " 15 " . strtolower(app_lang("days")),
            "1_month" => app_lang("in") . " 1 " . strtolower(app_lang("month")),
        );
    }

    function recently_meaning_modal_form() {
        $view_data["recently_meaning_dropdown"] = $this->get_recently_meaning_dropdown();
        return $this->template->view('tasks/recently_meaning_modal_form', $view_data);
    }

    function save_recently_meaning() {
        $recently_meaning = $this->request->getPost("recently_meaning");
        $recently_meaning = clean_data($recently_meaning);
        $this->Settings_model->save_setting("user_" . $this->login_user->id . "_recently_meaning", $recently_meaning, "user");
        echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
    }

    /* load notes tab  */

    function notes($user_id) {
        validate_numeric_value($user_id);
        $this->can_access_team_members_note($user_id);

        if ($user_id) {
            $view_data['user_id'] = clean_data($user_id);
            return $this->template->view("team_members/notes/index", $view_data);
        }
    }

    private function _validate_excel_import_access() {
        return $this->access_only_admin_or_member_creator();
    }

    private function _get_controller_slag() {
        return "team_members";
    }

    private function _get_custom_field_context() {
        return "team_members";
    }

    private function _get_headers_for_import() {
        return array(
            array("name" => "first_name", "required" => true, "required_message" => app_lang("import_team_member_error_name_field_required")),
            array("name" => "last_name", "required" => true, "required_message" => app_lang("import_team_member_error_name_field_required")),
            array("name" => "address"),
            array("name" => "phone"),
            array("name" => "gender"),
            array("name" => "job_title", "required" => true, "required_message" => app_lang("import_team_member_error_job_title_field_required")),
            array("name" => "email", "required" => true, "required_message" => app_lang("import_team_member_error_email_field_required"), "custom_validation" => function ($value, $row_data) {
                //checking duplicate email
                if ($this->Users_model->is_email_exists($value)) {
                    return array("error" => app_lang("duplicate_email"));
                }
            }),
            array("name" => "role", "custom_validation" => function ($value, $row_data) {
                //checking duplicate email
                if ($value === strtolower("admin") && !$this->login_user->is_admin) {
                    return array("error" => app_lang("only_admin_users_can_set_the_admin_role"));
                }
            })
        );
    }

    function download_sample_excel_file() {
        $this->access_only_admin_or_member_creator();
        return $this->download_app_files(get_setting("system_file_path"), serialize(array(array("file_name" => "import-team-members-sample.xlsx"))));
    }

    private function _init_required_data_before_starting_import() {

        $roles = $this->Roles_model->get_details()->getResult();
        $roles_id_by_title = array();
        foreach ($roles as $role) {
            $roles_id_by_title[$role->title] = $role->id;
        }

        $this->roles_id_by_title = $roles_id_by_title;
    }

    private function _save_a_row_of_excel_data($row_data) {
        $now = get_current_utc_time();

        $team_member_data_array = $this->_prepare_team_member_data($row_data);
        $team_member_data = get_array_value($team_member_data_array, "team_member_data");
        $custom_field_values_array = get_array_value($team_member_data_array, "custom_field_values_array");

        //couldn't prepare valid data
        if (!($team_member_data && count($team_member_data) > 1)) {
            return false;
        }

        //found information about team member, add some additional info
        $team_member_data["created_at"] = $now;

        //save team member data
        $saved_id = $this->Users_model->ci_save($team_member_data);
        if (!$saved_id) {
            return false;
        }

        //save custom fields
        $this->_save_custom_fields($saved_id, $custom_field_values_array);
        return true;
    }

    private function _prepare_team_member_data($row_data) {
        $team_member_data = array("user_type" => "staff");
        $custom_field_values_array = array();

        foreach ($row_data as $column_index => $value) {
            if (!$value) {
                continue;
            }

            $column_name = $this->_get_column_name($column_index);
            if ($column_name == "role") {
                //get existing role, if not then save as a team member
                //can't save as a admin, save as a team member

                if ($value == "Admin" || $value == "Team member") {
                    $team_member_data["role_id"] = 0;
                } else {
                    $role_id = get_array_value($this->roles_id_by_title, $value);
                    if ($role_id) {
                        $team_member_data["role_id"] = $role_id;
                    } else {
                        $team_member_data["role_id"] = 0;
                    }
                }
            } else if (strpos($column_name, 'cf') !== false) {
                $this->_prepare_custom_field_values_array($column_name, $value, $custom_field_values_array);
            } else {
                $team_member_data[$column_name] = $value;
            }
        }

        return array(
            "team_member_data" => $team_member_data,
            "custom_field_values_array" => $custom_field_values_array
        );
    }
}

/* End of file team_member.php */
/* Location: ./app/controllers/team_member.php */