<div class="clearfix default-bg details-view-container">
    <div class="row">
        <div class="col-md-9 d-flex">
            <div class="card p15 w-100 pt0">
                <div id="page-content" class="clearfix grid-button">
                    <div style="max-width: 1000px; margin: auto;">
                        <div class="no-border clearfix ">
                            <ul data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
                                <li><a role="presentation" data-bs-toggle="tab" href="javascript:;" data-bs-target="#contract-items"><?php echo app_lang("contract") . " " . app_lang("items"); ?></a></li>
                                <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("contracts/editor/" . $contract_info->id); ?>" data-bs-target="#contract-editor"><?php echo app_lang("contract_editor"); ?></a></li>
                                <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("contracts/preview/" . $contract_info->id . "/0/1"); ?>" data-bs-target="#contract-preview" data-reload="true"><?php echo app_lang("preview"); ?></a></li>
                            </ul>

                            <div class="tab-content">
                                <div role="tabpanel" class="tab-pane fade" id="contract-items">

                                    <div class="p15 mb15">
                                        <div class="clearfix p20">
                                            <!-- small font size is required to generate the pdf, overwrite that for screen -->
                                            <style type="text/css"> .invoice-meta {
                                                    font-size: 100% !important;
                                                }</style>

                                            <?php
                                            $color = get_setting("contract_color");
                                            if (!$color) {
                                                $color = get_setting("invoice_color");
                                            }
                                            $style = get_setting("invoice_style");
                                            ?>
                                            <?php
                                            $data = array(
                                                "client_info" => $client_info,
                                                "color" => $color ? $color : "#2AA384",
                                                "contract_info" => $contract_info
                                            );
                                            if ($style === "style_2") {
                                                echo view('contracts/contract_parts/header_style_2.php', $data);
                                            } else {
                                                echo view('contracts/contract_parts/header_style_1.php', $data);
                                            }
                                            ?>

                                        </div>

                                        <div class="table-responsive mt15 pl15 pr15">
                                            <table id="contract-item-table" class="display" width="100%">            
                                            </table>
                                        </div>

                                        <div class="clearfix">
                                            <div class="col-sm-8">
                                            </div>
                                            <?php if ($is_contract_editable) { ?>
                                                <div class="float-start ml15 mt20 mb20">
                                                    <?php echo modal_anchor(get_uri("contracts/item_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array("class" => "btn btn-primary text-white", "title" => app_lang('add_item'), "data-post-contract_id" => $contract_info->id)); ?>
                                                </div>
                                            <?php } ?>
                                            <div class="float-end pr15" id="contract-total-section">
                                                <?php echo view("contracts/contract_total_section", array("is_contract_editable" => $is_contract_editable)); ?>
                                            </div>
                                        </div>

                                        <?php
                                        $files = @unserialize($contract_info->files);
                                        if ($files && is_array($files) && count($files)) {
                                            ?>
                                            <div class="clearfix">
                                                <div class="col-md-12 mt20 pl15 pr15 row">
                                                    <p class="b-t"></p>
                                                    <div class="mb10 strong"><?php echo app_lang("files"); ?></div>
                                                    <?php
                                                    echo view("includes/file_list", array("files" => $contract_info->files, "model_info" => $contract_info, "mode_type" => "view", "context" => "contract"));
                                                    ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <p class="b-t b-info pt10 m15"><?php echo custom_nl2br($contract_info->note ? process_images_from_content($contract_info->note) : ""); ?></p>
                                    </div>
                                </div>
                                <div role="tabpanel" class="tab-pane fade" id="contract-editor"></div>
                                <div role="tabpanel" class="tab-pane fade" id="contract-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <div class="card p20">
                <div class="card-body">
                    <div id="contract-status-bar">
                        <?php echo view("contracts/contract_status_bar"); ?>
                    </div>
                </div>
            </div>

            <?php echo view("contracts/signer_info"); ?>

        </div>
    </div>
</div>