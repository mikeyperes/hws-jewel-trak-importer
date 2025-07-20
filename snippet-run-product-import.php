<?php namespace hws_jewel_trak_importer;

defined( 'ABSPATH' ) || exit;



function enable_product_importer() {
  //  add_action( 'plugins_loaded', __NAMESPACE__ . '\\enable_product_importer' );
  // Hard‐coded switch: when true, use FIFU PRO to assign remote images instead of sideloading
define( 'IMPORT_PHOTOS_WITH_FIFU', true );
// 1) Public AJAX endpoints (no login required):
add_action( 'wp_ajax_import_products_csv',       __NAMESPACE__ . '\\import_products_from_csv' );
add_action( 'wp_ajax_nopriv_import_products_csv', __NAMESPACE__ . '\\import_products_from_csv' ); 
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
    write_log( "DEBUG: WP media includes loaded", true );

    $file         = CSV_IMPORT_FILE;
    $total_count  = 0;
    $imported     = 0;
    $details      = [];
    $failed_items = [];

    write_log( "DEBUG: opening CSV file", true );
    if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
        write_log( "DEBUG: CSV opened successfully", true );
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

        write_log( "DEBUG: entering CSV row loop", true );
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            $total_count++;
            $product_data = array_combine( $header, $data );
            $sku          = $product_data['Sku'] ?? '';
            write_log( "DEBUG: Processing row {$total_count}, SKU={$sku}", true );

            try {
                $product_id     = handle_product_import( $product_data );
                $skipped_images = handle_product_images( $product_id, $product_data );
                // NEW: capture returned ACF‑based attributes
                $acf_fields     = handle_product_attributes( $product_id, $product_data );
                // NEW: get the product permalink
                $permalink      = get_permalink( $product_id );

                $imported++;
                $message = "Imported/Updated SKU: {$sku}";
                if ( ! empty( $skipped_images ) ) {
                    $message .= " — skipped existing images: " . implode( ', ', $skipped_images );
                }

                $details[] = [
                    'row'            => $total_count,
                    'sku'            => $sku,
                    'product_id'     => $product_id,
                    'permalink'      => $permalink,
                    'imported'       => true,
                    'message'        => $message,
                    'skipped_images' => $skipped_images,
                    'acf_fields'     => $acf_fields,
                ];
            } catch ( \Exception $e ) {
                $msg = "Failed to import SKU {$sku}: " . $e->getMessage();
                write_log( "DEBUG: {$msg}", true );
                $failed_items[] = $msg;
                $details[]      = [
                    'row'        => $total_count,
                    'sku'        => $sku,
                    'product_id' => null,
                    'imported'   => false,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        fclose( $handle );
        write_log( "DEBUG: CSV processing complete. Total rows: {$total_count}, Imported: {$imported}", true );

        $response_message = empty( $failed_items )
            ? "All {$imported} items imported successfully."
            : "Some items failed to import:\n\n" . implode("\n", array_map(fn($m) => " - {$m}", $failed_items));

        $response = [
            'success' => true,
            'message' => $response_message,
            'data'    => [
                'processed_rows' => $total_count,
                'imported'       => $imported,
                'errors'         => $failed_items,
                'details'        => $details,
            ],
        ];

        write_log( "DEBUG: outputting JSON response: " . print_r( $response, true ), true );
        echo wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        wp_die();
    }

    write_log( "DEBUG: failed to open CSV handle", true );
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

    $publish_date = current_time( 'mysql' );
    if ( ! empty( $product_data['AcquiredDate'] ) ) {
        $date_obj = \DateTime::createFromFormat( 'Ymd_His', $product_data['AcquiredDate'] );
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
        write_log( "DEBUG: creating new product for SKU={$sku}", true );
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
    write_log( "DEBUG: product saved with ID={$product_id}", true );

    // categories
    $category_ids = [];
    foreach ( explode( '|', $product_data['Category'] ) as $cat_name ) {
        $cat  = str_replace( '&', '-', $cat_name );
        $term = get_term_by( 'name', $cat, 'product_cat' );
        if ( ! $term ) {
            $res = wp_insert_term( $cat, 'product_cat' );
            if ( is_wp_error( $res ) ) {
                write_log( "DEBUG: error creating category '{$cat}': " . $res->get_error_message(), true );
                continue;
            }
            $term_id = $res['term_id'];
        } else {
            $term_id = $term->term_id;
        }
        $category_ids[] = $term_id;
        // include parent hierarchy
        $parent_id = $term->parent ?? 0;
        while ( $parent_id ) {
            $parent = get_term_by( 'id', $parent_id, 'product_cat' );
            if ( ! $parent ) break;
            $category_ids[] = $parent_id;
            $parent_id      = $parent->parent;
        }
    }
    // remove default if present
    if ( ( $key = array_search( get_option('default_product_cat'), $category_ids ) ) !== false ) {
        unset( $category_ids[ $key ] );
    }
    wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
    write_log( "DEBUG: categories assigned for product {$product_id}", true );

    return $product_id;
}

function handle_product_images( $product_id, $product_data ) {
    write_log( "DEBUG: handle_product_images start for product {$product_id}", true );
    write_log( "DEBUG: IMPORT_PHOTOS_WITH_FIFU = " . ( IMPORT_PHOTOS_WITH_FIFU ? 'true' : 'false' ), true );
    write_log( "DEBUG: function_exists('fifu_dev_set_image_list') = " . ( function_exists( 'fifu_dev_set_image_list' ) ? 'true' : 'false' ), true );

    $image_domain = $product_data['ImageDomain'];
    $images       = explode( '|', $product_data['Images'] );
    write_log( "DEBUG: raw image filenames = " . print_r( $images, true ), true );

    $skipped_urls = []; 

    // FIFU PRO: assign remote URLs via FIFU and skip local sideloading
    if ( IMPORT_PHOTOS_WITH_FIFU ) {
        // force-load the Pro dev helpers
        if ( ! function_exists('fifu_dev_set_image_list') ) {
          require_once WP_PLUGIN_DIR . '/fifu-premium/includes/util.php';
        }
        if ( function_exists('fifu_dev_set_image_list') ) {
          write_log("DEBUG: entering FIFU branch", true);
          $urls = array_map(fn($i)=>esc_url_raw($product_data['ImageDomain'].$i), explode('|',$product_data['Images']));
          $ok   = fifu_dev_set_image_list( $product_id, implode('|',$urls) );
          write_log("DEBUG: fifu_dev_set_image_list returned " . ($ok?'true':'false'), true);
          return [];
        }
      }

    write_log( "DEBUG: entering fallback sideload branch", true );

    // Default fallback: sideload into Media Library
    $existing_urls   = [];
    $attached_images = get_attached_media( 'image', $product_id );
    foreach ( $attached_images as $att ) {
        if ( $u = get_post_meta( $att->ID, '_imported_image_url', true ) ) {
            $existing_urls[] = $u;
        }
    }
    write_log( "DEBUG: existing imported URLs = " . implode( ', ', $existing_urls ), true );

    $gallery_ids = [];
    foreach ( $images as $index => $img ) {
        $url = esc_url_raw( $image_domain . $img );
        if ( in_array( $url, $existing_urls, true ) ) {
            write_log( "DEBUG: skipping existing {$url}", true );
            $skipped_urls[] = $url;
            continue;
        }
        $aid = media_sideload_image( $url, $product_id, '', 'id' );
        if ( is_wp_error( $aid ) ) {
            write_log( "DEBUG: sideload failed for {$url}: " . $aid->get_error_message(), true );
            continue;
        }
        update_post_meta( $aid, '_imported_image_url', $url );
        write_log( "DEBUG: sideloaded image ID={$aid}", true );

        if ( $index === 0 ) {
            set_post_thumbnail( $product_id, $aid );
            write_log( "DEBUG: set_post_thumbnail ID={$aid}", true );
        } else {
            $gallery_ids[] = $aid;
        }
    }

    if ( ! empty( $gallery_ids ) ) {
        update_post_meta( $product_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
        write_log( "DEBUG: updated product gallery with IDs=" . implode( ',', $gallery_ids ), true );
    }

    write_log( "DEBUG: handle_product_images end for product {$product_id}", true );
    return $skipped_urls;
}

function X_handle_product_attributes( $product_id, $product_data ) {
    write_log( "DEBUG: handle_product_attributes start for product {$product_id}", true );
    $map = [
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
    foreach ( $map as $tax => $col ) {
        if ( ! empty( $product_data[ $col ] ) ) {
            $vals = explode( '|', $product_data[ $col ] );
            wp_set_object_terms( $product_id, $vals, $tax );
            $attributes[ $tax ] = [
                'name'         => $tax,
                'value'        => $product_data[ $col ],
                'position'     => count( $attributes ) + 1,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
            write_log( "DEBUG: set attribute {$tax} => " . implode( ',', $vals ), true );
        }
    }
    update_post_meta( $product_id, '_product_attributes', $attributes );
    write_log( "DEBUG: handle_product_attributes end for product {$product_id}", true );
}








function handle_product_attributes( $product_id, $product_data ) {
    write_log( "DEBUG: handle_product_attributes start for product {$product_id}", true );

    $attributes = [];

    // 1) STATIC MAP (unchanged)
    $static_map = [
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
    foreach ( $static_map as $tax => $col ) {
        if ( ! empty( $product_data[ $col ] ) ) {
            $vals = array_map( 'trim', explode( '|', $product_data[ $col ] ) );
            wp_set_object_terms( $product_id, $vals, $tax );
            $attributes[ $tax ] = [
                'name'         => $tax,
                'value'        => $product_data[ $col ],
                'position'     => count( $attributes ) + 1,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
            write_log( "DEBUG: [static] set attribute {$tax} => " . implode( ',', $vals ), true );
        }
    }

    // 2) DYNAMIC ACF‑BASED FIELDS
    $acf_rows = get_field( 'product_custom_fields', 'option' );
    if ( is_array( $acf_rows ) ) {
        foreach ( $acf_rows as $i => $row ) {
            $display   = trim( $row['display_header']   );
            $csv_key   = trim( $row['csv_header']       );
            $attr_type = in_array( $row['type'] ?? '', ['select','text'], true ) ? $row['type'] : 'select';
            $visible   = ! empty( $row['visible'] ) ? 1 : 0;

            if ( ! $display || ! $csv_key ) {
                write_log( "DEBUG: skipping row {$i} missing display or csv_header", true );
                continue;
            }

            $raw = isset( $product_data[ $csv_key ] ) ? trim( $product_data[ $csv_key ] ) : '';
            if ( '' === $raw ) {
                write_log( "DEBUG: skipping '{$display}' because CSV value is empty", true );
                continue;
            }

            // --- USE 'id' WHEN SET ---
            $custom_id = trim( $row['id'] ?? '' );
            $slug      = $custom_id !== '' ? sanitize_title( $custom_id ) : sanitize_title( $display );
            $taxonomy  = wc_attribute_taxonomy_name( $slug );

            if ( 'select' === $attr_type ) {
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    $new_id = wc_create_attribute( [
                        'attribute_name'   => $slug,
                        'attribute_label'  => $display,
                        'attribute_type'   => 'select',
                        'attribute_orderby'=> 'menu_order',
                        'attribute_public' => 0,
                    ] );
                    if ( is_wp_error( $new_id ) ) {
                        write_log( "ERROR: wc_create_attribute failed for {$display}: ".$new_id->get_error_message(), true );
                        continue;
                    }
                    register_taxonomy( $taxonomy, ['product'], [
                        'labels'       => ['name'=>$display],
                        'hierarchical' => true,
                        'show_ui'      => false,
                        'query_var'    => true,
                        'rewrite'      => false,
                    ] );
                    write_log( "DEBUG: registered taxonomy {$taxonomy}", true );
                }

                $terms = array_map( 'trim', explode( '|', $raw ) );
                foreach ( $terms as $t ) {
                    if ( ! term_exists( $t, $taxonomy ) ) {
                        wp_insert_term( $t, $taxonomy );
                        write_log( "DEBUG: inserted term '{$t}' into {$taxonomy}", true );
                    }
                }

                wp_set_object_terms( $product_id, $terms, $taxonomy );
                write_log( "DEBUG: assigned terms for {$taxonomy}: ".implode(',', $terms), true );

                $attributes[ $slug ] = [
                    'name'         => $taxonomy,
                    'value'        => $raw,
                    'position'     => count( $attributes ) + 1,
                    'is_visible'   => $visible,
                    'is_variation' => 0,
                    'is_taxonomy'  => 1,
                ];
            } else {
                // TEXT ATTRIBUTE
                $attributes[ $slug ] = [
                    'name'         => $display,
                    'value'        => $raw,
                    'position'     => count( $attributes ) + 1,
                    'is_visible'   => $visible,
                    'is_variation' => 0,
                    'is_taxonomy'  => 0,
                ];
                write_log( "DEBUG: set text attribute '{$slug}' => '{$raw}'", true );
            }

            write_log( "DEBUG: [dynamic] added attribute for slug '{$slug}'", true );
        }
    }

    // 3) SAVE & RETURN
    update_post_meta( $product_id, '_product_attributes', $attributes );
    write_log( "DEBUG: handle_product_attributes end for product {$product_id}", true );

    return $attributes;
}














add_action( 'admin_init', function() {
    if ( isset( $_GET['run_import'] ) && $_GET['run_import'] == 1 ) {
        write_log( "DEBUG: admin_init run_import triggered", true );
        import_products_from_csv();
        exit;
    }
} );
