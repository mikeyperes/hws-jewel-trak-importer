<?php namespace hws_jewel_trak_importer;


function enable_product_importer_process_deletes()
{
    add_action('plugins_loaded', __NAMESPACE__ . '\\delete_products_from_csv');
   // add_action('init', 'delete_products_from_csv');
}




function delete_products_from_csv() {
    if (isset($_GET['run_delete'])) {
        define('CSV_IMPORT_DIR', $_SERVER['DOCUMENT_ROOT'] . "/products/");
        define('CSV_IMPORT_FILE', CSV_IMPORT_DIR . 'ProductDeleteList.csv');

        if (!is_file(CSV_IMPORT_FILE)) {
            echo "File not found.";
            exit;
        }
        
        if (!is_readable(CSV_IMPORT_FILE)) {
            echo "File not readable.";
            exit;
        }

        $file = CSV_IMPORT_FILE;
        if (($handle = fopen($file, 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $product_data = array_combine($header, $data);
                $sku = $product_data['Sku'];

                $product_id = wc_get_product_id_by_sku($sku);
                if ($product_id) {
               
                    delete_product_images_and_meta($product_id);
                    
                    wp_delete_post($product_id, true);
                }
            }

            fclose($handle);
            echo 'Success';
            exit; 
        }
        die();
    }
}

function delete_product_images_and_meta($product_id) {
    $images = get_post_meta($product_id, '_product_image_gallery', true);
    $image_ids = explode(',', $images);
    foreach ($image_ids as $image_id) {
        wp_delete_attachment($image_id, true);
    }
    $featured_image_id = get_post_thumbnail_id($product_id);
    wp_delete_attachment($featured_image_id, true);
}
