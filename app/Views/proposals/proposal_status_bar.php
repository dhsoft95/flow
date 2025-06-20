<div class="col-md-12 mb15">
    <?php
    if ($proposal_info->is_lead) {
        echo app_lang("lead") . ": ";
        echo (anchor(get_uri("leads/view/" . $proposal_info->client_id), $proposal_info->company_name));
    } else {
        echo app_lang("client") . ": ";
        echo (anchor(get_uri("clients/view/" . $proposal_info->client_id), $proposal_info->company_name));
    }
    ?>
</div>
<div class="col-md-12 mb15">
    <strong><?php echo app_lang('status') . ": "; ?></strong><?php echo $proposal_status_label; ?>
</div>
<div class="col-md-12 mb15">
    <strong><?php echo app_lang('last_email_sent') . ": "; ?></strong>
    <?php echo (is_date_exists($proposal_info->last_email_sent_date)) ? format_to_date($proposal_info->last_email_sent_date, FALSE) : app_lang("never"); ?>
</div>
<div class="col-md-12 mb15">
    <strong><?php echo app_lang('last_email_seen') . ": "; ?></strong>
    <?php echo $proposal_info->last_email_read_time ? format_to_relative_time($proposal_info->last_email_read_time) : "-"; ?>
</div>
<div class="col-md-12 mb15">
    <strong><?php echo app_lang('last_preview_seen') . ": "; ?></strong>
    <?php echo $proposal_info->last_preview_seen ? format_to_relative_time($proposal_info->last_preview_seen) : "-"; ?>
</div>
<?php if ($total_read_count) { ?>
    <div class="col-md-12 mb15">
        <strong><?php echo app_lang("email_seen_count") . ": "; ?></strong>
        <?php
        echo ajax_anchor(get_uri("proposals/email_view_report/" . $proposal_info->id), $total_read_count, array("data-real-target" => "#proposal-email-view-report", "class" => "strong"));
        ?>
    </div>
    <div class="col-md-12 mb15">
        <div id="proposal-email-view-report"></div>
    </div>
<?php } ?>
<?php if ($proposal_info->project_id) { ?>
    <div class="col-md-12 mb15">
        <strong><?php echo app_lang('project') . ": "; ?></strong>
        <?php echo (anchor(get_uri("projects/view/" . $proposal_info->project_id), $proposal_info->project_title)); ?>
    </div>
<?php } ?>
<?php if (can_access_reminders_module()) { ?>
    <div class="col-md-12 mb15" id="proposal-reminders">
        <div class="mb15"><strong><?php echo app_lang("reminders") . " (" . app_lang('private') . ")" . ": "; ?> </strong></div>
        <?php echo view("reminders/reminders_view_data", array("proposal_id" => $proposal_info->id, "hide_form" => true, "reminder_view_type" => "proposal")); ?>
    </div>
<?php } ?>