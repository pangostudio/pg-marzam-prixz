<?php
global $product;
	function orbis_prixz_settings_page() {
		add_menu_page(
			__( "Plan Apego marzam", "orbis-prixz" ),
			__( "Plan Apego marzam", "orbis-prixz" ),
			'manage_options',
			'plan-apego-marzam',
			'orbis_prixz_settings'
		);
		add_action('admin_init', 'orbis_prixz_options_registration');
	}

	function orbis_prixz_settings() {
		echo '<div class="wrap">
			<h1>'.__( "Plan Apego marzam", "orbis-prixz" ).'</h1>';
			echo '<form method="post" action="options.php">';
				settings_fields( 'orbis-prixz-settings-group' );
				do_settings_sections( 'orbis-prixz-settings-group' ); 
				echo '<h2>'.__('', 'orbis-prixz').'</h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th><label for="orbis_prixz_url_webservice">'.__( 'Webservice', 'orbis-prixz' ).'</label></th>
							<td><input class="regular-text" type="url" id="orbis_prixz_url_webservice" name="orbis_prixz_url_webservice" value="'.get_option('orbis_prixz_url_webservice').'"></td>
						</tr>
					</tbody>
				</table>';
				submit_button();
			echo '</form>
		</div>';
	}
	add_action( 'admin_menu', 'orbis_prixz_settings_page', 10 );

	function orbis_prixz_options_registration(){
		register_setting('orbis-prixz-settings-group', 'orbis_prixz_url_webservice');
	}

?>