<?php
/*
	Plugin Name: Mailchimp Segmentation
	Plugin URI: http://cameronhurd.com/mc-segmentation
	Description: This plugin allows wp admins to control *how* a sign-up is handled. Newsletter opt-ins on a checkout page could for instance, pass the purchase info to that user's "purchases" column in MC.
	Author: Cameron Hurd
	Version: 1.0.0
	Author URI: http://cameronhurd.com/
*/


// Let's let the users select the list that the product adds them to!
// Metaboxes to the rescue!
function woo_mailchimp_admin_init()
{
	$meta = array(
		'id'    => 'woo_mailchimp_list',
		'title' => 'Woocommerce + Mailchimp',
		'pages' => array('product'),

		'fields' => array(
			array(
				'name' => 'Mailchimp List',
				'id'   => 'woo_mailchimp_list_id',
				'type' => 'woo_mailchimp_list',
			),
		)
	);

	new RW_Meta_Box($meta);
}

if ( is_admin() )
{
	class RWMB_Woo_mailchimp_list_Field extends RWMB_Select_Field
	{
		/**
		 * Get field HTML
		 *
		 * @param string $html
		 * @param mixed  $meta
		 * @param array  $field
		 *
		 * @return string
		 */
		static function html( $html, $meta, $field )
		{
			global $wpdb;

			$html = sprintf(
				'<select class="rwmb-select-advanced" name="%s" id="%s" size="%s"%s data-options="%s">',
				$field['field_name'],
				$field['id'],
				$field['size'],
				$field['multiple'] ? ' multiple="multiple"' : '',
				esc_attr( json_encode( $field['js_options'] ) )
			);

			if (class_exists('GFMailChimp'))
			{
				$post_id = isset($_GET['post']) ? (int) $_GET['post'] : null;

				// Return a list of lists
				$api_key = GFMailChimpWrapper::wrapper_get_api_key();

				if ( !empty($api_key) )
				{
					$default = get_post_meta($post_id, 'woo_mailchimp_list_id', true);
					$html .= "<option value=\"\">-</option>";
					$html .= GFMailChimpWrapper::get_lists($api_key, $default);
				}
			}

			$html .= '</select>';

			return $html;
		}
	}
}

add_action('admin_init', 'woo_mailchimp_admin_init');

class GFMailChimpWrapper extends GFMailChimp
{
    // I'm the fucking lizard king.
    public static function get_api($api_key, $password=null) {
        if( !class_exists("MCAPI") ) {
            require_once( "api/MCAPI.class.php" );
        }
        $api = new MCAPI(trim($api_key), trim($password));
        return $api;
    }

    // Borrowing from gravity forms.
    public static function wrapper_get_api_key(){
        $settings = get_option("gf_mailchimp_settings");
        $api_key  = $settings["apikey"];
        return $api_key;
    }

    // Return the lists for the admin screen.
    public static function get_lists($api_key, $default) {
    	$api = self::get_api($api_key);
    	$lists = $api->lists();
    	$filtered_list = '';
    	foreach($lists['data'] as $number => $attributes) {
    		$selected = ($attributes['id'] == $default ? 'selected="selected"' : '' );
    		$filtered_list .= "<option " . $selected . " value='" . $attributes['id'] . "'>" . $attributes['name'] . "</option>";
    	}
    	return $filtered_list;
    }

}

function woo_mailchimp_complete_order( $order_id = 0 )
{

	// Check if there are transdimensionals afoot.
	if( !class_exists("MCAPI") ) {
        require_once( "api/MCAPI.class.php" );
    }

	if ( 0 < $order_id )
	{
		// Get order object
		$order = new WC_Order( $order_id );

		// Run through each product ordered
		$items = $order->get_items();

		if (sizeof($items)>0)
		{
			foreach($items as $item) 
			{
				if (isset($item['variation_id']) && $item['variation_id'] > 0) {
					$_product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$_product = new WC_Product( $item['product_id'] );
				}

				$list = get_post_meta($_product->post->id, 'woo_mailchimp_list_id', true);

				if ($list)
				{		

						//wp_mail('me@cameronhurd.com', 'It work', 'we try sign up!!');

						$api_key = GFMailChimpWrapper::wrapper_get_api_key();
						$api = GFMailChimpWrapper::get_api($api_key);
						$merge = array(
							'FNAME' => $order->billing_first_name,
							'LNAME' => $order->billing_last_name);

						// var_dump(array("apikey" => $api_key, "list_id" => $list, "merge" => $merge));

						$retval = $api->listSubscribe($list, $order->billing_email, $merge);

						if ($api->errorCode) {

							wp_mail('me@cameronhurd.com', 'Maichimp Subscribe Error', 
								"Unable to load listSubscribe()!\n"."\tCode=".$api->errorCode."\n"."\tMsg=".$api->errorMessage."\n");
						} 

				}
			}
		}
	}
}

add_action( 'woocommerce_payment_complete' , 'woo_mailchimp_complete_order' );
add_action( 'woocommerce_order_status_completed' , 'woo_mailchimp_complete_order' );

?>