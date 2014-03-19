<?php
/*
	Plugin Name: Mailchimp Segmentation
	Plugin URI: http://cameronhurd.com/mc-segmentation
	Description: This plugin allows wp admins to control *how* a sign-up is handled. Newsletter opt-ins on a checkout page could for instance, pass the purchase info to that user's "purchases" column in MC.
	Author: Cameron Hurd
	Version: 1.0.0
	Author URI: http://cameronhurd.com/
*/

include(plugin_dir_path( __FILE__ ) . '/custom-metadata/custom_metadata.php');

add_action( 'admin_menu', 'register_mc_segmentation_menu_page' );
function register_mc_segmentation_menu_page(){
    add_management_page(
        'Mailchimp Segmentation Settings', 
        'MC Segmentation Settings',
    	'edit_theme_options', 
    	'mc_segmentation',
    	'mc_segmentation'
    );
}

function mc_segmentation() {
	include( plugin_dir_path( __FILE__ ) . '/admin.php' );
    new MC_Segmentation_Editor();
}


add_shortcode('mc_segmentation', 'mc_segmentation_check' );

function mc_segmentation_check() {
    include( plugin_dir_path( __FILE__ ) . '/MailChimpApi.php' );

    $api = new \Drewm\MailChimp(get_option('mc_segmentation')['apikey']);

    $merge_vars = Array( 
        'EMAIL' => 'me@cameronhurd.com',
        'FNAME' => 'Firsty', 
        'LNAME' => 'LastName'
    );

    $list_id = "37d5137c62";

    $result = $api->call('lists/subscribe', array(
        'id'                => $list_id,
        'email'             => array( 'email' => $merge_vars['EMAIL'] ),
        'merge_vars'        => array( $merge_vars ),
        'double_optin'      => true,
        'update_existing'   => true,
        'replace_interests' => false,
        'send_welcome'      => true
        ) 
    );

    if( $result )
        return 'Success!&nbsp; Check your inbox or spam folder for a message containing a confirmation link.';
    else
        return '<b>Error:</b>&nbsp; ' . $api->errorMessage;

}

add_action( 'admin_init', 'mc_woocommerce_custom_fields' );
function mc_woocommerce_custom_fields() {
    if( is_plugin_active('woocommerce/woocommerce.php') && function_exists( 'x_add_metadata_group' ) && function_exists( 'x_add_metadata_field' ) ) {

        x_add_metadata_group( 'mcList', 'product', array(
            'label' => 'Mailchimp Options'
        ) );

        x_add_metadata_field('record_purchase', 'product', array(
            'group' => 'mcList',
            'field_type' => 'checkbox',
            'label' => 'Record Purchase of this product?',
            'display_column' => true
        ));
    }
}

add_action( 'woocommerce_payment_complete' , 'woo_mc_segmentation_complete_order' );
add_action( 'woocommerce_order_status_completed' , 'woo_mc_segmentation_complete_order' );

function woo_mc_segmentation_complete_order( $order_id = 0 )
{

    if ( 0 < $order_id )
    {
        // Get order object
        $order = new WC_Order( $order_id );

        // Run through each product ordered
        $items = $order->get_items();

        // Placeholder for what we're taking down.
        $items_to_record = array();

        if (sizeof($items)>0)
            foreach($items as $item) {
                if (isset($item['variation_id']) && $item['variation_id'] > 0)
                    $_product = new WC_Product_Variation( $item['variation_id'] );
                else
                    $_product = new WC_Product( $item['product_id'] );

                if (get_post_meta($_product->get_post_data()->ID, 'record_purchase', true))
                    $items_to_record[] = $_product->get_sku();
            }

        include( plugin_dir_path( __FILE__ ) . '/MailChimpApi.php' );

        $api = new \Drewm\MailChimp(get_option('mc_segmentation')['apikey']);

        $merge_vars = array(
            'EMAIL' => 'mc_purchas_test_11dg@cameronhurd.com',
            'FNAME' => $order->billing_first_name,
            'LNAME' => $order->billing_last_name
        );

        if (sizeof($items_to_record)>0)
            $merge_vars['PURCHASED'] = implode(', ', $items_to_record);

        $list_id = "37d5137c62";
        
        $result = $api->call('lists/subscribe', array(
            'id'                => $list_id,
            'email'             => array( 'email' => $merge_vars['EMAIL'] ),
            'merge_vars'        => $merge_vars,
            'double_optin'      => true,
            'update_existing'   => true,
            'replace_interests' => false,
            'send_welcome'      => true
            ) 
        );
        
    }
}

?>