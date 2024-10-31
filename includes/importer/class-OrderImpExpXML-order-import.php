<?php
if (!class_exists('WP_Importer'))
    return;

class OrderImpExpXML_OrderImport extends WP_Importer {

    var $id;
    var $file_url;
    var $profile;
    var $merge_empty_cells;
    var $processed_posts = array();
    var $merged = 0;
    var $skipped = 0;
    var $imported = 0;
    var $errored = 0;
    var $import_results = array();

    public function __construct() {

        if(WC()->version < '2.7.0')
	{
		$this->log                     = new WC_Logger();
	}
	else
	{
		$this->log                     = wc_get_logger();
	}
	
        $this->import_page = 'woocommerce_wf_import_order_xml';
        $this->file_url_import_enabled = apply_filters('woocommerce_xml_order_file_url_import_enabled', true);
    }

    
    public function hf_log_data_change ($content = 'xml-import',$data='')
    {
	    if (WC()->version < '2.7.0')
	    {
		    $this->log->add($content,$data);
	    }else
	    {
		    $context = array( 'source' => $content );
		    $this->log->log("debug", $data ,$context);
	    }
    }
	
    public function dispatch() {
        
        global $woocommerce, $wpdb;
        if (!empty($_POST['delimiter'])) {
            $this->delimiter = sanitize_text_field(stripslashes(trim($_POST['delimiter'])));
        }

        if (!empty($_POST['profile'])) {
            $this->profile = sanitize_text_field(stripslashes(trim($_POST['profile'])));
        } else if (!empty($_GET['profile'])) {
            $this->profile = sanitize_text_field(stripslashes(trim($_GET['profile'])));
        }
        if (!$this->profile)
            $this->profile = '';

        if (!empty($_POST['merge_empty_cells']) || !empty($_GET['merge_empty_cells'])) {
            $this->merge_empty_cells = 1;
        } else {
            $this->merge_empty_cells = 0;
        }

        $step = empty($_GET['step']) ? 0 : absint($_GET['step']);
        
        switch ($step) {
            case 0 :
                $this->header();
                $this->greet();
                break;
            case 1 :
                $import_type        = !empty($_POST['order_import_type']) ? sanitize_text_field($_POST['order_import_type']): 'general';
                $import_decision    = !empty($_POST['order_import_type_decision']) ? sanitize_text_field($_POST['order_import_type_decision']): 'skip';
                $this->header();

                check_admin_referer('import-upload');

                if (!empty($_GET['file_url']))
                    $this->file_url = esc_url($_GET['file_url']);
                if (!empty($_GET['file_id']))
                    $this->id = intval($_GET['file_id']);

                if (!empty($_GET['clearmapping']) || $this->handle_upload())
                    $this->import_options( $import_type,$import_decision );
                else
                    _e('Error with handle_upload!', 'order-xml-file-export-import-for-woocommerce');
                break;
            case 2 :
                $this->header();

                check_admin_referer('import-woocommerce');

                $this->id = absint($_POST['import_id']);

                if ($this->file_url_import_enabled)
                    $this->file_url = esc_url($_POST['import_url']);
                if ($this->id)
                    $file = get_attached_file($this->id);
                else if ($this->file_url_import_enabled)
                    $file = ABSPATH . $this->file_url;

                $file = str_replace("\\", "/", $file);

                if ($file) {

                    $xml = simplexml_load_file($file);
                    $root_tag = $xml->getName();
                    $xml_array = array();
                    $xml_array[$root_tag] = $xml;
                    ?>
                    <table id="import-progress" class="widefat_importer widefat">
                        <thead>
                            <tr>
                                <th class="status">&nbsp;</th>
                                <th class="row"><?php _e('Row', 'order-xml-file-export-import-for-woocommerce'); ?></th>
                                <th><?php _e('OrderID', 'order-xml-file-export-import-for-woocommerce'); ?></th>
                                <th><?php _e('Order Status', 'order-xml-file-export-import-for-woocommerce'); ?></th>
                                <th class="reason"><?php _e('Status Msg', 'order-xml-file-export-import-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="importer-loading">
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <tbody></tbody>
                    </table>
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {

                        if (! window.console) { window.console = function(){}; }

                        var processed_posts = [];
                        var i = 1;
                        var done_count = 0;
                        function import_rows() {
                            
                        var data = {
                                action:     'woocommerce_xml_order_import_request',
                                file:       '<?php echo addslashes($file); ?>',
                                import_type:'<?php echo sanitize_text_field($_POST['import_type']); ?>',
                                import_decision: '<?php echo sanitize_text_field($_POST['import_decision']); ?>',
                                wt_nonce : '<?php echo wp_create_nonce( WF_ORDER_IMP_EXP_XML_ID )?>',
                        };
                                                return $.ajax({
                                        url:        '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '3', 'merge' => !empty($_GET['merge']) ? '1' : '0'), admin_url('admin-ajax.php')); ?>',
                                        data:       data,
                                        type:       'POST',
                                        success:    function(response) {
                                if (response) {

                                try {
                                                // Get the valid JSON only from the returned string
                                                if (response.indexOf("<!--WC_START-->") >= 0)
                                                        response = response.split("<!--WC_START-->")[1]; // Strip off before after WC_START

                                                if (response.indexOf("<!--WC_END-->") >= 0)
                                                        response = response.split("<!--WC_END-->")[0]; // Strip off anything after WC_END

                                                // Parse
                                                var results = $.parseJSON(response);
                                                if (results.error) {

                                                $('#import-progress tbody').append('<tr id="row-' + i + '" class="error"><td class="status" colspan="5">' + results.error + '</td></tr>');
                                                                            i++;
                                                                            } else if (results.import_results && $(results.import_results).size() > 0) {

                                                                            $.each(results.processed_posts, function(index, value) {
                                                                            processed_posts.push(value);
                                                                            });
                                                                            $(results.import_results).each(function(index, row) {
                                                                            $('#import-progress tbody').append('<tr id="row-' + i + '" class="' + row['status'] + '"><td><mark class="result" title="' + row['status'] + '">' + row['status'] + '</mark></td><td class="row">' + i + '</td><td>' + row['order_number'] + '</td><td>' + row['post_id'] + ' - ' + row['post_title'] + '</td><td class="reason">' + row['reason'] + '</td></tr>');
                                                                                                        i++;
                                                                                                        });
                                                                                                        }

                                                                                                        } catch (err) {}

                                                                                                        } else {
                                                                                                        $('#import-progress tbody').append('<tr class="error"><td class="status" colspan="5">' +  '<?php _e('AJAX Error', 'order-xml-file-export-import-for-woocommerce'); ?>' + '</td></tr>');
                                                                                                                                    }

                                                                                                                                    var w = $(window);
                                                                                                                                    var row = $("#row-" + (i - 1));
                                                                                                                                    if (row.length) {
                                                                                                                                    w.scrollTop(row.offset().top - (w.height() / 2));
                                                                                                                                    }

                                                                                                                                    done_count++;
                                                                                                                                    $('body').trigger('woocommerce_xml_order_import_request_complete');
                                                                                                                                    }
                                                                                                                            });
                                                                                                                            }

                                                                                                                            var rows = [];
                    <?php
                    $limit = apply_filters('woocommerce_xml_import_limit_per_request', 10);
                    $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
                    if ($enc)
                        setlocale(LC_ALL, 'en_US.' . $enc);
                    @ini_set('auto_detect_line_endings', true);

                    $count = 0;

                    $import_count = 0;
                    ?>

                                                                                                                            var data = rows.shift();
                                                                                                                            var regen_count = 0;
                                                                                                                            import_rows();
                                                                                                                            $('body').on('woocommerce_xml_order_import_request_complete', function() {
                          import_done();
                    //								if ( done_count == <?php //echo $import_count;  ?> ) {
                    //
                    //										import_done();
                    //								} else {
                    //									// Call next request
                    //									data = rows.shift();
                    //									import_rows( );
                    //								}
                                    } );

                                    function import_done() {
                                            var data = {
                                                    action: 'woocommerce_xml_order_import_request',
                                                    file: '<?php echo $file; ?>',
                                                    processed_posts: processed_posts,
                                                    wt_nonce : '<?php echo wp_create_nonce( WF_ORDER_IMP_EXP_XML_ID )?>',
                                                        };

                                            $.ajax({
                                                    url: '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '4', 'merge' => !empty($_GET['merge']) ? 1 : 0), admin_url('admin-ajax.php')); ?>',
                                                    data:       data,
                                                    type:       'POST',
                                                    success:    function( response ) {
                                                            console.log( response );
                                                            $('#import-progress tbody').append( '<tr class="complete"><td colspan="5">' + response + '</td></tr>' );
                                                            $('.importer-loading').hide();
                                                    }
                                            });
                                    }
                            });
                    </script>
                    <?php
                } else {
                    echo '<p class="error">' . __('Error finding uploaded file!', 'order-xml-file-export-import-for-woocommerce') . '</p>';
                }
                break;
            case 3 :  
                $nonce = (isset($_POST['wt_nonce']) ? sanitize_text_field($_POST['wt_nonce']) : '');
                if (!wp_verify_nonce($nonce, WF_ORDER_IMP_EXP_XML_ID) || !OrderImpExpXML_Basic::hf_user_permission()) {
                    wp_die(__('Access Denied', 'order-xml-file-export-import-for-woocommerce'));
                }
                $file      = stripslashes( $_POST['file'] ); // Validating given path is valid path, not a rul
                if (filter_var($file, FILTER_VALIDATE_URL)){ 
                    die();
                }
                add_filter('http_request_timeout_xml', array($this, 'bump_request_timeout_xml'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();               
                $import_type        = sanitize_text_field($_POST['import_type']);
                $import_decision    = sanitize_text_field($_POST['import_decision']);
               
                $this->parsed_data = $this->import_start($file);
                $this->import($import_type, $import_decision);
                $this->import_end();

                $results = array();
                $results['import_results'] = $this->import_results;
                $results['processed_posts'] = $this->processed_posts;

                echo "<!--WC_START-->";
                echo json_encode($results);
                echo "<!--WC_END-->";
                exit;
                break;
            case 4 :
                $nonce = (isset($_POST['wt_nonce']) ? sanitize_text_field($_POST['wt_nonce']) : '');
                if (!wp_verify_nonce($nonce, WF_ORDER_IMP_EXP_XML_ID) || !OrderImpExpXML_Basic::hf_user_permission()) {
                    wp_die(__('Access Denied', 'order-xml-file-export-import-for-woocommerce'));
                }
                add_filter('http_request_timeout_xml', array($this, 'bump_request_timeout_xml'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();

                $this->processed_posts = isset($_POST['processed_posts']) ? wc_clean($_POST['processed_posts']) : array();

                _e('Step 1...', 'order-xml-file-export-import-for-woocommerce') . ' ';

                wp_defer_term_counting(true);
                wp_defer_comment_counting(true);

                _e('Step 2...', 'order-xml-file-export-import-for-woocommerce') . ' ';

                echo 'Step 3...' . ' '; // Easter egg

                _e('Finalizing...', 'order-xml-file-export-import-for-woocommerce') . ' ';

                _e('Finished. Import complete.', 'order-xml-file-export-import-for-woocommerce');
                
                $file = isset($_POST['file']) ? stripslashes($_POST['file']) : '';                                 
                if(in_array(pathinfo($file, PATHINFO_EXTENSION),array('txt','csv'))){
                    unlink($file);
                }
                $this->import_end();
                exit;
                break;
        }

        $this->footer();
    }

    public function format_data_from_csv($data, $enc) {
        return ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
    }

    public function import_options($import_type,$import_decision) {
        $j = 0;
        $import_type = $import_type;
        $import_decision = $import_decision;
        if ($this->id)
            $file = get_attached_file($this->id);
        else if ($this->file_url_import_enabled)
            $file = ABSPATH . $this->file_url;
        else
            return;
        $merge = (!empty($_GET['merge']) && sanitize_text_field($_GET['merge'])) ? 1 : 0;

        include( 'views/html-wf-import-options.php' );
    }

    public function import($import_type, $import_decision) {
        global $woocommerce, $wpdb;
        wp_suspend_cache_invalidation(true);
        $this->hf_log_data_change('xml-import', '---');
        $this->hf_log_data_change('xml-import', __('Processing orders.', 'order-xml-file-export-import-for-woocommerce'));
        $merging = 1;
        $record_offset = 0;
        switch($import_type)
        {
            case 'general':
                $parsed_data_arr = $this->parsed_data[0]['Orders']->Order;
                break;
            case 'stamps':
                $parsed_data_arr = $this->parsed_data[0]['Print']->Item;
                break;
            case 'fedex':
                $parsed_data_arr = $this->parsed_data;
                break;
            case 'endicia':
                $parsed_data_arr = $this->parsed_data[0]['DAZzleLog']->Record;
                break;
            case 'ups':
                $parsed_data_arr = $this->parsed_data;
                break;
        }
        foreach ($parsed_data_arr as $key => $item) {
            $order = $this->parser->parse_orders($item,$import_type);
            if (!is_wp_error($order))
                $this->process_orders($order['shop_order'][0],$import_type,$import_decision);
            else
                $this->add_import_result('failed', $order->get_error_message(), 'Not parsed', json_encode($item), '-');

            unset($item, $order);
            //$i++;
        }
        $this->hf_log_data_change('xml-import', __('Finished processing Orders.', 'order-xml-file-export-import-for-woocommerce'));
        wp_suspend_cache_invalidation(false);
    }

    public function import_start($file) {

        $memory = (WC()->version < '3.0') ? size_format(woocommerce_let_to_num(ini_get('memory_limit'))) : size_format(wc_let_to_num(ini_get('memory_limit')));
        $wp_memory = (WC()->version < '3.0') ? size_format(woocommerce_let_to_num(WP_MEMORY_LIMIT)) : size_format(wc_let_to_num(WP_MEMORY_LIMIT));

        $this->hf_log_data_change('xml-import', '---[ New Import ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
        $this->hf_log_data_change('xml-import', __('Parsing order XML.', 'order-xml-file-export-import-for-woocommerce'));

        $this->parser = new OrderImpExpXML_Parser('shop_order');

        $this->parsed_data = $this->parser->parse_data($file);

        $this->hf_log_data_change('xml-import', __('Finished parsing order XML.', 'order-xml-file-export-import-for-woocommerce'));

        unset($import_data);

        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        return $this->parsed_data;
    }

    public function import_end() {
        do_action('import_end');
    }

    public function handle_upload() {
        
        if (empty($_POST['file_url'])) {

            $file = wp_import_handle_upload();

            if (isset($file['error'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'order-xml-file-export-import-for-woocommerce') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            }

            $this->id = absint($file['id']);
            return true;
        } else {

            if (file_exists(ABSPATH . $_POST['file_url'])) {

                $this->file_url = esc_url($_POST['file_url']);
                return true;
            } else {

                echo '<p><strong>' . __('Sorry, there has been an error.', 'order-xml-file-export-import-for-woocommerce') . '</strong></p>';
                return false;
            }
        }

        return false;
    }
    
    public function order_exists($orderID) {
        global $wpdb;
        $query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_status IN ( 'wc-pending', 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-failed' , 'wc-refunded', 'wc-cancelled')";
        $args = array();
        $posts_are_exist = $wpdb->get_col($query);

        if ($posts_are_exist) {
            foreach ($posts_are_exist as $exist_id) {
                $found = false;
                if ($exist_id == $orderID) {
                    $found = TRUE;
                }
                if ($found)
                    return TRUE;
            }
        } else {
            return FALSE;
        }
    }

    private function process_orders($post,$import_type,$import_decision) {
        
        global $wpdb;
        if($import_type == 'general')
        {
            if ( ! class_exists( 'OrderImpExpXML_GeneralCaseImporter' ) )
                include_once 'class-OrderImpExpXML-general-case-importer.php' ;
            $general_import_obj = new OrderImpExpXML_GeneralCaseImporter();
        }
        $is_order_exist = $this->order_exists($post['order_number']);
        if ($is_order_exist) 
        {
            if($import_type == 'general')
            {
                switch($import_decision)
                {
                    case 'skip':
                        $out_updated_msg = 'Order Skipped';
                        $view_status = 'skipped';
                        $this->skipped++;
                        break;
                    case 'overwrite':
                        $id = $general_import_obj->wf_xml_process_order_general_order_exist($post,$import_decision);
                        if($id)
                        {
                            $out_updated_msg = 'Order Successfully updated.';
                            $view_status = 'imported';
                            $this->imported++;
                        }
                        else
                        {
                            $out_updated_msg = 'Order Skipped';
                            $view_status = 'skipped';
                            $this->skipped++;
                        }
                        break;
                }
            }
            else
            {
                foreach ($post['postmeta'] as $key => $meta)
                {
                    update_post_meta($post['order_number'], $key, $meta);
                }
                $out_updated_msg = 'Order Successfully updated.';
                $view_status = 'imported';
                $this->imported++;
            }
        } 
        else 
        {
            if($import_type == 'general')
            {
                $id = $general_import_obj->wf_xml_process_order_general_new_insert($post);
                if($id)
                {
                    $out_updated_msg = 'Order Successfully inserted.';
                    $view_status = 'imported';
                    $this->imported++;
                }
                else
                {
                    $out_updated_msg = 'Order not created.';
                    $view_status = 'skipped';
                    $this->skipped++;
                }
                
            }
            else
            {
                $out_updated_msg = 'Order doesnot exist.';
                $view_status = 'skipped';
                $this->skipped++;
            }
        }

        
        
        $this->processed_posts[$post['order_number']] = $post['order_number'];

        $this->add_import_result($view_status, __($out_updated_msg, 'order-xml-file-export-import-for-woocommerce'), $post['order_number'], $post['order_number'], $post['order_number']);
        $this->hf_log_data_change('xml-import', sprintf(__('> &#8220;%s&#8221;' . $out_updated_msg, 'order-xml-file-export-import-for-woocommerce'), $post['order_number']), true);
        $this->hf_log_data_change('xml-import', sprintf(__('> Finished importing order %s', 'order-xml-file-export-import-for-woocommerce'), $post['order_number']) );


        $this->hf_log_data_change( 'xml-import', __('Finished processing orders.', 'order-xml-file-export-import-for-woocommerce') );

        unset($post);
    }

    protected function add_import_result($status, $reason, $post_id = '', $post_title = '', $order_number = '') {
        $this->import_results[] = array(
            'post_title' => $post_title,
            'post_id' => $post_id,
            'order_number' => $order_number,
            'status' => $status,
            'reason' => $reason
        );
    }

    public function max_attachment_size() {
        return apply_filters('import_attachment_size_limit', 0);
    }

    public function header() {
        echo '<div class="woocommerce">';
        echo '<div><div class="icon32" id="icon-woocommerce-importer"><br></div>';
        $tab = 'import';
        include_once(plugin_dir_path(WF_OrderImpExpXML_FILE).'includes/views/html-wf-common-header.php');
    }

    public function footer() {
        echo '</div>';
    }

    public function greet() {
        $action = 'admin.php?import=woocommerce_wf_import_order_xml&amp;step=1&amp;merge=' . (!empty($_GET['merge']) ? 1 : 0 );
        $bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size = size_format($bytes);
        $upload_dir = wp_upload_dir();
        include( 'views/html-wf-import-greeting.php' );
    }

    public function bump_request_timeout_xml($val) {
        return 60;
    }

    public function xml_to_array($xml_tree, $root = false) {
        //print_r($xml_tree);
        exit;
        $array_name = $xml_tree['tag'];
        foreach ($xml_tree['children'] as $children) {
            $child_id = $children['attributes']['id'];
            $child_name = $children['tag'];
            $child_name.=($child_id) ? "__" . $child_id : '';
            if (is_array($children['children'])) {
                $child_array = xml_to_array($children);
                $temp_array[$child_name] = $child_array;
            } else {
                $temp_array[$child_name] = $children['value'];
            }
        }

        if (!$root)
            $xml_array = $temp_array;
        else
            $xml_array[$array_name] = $temp_array;

        return $xml_array;
    }

}
