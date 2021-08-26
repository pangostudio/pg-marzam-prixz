<?php
/**
 * Plugin Name: pg-marzam-prixz
 * Description: Plugin personalizado para funciones extra en WooCommerce en Prixz.
 * Author: Pangostudio
 */

include 'shortcodes.php';
include 'settings.php';

function orbis_prixz_woocommerce_before_calculate_totals( $cart ) {
    global $woocommerce;
    $user_id = get_current_user_id();
    $wc_url = get_option('orbis_prixz_url_webservice');

    // Get transient data
    $transient = 'orbis-prixz-cart-'.$user_id;
    if ( !get_transient( $transient ) ){
        set_transient(
            $transient,
            array(
                "user_id"		=> $user_id,
                "card_id" 	=> $cart_data["card_id"],
                "products"	=> array(),
                "benefits"	=> array(),

            )
        );
    }

    $cart_data = get_transient( $transient );

    // Find products
    $cart_data["products"] = array();
    $eanarray = [];
	$arrayWS =[];
    foreach( $cart->get_cart() as $item => $values ) {   
        //var_dump($values) ;
        if ( !isset( $values["marzam-benefit-gift"]) ){
            $product_id = $values['product_id'];
            $variation_id = $values['variation_id'];
            if ( $variation_id ) $_product = wc_get_product( $variation_id );
            else $_product = wc_get_product( $product_id);
            $ean = wpm_get_code_gtin_by_product($product_id);
            //$segmentedEan = explode(" ", $ean);
            
            /*foreach($segmentedEan as $segmentedEanInterior) {
            }*/
            $eanarray[] = $ean;
		$arrayWS[] =  $items = $ean_aux . ',' . $values['quantity'] .','. $_product->get_price() . ',' . ($values['quantity'] * $_product->get_price());
            //Coge el atributo
            $isMarzam = $_product->get_attribute('pa_marzam');
            if ($isMarzam == 'marzam'){
                // hide coupon field on cart page
                //add_filter( 'woocommerce_coupons_enabled', 'hide_coupon_field_on_woocommerce_cart' );
                $cart_data["products"][] = array(
                    "product_id" 		=> $product_id,
                    "variation_id" 	=> $variation_id,
                    "quantity"			=> $values['quantity'],
                    "sku"					=> $ean,
                    "price"					=> $_product->get_price(),
                    "quantity"			=> $values['quantity'],                  
                );  
            }
        }
    }
    //var_dump($eanarray);
	var_dump('arrayWS',$arrayWS);
	$stringWS = implode('|',$arrayWS);
	var_dump($stringWS);
       // Get benefits
		$cart_data["benefits"] = array();

       $key = '7CC7DDAA1760675BC84D010390627FA8';
       //seteamos array para las respuestas del webservice
       $array_respuesta = [];
       if ( $cart_data["card_id"] ){
           // Webservice
           //Primer método (transactionInit)
           $client = new SoapClient( $wc_url );
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

                    foreach ($result[0] as $title) {
                        
                    }
		   foreach($eanarray as $ean_aux) {
			   //tenemos el ean de cada producto
			   var_dump($ean_aux);
               $items = $ean_aux . ',' . $values['quantity'] .','. $_product->get_price() . ',' . ($values['quantity'] * $_product->get_price());
			   var_dump($items);
		   }
               //Sacar variables de response que usaremos en la siguiente llamada
              $transactionid = $title->transactionid;
             
              $cart_data['transactionid'] = (array)$transactionid;
              //var_dump($items);
           }
           //catch primer método
           catch (Exception $e) {
               echo 'no funciona';
           }
	       $auxArray = 0;
           foreach( $cart_data["products"] as $product ){
               //var_dump($product);
                //$items = $id . ',' . $product["quantity"] .','. $product["price"] . ',' . ($product["quantity"] * $product["price"] );
            
                        //Llamada al segundo método (transactionQuote)
                        $client = new SoapClient( $wc_url );
                            $args = array(
                            "cardnumber" => $cart_data["card_id"],
                            "storeid" 	=> '10',
                            "posid"		=> '1',
                            "employeeid"	=> '100',
                            "transactionid" => $transactionid,
                            //"transactionitems" => $items,
			"transactionitems" => $arrayWS[$auxArray],
                            "key"	=> $key,
                    );
		   $auxArray++;
                   
                    // try segundo método
                   try{
                    $result = $client->setTransactionQuote( $args );
                    //Sacamos los valores recibidos parseando
                   $parse_xml= $result->setTransactionQuoteResult->any;
                   $sxe = new SimpleXMLElement($parse_xml);
                   $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
                   $result = $sxe->xpath("//NewDataSet");

                   foreach ($result[0] as $title) {
                       
                   }


                        //Sacar las variables del segundo método
                        $transactionitemsDiscount = $title->transactionitems;
                        //var_dump($result[0]);  
                        $cart_data['transactionitemsDiscount'] = (array)$transactionitemsDiscount;
                        $transactiondate = $title->transactiondate; 
                        $transactionwithdrawal = 0; 
                        $invoicenumber = 1; 
                        $cart_data['invoicenumber'] = $invoicenumber;
                        $invoicedate = $title->transactiondate;
                        $cart_data['invoicedate'] = (array)$invoicedate;
                        $invoiceamount = 1;
                        $cart_data['invoiceamount'] = $invoiceamount;                      
                    }   
                    //catch segundo método
                    catch(Exception $e) {
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
                        "free_pieces" => $transactionitem[3],
                        "product_id"    => $product["product_id"],
                        "variation_id"	=> $product["variation_id"],
                        ];
                        if($array_respuesta[$transactionitem[0]]['free_pieces'] > 0) {
                        $array_respuesta[$transactionitem[0]]['type'] = 2;
                        $array_respuesta[$transactionitem[0]]['message'] = $array_respuesta[$transactionitem[0]]['free_pieces'] . " pieza gratis";
                        }
                        elseif($array_respuesta[$transactionitem[0]]['discount'] > 0) {
                        $array_respuesta[$transactionitem[0]]['type'] = 1;
                        $array_respuesta[$transactionitem[0]]['message'] = $array_respuesta[$transactionitem[0]]['discount'] . "% de Descuento";
                        $array_respuesta[$transactionitem[0]]['discount'] = $_product->get_price() * $array_respuesta[$ean]["discount"] / 100;
                        }
                        else {
                        $array_respuesta[$transactionitem[0]]['type'] = 0;
                        $array_respuesta[$transactionitem[0]]['message'] = "";
                        }
                       
                        }
                        $cart_data["benefits"][] = $array_respuesta;
    
           }
           // Set benefits and apply them
           foreach ( $cart->get_cart() as $item => $values ) {
               $product_id = $values['product_id'];
               $variation_id = $values['variation_id'];
               if ( $variation_id ) $_product = wc_get_product( $variation_id );
               else $_product = wc_get_product( $product_id);
            
               $sku = wpm_get_code_gtin_by_product($product_id);
               
               $benefit_type = isset( $array_respuesta[$ean]["type"] ) ? $array_respuesta[$ean]["type"] : '';
               switch( $benefit_type  ){
                   case "2":
                           $item_name = $_product->get_name().' ('.__( 'Producto gratis','orbis-prixz' ).')'.'<br><span class="message">'.$array_respuesta[$ean]["message"].'</span>';
                           $values["data"]->set_name( $item_name );
                           $values["data"]->set_price( 0 );
                       break;
                   case "1":
                           $item_name = $_product->get_name().'<br><span class="message">'.$array_respuesta[$ean]["message"].'</span>';
                           $values["data"]->set_name( $item_name );
                           
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
    global $woocommerce;
    $user_id = get_current_user_id();
    $transient = 'orbis-prixz-cart-'.$user_id;
    $cart_data = get_transient( $transient );

    foreach ( $cart_data["benefits"] as $benefit ){
       foreach( $benefit as $information){
        if ( $information["type"] == 1 && isset( $information["discount"] ) && $information["discount"] > 0 ){
            if ( isset( $information["variation_id"] ) && $information["variation_id"] ) $_product = wc_get_product( $information["variation_id"] );
            else $_product = wc_get_product( $information["product_id"] );
            $discount_title = __('Descuento en','orbis-prixz').': '.$_product->get_name();
            $woocommerce->cart->add_fee( $discount_title, - $information["discount"], true, 'standard' );
           // var_dump ($cart_data["benefits"]);
            
        }
       }
        
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'orbis_prixz_woocommerce_cart_calculate_fees' ); 

function orbis_prixz_woocommerce_thankyou( $order_id ){

    $transient = 'orbis-prixz-cart-'.get_current_user_id();
    $cart_data = get_transient( $transient );
    $wc_url = get_option('orbis_prixz_url_webservice');
    $key = '7CC7DDAA1760675BC84D010390627FA8';

    if ( $transient ){
        update_post_meta( $order_id, 'orbis_prixz_cart_data', $cart_data );
        if ( count( $cart_data["benefits"] ) > 0 ){
            // If you don't have the WC_Order object (from a dynamic $order_id)
            $order = wc_get_order(  $order_id );
            // The text for the note
            $note = __("Pedido con beneficios del Plan de Apego marzam");
            // Add the note
            $order->add_order_note( $note );
            /* Close the order in WS */
            $client = new SoapClient( $wc_url );
                        $args = array(
                        "cardnumber" => $cart_data["card_id"],
                        "storeid" 	=> '10',
                        "posid"		=> '1',
                        "employeeid"	=> '100',
                        "transactionid" => $cart_data["transactionid"][0],
                        "transactionitems" => $cart_data["transactionitemsDiscount"][0],
                        "transactionwithdrawal" => '0',
                        "invoicenumber" => '1',
                        "invoicedate" => $cart_data["invoicedate"][0], 
                        "invoiceamount" => $cart_data["invoiceamount"],
                        "key"	=> $key,
                         );
                            //try tercer método
                            try{
                               $result = $client->setTransactionSale( $args );  
                                
                                //Sacamos los valores recibidos parseando
                                $parse_xml= $result->setTransactionSaleResult->any;
                                $sxe = new SimpleXMLElement($parse_xml);
                                $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
                                $result = $sxe->xpath("//NewDataSet");  
                            }
                            
                            //cerrar venta

                            //catch tercer método
                            catch(Exception $e) {
                                echo 'no funciona';
                               }

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

function orbis_change_status_to_refund( $order_id ) {
    $order = new WC_Order( $order_id );
    $client = new SoapClient( $wc_url );
                    $args = array(
                    "cardnumber" => $cart_data["card_id"],
                    "storeid" 	=> '10',
                    "posid"		=> '1',
                    "employeeid"	=> '100',
                    "transactionid" => $transactionid,
                    "saleauthnumber" => '1',
                    "invoicenumber" => '1',
                    "key"	=> $key,
                     );
                        //try tercer método
                        try{
                           $result = $client->setSaleReverse( $args );  
                            
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
                        

                        }
                        
                        //cerrar venta

                        //catch tercer método
                        catch(Exception $e) {
                            echo 'no funciona';
                           }


}

?>
