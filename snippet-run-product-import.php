<?php namespace hws_jewel_trak_importer;


function enable_product_importer()
{
    add_action('plugins_loaded', __NAMESPACE__ . '\\enable_product_importer');
}

// The main import function
function import_products_from_csv() {
    echo '<h2>Import started...</h2>';

    /*******************  SETTINGS *****************/

    // Change this path if your CSV is outside plugin folder (adjust accordingly)
    define('CSV_IMPORT_DIR', $_SERVER['DOCUMENT_ROOT'] . "/products/");
     define('CSV_IMPORT_FILE', CSV_IMPORT_DIR . 'SunsetJewelry.csv');
    //define('CSV_IMPORT_FILE', ABSPATH . 'products/SunsetJewelry.csv');

    if (!is_file(CSV_IMPORT_FILE)) {
        echo "File " . CSV_IMPORT_FILE . " is not found.";
        exit;
    }

    if (!is_readable(CSV_IMPORT_FILE)) {
        echo "File " . CSV_IMPORT_FILE . " is not readable.";
        exit;
    }

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $file = CSV_IMPORT_FILE;

    if (($handle = fopen($file, 'r')) !== false) {
        $header = fgetcsv($handle);
        $total_count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $total_count++;
            $product_data = array_combine($header, $data);

            try {
                $product_id = handle_product_import($product_data);
                handle_product_images($product_id, $product_data);
                handle_product_attributes($product_id, $product_data);
                echo "Imported product SKU: " . esc_html($product_data['Sku']) . "<br>";
            } catch (\Exception $e) { // global Exception class with backslash
                echo 'Error importing product: ' . esc_html($e->getMessage()) . "<br>";
                error_log('Error importing product: ' . $e->getMessage());
                exit;
            }
        }

        fclose($handle);

        echo '<h3>Import finished! Total products imported: ' . intval($total_count) . '</h3>';
        exit;
    } else {
        echo 'Error opening CSV file.';
        error_log('Error opening CSV file.');
        exit;
    }
}

function handle_product_import($product_data) {
    $sku = $product_data['Sku'];
    $title = $product_data['Title'];
    $description = $product_data['Description'];
    $price = $product_data['RetailPrice'];
    $quantity = $product_data['Quantity'];
    $stock = $product_data['Status'];
    $categories = explode('|', $product_data['Category']);
    $acquired_date = $product_data['AcquiredDate'];

    // Safely parse date or fallback to current time
    $publish_date = false;
    if ($acquired_date) {
        $date_obj = \DateTime::createFromFormat('Ymd_His', $acquired_date); // <-- backslash here
        if ($date_obj) {
            $publish_date = $date_obj->format('Y-m-d H:i:s');
        }
    }
    if (!$publish_date) {
        $publish_date = current_time('mysql');
    }

    $product_id = wc_get_product_id_by_sku($sku);
    if ($product_id) {
        $product = wc_get_product($product_id);
    } else {
        $product = new \WC_Product_Simple(); // backslash here for WooCommerce class
        $product->set_sku($sku);
    }

    $product->set_name($title);
    $product->set_description($description);
    $product->set_regular_price($price);
    $product->set_manage_stock(true);
    $product->set_stock_quantity($quantity);
    $product->set_stock_status($stock);
    $product->set_date_created($publish_date);
    $product_id = $product->save();

    $category_ids = array();
    foreach ($categories as $category_name) {
        $category_name = str_replace('&', '-', $category_name);  // Replace '&' with '-'
        $category_term = get_term_by('name', $category_name, 'product_cat');
        if (!$category_term) {
            $term_result = wp_insert_term($category_name, 'product_cat');
            if (is_wp_error($term_result)) {
                error_log('Error creating category: ' . $term_result->get_error_message());
                continue;
            }
            $category_term_id = $term_result['term_id'];
            $category_term = get_term_by('id', $category_term_id, 'product_cat'); // refresh term object
        } else {
            $category_term_id = $category_term->term_id;
        }
        $category_ids[] = $category_term_id;

        // Check $category_term is valid before accessing ->parent
        if ($category_term && isset($category_term->parent)) {
            $parent_id = $category_term->parent;
            while ($parent_id != 0) {
                $parent_term = get_term_by('id', $parent_id, 'product_cat');
                if ($parent_term) {
                    $category_ids[] = $parent_term->term_id;
                    $parent_id = $parent_term->parent;
                } else {
                    break;
                }
            }
        }
    }

    $uncategorized_id = get_option('default_product_cat');
    if (($key = array_search($uncategorized_id, $category_ids)) !== false) {
        unset($category_ids[$key]);
    }

    wp_set_object_terms($product_id, $category_ids, 'product_cat');

    return $product_id;
}

function handle_product_images($product_id, $product_data) {
    $image_domain = $product_data['ImageDomain'];
    $images = explode('|', $product_data['Images']);
    $image_ids = array();
    $featured_image_id = null;

    foreach ($images as $index => $image) {
        $image_url = $image_domain . $image;
        $attachment_id = attachment_url_to_postid($image_url);

        if ($attachment_id) {
            if ($index === 0) {
                $featured_image_id = $attachment_id;
            } else {
                $image_ids[] = $attachment_id;
            }
        } else {
            $image_id = media_sideload_image($image_url, $product_id, '', 'id');
            if (!is_wp_error($image_id)) {
                if ($index === 0) {
                    $featured_image_id = $image_id;
                } else {
                    $image_ids[] = $image_id;
                }
            }
        }
    }

    if ($featured_image_id) {
        set_post_thumbnail($product_id, $featured_image_id);
    }

    if (!empty($image_ids)) {
        update_post_meta($product_id, '_product_image_gallery', implode(',', $image_ids));
    }
}

function handle_product_attributes($product_id, $product_data) {
    $attributes = array();
    $attribute_map = array(
        'pa_stonetypes' => 'StoneTypes',
        'pa_stoneweights' => 'StoneWeights',
        'pa_watchmodel' => 'WatchModel',
        'pa_watchserialnumber' => 'WatchSerialNumber',
        'pa_watchbandtype' => 'WatchBandType',
        'pa_watchdialtype' => 'WatchDialType',
        'pa_watchyear' => 'WatchYear',
        'pa_watchhasbox' => 'WatchHasBox',
        'pa_watchhaspapers' => 'WatchHasPapers',
        'pa_watchcondition' => 'WatchCondition',
        'pa_watchmovement' => 'WatchMovement',
        'pa_size' => 'Size',
        'pa_metaltype' => 'MetalType',
        'pa_goldcolor' => 'GoldColor',
    );

    foreach ($attribute_map as $taxonomy => $csv_column) {
        if (!empty($product_data[$csv_column])) {
            $values = explode('|', $product_data[$csv_column]);
            $attributes[$taxonomy] = array(
                'name' => $taxonomy,
                'value' => $product_data[$csv_column],
                'position' => count($attributes) + 1,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 1
            );
            wp_set_object_terms($product_id, $values, $taxonomy);
        }
    }

    update_post_meta($product_id, '_product_attributes', $attributes);
}

// Hook to admin_init to run import when requested via URL parameter
add_action('admin_init', function() {
    if (isset($_GET['run_import']) && $_GET['run_import'] == 1) {
        import_products_from_csv();
        exit; // Stop further page loading after import
    }
});


