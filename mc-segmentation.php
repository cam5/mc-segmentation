<?php
/*
	Plugin Name: Mailchimp Segmentation
	Plugin URI: http://cameronhurd.com/mc-segmentation
	Description: This plugin allows wp admins to control *how* a sign-up is handled. Newsletter opt-ins on a checkout page could for instance, pass the purchase info to that user's "purchases" column in MC.
	Author: Cameron Hurd
	Version: 1.0.0
	Author URI: http://cameronhurd.com/
*/

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

?>