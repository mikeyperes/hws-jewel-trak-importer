<?php
/*
Plugin Name: HWS JewelTrak Import Tool (Hexa Web Systems)
Description: Jewelry import tool
Author: Hexa Web Systems
Plugin URI: https://github.com/mikeyperes/hws-jewel-trak-importer
Version: 4.1
Text Domain: hws-jewel-trak-importer
Domain Path: /languages
Author URI: https://hexawebsystems.com
GitHub Plugin URI: https://github.com/mikeyperes/hws-jewel-trak-importer
GitHub Branch: main
*/ 
namespace hws_jewel_trak_importer;
 
// Ensure this file is being included by a parent file
defined('ABSPATH') or die('No script kiddies please!');

// Generic functions import 
include_once("generic-functions.php");
 
// Define constants
class Config {
    public static $plugin_name = "HWS JewelTrak Import Tool (Hexa Web Systems)";
    public static $plugin_starter_file = "init.php";

    public static $settings_page_html_id = "hws_jewel_trak_importer";
    public static $settings_page_name = "Hexa Web Systems Importer - Settings";
    public static $settings_page_capability = "manage_options";
    public static $settings_page_slug = "hws-jewel-trak-importer";
    public static $settings_page_display_title = "Hexa Web Systems Jewelry Importer - Settings";
    public static $plugin_short_id = "hws_jt_importer";
    


 

public static function get_github_config() {
    return [
        // 1) The plugin’s WP-slug (must point to your initialization.php)
        'slug'               => 'hws-jewel-trak-importer/init.php',

        // 2) Your folder name on disk
        'proper_folder_name' => 'hws-base-tools',

        // 3) GitHub API endpoints & download URLs
        'api_url' => 'https://api.github.com/repos/mikeyperes/hws-jewel-trak-importer', // GitHub API URL
        'raw_url' => 'https://raw.github.com/mikeyperes/hws-jewel-trak-importer/main', // Raw GitHub URL
        'github_url' => 'https://github.com/mikeyperes/hws-jewel-trak-importer', // GitHub repository URL
        'zip_url' => 'https://github.com/mikeyperes/hws-jewel-trak-importer/archive/main.zip', // Zip URL for the latest version
        // 4) HTTP settings
        'sslverify'          => true,
        'access_token'       => '',

        // 5) WP compatibility info
        'requires'           => '5.0',    // minimum WP version required
        'tested'             => '6.0',    // tested up to this WP version
        'readme'             => 'README.md',

        // 6) Which file to pull “Version:” from
        'plugin_starter_file'=> 'init.php',

        // 7) Explicit plugin metadata (so we never scan PHP headers)
        'plugin_name'        => 'HWS - Jewel Trak Importer',
        'author'             => 'Michael Peres',
        'homepage'           => 'https://github.com/mikeyperes/hws-base-tools',
        'description'        => 'Jewel Trak Importer.',
    ];
}
}




// Always loaded on every admin page:
if ( is_admin() ) {
    // Only remove the shutdown hook on our settings page:
    if ( isset( $_GET['page'] ) && $_GET['page'] === Config::$settings_page_slug ) {
        remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
    }
}


include_once("GitHub_Updater.php");
//hws_import_tool('GitHub_Updater.php', 'WP_GitHub_Updater');
// Automatically imports the class into your current namespace
hws_alias_namespace_functions('hws_base_tools', __NAMESPACE__);





 // Hook to acf/init to ensure ACF is initialized before running any ACF-related code



add_action( 'admin_init', function() {
    $updater = new WP_GitHub_Updater( Config::get_github_config() );

    // if you still want your “force‐update‐check” debug hook:
    if ( isset( $_GET['force-update-check'] ) ) {
        wp_clean_update_cache();
        set_site_transient( 'update_plugins', null );
        wp_update_plugins();
        error_log( 'WP_GitHub_Updater: Forced plugin update check triggered.' );
    }
} );



