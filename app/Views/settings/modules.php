<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "modules";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <?php echo form_open(get_uri("settings/save_module_settings"), array("id" => "module-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
            <div class="card">
                <div class="card-header">
                    <h4><?php echo app_lang("manage_modules"); ?></h4>
                    <div><?php echo app_lang("module_settings_instructions"); ?></div>
                </div>
                <div class="card-body">

                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("self_improvements") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_todo", "1", get_setting("module_todo") ? true : false, "id='module_todo' class='form-check-input'"); ?>
                                        <label for="module_todo" class="block"><?php echo app_lang('todo'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_note", "1", get_setting("module_note") ? true : false, "id='module_note' class='form-check-input'"); ?>
                                        <label for="module_note" class="block"><?php echo app_lang('note'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_reminder", "1", get_setting("module_reminder") ? true : false, "id='module_reminder' class='form-check-input'"); ?>
                                        <label for="module_reminder" class="block"><?php echo app_lang('reminder'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_event", "1", get_setting("module_event") ? true : false, "id='module_event' class='form-check-input'"); ?>
                                        <label for="module_event" class="block"><?php echo app_lang('event'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("business_growth") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_lead", "1", get_setting("module_lead") ? true : false, "id='module_lead' class='form-check-input'"); ?>
                                        <label for="module_lead" class="block"><?php echo app_lang('lead'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_expense", "1", get_setting("module_expense") ? true : false, "id='module_expense' class='form-check-input'"); ?>
                                        <label for="module_expense" class="block"><?php echo app_lang('expense'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("sales_management") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_contract", "1", get_setting("module_contract") ? true : false, "id='module_contract' class='form-check-input'"); ?>
                                        <label for="module_contract" class="block"><?php echo app_lang('contract'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_proposal", "1", get_setting("module_proposal") ? true : false, "id='module_proposal' class='form-check-input'"); ?>
                                        <label for="module_proposal" class="block"><?php echo app_lang('proposal'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_estimate", "1", get_setting("module_estimate") ? true : false, "id='module_estimate' class='form-check-input'"); ?>
                                        <label for="module_estimate" class="block"><?php echo app_lang('estimate'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_estimate_request", "1", get_setting("module_estimate_request") ? true : false, "id='module_estimate_request' class='form-check-input'"); ?>
                                        <label for="module_estimate_request" class="block"><?php echo app_lang('estimate_request'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_invoice", "1", get_setting("module_invoice") ? true : false, "id='module_invoice' class='form-check-input'"); ?>
                                        <label for="module_invoice" class="block"><?php echo app_lang('invoice'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_subscription", "1", get_setting("module_subscription") ? true : false, "id='module_subscription' class='form-check-input'"); ?>
                                        <label for="module_subscription" class="block"><?php echo app_lang('subscription'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_order", "1", get_setting("module_order") ? true : false, "id='module_order' class='form-check-input'"); ?>
                                        <label for="module_order" class="block"><?php echo app_lang('order'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("customer_support") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_ticket", "1", get_setting("module_ticket") ? true : false, "id='module_ticket' class='form-check-input'"); ?>
                                        <label for="module_ticket" class="block"><?php echo app_lang('ticket'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_knowledge_base", "1", get_setting("module_knowledge_base") ? true : false, "id='module_knowledge_base' class='form-check-input'"); ?>
                                        <label for="module_knowledge_base" class="block"><?php echo app_lang('knowledge_base') . " (" . app_lang("public") . ")"; ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("team_management") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_leave", "1", get_setting("module_leave") ? true : false, "id='module_leave' class='form-check-input'"); ?>
                                        <label for="module_leave" class="block"><?php echo app_lang('leave'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_attendance", "1", get_setting("module_attendance") ? true : false, "id='module_attendance' class='form-check-input'"); ?>
                                        <label for="module_attendance" class="block"><?php echo app_lang('attendance'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_project_timesheet", "1", get_setting("module_project_timesheet") ? true : false, "id='module_project_timesheet' class='form-check-input'"); ?>
                                        <label for="module_project_timesheet" class="block"><?php echo app_lang('project_timesheet'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_gantt", "1", get_setting("module_gantt") ? true : false, "id='module_gantt' class='form-check-input'"); ?>
                                        <label for="module_gantt" class="block"><?php echo app_lang('gantt'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_help", "1", get_setting("module_help") ? true : false, "id='module_help' class='form-check-input'"); ?>
                                        <label for="module_help" class="block"><?php echo app_lang('help') . " (" . app_lang("team_members") . ")"; ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <div class="mb20">
                            <span class="highlight-toolbar strong ml0"><?php echo app_lang("collaboration") ?></span>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_message", "1", get_setting("module_message") ? true : false, "id='module_message' class='form-check-input'"); ?>
                                        <label for="module_message" class="block"><?php echo app_lang('message'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_chat", "1", get_setting("module_chat") ? true : false, "id='module_chat' class='form-check-input'"); ?>
                                        <label for="module_chat" class="block"><?php echo app_lang('chat'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_file_manager", "1", get_setting("module_file_manager") ? true : false, "id='module_file_manager' class='form-check-input'"); ?>
                                        <label for="module_file_manager" class="block"><?php echo app_lang('file_manager'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_timeline", "1", get_setting("module_timeline") ? true : false, "id='module_timeline' class='form-check-input'"); ?>
                                        <label for="module_timeline" class="block"><?php echo app_lang('timeline'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="card-text">
                                        <?php echo form_checkbox("module_announcement", "1", get_setting("module_announcement") ? true : false, "id='module_announcement' class='form-check-input'"); ?>
                                        <label for="module_announcement" class="block"><?php echo app_lang('announcement'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#module-settings-form").appForm({
            isModal: false,
            onSuccess: function(result) {
                appAlert.success(result.message, {
                    duration: 10000
                });
                location.reload();
            }
        });
    });
</script>