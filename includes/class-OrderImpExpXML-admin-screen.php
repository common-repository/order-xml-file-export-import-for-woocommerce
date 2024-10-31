<?php
if (!defined('ABSPATH')) {
    exit;
}

class WF_OrderImpExpXML_Admin_Screen {

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_print_styles', array($this, 'admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    public function admin_notices() {
        if (!function_exists('mb_detect_encoding')) {
            echo '<div class="error"><p>' . __('Order XML Import Export requires the function <code>mb_detect_encoding</code> to import and export XML files. Please ask your hosting provider to enable this function.', 'order-xml-file-export-import-for-woocommerce') . '</p></div>';
        }
    }

    public function admin_menu() {
        $page = add_submenu_page('woocommerce', __('Order Export Import XML', 'order-xml-file-export-import-for-woocommerce'), __('Order Export Import XML', 'order-xml-file-export-import-for-woocommerce'), apply_filters('woocommerce_csv_order_role', 'manage_woocommerce'), 'wf_woocommerce_order_im_ex_xml', array($this, 'output'));
    }

    public function admin_scripts() {
        global $wp_scripts;
        $wc_path = self::hf_get_wc_path();
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_style('woocommerce_admin_styles', $wc_path . '/assets/css/admin.css');
        wp_enqueue_style('woocommerce-order-xml-importer', plugins_url(basename(plugin_dir_path(WF_OrderImpExpXML_FILE_URL)) . '/styles/wf-style.css', basename(__FILE__)), '', WT_OXIEP_VERSION, 'screen');

        wp_enqueue_script('woocommerce-order-xml-importer', plugins_url(basename(plugin_dir_path(WF_OrderImpExpXML_FILE_URL)) . '/js/woocommerce-order-xml-importer.js', basename(__FILE__)), array(), WT_OXIEP_VERSION, true);
        wp_localize_script('woocommerce-order-xml-importer', 'woocommerce_order_xml_import_params', array('calendar_icon' => plugins_url(basename(plugin_dir_path(WF_OrderImpExpXML_FILE_URL)) . '/images/calendar.png', basename(__FILE__))));
        wp_enqueue_script('jquery-ui-datepicker');
    }

    public function output() {
        $tab = 'import';
        if (!empty($_GET['tab'])) {
            if ($_GET['tab'] == 'export') {
                $tab = 'export';
            }else if($_GET['tab'] == 'help'){
                $tab = 'help';
            }

        }
        include( 'views/html-wf-admin-screen.php' );
    }
    
    public function admin_import_page() {
        include( 'views/import/html-wf-import-orders.php' );
        include( 'views/export/html-wf-export-orders.php' );
    }

    public function admin_export_page() {
        include( 'views/export/html-wf-export-orders.php' );
    }

    public function admin_help_page() {
        include( 'views/help/html-wf-help.php' );
    }
    
    /**
     * Get WC Plugin path without fail on any version
     */
    public static function hf_get_wc_path() {
        if (function_exists('WC')) {
            $wc_path = WC()->plugin_url();
        } else {
            $wc_path = plugins_url() . '/woocommerce';
        }
        return $wc_path;
    }

}

new WF_OrderImpExpXML_Admin_Screen();
