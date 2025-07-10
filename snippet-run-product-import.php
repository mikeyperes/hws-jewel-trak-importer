<?php namespace hws_jewel_trak_importer;

defined( 'ABSPATH' ) || exit;

// 1) Public AJAX endpoints (no login required):
add_action( 'wp_ajax_import_products_csv',       __NAMESPACE__ . '\\import_products_from_csv' );
add_action( 'wp_ajax_nopriv_import_products_csv', __NAMESPACE__ . '\\import_products_from_csv' );

function enable_product_importer() {
    add_action( 'plugins_loaded', __NAMESPACE__ . '\\enable_product_importer' );
}

// The main import function
function import_products_from_csv() {
    write_log("DEBUG: import_products_from_csv start", true);

    $current_timeout = ini_get('max_execution_time');
    write_log("DEBUG: max_execution_time = {$current_timeout} seconds", true);

    header( 'Content-Type: application/json; charset=utf-8' );
    write_log( "DEBUG: headers sent", true );

    if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        write_log( "DEBUG: not DOING_AJAX", true );
        echo wp_json_encode([
            'success' => false,
            'message' => 'Import not triggered.',
            'data'    => null,
        ]);
        wp_die();
    }
    write_log( "DEBUG: confirmed DOING_AJAX", true );

    define( 'CSV_IMPORT_DIR',  $_SERVER['DOCUMENT_ROOT'] . "/products/" );
    define( 'CSV_IMPORT_FILE', CSV_IMPORT_DIR . 'add_products.csv' );
    write_log( "DEBUG: CSV path = " . CSV_IMPORT_FILE, true );

    if ( ! is_file( CSV_IMPORT_FILE ) || ! is_readable( CSV_IMPORT_FILE ) ) {
        write_log( "DEBUG: CSV missing or unreadable", true );
        echo wp_json_encode([
            'success' => false,
            'message' => 'CSV file is missing or unreadable.',
            'data'    => null,
        ]);
        wp_die();
    }

    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    write_log( "DEBUG: WP includes loaded", true );

    $file         = CSV_IMPORT_FILE;
    $total_count  = 0;
    $imported     = 0;
    $errors       = [];
    $details      = [];
    $failed_items = [];

    write_log( "DEBUG: opening CSV file", true );
    if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
        write_log( "DEBUG: CSV opened", true );
        $header = fgetcsv( $handle );
        write_log( "DEBUG: header row = " . print_r( $header, true ), true );

        if ( ! $header ) {
            write_log( "DEBUG: could not read header", true );
            fclose( $handle );
            echo wp_json_encode([
                'success' => false,
                'message' => 'Could not read header row.',
                'data'    => null,
            ]);
            wp_die();
        }

        write_log( "DEBUG: entering row loop", true );
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            $total_count++;
            $product_data = array_combine( $header, $data );
            $sku = $product_data['Sku'] ?? '';
            write_log( "DEBUG: processing row {$total_count}, SKU={$sku}", true );

            try {
                $product_id = handle_product_import( $product_data );

                // capture skipped images
                $skipped_images = handle_product_images( $product_id, $product_data );

                handle_product_attributes( $product_id, $product_data );

                $imported++;

                // build message including skipped
                $message = "Imported/Updated SKU: {$sku}";
                if ( ! empty( $skipped_images ) ) {
                    $message .= " — skipped existing images: " . implode( ', ', $skipped_images );
                }

                $details[] = [
                    'row'            => $total_count,
                    'sku'            => $sku,
                    'product_id'     => $product_id,
                    'imported'       => true,
                    'message'        => $message,
                    'skipped_images' => $skipped_images,
                ];
            } catch ( \Exception $e ) {
                $msg = "Failed to import SKU {$sku}: " . $e->getMessage();
                write_log( "DEBUG: {$msg}", true );
                $failed_items[] = $msg;
                $details[] = [
                    'row'        => $total_count,
                    'sku'        => $sku,
                    'product_id' => null,
                    'imported'   => false,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        fclose( $handle );

        $response_success = true;
        $response_message = empty($failed_items)
            ? "All {$imported} items imported successfully."
            : "Some items failed to import:\n\n" . implode("\n", array_map(fn($msg) => " - {$msg}", $failed_items));

        $response = [
            'success' => $response_success,
            'message' => $response_message,
            'data'    => [
                'processed_rows' => $total_count,
                'imported'       => $imported,
                'errors'         => $failed_items,
                'details'        => $details,
            ],
        ];

        write_log( "DEBUG: output response " . print_r( $response, true ), true );
        echo wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        wp_die();
    }

    write_log( "DEBUG: failed to open CSV", true );
    echo wp_json_encode([
        'success' => false,
        'message' => 'Error opening CSV file.',
        'data'    => null,
    ]);
    wp_die();
}

