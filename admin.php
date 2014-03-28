<? 

class MC_Segmentation_Editor {

	public $current_options;
	public $saved;

	public function __construct() {

		$this->save_data($_POST);
		$this->get_data();
		$this->render_template();

	}
	
	public function get_data() {

		$data = get_option('mc_segmentation');

		$defaults = array(
			'apikey' => 'ThisIsNotARealAPIKey',
			'listId' => 'ThisIsNotARealListID'
		);

		$this->current_options = wp_parse_args($data, $defaults);

	}

	public function save_data($ar) {

		if ( !empty($ar) && check_admin_referer('mc_segmentation', 'mc_segmentation_nonce') ) {
		
			if ( update_option( 'mc_segmentation', $ar['mc_segmentation'] ) )
				$this->saved = true;

			else $this->saved = false;

		}

	}

	
	public function render_template() { ?>

		<div class="wrap">

			<div id="icon-themes" class="icon32 icon32-posts-post">
				<br>
			</div>

			<h2>Mailchimp Segmentation - Settings</h2>

			<? if( isset($_POST[ 'hidden_submit' ]) && $_POST[ 'hidden_submit' ] == 'Y' && $this->saved ) : ?>
				
				<div class="updated"><p><strong><?php _e('Settings saved.', 'speakers' ); ?></strong></p></div>

			<? elseif( isset($_POST[ 'hidden_submit' ]) && ! $this->saved ) : ?>
				
				<div class="updated"><p><strong><?php _e('Settings NOT saved.', 'speakers' ); ?></strong></p></div>

			<? endif; ?>

			<form name="espeakers_form" method="post" action="">

				<?php wp_nonce_field('mc_segmentation','mc_segmentation_nonce'); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="mc_segmentation[apikey]">Api Key</label></th>
						<td>
							<input name="mc_segmentation[apikey]" id="api_key" value="<? echo $this->current_options['apikey'] ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="mc_segmentation[listId]">List Id</label></th>
						<td>
							<input name="mc_segmentation[listId]" id="list_id" value="<? echo $this->current_options['listId'] ?>" />
						</td>
					</tr>
				</table>
				<br />

				<input type="hidden" name="hidden_submit" value="Y">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />

			</form>

		</div>

	<? }	
}