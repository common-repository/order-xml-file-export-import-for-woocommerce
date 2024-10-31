<div class="tool-box bg-white p-20p">
    <?php
    $order_statuses = wc_get_order_statuses();
    ?>
    <h3 class="title"><?php _e('Export Orders in XML Format:', 'order-xml-file-export-import-for-woocommerce'); ?></h3>
    <h5><?php _e('(For sample format of XML export, <a href="'.admin_url('admin.php?page=wf_woocommerce_order_im_ex_xml&tab=help').'"> Click Here </a>)','order-xml-file-export-import-for-woocommerce')?></h5>
    <p><?php _e('Export and download your orders in XML format.', 'order-xml-file-export-import-for-woocommerce'); ?></p>
    <form action="<?php echo admin_url('admin.php?page=wf_woocommerce_order_im_ex_xml&action=export'); ?>" method="post">
        <table class="form-table"> 
            <tr>
                <th>
                    <label for="v_order_export_type"><?php _e('Order Export Type', 'order-xml-file-export-import-for-woocommerce'); ?></label>
                </th>
                <td>
                    <select id="v_order_export_type" name="order_export_type" data-placeholder="<?php _e('Orders Export Type', 'order-xml-file-export-import-for-woocommerce'); ?>">
                        <option value="general"><?php _e("WooCommerce",'order-xml-file-export-import-for-woocommerce') ?></option>
                        <option value="stamps"><?php _e("Stamps.Com",'order-xml-file-export-import-for-woocommerce') ?></option>
                        <option value="fedex"><?php _e("FedEx",'order-xml-file-export-import-for-woocommerce') ?></option>
                        <option value="ups"><?php _e("UPS WorldShip",'order-xml-file-export-import-for-woocommerce') ?></option>
                        <option value="endicia"><?php _e("Endicia",'order-xml-file-export-import-for-woocommerce') ?></option>
                    </select>                                                        
                    <p style="font-size: 12px"><?php _e('Orders with these type XML will be exported.', 'order-xml-file-export-import-for-woocommerce'); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit" style="padding-left: 10px;"><input type="submit" class="button button-primary" value="<?php _e('Export Orders', 'order-xml-file-export-import-for-woocommerce'); ?>" /></p>
    </form>
</div>
</div>
       <?php include_once(plugin_dir_path(WF_OrderImpExpXML_FILE).'includes/views/market.php'); ?>
       <div class="clearfix"></div>
</div>