function handle_product_import( $product_data ) {
    $sku = $product_data['Sku'];
    write_log( "DEBUG: handle_product_import start SKU={$sku}", true );

    $acquired_date = $product_data['AcquiredDate'] ?? '';
    $publish_date  = current_time( 'mysql' );
    if ( $acquired_date ) {
        $date_obj = \DateTime::createFromFormat( 'Ymd_His', $acquired_date );
        if ( $date_obj ) {
            $publish_date = $date_obj->format( 'Y-m-d H:i:s' );
        }
    }

    $existing_id = wc_get_product_id_by_sku( $sku );
    if ( $existing_id ) {
        $product = wc_get_product( $existing_id );
        write_log( "DEBUG: updating existing product ID={$existing_id}", true );
    } else {
        $product = new \WC_Product_Simple();
        write_log( "DEBUG: creating new product", true );
    }

    $product->set_sku( $sku );
    $product->set_name( $product_data['Title'] );
    $product->set_description( $product_data['Description'] );
    $product->set_regular_price( $product_data['RetailPrice'] );
    $product->set_manage_stock( true );
    $product->set_stock_quantity( $product_data['Quantity'] );
    $product->set_stock_status( $product_data['Status'] );

    if ( ! $existing_id ) {
        $product->set_date_created( $publish_date );
    }

    $product_id = $product->save();
    write_log( "DEBUG: product saved ID={$product_id}", true );

    $category_ids = [];
    $categories   = explode( '|', $product_data['Category'] );
    foreach ( $categories as $category_name ) {
        $cat = str_replace( '&', '-', $category_name );
        $term = get_term_by( 'name', $cat, 'product_cat' );
        if ( ! $term ) {
            $term_result = wp_insert_term( $cat, 'product_cat' );
            if ( is_wp_error( $term_result ) ) {
                write_log( "DEBUG: error creating category '{$cat}': " . $term_result->get_error_message(), true );
                continue;
            }
            $term_id = $term_result['term_id'];
        } else {
            $term_id = $term->term_id;
        }
        $category_ids[] = $term_id;

        $parent_id = $term->parent ?? 0;
        while ( $parent_id ) {
            $parent = get_term_by( 'id', $parent_id, 'product_cat' );
            if ( $parent ) {
                $category_ids[] = $parent_id;
                $parent_id = $parent->parent;
            } else {
                break;
            }
        }
    }

    $uncat = get_option( 'default_product_cat' );
    if ( ( $key = array_search( $uncat, $category_ids ) ) !== false ) {
        unset( $category_ids[ $key ] );
    }

    wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
    return $product_id;
}

function handle_product_images( $product_id, $product_data ) {
    write_log( "DEBUG: handle_product_images start ID={$product_id}", true );
    $image_domain = $product_data['ImageDomain'];
    $images       = explode( '|', $product_data['Images'] );
    $image_ids    = [];
    $featured_id  = null;
    $skipped_urls = [];

    $existing_urls = [];
    $attached_images = get_attached_media( 'image', $product_id );
    foreach ( $attached_images as $attachment ) {
        $stored_url = get_post_meta( $attachment->ID, '_imported_image_url', true );
        if ( $stored_url ) {
            $existing_urls[] = $stored_url;
        }
    }

    foreach ( $images as $index => $image ) {
        $url = $image_domain . $image;
        if ( in_array( $url, $existing_urls, true ) ) {
            write_log( "DEBUG: image '{$url}' already attached to product. Skipping upload.", true );
            $skipped_urls[] = $url;
            continue;
        }

        $attachment_id = media_sideload_image( $url, $product_id, '', 'id' );
        if ( is_wp_error( $attachment_id ) ) {
            write_log( "DEBUG: media_sideload_image failed for '{$url}': " . $attachment_id->get_error_message(), true );
            continue;
        }

        update_post_meta( $attachment_id, '_imported_image_url', $url );
        write_log( "DEBUG: media_sideload_image returned ID={$attachment_id}", true );

        if ( $index === 0 && ! $featured_id ) {
            $featured_id = $attachment_id;
        } else {
            $image_ids[] = $attachment_id;
        }
    }

    if ( $featured_id ) {
        set_post_thumbnail( $product_id, $featured_id );
        write_log( "DEBUG: set featured image ID={$featured_id}", true );
    }

    if ( ! empty( $image_ids ) ) {
        update_post_meta( $product_id, '_product_image_gallery', implode( ',', $image_ids ) );
        write_log( "DEBUG: updated gallery meta IDs=" . implode( ',', $image_ids ), true );
    }

    write_log( "DEBUG: handle_product_images end", true );
    return $skipped_urls;
}

function handle_product_attributes( $product_id, $product_data ) {
    write_log( "DEBUG: handle_product_attributes start ID={$product_id}", true );

    $attribute_map = [
        'pa_stonetypes'        => 'StoneTypes',
        'pa_stoneweights'      => 'StoneWeights',
        'pa_watchmodel'        => 'WatchModel',
        'pa_watchserialnumber' => 'WatchSerialNumber',
        'pa_watchbandtype'     => 'WatchBandType',
        'pa_watchdialtype'     => 'WatchDialType',
        'pa_watchyear'         => 'WatchYear',
        'pa_watchhasbox'       => 'WatchHasBox',
        'pa_watchhaspapers'    => 'WatchHasPapers',
        'pa_watchcondition'    => 'WatchCondition',
        'pa_watchmovement'     => 'WatchMovement',
        'pa_size'              => 'Size',
        'pa_metaltype'         => 'MetalType',
        'pa_goldcolor'         => 'GoldColor',
    ];

    $attributes = [];

    foreach ( $attribute_map as $taxonomy => $col ) {
        if ( ! empty( $product_data[ $col ] ) ) {
            $values = explode( '|', $product_data[ $col ] );
            wp_set_object_terms( $product_id, $values, $taxonomy );
            $attributes[ $taxonomy ] = [
                'name'         => $taxonomy,
                'value'        => $product_data[ $col ],
                'position'     => count( $attributes ) + 1,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
        }
    }

    update_post_meta( $product_id, '_product_attributes', $attributes );
    write_log( "DEBUG: handle_product_attributes end", true );
}

add_action( 'admin_init', function() {
    if ( isset( $_GET['run_import'] ) && $_GET['run_import'] == 1 ) {
        import_products_from_csv();
        exit;
    }
} );