// Array of plugins to check
$plugins_to_check = [
    'advanced-custom-fields-pro/acf.php',
    'advanced-custom-fields-pro-temp/acf.php'
];

// Initialize flags for active status
$acf_active = false;

// Check if any of the plugins is active
foreach ($plugins_to_check as $plugin) {
    list($installed, $active) = check_plugin_status($plugin);
    if ($active) {
        $acf_active = true;
        break; // Stop checking once we find an active one
    }
}

// If none of the ACF plugins are active, display a warning and prevent the plugin from running
if (!$acf_active) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>'.Config::$plugin_name.'</strong> The Advanced Custom Fields (ACF) or Advanced Custom Fields Pro (ACF Pro) plugin is required and must be active to use this plugin. Please activate ACF or ACF Pro.</p></div>';
    });
    return; // Stop further execution of the plugin
}


//include_once("activate-snippets.php");


add_action('acf/init', function() {
    include_once("acf-register-theme-options.php");
    activate_snippets("acf");
    
  //  if (is_admin()) {
  /*
    include_once("register-acf-structure-theme-options.php");
    include_once("register-acf-structures.php");
    include_once("register-acf-user-profile.php");
    include_once("register-acf-verified-profile.php");


 
    //register_verified_profile_custom_fields();
    */
 //   } 
     
}, 11 );

add_action('init', function() { 

    if(is_admin()){
        include_once("settings-dashboard-snippets.php");
        include_once("settings-dashboard-plugin-info.php");
        include_once("settings-dashboard.php");
        include_once("settings-event-handling.php");

        include_once("snippet-run-product-import.php");
        include_once("snippet-run-product-delete.php");

        activate_snippets("admin");

}


      include_once("settings-dashboard-importer-settings.php");
      include_once("snippet-display-all-skus.php");
      activate_snippets("non_admin");




}, 11 );
  








function get_snippets($type = "")
{

    $snippets_acf = [
        [
            'id'                => 'enable_acf_theme_options',
            'name'              => 'enable_acf_theme_options',
            'description'       => 'this enables abc',
            'info'              => display_acf_structure(["group_68633c8e28585"]),

            'function'          => 'enable_acf_theme_options',
            'scope_admin_only'  => false

        ],/*s
        [
            'id'          => 'register_profile_general_acf_fields',
            'name'        => 'register_profile_acf_fields',
            'description' => display_acf_structure(
                [
                    'group_66b7bdf713e77',  // Post - Verified Profile - Admin
                    'group_656ea6b4d7088',  // Profile - Admin
                    'group_656eb036374de',  // Profile - Person - Public
                    'group_65a8b25062d91',  // User - Profile Manager
                    'group_658602c9eaa49',  // User - Verified Profile Manager - Admin
                ]
            ),
            'info'     => '',
            'function' => 'register_profile_general_acf_fields'
        ],*/
    ];

    $snippet_non_admin = [
        [
            'id'          => 'enable_display_all_skus',
            'name'        => 'enable_display_all_skus',
            'description' => sprintf(
                '<a href="%1$s" target="_blank">%1$s</a>',
                admin_url( 'admin-ajax.php?action=display_skus' )
            ),
            'info'        => '',
            'function'    => 'enable_display_all_skus',
        ]
    ];
        /*
  
        [
            'id'          => 'enable_snippet_verified_profile_shortcodes',
            'name'        => 'enable_snippet_verified_profile_shortcodes',
            'description' => get_formatted_shortcode_list(__NAMESPACE__."\get_verified_profile_shortcodes"),
            'info'        => '',
            'function'    => 'enable_snippet_verified_profile_shortcodes'
        ]*/

  //  $_verified_profile_settings    = get_verified_profile_settings();

    $snippets_admin = [


        [
            'id'               => 'enable_product_importer',
            'name'             => 'enable_product_importer',
            'description'      => sprintf(
                '<a href="%1$s" target="_blank">%1$s</a>',
                admin_url( 'admin-ajax.php?action=import_products_csv' )
            ),
            'info'             => '',
            'function'         => 'enable_product_importer',
            'scope_admin_only' => true,
        ],
        
        [
            'id'               => 'enable_product_importer_process_deletes',
            'name'             => 'enable_product_importer_process_deletes',
            'description'      => sprintf(
                '<a href="%1$s" target="_blank">%1$s</a>',
                admin_url( 'admin-ajax.php?action=delete_products_csv' )
            ),
            'info'             => '',
            'function'         => 'enable_product_importer_process_deletes',
            'scope_admin_only' => true,
        ],
        
        


        /*
        [
            'id'          => 'snippet_post_functionality',
            'name'        => 'snippet_post_functionality',
            'description' => '',
            'info'        => '',
            'function'    => 'snippet_post_functionality'
        ],

   
        */

    ];




    if ($type === 'non_admin') {
        return $snippet_non_admin;
    }

    if ($type === 'admin') {
        return $snippets_admin;
    }

    return $snippets_acf;
}









