<?php

	function orbis_prixz_shortcode( $atts ){
		ob_start();
		global $woocommerce;
		
		$user_id = get_current_user_id();
		if ( $user_id ){
			$actual_url = get_the_permalink();
			$reload = false;
			$transient = 'orbis-prixz-cart-'.$user_id;
			
			// Init (just in case)
			if ( !get_transient( $transient ) ){
				set_transient(
					$transient,
					array(
						"user_id"		=> $user_id,
						"card_id" 	=> 0,
						"products"	=> array(),
						"benefits"	=> array(),
					)
				);
			}
			
			// Get cart data
			$cart_data = get_transient( $transient );
			
			// Remove card
			if ( isset( $_GET["remove-cart"] ) ){
				$cart_data["card_id"] = 0;
				$cart_data["benefits"] = array();
				set_transient( $transient, $cart_data );
				update_user_meta( $user_id, 'marzam_card_id', '' );
				$reload = true;
			}
			
			// Set card
			if ( isset( $_POST["card_id"] ) ){
				$cart_data["card_id"] = $_POST["card_id"];
				$cart_data["benefits"] = array();
				set_transient( $transient, $cart_data );
				update_user_meta( $user_id, 'marzam_card_id', $_POST["card_id"] );
				$reload = true;
			}
			
			if ( !$cart_data["card_id"] && get_user_meta( $user_id, 'marzam_card_id', true ) ){
				$cart_data["card_id"] = get_user_meta( $user_id, 'marzam_card_id', true );
				$cart_data["benefits"] = array();
				set_transient( $transient, $cart_data );
				$reload = true;
			}
			
			// Card id form
			if ( !$cart_data["card_id"] && count( $cart_data["products"] ) > 0 ) {
				echo '<label>Agrega el folio de tu tarjeta de lealtad <a style="text-decoration: none;"  href="#" data-toggle="tooltip" data-placement="top" title="Consulta a tu médico para saber más sobre el Programa de Lealtad"><i class="fa fa-info-circle" aria-hidden="true"></i></a></label>
				<form name="card_id_form" method="post" action="'.$actual_url.'">
					<input type="text" id="card_id" name="card_id" placeholder="'.__('Número de tarjeta','orbis-prixz').'" required>
					<input type="submit" value="'.__('Comprobar','orbis-prixz').'">
				</form>';
			}
			if ( $reload ){
				echo '<script>window.location.href = \''.$actual_url.'\';</script>';
			}
			
			// Show card id
			if( $cart_data["card_id"] ){
				echo '<p>'.__('Tarjeta','orbis-prixz').' <a style="text-decoration: none;"  href="#" data-toggle="tooltip" data-placement="top" title="Consulta a tu médico para saber más sobre el Programa de Lealtad"><i class="fa fa-info-circle" aria-hidden="true"></i></a>: '.$cart_data["card_id"].' <a href="'.$actual_url.'?remove-cart">'.__('eliminar','orbis-prixz').'</a></p>';
			}
			
			// Set benefits
			$cart = $woocommerce->cart;
			foreach ( $cart->get_cart() as $item => $values ) {
				if ( isset( $values["marzam-benefit-gift"]) ){
					$cart->remove_cart_item( $item );
				}
			}
			foreach ( $cart_data["benefits"] as $benefit ){
				if ( $benefit["type"] == 'free-products' && isset( $benefit["amount"] ) && $benefit["amount"] > 0 ){
					$cart->add_to_cart(
						$benefit["product_id"],
						$benefit["amount"],
						$benefit["variation_id"],
						array(),
						array ( 'marzam-benefit-gift' => true )
					);
				}
			}
		}
		return ob_get_clean();
	}
	add_shortcode( 'wc_marzam_prixz', 'orbis_prixz_shortcode' );

?>