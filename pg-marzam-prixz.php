<?php
/**
 * Plugin Name: pg-marzam-prixz
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

include 'shortcodes.php';
include 'settings.php';

class Transaction {
    public $transactionid;
    public $cardnumber;
    }



function orbis_prixz_woocommerce_before_calculate_totals( $cart ){
    global $woocommerce;
    $user_id = get_current_user_id();
    $wc_url = get_option('orbis_prixz_url_webservice');

    //llamamos al campo personalizado $categories_search = array( get_option('orbis_prixz__products') );
    echo 'adios';
    // Get transient data
    $transient = 'orbis-prixz-cart-'.$user_id;
    if ( !get_transient( $transient ) ){
        set_transient(
            $transient,
            array(
                "user_id"		=> $user_id,
                "products" => array(),
                "carditems"	=> array(),
                "benefits"	=> array(),
                "cardnumber" => 3601001084425,
                "storeid" 	=> '10',
                "posid"		=> '1',
                "employeeid"	=> '1',
                "key"	=> '7CC7DDAA1760675BC84D010390627FA8',
                "transactionid" => 0,

            )
        );
    }

    $cart_data = get_transient( $transient );
    
    // Find products
    $cart_data["products"] = array();
    foreach( $cart->get_cart() as $item => $values ) {

       
        //Revisar que hace
        if ( !isset( $values["marzam-benefit-gift"]) ){
            $product_id = $values['product_id'];
            $variation_id = $values['variation_id'];
            if ( $variation_id ) $_product = wc_get_product( $variation_id );
            else $_product = wc_get_product( $product_id);
            $ean = wpm_get_code_gtin_by_product($product_id);
            //Coge el atributo
            $isMarzam = $_product->get_attribute('pa_marzam');
            if ($isMarzam == 'marzam'){
                // hide coupon field on cart page
                add_filter( 'woocommerce_coupons_enabled', 'hide_coupon_field_on_woocommerce_cart' );
                $cart_data["products"][] = array(
                    "product_id" 		=> $product_id,
                    "variation_id" 	=> $variation_id,
                    "quantity"			=> $values['quantity'],
                    //"sku"						=> $_product->get_sku(),
                    "sku"					=> $ean,
                    "price"					=> $_product->get_price(),
                    "quantity"			=> $values['quantity'],
                    
                );
                
            }
        }
    }
    
       // Get benefits
       $cart_data["benefits"] = array();
       $cart_data["card_id"] = '3601001084425';
       $key = '7CC7DDAA1760675BC84D010390627FA8';
       //seteamos array para las respuestas del webservice
       $array_respuesta = [];
       if ( $cart_data["card_id"] ){

           // Webservice
           foreach( $cart_data["products"] as $product ){
               //Primer método (transactionInit)
               $client = new SoapClient( $wc_url, array('classmap' => array('transactionid' => "Transaction") ));
               $args = array(
                       "cardnumber" => $cart_data["card_id"],
                       "storeid" 	=> '10',
                       "posid"		=> '1',
                       "employeeid"	=> '100',
                       "key"	=> $key,
               );
               
                //try primer método
               try {
                   $result = $client->setTransactionInit( $args );

                   //Sacamos los valores recibidos parseando
                   $parse_xml= $result->setTransactionInitResult->any;
                        $sxe = new SimpleXMLElement($parse_xml);
                        $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
                        $result = $sxe->xpath("//NewDataSet");
                        echo "<pre>";
                        foreach ($result[0] as $title) {
                            print_r($title);
                        }
                    echo "</pre>";                
                   //Sacar variables de response que usaremos en la siguiente llamada
                         
                  $transactionid = $title->transactionid;
                  $carditems = $title->carditems;
                  
                  $items = $ean . ',' . $values['quantity'] .','. $_product->get_price() . ',' . ($values['quantity'] * $_product->get_price());
            
                   
                   //Llamada al segundo método (transactionQuote)
                        $client = new SoapClient( $wc_url );
                            $args = array(
                            "cardnumber" => $cart_data["card_id"],
                            "storeid" 	=> '10',
                            "posid"		=> '1',
                            "employeeid"	=> '100',
                            "transactionid" => $transactionid,
                            "transactionitems" => $items,
                            "key"	=> $key,

                    );
                  
                   // try segundo método
                   try{
                    $result = $client->setTransactionQuote( $args );

                    //Sacamos los valores recibidos parseando
                   $parse_xml= $result->setTransactionQuoteResult->any;
                   $sxe = new SimpleXMLElement($parse_xml);
                   $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
                   $result = $sxe->xpath("//NewDataSet");
                   echo "<pre>";
                   foreach ($result[0] as $title) {
                       print_r($title);
                   }
               echo "</pre>";

                    //Sacar las variables del segundo método
                    $transactionitemsDiscount = $title->transactionitems;
                    $transactiondate = $title->transactiondate; 
                    $transactionwithdrawal = 0; 
                    $invoicenumber = 1; 
                    $invoicedate = $title->transactiondate;
                    $invoiceamount = 1;

                        //Llamada al tercer método
                        $client = new SoapClient( $wc_url );
                        $args = array(
                        "cardnumber" => $cart_data["card_id"],
                        "storeid" 	=> '10',
                        "posid"		=> '1',
                        "employeeid"	=> '100',
                        "transactionid" => $transactionid,
                        "transactionitems" => $transactionitemsDiscount,
                        "transactionwithdrawal" => '0',
                        "invoicenumber" => '1',
                        "invoicedate" => $invoicedate, //la fecha va junta (20210812)
                        "invoiceamount" => '1',
                        "key"	=> $key,
                         );
                            //try tercer método
                            try{
                               /* $result = $client->setTransactionSale( $args );  
                                
                                //Sacamos los valores recibidos parseando
                                $parse_xml= $result->setTransactionSaleResult->any;
                                $sxe = new SimpleXMLElement($parse_xml);
                                $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
                                $result = $sxe->xpath("//NewDataSet");
                                echo "<pre>";
                                foreach ($result[0] as $title) {
                                    print_r($title);
                                }
                            echo "</pre>";
                                */

                            }
                            
                            //cerrar venta

                            //catch tercer método
                            catch(Exception $e) {
                                echo 'no funciona';
                               }
                        }   
                    //catch segundo método
                    catch(Exception $e) {
                        echo 'no funciona';
                       }
               }
               //catch primer método
               catch (Exception $e) {
                   echo 'no funciona';
               }

                        //Se setean los tipos de ofertas
                       
                        $transactionimtesDiscount_array = explode('|',$transactionitemsDiscount);

                        foreach($transactionimtesDiscount_array as $transactionitemall) {
                        $transactionitem = explode(',',$transactionitemall);
                        $array_respuesta[$transactionitem[0]] = [
                        "ean" => $transactionitem[0],
                        "quantity" => $transactionitem[1],
                        "discount" => $transactionitem[2],
                        "free_pieces" => $transactionitem[3]
                        ];
                        if($array_respuesta[$transactionitem[0]]['free_pieces'] > 0) {
                        $array_respuesta[$transactionitem[0]]['type'] = 2;
                        $array_respuesta[$transactionitem[0]]['message'] = $array_respuesta[$transactionitem[0]]['free_pieces'] . " pieza gratis";
                        }
                        elseif($array_respuesta[$transactionitem[0]]['discount'] > 0) {
                        $array_respuesta[$transactionitem[0]]['type'] = 1;
                        $array_respuesta[$transactionitem[0]]['message'] = $array_respuesta[$transactionitem[0]]['discount'] . "% de Descuento";
                        }
                        else {
                        $array_respuesta[$transactionitem[0]]['type'] = 0;
                        $array_respuesta[$transactionitem[0]]['message'] = null;
                        }
            
                        }

                       // var_dump($array_respuesta);     

             /*  if( isset( $result) ){
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
                   case "free_pieces":
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
                           var_dump($benefit);
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
     echo do_shortcode('[wc_marzam_prixz]');
    
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

?>