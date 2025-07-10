<?php namespace hws_jewel_trak_importer;

function enable_product_importer_process_deletes(){
// 1) Register AJAX for logged-in and public
add_action( 'wp_ajax_delete_products_csv',       __NAMESPACE__ . '\\delete_products_ajax' );
add_action( 'wp_ajax_nopriv_delete_products_csv', __NAMESPACE__ . '\\delete_products_ajax' );
}

// 2) AJAX handler
function delete_products_ajax() {
    // force JSON output
    header( 'Content-Type: application/json; charset=utf-8' );

    $file = ABSPATH . 'products/delete_products.csv';
    if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
        $response = [
            'success' => false,
            'message' => 'CSV file is missing or unreadable.',
            'data'    => null,
        ];
        echo wp_json_encode( $response );
        wp_die();
    }

    $handle = fopen( $file, 'r' );
    $header = fgetcsv( $handle );
    if ( ! $header ) {
        $response = [
            'success' => false,
            'message' => 'Could not read header row.',
            'data'    => null,
        ];
        echo wp_json_encode( $response );
        wp_die();
    }

    // normalize header keys
    $header      = array_map( 'trim', $header );
    $header_keys = array_map( 'strtolower', $header );

    $row_count = 0;
    $deleted   = 0;
    $details   = [];

    // process each row
    while ( ( $data = fgetcsv( $handle ) ) !== false ) {
        $row_count++;
        $row = array_combine( $header_keys, $data );
        $sku = isset( $row['sku'] ) ? trim( $row['sku'] ) : '';

        $product_id = wc_get_product_id_by_sku( $sku );
        $was_deleted = false;
        $msg         = '';

        if ( $product_id ) {
            // delete images & gallery
            delete_product_images_and_meta( $product_id );
            // delete the product
            wp_delete_post( $product_id, true );
            $deleted++;
            $was_deleted = true;
            $msg         = "Deleted product ID {$product_id}";
        } else {
            $msg = "No product found for SKU “{$sku}”";
        }

        $details[] = [
            'row'         => $row_count,
            'sku'         => $sku,
            'product_id'  => $product_id ?: null,
            'deleted'     => $was_deleted,
            'message'     => $msg,
        ];
    }

    fclose( $handle );

    $response = [
        'success' => true,
        'data'    => [
            'processed_rows' => $row_count,
            'total_deleted'  => $deleted,
            'details'        => $details,
        ],
    ];

    echo wp_json_encode( $response );
    wp_die(); // terminate properly
}

function delete_product_images_and_meta( $product_id ) {
    // delete gallery images
    $images    = get_post_meta( $product_id, '_product_image_gallery', true );
    $image_ids = explode( ',', $images );
    foreach ( $image_ids as $image_id ) {
        if ( $image_id ) {
            wp_delete_attachment( intval( $image_id ), true );
        }
    }

    // delete featured image
    if ( $thumb = get_post_thumbnail_id( $product_id ) ) {
        wp_delete_attachment( $thumb, true );
    }
}
