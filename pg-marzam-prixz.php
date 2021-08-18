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

function orbis_prixz_woocommerce_before_calculate_totals( $cart ){
    global $woocommerce;
    $user_id = get_current_user_id();
    $wc_url = get_option('orbis_prixz_url_TransactionInit');

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
                );
            }
        }
    }
    
       // Get benefits
       $cart_data["benefits"] = array();
       $wc_url = 'https://orbisws00.orbisfarma.com.mx/Transaccion.asmx?wsdl';
       $cart_data["card_id"] = '3601001084425';
       $key = '7CC7DDAA1760675BC84D010390627FA8';
       if ( $cart_data["card_id"] ){
           
        
           // Webservice
           foreach( $cart_data["products"] as $product ){
               //$client = new SoapClient( $wc_url ,  array('setTransactionInit'));
               /*$args = array(
                       "cardnumber" => $cart_data["card_id"],
                       "storeid" 	=> '10',
                       "posid"		=> '1',
                       "employeeid"	=> '1',
                       "key"	=> $key,
               );*/
               //llamada al primer método
               /*$curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://orbisws00.orbisfarma.com.mx/Transaccion.asmx/setTransactionInit',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'cardnumber=3601001084425%20&storeid=10&posid=1&employeeid=100&key=7CC7DDAA1760675BC84D010390627FA8',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
                ));
                */

                $client = new SoapClient( $wc_url );
                $args = array(
                   // "setTransactionInit" => array(
                        "cardnumber" => '3601001084425',
                        "storeid" 	=> '10',
                        "posid"		=> '1',
                        "employeeid"	=> '1',
                        "key"	=> '7CC7DDAA1760675BC84D010390627FA8',
            
                    //),
                );
                //try primer método
               try {
                   $result = $client->setTransactionInit( $args );
                   var_dump($result);
                  /* $response = curl_exec($curl);
                    curl_close($curl);

                    //Se convierte el string que se nos devuelve a un array
                    $array = json_decode(json_encode((array)simplexml_load_string($response)),true);
                    var_dump($array);
                    //  echo $response;
                 */
                   //Sacar variables de response que usaremos en la siguiente llamada
                   /*
        
                   $transactionid =  ;
                   $transactionitems =  ;
                  
                   */
                   //Llamada al segundo método
                   $curl2 = curl_init();

                   curl_setopt_array($curl2, array(
                     CURLOPT_URL => 'https://orbisws00.orbisfarma.com.mx/Transaccion.asmx/setTransactionQuote?cardnumber=3601001084425&storeid=10&posid=1&employeeid=100&key=7CC7DDAA1760675BC84D010390627FA8',
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_ENCODING => '',
                     CURLOPT_MAXREDIRS => 10,
                     CURLOPT_TIMEOUT => 0,
                     CURLOPT_FOLLOWLOCATION => true,
                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                     CURLOPT_CUSTOMREQUEST => 'POST',
                     CURLOPT_POSTFIELDS => 'cardnumber=3601001084425%20&storeid=10&posid=1&employeeid=100&key=7CC7DDAA1760675BC84D010390627FA8&transactionid=13829131856354&transactionitems=4005900436962%2C3%2C2%2C6',
                     CURLOPT_HTTPHEADER => array(
                       'Content-Type: application/x-www-form-urlencoded'
                     ),
                   ));
                  
                   // try segundo método
                   try{
                    $response = curl_exec($curl2);
                    
                    curl_close($curl2);
                    
                    //Sacar las variables del segundo método
                   // $transactionitemsDiscount =  ;
                   // $transactiondate =  ; 
                   // $transactionwithdrawal = 0; 
                   // $invoicenumber = ; 
                   // $invoicedate = (va todo junto 20210812); 
                   // $invoiceamount = ;

                        //Llamada al tercer método
                        $curl3 = curl_init();

                        curl_setopt_array($curl3, array(
                        CURLOPT_URL => 'https://orbisws00.orbisfarma.com.mx/Transaccion.asmx/setTransactionSale?cardnumber=3601001084425&storeid=10&posid=1&employeeid=100&key=7CC7DDAA1760675BC84D010390627FA8',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => 'cardnumber=3601001084425%20&storeid=10&posid=1&employeeid=100&key=7CC7DDAA1760675BC84D010390627FA8&transactionid=13829131856354&transactionitems=4005900436962%2C3%2C2%2C6&transactionwithdrawal=0&invoicenumber=1&invoicedate=20210812&invoiceamount=1',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/x-www-form-urlencoded'
                        ),
                        ));
                            //try tercer método
                            try{
                                $response3 = curl_exec($curl3);
                                
                                curl_close($curl3);
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

}
add_action('woocommerce_before_calculate_totals', 'orbis_prixz_woocommerce_before_calculate_totals', 10, 1);

function orbis_prixz_woocommerce_before_cart(){
    echo 'hola';
     echo do_shortcode('[wc_marzam_prixz]');
    
 }
 add_action( 'woocommerce_before_cart', 'orbis_prixz_woocommerce_before_cart' );
 add_action( 'woocommerce_before_checkout_form', 'orbis_prixz_woocommerce_before_cart' );

 
?>