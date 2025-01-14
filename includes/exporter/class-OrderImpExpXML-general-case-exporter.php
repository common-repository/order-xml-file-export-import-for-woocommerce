<?php

if (!defined('ABSPATH')) {
    exit;
}

class OrderImpExpXML_GeneralCaseExporter {

    public function generate_xml_general_case($order_ids,$filename='') {
        include_once( 'class-OrderImpExpXML-order-exp-xml-general.php' );
        $export = new OrderImpExpXML_OrderExpXMLGeneral($order_ids);
        $order_details = $export->get_orders($order_ids);
        $data_array = array('Orders' => array('Order' => $order_details));
        $data_array = OrderImpExpXML_GeneralCaseExporter::wf_order_xml_general_case_export_format($data_array, $order_details);
        $filename .= 'wc_xml';
        $export->do_xml_export($filename, $export->get_order_details_xml($data_array));
    }

    public function wf_order_xml_general_case_export_format($formated_orders, $raw_orders) {
        $order_details = array();
        foreach ($raw_orders as $order) {
            $order_data = array(
                'OrderId' => $order['OrderId'],
                'OrderNumber' => $order['OrderId'],
                'OrderDate' => $order['OrderDate'],
                'OrderStatus' => $order['OrderStatus'],
                'BillingFirstName' => $order['BillingFirstName'],
                'BillingLastName' => $order['BillingLastName'],
                'BillingFullName' => $order['BillingFullName'],
                'BillingCompany' => $order['BillingCompany'],
                'BillingAddress1' => $order['BillingAddress1'],
                'BillingAddress2' => $order['BillingAddress2'],
                'BillingCity' => $order['BillingCity'],
                'BillingState' => $order['BillingState'],
                'BillingPostCode' => $order['BillingPostCode'],
                'BillingCountry' => $order['BillingCountry'],
                'BillingPhone' => $order['BillingPhone'],
                'BillingEmail' => $order['BillingEmail'],
                'ShippingFirstName' => $order['ShippingFirstName'],
                'ShippingLastName' => $order['ShippingLastName'],
                'ShippingFullName' => $order['ShippingFullName'],
                'ShippingCompany' => $order['ShippingCompany'],
                'ShippingAddress1' => $order['ShippingAddress1'],
                'ShippingAddress2' => $order['ShippingAddress2'],
                'ShippingCity' => $order['ShippingCity'],
                'ShippingState' => $order['ShippingState'],
                'ShippingPostCode' => $order['ShippingPostCode'],
                'ShippingCountry' => $order['ShippingCountry'],
                'ShippingMethodId' => $order['ShippingMethodId'],
                'ShippingMethod' => $order['ShippingMethod'],
                'PaymentMethodId' => $order['PaymentMethodId'],
                'PaymentMethod' => $order['PaymentMethod'],
                'OrderDiscountTotal' => $order['OrderDiscountTotal'],
                'CartDiscountTotal' => $order['CartDiscountTotal'],
                'DiscountTotal' => $order['DiscountTotal'],
                'ShippingTotal' => $order['ShippingTotal'],
                'ShippingTaxTotal' => $order['ShippingTaxTotal'],
                'OrderTotal' => $order['OrderTotal'],
                'FeeTotal' => $order['FeeTotal'],
                'TaxTotal' => $order['TaxTotal'],
                'Currency' => get_woocommerce_currency(),
                'CompletedDate' => $order['CompletedDate'],
                'CustomerNote' => $order['CustomerNote'],
                'CustomerId' => $order['CustomerId']
            );
            if (sizeof($order['OrderLineItems']) >= 1) {
                unset($order['OrderLineItems']['total_weight']);
                unset($order['OrderLineItems']['total_qty']);
                unset($order['OrderLineItems']['weight_unit']);
                unset($order['OrderLineItems']['total_height']);
                unset($order['OrderLineItems']['total_width']);
                unset($order['OrderLineItems']['total_length']);
                foreach ($order['OrderLineItems'] as $lineItems) {
                    if (count($lineItems) > 1) {
                        $order_data['OrderLineItems'][] = $lineItems;
                    }
                }
            }


            $order_details[] = $order_data;
        }
        $formated_orders = array('Orders' => array('Order' => $order_details));
        return apply_filters('hf_general_order_export', $formated_orders);
    }

}
