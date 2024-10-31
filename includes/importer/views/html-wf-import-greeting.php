<div class="orderxmlimpexp-main-box">
    <div class="orderxmlimpexp-view" style="width:68%;">
        <div class="tool-box bg-white p-20p">

            <p><?php _e('You can import orders (in XML format) in to the shop using any of below methods.', 'order-xml-file-export-import-for-woocommerce'); ?></p>



        <?php if (!empty($upload_dir['error'])) : ?>

                <div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:', 'order-xml-file-export-import-for-woocommerce'); ?></p>

                    <p><strong><?php echo $upload_dir['error']; ?></strong></p></div>

            <?php else : ?>

                <form enctype="multipart/form-data" id="import-upload-form" method="POST" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>" name="import_data">

                    <table class="form-table">

                        <tbody>

                            <tr>

                                <th>
                                    <?php _e('XML Type','order-xml-file-export-import-for-woocommerce'); ?>
                                </th>

                                <td>

                                    <select id="v_order_import_type" name="order_import_type" data-placeholder="<?php _e('Orders Import Type', 'order-xml-file-export-import-for-woocommerce'); ?>" onchange="showDiv(this)">
                                        <option value="general"><?php _e("WooCommerce",'order-xml-file-export-import-for-woocommerce') ?></option>
                                        <option value="stamps"><?php _e("Stamps.Com",'order-xml-file-export-import-for-woocommerce') ?></option>
                                        <option value="fedex"><?php _e("FedEx",'order-xml-file-export-import-for-woocommerce') ?></option>
                                        <option value="ups"><?php _e("UPS WorldShip",'order-xml-file-export-import-for-woocommerce') ?></option>
                                        <option value="endicia"><?php _e("Endicia",'order-xml-file-export-import-for-woocommerce') ?></option>
                                    </select>
                                    <div id="add_edit_choice">                           
                                            <?php _e('For existing order,','order-xml-file-export-import-for-woocommerce'); ?>
                                        &nbsp;
                                        <input type="radio" name="order_import_type_decision" value="skip" checked /> <?php _e('Skip','order-xml-file-export-import-for-woocommerce'); ?>

                                        <input type="radio" name="order_import_type_decision" value="overwrite" /> <?php _e('Overwrite','order-xml-file-export-import-for-woocommerce'); ?>
                                        &nbsp;
                                        <?php _e(' the order.','order-xml-file-export-import-for-woocommerce'); ?>
                                    </div>
                                </td>

                            </tr>
                            <script type="text/javascript">
                                function showDiv(elem){
                                    if(elem.value == 'general')
                                      document.getElementById('add_edit_choice').style.display = "block";
                                    else
                                      document.getElementById('add_edit_choice').style.display = "none";  
                                }
                            </script>
                            <tr>

                                <th>

                                    <label for="upload"><?php _e('Select a file from your computer', 'order-xml-file-export-import-for-woocommerce'); ?></label>

                                </th>

                                <td>

                                    <input type="file" id="upload" name="import" size="25" />

                                    <input type="hidden" name="action" value="save" />

                                    <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />

                                    <small><?php printf(__('Maximum size: %s'), $size); ?></small>

                                </td>

                            </tr>


                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Upload file and import'); ?>" />
                    </p>
                </form>

        <?php endif; ?>

        </div>
    </div>
    <?php include(plugin_dir_path(WF_OrderImpExpXML_FILE) . 'includes/views/market.php'); ?>
       <div class="clearfix"></div>
</div>