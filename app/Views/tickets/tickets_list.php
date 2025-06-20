<div id="page-content" class="page-wrapper clearfix grid-button tickets-list-view">

    <ul class="nav nav-tabs bg-white title scrollable-tabs" role="tablist">
        <li class="title-tab"><h4 class="pl15 pt10 pr15"><?php echo app_lang('tickets'); ?></h4></li>

        <?php echo view("tickets/index", array("active_tab" => "tickets_list")); ?>

        <div class="tab-title clearfix no-border tickets-page-title">
            <div class="title-button-group">
                <?php
                echo modal_anchor(get_uri("labels/modal_form"), "<i data-feather='tag' class='icon-16'></i> " . app_lang('manage_labels'), array("class" => "btn btn-default", "title" => app_lang('manage_labels'), "data-post-type" => "ticket"));
                echo modal_anchor(get_uri("tickets/settings_modal_form"), "<i data-feather='settings' class='icon-16'></i> " . app_lang('settings'), array("class" => "btn btn-default", "title" => app_lang('settings')));
                echo modal_anchor(get_uri("tickets/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_ticket'), array("class" => "btn btn-default", "title" => app_lang('add_ticket')));
                ?>
            </div>
        </div>

    </ul>

    <div class="card border-top-0 rounded-top-0">
        <div class="table-responsive">
            <table id="ticket-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>

</div>

<?php 

$statuses = array();
$ticket_statuses = array("new", "open", "client_replied", "closed");
$selected_status = isset($status) ? $status : "";

//Check the clickable links from dashboard
$ignore_saved_filter = false;

foreach ($ticket_statuses as $status) {
    $is_selected = false;

    if ($selected_status) {
        //if there is any specific status selected, select only the status.
        if ($selected_status == $status) {
            $is_selected = true;
            $ignore_saved_filter = true;
        }
    } else if ($status === "new" || $status === "open" || $status === "client_replied") {
        //select the New, Open & Client replied filter by default
        $is_selected = true;
    }

    $statuses[] = array("text" => app_lang($status), "value" => $status, "isChecked" => $is_selected);
}

?>

<script type="text/javascript">
    $(document).ready(function () {

        var optionsVisibility = false;
        if ("<?php
                if (isset($show_options_column) && $show_options_column) {
                    echo '1';
                }
                ?>" == "1") {
            optionsVisibility = true;
        }

        var projectVisibility = false;
        if ("<?php echo $show_project_reference; ?>" == "1") {
            projectVisibility = true;
        }

        var ignoreSavedFilter = false;
        <?php if ($ignore_saved_filter) { ?>
            ignoreSavedFilter = true;
        <?php } ?>

        var filterDropdowns = [];

        var clientAccessPermission = "<?php echo get_array_value($login_user->permissions, "client"); ?>";
        if (clientAccessPermission === "all" || <?php echo $login_user->is_admin ?>) {
            filterDropdowns.push({name: "client_id", class: "w200", options: <?php echo $clients_dropdown; ?>});
        }

        filterDropdowns.push({name: "ticket_type_id", class: "w200", options: <?php echo $ticket_types_dropdown; ?>});
        filterDropdowns.push({name: "ticket_label", class: "w200", options: <?php echo $ticket_labels_dropdown; ?>});
        filterDropdowns.push({name: "assigned_to", class: "w200", options: <?php echo $assigned_to_dropdown; ?>});
        filterDropdowns.push(<?php echo $custom_field_filters; ?>);

        var batchUpdateUrl = "<?php echo get_uri("tickets/batch_update_modal_form/"); ?>";

        $("#ticket-table").appTable({
            source: '<?php echo_uri("tickets/list_data") ?>',
            serverSide: true,
            order: [[7, "desc"]],
            smartFilterIdentity: "tickets_list", //a to z and _ only. should be unique to avoid conflicts 
            ignoreSavedFilter: ignoreSavedFilter,
            multiSelect: [
                {
                    class: "w150",
                    name: "status",
                    text: "<?php echo app_lang('status'); ?>",
                    options: <?php echo json_encode($statuses); ?>
                }
            ],
            filterDropdown: filterDropdowns,
            selectionHandler: {batchUpdateUrl: batchUpdateUrl},
            singleDatepicker: [{name: "created_at", defaultText: "<?php echo app_lang('created') ?>",
                    options: [
                        {value: moment().subtract(2, 'days').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_last_number_of_days'), 2); ?>"},
                        {value: moment().subtract(7, 'days').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_last_number_of_days'), 7); ?>"},
                        {value: moment().subtract(15, 'days').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_last_number_of_days'), 15); ?>"},
                        {value: moment().subtract(1, 'months').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_last_number_of_month'), 1); ?>"},
                        {value: moment().subtract(3, 'months').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_last_number_of_months'), 3); ?>"}
                    ]}],
            columns: [
                {visible: false, searchable: false, order_by: "id"},
                {title: '<?php echo app_lang("ticket_id") ?>', "iDataSort": 0, "class": "w10p", order_by: "id"},
                {title: '<?php echo app_lang("title") ?>', "class": "all", order_by: "title"},
                {title: '<?php echo app_lang("client") ?>', "class": "w15p", order_by: "client"},
                {title: '<?php echo app_lang("project") ?>', "class": "w15p", visible: projectVisibility, order_by: "project"},
                {title: '<?php echo app_lang("ticket_type") ?>', "class": "w10p", order_by: "ticket_type"},
                {title: '<?php echo app_lang("assigned_to") ?>', "class": "w10p", order_by: "assigned_to"},
                {visible: false, searchable: false, order_by: "last_activity"},
                {title: '<?php echo app_lang("last_activity") ?>', "iDataSort": 7, "class": "w10p", order_by: "last_activity"},
                {title: '<?php echo app_lang("status") ?>', "class": "w5p"}
<?php echo $custom_field_headers; ?>,
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center dropdown-option w10p", visible: optionsVisibility}
            ],
            printColumns: combineCustomFieldsColumns([1, 2, 3, 4, 5, 6, 8, 9], '<?php echo $custom_field_headers; ?>'),
            xlsColumns: combineCustomFieldsColumns([1, 2, 3, 4, 5, 6, 8, 9], '<?php echo $custom_field_headers; ?>')
        });

    });
</script>