/**
 * 1) Format any attribute whose label contains "Price" as $X,XXX
 * 2) Paint the entire <tr> green for “Dealer Buy Price” (and XDealer Buy Price)
 * 3) Paint the entire <tr> red   for “Dealer Memo Price”
 * Only runs on the single‐product page.
 */
add_filter( 'woocommerce_display_product_attributes', __NAMESPACE__ . '\\format_price_rows', 10, 2 );
function format_price_rows( $attributes, $product ) {
    foreach ( $attributes as $key => $attr ) {
        $label = $attr['label'];

        // only affect rows with “price” in the label
        if ( stripos( $label, 'price' ) === false ) {
            continue;
        }

        // strip out HTML to get the raw number
        $raw = wp_strip_all_tags( $attr['value'] );
        // if it’s a number, format it
        if ( is_numeric( $raw ) ) {
            $num = floatval( $raw );
            $attributes[ $key ]['value'] = '<p>$' . number_format( $num, 0 ) . '</p>';
        }
    }

    return $attributes;
}

/**
 * Inject inline CSS to color the entire <tr> based on the attribute key.
 * WooCommerce renders <tr class="attribute_{$key}">, so we target those.
 */
add_action( 'wp_head', __NAMESPACE__ . '\\price_row_colors' );
function price_row_colors() {
    if ( ! is_product() ) {
        return;
    }
    ?>
    <style>
      /* Dealer Buy Price & XDealer Buy Price → green */
      tr.woocommerce-product-attributes-item--attribute_dealer-buy-price,
      tr.woocommerce-product-attributes-item--attribute_xdealer-buy-price {
        color: green !important;
        font-weight:bold !important;
      }
      /* Dealer Memo Price → red */
      tr.woocommerce-product-attributes-item--attribute_dealer-memo-price {
        color: red !important;
                font-weight:bold !important;
      }
    </style>


    <?php
}










/**
 * Add the attribute ID below each attribute name field
 * in the Product Data → Attributes metabox.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_attribute_id_script' );

function enqueue_attribute_id_script( $hook ) {
    // Only on the Add/Edit Product screens
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( 'product' !== $screen->post_type ) {
        return;
    }

    // Inject inline JS after jQuery is loaded
    wp_add_inline_script(
        'jquery-core',
        "(function($){
            $(function(){
                $('input[name^=\"attribute_ids\"]').each(function(){
                    var attrID = $(this).val();
                    if ( attrID && attrID !== '0' ) {
                        $(this)
                            .closest('td')
                            .append(
                                '<p class=\"attribute-id-display\" ' +
                                'style=\"margin:4px 0 0;font-size:12px;color:#555;\">' +
                                'ID: ' + attrID +
                                '</p>'
                            );
                    }
                });
            });
        })(jQuery);"
    );
}