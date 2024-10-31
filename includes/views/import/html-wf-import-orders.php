<div class="orderxmlimpexp-main-box">
    <div class="orderxmlimpexp-view" style="width:68%;">
        <div class="tool-box bg-white p-20p" style="margin-bottom: 20px;">
            <h3 class="title"><?php _e('Import Orders in XML Format:', 'order-xml-file-export-import-for-woocommerce'); ?></h3>
            <h5><?php _e('(For sample format of XML import, <a href="'.admin_url('admin.php?page=wf_woocommerce_order_im_ex_xml&tab=help').'"> Click Here </a>)','order-xml-file-export-import-for-woocommerce')?></h5>
            <p><?php _e('Import Orders in XML format from different sources (  from your computer )', 'order-xml-file-export-import-for-woocommerce'); ?></p>
            <p class="submit" style="padding-left: 10px;">
                <?php
                $import_url = admin_url('admin.php?import=woocommerce_wf_import_order_xml');
                ?>
                <a class="button button-primary" id="mylink" href="<?php echo $import_url; ?>"><?php _e('Update Orders', 'order-xml-file-export-import-for-woocommerce'); ?></a>
                &nbsp;
                <br>
            </p>
        </div>
