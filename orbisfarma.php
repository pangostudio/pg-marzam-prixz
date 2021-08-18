<?php

/**
 * Plugin Name: orbis
 * Plugin URI: https://pangostudio.com
 * Description: Plugin personalizado para funciones extra en WooCommerce en Prixz.
 * Version: 1.0
 * Author: Pangostudio
 * Author URI: https://pangostudio.com
 * Requires at least: 5.0
 * Tested up to: 5.6
 *
 * Text Domain: orbis-prixz
 * Domain Path: /languages/
 */



defined( ' ABSPATH ');

include 'shortcodes.php';
include 'settings.php';

    function orbis_prixz_woocommerce_before_calculate_totals( $cart ){
        global $woocommerce;
        $user_id = get_current_user_id();
        $wc_url = get_option('orbis_prixz_url_setTransactionInit');
        $categories_search = array( get_option('orbis_prixz__products') );

        // Get transient data
        $transient = 'orbis-prixz-cart-'.$user_id;
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
        $cart_data = get_transient( $transient );

        // Find products
        $cart_data["products"] = array();
        foreach( $cart->get_cart() as $item => $values ) {
            if ( !isset( $values["marzam-benefit-gift"]) ){
                $product_id = $values['product_id'];
                $variation_id = $values['variation_id'];
                if ( $variation_id ) $_product = wc_get_product( $variation_id );
                else $_product = wc_get_product( $product_id);
                $ean = wpm_get_code_gtin_by_product($product_id);
               //cambiar por campo custom
                if ( count( array_intersect( $categories_search, $_product->get_category_ids() ) ) > 0 ){
                    // hide coupon field on cart page
                    add_filter( 'woocommerce_coupons_enabled', 'hide_coupon_field_on_woocommerce_cart' );
                    $cart_data["products"][] = array(
                        "product_id" 		=> $product_id,
                        "variation_id" 	=> $variation_id,
                        "quantity"			=> $values['quantity'],
                        //"sku"						=> $_product->get_sku(),
                        "sku"					=> $ean,
                        "price"					=> $_product->get_price(),
                    );
                }
            }
        }
        
        // Get benefits
        $cart_data["benefits"] = array();
        if ( $cart_data["card_id"] ){
            
            
            // Webservice
            foreach( $cart_data["products"] as $product ){
                $client = new SoapClient( $wc_url );
                $args = array(
                    "Compra" => array(
                        "cardnumber" => 3601001084425,
                        "storeid" 	=> '10',
                        "posid"		=> '1',
                        "employeeid"	=> '1',
                        "key"	=> '1',
                        "Param1" 					=> '0',
                        "Param2"					=> '0',
                        "Pedido"					=> 
                        //segundo metodo
                        array(
                                "SKU" 						=> $product["sku"],
                                "Cantidad" 				=> $product["quantity"],
                                "PrecioFarmacia" 	=> $product["price"],
                        ),
                    ),
                );
                try {
                    $result = $client->setTransactionInit( $args );
                    //llamada al segundo método try catch
                    //llamada al tercer metodo try catch
                    //var_dump($result->Ticket->Folio);
                   // if ( isset( $result->Ticket->DescripcionExcepcion ) ){
                        //echo '<p>'.$result->Ticket->DescripcionExcepcion.'</p>';
                   // }
                   /*
                   if( isset( $result->Ticket->Folio->TipoOperacion ) ){
                        //Guardamos los mensajes que devuelve el WS
                        $marzam_messages = [];
                        switch( $result->Ticket->Folio->TipoOperacion ){
                            case 1:
                                $cart_data["benefits"][$product["sku"]] = array(
                                    "type" 					=> 'free-products',
                                    "product_id"		=> $product["product_id"],
                                    "variation_id"	=> $product["variation_id"],
                                    "price"					=> $product["price"],
                                    "amount"				=> $result->Ticket->Folio->Beneficios,
                                    "message"				=> $result->Ticket->Folio->Mensaje,
                                    "suggestion"		=> $result->Ticket->Folio->Sugeridos,
                                    "CotizacionId"	=> $result->Ticket->CotizacionId,
                                );
                                $marzam_messages[] = $result->Ticket->Folio->Mensaje;
                                //wc_add_notice( sprintf( $result->Ticket->Folio->Mensaje ), 'notice' );
                                //var_dump($result->Ticket->Folio->Mensaje);
                                break;
                            case 2:
                                $cart_data["benefits"][$product["sku"]] = array(
                                    "type" 					=> 'discount',
                                    "product_id"		=> $product["product_id"],
                                    "variation_id"	=> $product["variation_id"],
                                    "price"					=> $result->Ticket->Folio->PrecioDescuento,
                                    "discount"			=> $result->Ticket->Folio->PrecioTotalDescuento,
                                    "message"				=> $result->Ticket->Folio->Mensaje,
                                    "suggestion"		=> $result->Ticket->Folio->Sugeridos,
                                    "CotizacionId"	=> $result->Ticket->CotizacionId,
                                );
                                //echo '<div class="pfizer-conmigo">Pifzer Conmigo: '.$result->Ticket->Folio->Mensaje.'</div>';
                                $marzam_messages[] = $result->Ticket->Folio->Mensaje;
                                break;
                        }
                    }*/
                }
                catch (Exception $e) {
                    
                }

            }
            
            if(isset($marzam_messages)) {
                $marzam_messages = array_unique($marzam_messages);
                foreach($marzam_messages as $message) {
                    //wc_add_notice($message, 'notice');
                }
            }
            // Set benefits
            foreach ( $cart->get_cart() as $item => $values ) {
                $product_id = $values['product_id'];
                $variation_id = $values['variation_id'];
                if ( $variation_id ) $_product = wc_get_product( $variation_id );
                else $_product = wc_get_product( $product_id);
                //$sku = $values['data']->get_sku();
                $sku = wpm_get_code_gtin_by_product($product_id);
                $benefit = isset( $cart_data["benefits"][$sku] ) ? $cart_data["benefits"][$sku] : array();
                //var_dump($benefit);
                $benefit_type = isset( $benefit["type"] ) ? $benefit["type"] : '';
                switch( $benefit_type  ){
                    case "free-products":
                        if ( !isset( $values["marzam-benefit-gift"] ) && $benefit["suggestion"] ){
                            $item_name = $_product->get_name()/*.'<br><span class="suggestion">'.sprintf( __( 'Añade %s más para conseguir otro gratis','orbis-prixz' ), $benefit["suggestion"] ).'</span>'*/;
                            $values["data"]->set_name( $item_name );
                        }
                        if ( isset( $values["marzam-benefit-gift"] ) ){
                            $item_name = $_product->get_name().' ('.__( 'Producto gratis','orbis-prixz' ).')'.'<br><span class="message">'.$benefit["message"].'</span>';
                            $values["data"]->set_name( $item_name );
                            $values["data"]->set_price( 0 );
                        }
                        break;
                    case "discount":
                        if ( $benefit["suggestion"] ){
                            $item_name = $_product->get_name().'<br><span class="suggestion">'.sprintf( __( 'Añade %s más para conseguir un descuento','orbis-prixz' ), $benefit["suggestion"] ).'</span>';
                            $values["data"]->set_name( $item_name );
                        }
                        if ( $benefit["message"] ){
                            $item_name = $_product->get_name().'<br><span class="message">'.$benefit["message"].'</span>';
                            $values["data"]->set_name( $item_name );
                        }
                        break;
                }
            }
        }
        set_transient( $transient, $cart_data );
    }
    add_action('woocommerce_before_calculate_totals', 'orbis_prixz_woocommerce_before_calculate_totals', 10, 1);

    function orbis_prixz_woocommerce_before_cart(){
        echo do_shortcode('[wc_prixz]');
    }
    add_action( 'woocommerce_before_cart', 'orbis_prixz_woocommerce_before_cart' );
    add_action( 'woocommerce_before_checkout_form', 'orbis_prixz_woocommerce_before_cart' );

    function orbis_prixz_woocommerce_cart_calculate_fees(){ 
        //var_dump('orbis_prixz_woocommerce_cart_calculate_fees');
        global $woocommerce;
        $user_id = get_current_user_id();
        $transient = 'orbis-prixz-cart-'.$user_id;
        $cart_data = get_transient( $transient );
        foreach ( $cart_data["benefits"] as $benefit ){
            if ( $benefit["type"] == 'discount' && isset( $benefit["discount"] ) && $benefit["discount"] > 0 ){
                if ( isset( $benefit["variation_id"] ) && $benefit["variation_id"] ) $_product = wc_get_product( $benefit["variation_id"] );
                else $_product = wc_get_product( $benefit["product_id"] );
                $discount_title = __('Descuento en','orbis-prixz').': '.$_product->get_name();
                $woocommerce->cart->add_fee( $discount_title, -$benefit["discount"], true, 'standard' ); 
            }
        }
    }
    add_action( 'woocommerce_cart_calculate_fees', 'orbis_prixz_woocommerce_cart_calculate_fees' ); 

    function orbis_prixz_woocommerce_thankyou( $order_id ){
        //var_dump('thank you', $order_id);
        $transient = 'orbis-prixz-cart-'.get_current_user_id();
        $cart_data = get_transient( $transient );

        if ( $transient ){
            update_post_meta( $order_id, 'orbis_prixz_cart_data', $cart_data );
            if ( count( $cart_data["benefits"] ) > 0 ){
                /* Add a note for Pfizer Conmigo Orders*/
                // If you don't have the WC_Order object (from a dynamic $order_id)
                $order = wc_get_order(  $order_id );
                // The text for the note
                $note = __("Pedido con beneficios del Plan de Apego marzam");
                // Add the note
                $order->add_order_note( $note );
                /* Close the order in WS */
                $wc_url = get_option('orbis_prixz_url_cerrarcompra');
                $client = new SoapClient( $wc_url );
                $args = [];
                foreach( $cart_data["benefits"] as $benefit ){
                    $args = array(
                        //"CerrarCompra" => array(
                            "Token" => array(
                                "Tarjeta"				=> $cart_data["card_id"], 
                                "CotizacionId" 	=> $benefit["CotizacionId"],
                            ),
                        //),
                    );
                }
                //var_dump($args);
                //$result = $client->ProcesarCompra( $args );
                $result = $client->setTransactionSale( $args );
                //var_dump($result);
            }
            delete_transient( $transient );
        }
    }
    add_action( 'woocommerce_thankyou', 'orbis_prixz_woocommerce_thankyou', 10, 1 );
    //hide coupons
    function orbis_hide_coupon_field_on_woocommerce_cart( $enabled ) {

        if ( is_cart() || is_checkout() ) {
            //$enabled = false;
        }

    return $enabled;

    }
    //Pedido cancelado
    //add_action( 'woocommerce_order_status_cancelled', 'orbis_change_status_to_refund', 21, 1 );
    function orbis_change_status_to_refund( $order_id ) {
        $order = new WC_Order( $order_id );
        $wc_url = get_option('orbis_prixz_url_cancelarcompra');
        $client = new SoapClient( $wc_url );
        $result = $client->setTransactionCancel( $args );
        $args = array(
            //"CerrarCompra" => array(
                "Token" => array(
                    "Tarjeta"	=> $cart_data["card_id"], 
                    "CotizacionId" 	=> $benefit["CotizacionId"],
                    ),
            //),
        );
    }
?>