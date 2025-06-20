<div class="d-flex">
    <div class="float-start mr10">
        <strong><?php echo app_lang('email_seen_at') . ": "; ?></strong>
    </div>
    <div>
        <?php foreach ($email_read_logs as $log) { ?>
            <div><?php echo format_to_relative_time($log); ?></div>
        <?php } ?>
    </div>
</div>