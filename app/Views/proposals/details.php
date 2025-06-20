<div class="clearfix default-bg details-view-container">
    <div class="row">
        <div class="col-md-9 d-flex">
            <div class="card p15 w-100 pt0">
                <div id="page-content" class="clearfix grid-button">
                    <div style="max-width: 1000px; margin: auto;">
                        <div class="no-border clearfix ">
                            <ul data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
                                <li><a role="presentation" data-bs-toggle="tab" href="javascript:;" data-bs-target="#proposal-items"><?php echo app_lang("proposal") . " " . app_lang("items"); ?></a></li>
                                <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("proposals/editor/" . $proposal_info->id); ?>" data-bs-target="#proposal-editor"><?php echo app_lang("proposal_editor"); ?></a></li>
                                <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("proposals/preview/" . $proposal_info->id . "/0/1"); ?>" data-bs-target="#proposal-preview" data-reload="true"><?php echo app_lang("preview"); ?></a></li>
                            </ul>

                            <div class="tab-content">
                                <div role="tabpanel" class="tab-pane fade" id="proposal-items">

                                    <div class="p15 mb15">
                                        <div class="clearfix p20">
                                            <!-- small font size is required to generate the pdf, overwrite that for screen -->
                                            <style type="text/css"> .invoice-meta {
                                                    font-size: 100% !important;
                                                }</style>

                                            <?php
                                            $color = get_setting("proposal_color");
                                            if (!$color) {
                                                $color = get_setting("invoice_color");
                                            }
                                            $style = get_setting("invoice_style");
                                            ?>
                                            <?php
                                            $data = array(
                                                "client_info" => $client_info,
                                                "color" => $color ? $color : "#2AA384",
                                                "proposal_info" => $proposal_info
                                            );
                                            ?>

                                            <div class="row">
                                                <div class="col-md-5 mb15">
                                                    <?php echo view('proposals/proposal_parts/proposal_from', $data); ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?php echo view('proposals/proposal_parts/proposal_to', $data); ?>
                                                </div>
                                                <div class="col-md-4 text-right info-section">
                                                    <?php echo view('proposals/proposal_parts/proposal_info', $data); ?>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive mt15 pl15 pr15">
                                            <table id="proposal-item-table" class="display" width="100%">            
                                            </table>
                                        </div>

                                        <div class="clearfix">
                                            <div class="col-sm-8">

                                            </div>
                                            <?php if ($is_proposal_editable) { ?>
                                                <div class="float-start ml15 mt20 mb20">
                                                    <?php echo modal_anchor(get_uri("proposals/item_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array("class" => "btn btn-primary text-white", "title" => app_lang('add_item'), "data-post-proposal_id" => $proposal_info->id)); ?>
                                                </div>
                                            <?php } ?>
                                            <div class="float-end pr15" id="proposal-total-section">
                                                <?php echo view("proposals/proposal_total_section", array("is_proposal_editable" => $is_proposal_editable)); ?>
                                            </div>
                                        </div>

                                        <p class="b-t b-info pt10 m15"><?php echo custom_nl2br($proposal_info->note ? process_images_from_content($proposal_info->note) : ""); ?></p>

                                        <?php
                                        if (get_setting("enable_comments_on_proposals") && !($proposal_info->status === "draft")) {
                                            echo view("proposals/comment_form");
                                        }
                                        ?>

                                    </div>
                                </div>
                                <div role="tabpanel" class="tab-pane fade" id="proposal-editor"></div>
                                <div role="tabpanel" class="tab-pane fade" id="proposal-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <div class="card p20">
                <div class="card-body">
                    <div id="proposal-status-bar">
                        <?php echo view("proposals/proposal_status_bar"); ?>
                    </div>
                </div>
            </div>

            <?php echo view("proposals/signer_info") ?>
        </div>
    </div>
</div>