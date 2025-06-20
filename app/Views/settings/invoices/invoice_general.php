<?php echo form_open(get_uri("settings/save_invoice_general_settings"), array("id" => "invoice-general-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

<div class="card-body">
    <div class="form-group">
        <div class="row">
            <label for="default_due_date_after_billing_date" class="col-md-2"><?php echo app_lang('default_due_date_after_billing_date'); ?></label>
            <div class="col-md-3">
                <?php
                echo form_input(array(
                    "id" => "default_due_date_after_billing_date",
                    "name" => "default_due_date_after_billing_date",
                    "type" => "number",
                    "value" => get_setting("default_due_date_after_billing_date"),
                    "class" => "form-control mini",
                    "min" => 0
                ));
                ?>
            </div>
            <label class="col-md-1 mt5"><?php echo app_lang('days'); ?></label>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="send_bcc_to" class=" col-md-2"><?php echo app_lang('send_bcc_to'); ?></label>
            <div class=" col-md-10">
                <?php
                echo form_input(array(
                    "id" => "send_bcc_to",
                    "name" => "send_bcc_to",
                    "value" => get_setting("send_bcc_to"),
                    "class" => "form-control",
                    "placeholder" => app_lang("email")
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="allow_partial_invoice_payment_from_clients" class=" col-md-2"><?php echo app_lang('allow_partial_invoice_payment_from_clients'); ?></label>

            <div class="col-md-10">
                <?php
                echo form_dropdown(
                    "allow_partial_invoice_payment_from_clients",
                    array("1" => app_lang("yes"), "0" => app_lang("no")),
                    get_setting('allow_partial_invoice_payment_from_clients'),
                    "class='select2 mini'"
                );
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="client_can_pay_invoice_without_login" class=" col-md-2"><?php echo app_lang('client_can_pay_invoice_without_login'); ?> <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('client_can_pay_invoice_without_login_help_message'); ?>"><i data-feather='help-circle' class="icon-16"></i></span></label>

            <div class="col-md-10">
                <?php
                echo form_dropdown(
                    "client_can_pay_invoice_without_login",
                    array("1" => app_lang("yes"), "0" => app_lang("no")),
                    get_setting('client_can_pay_invoice_without_login'),
                    "class='select2 mini'"
                );
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <label for="enable_invoice_lock_state" class="col-md-2"><?php echo app_lang('enable_lock_state'); ?> <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('invoice_lock_state_description'); ?>"><i data-feather='help-circle' class="icon-16"></i></span></label>
            <div class="col-md-10">
                <?php
                echo form_checkbox("enable_invoice_lock_state", "1", get_setting("enable_invoice_lock_state") ? true : false, "id='enable_invoice_lock_state' class='form-check-input'");
                ?>
            </div>
        </div>
    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#invoice-general-settings-form").appForm({
            isModal: false,
            onSuccess: function(result) {
                if (result.success) {
                    appAlert.success(result.message, {
                        duration: 10000
                    });
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        $("#invoice-general-settings-form .select2").select2();
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>