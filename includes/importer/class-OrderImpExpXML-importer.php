<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderImpExpXML_Importer {

	public static function load_wp_importer() {
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}
	}

	public static function order_importer() {
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			return;
		}
		self::load_wp_importer();

		require_once 'class-OrderImpExpXML-order-import.php';
		require_once 'class-OrderImpExpXML-base-xml-parser.php';

		$GLOBALS['WF_XML_Order_Import'] = new OrderImpExpXML_OrderImport();
		$GLOBALS['WF_XML_Order_Import'] ->dispatch();
	}	
}