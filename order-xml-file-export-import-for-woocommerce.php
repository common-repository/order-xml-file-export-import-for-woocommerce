<?php

/*
  Plugin Name: Order XML File Export Import for WooCommerce
  Plugin URI: https://wordpress.org/plugins/order-xml-file-export-import-for-woocommerce/
  Description: Import and Export Order detail including line items, From and To your WooCommerce Store as Endicia XML, FedEx XML, UPS WorldShip XML and also WooCommerce XML.
  Author: WebToffee
  Author URI: http://www.webtoffee.com/
  Version: 1.4.3
  WC tested up to: 7.4
  Text Domain: order-xml-file-export-import-for-woocommerce
  License: GPLv3
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH') || !is_admin()) {
    return;
}

if ( ! defined( 'WF_ORDER_IMP_EXP_XML_ID' ) ) {
    define( 'WF_ORDER_IMP_EXP_XML_ID', 'wf_order_imp_exp_xml' );
}
if ( ! defined( 'wf_woocommerce_order_im_ex_xml' ) ) {
    define( 'wf_woocommerce_order_im_ex_xml', 'wf_woocommerce_order_im_ex_xml' );
}

if ( ! defined( 'WT_OXIEP_VERSION' ) ) {
    define( 'WT_OXIEP_VERSION', '1.4.3' );
}
/**
 * Check if WooCommerce is active
 */

if ( ! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && !array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins', array() ) ) ) ) { // deactive if woocommerce in not active
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename(__FILE__) );
}

register_activation_hook(__FILE__, 'wt_order_xml_register_activation_hook_callback');

function wt_order_xml_register_activation_hook_callback() {
    if(!class_exists( 'WooCommerce' )){
        deactivate_plugins(basename(__FILE__));
        wp_die(__("WooCommerce is not installed/actived. it is required for this plugin to work properly. Please activate WooCommerce.", 'order-xml-file-export-import-for-woocommerce'), "", array('back_link' => 1));
    }
    if (is_plugin_active('order-import-export-for-woocommerce-pro/order-import-export.php')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__("Looks like you have both free and premium version installed on your site! Prior to activating premium, deactivate and delete the free version. For any issue kindly contact our support team here: <a target='_blank' href='https://www.webtoffee.com/support/'>support</a>", 'order-xml-file-export-import-for-woocommerce'), "", array('back_link' => 1));
    }
    update_option('wt_ord_xml_plugin_installed_date', date('Y-m-d H:i:s'));
}

if( class_exists( 'WooCommerce' ) && ! defined('WC_VERSION') ){
    /**
     * Woocommerce version
     */
    define('WC_VERSION', WC()->version);
}

