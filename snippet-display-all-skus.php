<?php namespace hws_jewel_trak_importer;

defined( 'ABSPATH' ) || exit;
function enable_display_all_skus(){
// Register AJAX handlers for both logged‑in and guest users
add_action( 'wp_ajax_display_skus', __NAMESPACE__ . '\\ajax_display_skus' );
add_action( 'wp_ajax_nopriv_display_skus', __NAMESPACE__ . '\\ajax_display_skus' );
}

/**
 * AJAX callback: output all product SKUs as a comma‑separated list.
 */
function ajax_display_skus() {
    // Ensure WooCommerce is active
    if ( ! class_exists( '\\WC_Product_Query' ) ) {
        wp_die( '' );
    }

    // Query all published products
    $query    = new \WC_Product_Query( [
        'limit'  => -1,
        'status' => 'publish',
    ] );
    $products = $query->get_products();

    $skus = [];
    foreach ( $products as $product ) {
        $sku = $product->get_sku();
        if ( $sku ) {
            $skus[] = $sku;
        }
    }

    // Echo comma‑separated SKUs (or empty string if none)
    echo implode( ',', $skus );

    wp_die();
}

/*
To fetch via GET, use:
https://jewelryworld.com/wp-admin/admin-ajax.php?action=display_skus
*/
