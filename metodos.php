<?php

   /**
 * Añade el campo marzam a la página de checkout de WooCommerce
 */
add_action( 'woocommerce_after_order_notes', 'agrega_mi_campo_personalizado' );
 
function agrega_campo_personalizado( $checkout ) {
 
    echo '<div id="additional_checkout_field"><h2>' . __('Información adicional') . '</h2>';
 
    woocommerce_form_field( 'marzam', array(
        'type'          => 'text',
        'class'         => array('my-field-class form-row-wide'),
        'label'         => __('marzam'),
        'placeholder'   => __('Ej: si'),
        ), $checkout->get_value( 'marzam' ));
 
    echo '</div>';
}

/**
 * Actualiza la información del pedido con el nuevo campo
 */
add_action( 'woocommerce_checkout_update_order_meta', 'actualizar_info_pedido_con_nuevo_campo' );
 
function actualizar_info_pedido_con_nuevo_campo( $order_id ) {
    if ( ! empty( $_POST['marzam'] ) ) {
        update_post_meta( $order_id, 'marzam', sanitize_text_field( $_POST['marzam'] ) );
    }
}
?>