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

?>