//if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    if (!class_exists('OrderImpExpXML_Basic')) :

        class OrderImpExpXML_Basic {

            public function __construct() {
                define( 'WF_OrderImpExpXML_FILE', __FILE__ );
                define('WF_OrderImpExpXML_FILE_URL',  plugin_dir_url( __FILE__ ) . 'sample-files/');
                add_filter('woocommerce_screen_ids', array($this, 'woocommerce_screen_ids'));
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wf_plugin_action_links'));
                add_action('init', array($this, 'load_plugin_textdomain'));
                add_action('init', array($this, 'catch_export_request'), 20);
                add_action('admin_init', array($this, 'register_importers'));
                
                add_filter('admin_footer_text', array($this, 'WT_admin_footer_text'), 100);
                add_action('wp_ajax_oxie_wt_review_plugin', array($this, "review_plugin"));
                
                // if (!get_option('OXEIPF_Webtoffee_storefrog_admin_notices_dismissed')) {
                //     add_action('admin_notices', array($this,'webtoffee_storefrog_admin_notices'));
                //     add_action('wp_ajax_OXEIPF_webtoffee_storefrog_notice_dismiss', array($this,'webtoffee_storefrog_notice_dismiss'));
                // }

                
                include_once( 'includes/class-OrderImpExpXML-system-status-tools.php' );
                include_once( 'includes/class-OrderImpExpXML-admin-screen.php' );
                include_once( 'includes/importer/class-OrderImpExpXML-importer.php' );

                if (defined('DOING_AJAX')) {
                    include_once( 'includes/class-OrderImpExpXML-ajax-handler.php' );
                }
            }

            public function wf_plugin_action_links($links) {
                $plugin_links = array(
                    '<a href="' . admin_url('admin.php?page=wf_woocommerce_order_im_ex_xml') . '">' . __('Import Export', 'order-xml-file-export-import-for-woocommerce') . '</a>',
                    '<a href="https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_listing&utm_medium=order_xml_imp_exp_basic&utm_campaign=Order_Import_Export&utm_content='.WT_OXIEP_VERSION.'" target="_blank" style="color:#3db634;">' . __('Premium Upgrade', 'order-xml-file-export-import-for-woocommerce') . '</a>',
                    '<a href="https://www.webtoffee.com/support/">' . __('Support', 'order-xml-file-export-import-for-woocommerce') . '</a>',
                    '<a target="_blank" href="https://wordpress.org/support/plugin/order-xml-file-export-import-for-woocommerce/reviews/">' . __('Review', 'order-xml-file-export-import-for-woocommerce') . '</a>',
                );
                return array_merge($plugin_links, $links);
            }

           

            public function woocommerce_screen_ids($ids) {
                $ids[] = 'admin'; // For import screen
                return $ids;
            }

            public function load_plugin_textdomain() {
                load_plugin_textdomain('order-xml-file-export-import-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/lang/');
            }

            public function catch_export_request() {
                if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'wf_woocommerce_order_im_ex_xml') {
                    switch ($_GET['action']) {
                        case "export" :
                            $user_ok = self::hf_user_permission();
                            if ($user_ok) {
                            include_once( 'includes/exporter/class-OrderImpExpXML-base-exporter.php' );
                            $order_imp_exp_obj = new OrderImpExpXMLBase_Exporter();
                            $order_imp_exp_obj->do_export('shop_order');
                            } else {
                                wp_redirect(wp_login_url());
                            }
                            break;
                    }
                }
            }

            
            public function register_importers() {
                register_importer('woocommerce_wf_import_order_xml', 'WooCommerce Order XML', __('Import <strong>Orders</strong> details to your store via a xml file.', 'order-xml-file-export-import-for-woocommerce'), 'OrderImpExpXML_Importer::order_importer');
            }
            public static function hf_user_permission() {
                // Check if user has rights to export
                $current_user = wp_get_current_user();
                $current_user->roles = apply_filters('hf_add_user_roles', $current_user->roles);
                $current_user->roles = array_unique($current_user->roles);
                $user_ok = false;
                $wf_roles = apply_filters('hf_user_permission_roles', array('administrator', 'shop_manager'));
                if ($current_user instanceof WP_User) {
		    $can_users = array_intersect($wf_roles, $current_user->roles);	
                    if (!empty($can_users) || is_super_admin($current_user->ID)) {
                        $user_ok = true;
                    }
                }
                return $user_ok;
            }
            
            
            
            
            function webtoffee_storefrog_admin_notices() {

                if (apply_filters('webtoffee_storefrog_suppress_admin_notices', false) || !self::hf_user_permission()) {
                    return;
                }
                $screen = get_current_screen();

                $allowed_screen_ids = array('woocommerce_page_wf_woocommerce_order_im_ex_xml');
                if (in_array($screen->id, $allowed_screen_ids)|| (isset($_GET['import']) && $_GET['import'] == 'woocommerce_wf_import_order_xml')) {

                    $notice = __('<h3>Save Time, Money & Hassle on Your WooCommerce Data Migration?</h3>', 'order-xml-file-export-import-for-woocommerce');
                    $notice .= __('<h3>Use StoreFrog Migration Services.</h3>', 'order-xml-file-export-import-for-woocommerce');

                    $content = '<style>.webtoffee-storefrog-nav-tab.updated {z-index:2;display: flex;align-items: center;margin: 18px 20px 10px 0;padding:23px;border-left-color: #2c85d7!important}.webtoffee-storefrog-nav-tab ul {margin: 0;}.webtoffee-storefrog-nav-tab h3 {margin-top: 0;margin-bottom: 9px;font-weight: 500;font-size: 16px;color: #2880d3;}.webtoffee-storefrog-nav-tab h3:last-child {margin-bottom: 0;}.webtoffee-storefrog-banner {flex-basis: 20%;padding: 0 15px;margin-left: auto;} .webtoffee-storefrog-banner a:focus{box-shadow: none;}</style>';
                    $content .= '<div class="updated woocommerce-message webtoffee-storefrog-nav-tab notice is-dismissible"><ul>' . $notice . '</ul><div class="webtoffee-storefrog-banner"><a href="http://www.storefrog.com/" target="_blank"> <img src="' . plugins_url(basename(plugin_dir_path(WF_OrderImpExpXML_FILE_URL))) . '/images/storefrog.png"/></a></div><div style="position: absolute;top: 0;right: 1px;z-index: 10000;" ><button type="button" id="webtoffee-storefrog-notice-dismiss" class="notice-dismiss"><span class="screen-reader-text">' . __('Dismiss this notice.', 'wf_order_import_export') . '</span></button></div></div>';
                    echo $content;


                    wc_enqueue_js("jQuery( '#webtoffee-storefrog-notice-dismiss' ).click( function() {
                                        jQuery.post( '" . admin_url("admin-ajax.php") . "', { action: 'OXEIPF_webtoffee_storefrog_notice_dismiss' } );
                                        jQuery('.webtoffee-storefrog-nav-tab').fadeOut();
                                    });
                                ");
                }
            }

            function webtoffee_storefrog_notice_dismiss() {

                if (!self::hf_user_permission()) {
                    wp_die(-1);
                }
                update_option('OXEIPF_Webtoffee_storefrog_admin_notices_dismissed', 1);
                wp_die();
            }

            
            public function WT_admin_footer_text($footer_text) {
                if (!self::hf_user_permission()) {
                    return $footer_text;
                }
                $screen = get_current_screen();
                $allowed_screen_ids = array('woocommerce_page_wf_woocommerce_order_im_ex_xml');
                if (in_array($screen->id, $allowed_screen_ids)|| (isset($_GET['import']) && $_GET['import'] == 'woocommerce_wf_import_order_xml')) {
                    if (!get_option('oxie_wt_plugin_reviewed')) {
                        $footer_text = sprintf( 
                                __('If you like the plugin please leave us a %1$s review.', 'order-xml-file-export-import-for-woocommerce'), '<a href="https://wordpress.org/support/plugin/order-xml-file-export-import-for-woocommerce/reviews?rate=5#new-post" target="_blank" class="wt-review-link" data-rated="' . esc_attr__('Thanks :)', 'order-xml-file-export-import-for-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                        );
                        wc_enqueue_js(
                                "jQuery( 'a.wt-review-link' ).click( function() {
                                                   jQuery.post( '" . WC()->ajax_url() . "', { action: 'oxie_wt_review_plugin' } );
                                                   jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                                           });"
                        );
                    } else {
                        $footer_text = __('Thank you for your review.', 'order-xml-file-export-import-for-woocommerce');
                    }
                }

                return '<i>'.$footer_text.'</i>';
            }
            
            
            public function review_plugin(){
                if (!self::hf_user_permission()) {
                    wp_die(-1);
                }
                update_option('oxie_wt_plugin_reviewed', 1);
                wp_die();
                
            }


        }

    endif;

    new OrderImpExpXML_Basic();
//}
    
/*
 *  Displays update information for a plugin. 
 */
function wt_order_xml_file_export_import_for_woocommerce_update_message( $data, $response )
{
    if(isset( $data['upgrade_notice']))
    {
        printf(
        '<div class="update-message wt-update-message">%s</div>',
           $data['upgrade_notice']
        );
    }
}
add_action( 'in_plugin_update_message-order-xml-file-export-import-for-woocommerce/order-import-export-xml.php', 'wt_order_xml_file_export_import_for_woocommerce_update_message', 10, 2 );
