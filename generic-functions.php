<?php namespace hws_jewel_trak_importer; 


function activate_snippets($type="") {

$settings_snippets = get_snippets($type);
foreach ($settings_snippets as $snippet) {
    $snippet_id = $snippet['id'];
    $function_to_call = $snippet['function'];

    // Check if the snippet is enabled
    $is_enabled = get_option($snippet_id, false);

    // Log snippet information
    write_log("Processing snippet: {$snippet['name']} (ID: $snippet_id)", false);

    if ($is_enabled) {
        write_log("Snippet $snippet_id is enabled. Preparing to activate.");
        
        // Adjust function name for correct namespace
        $function_to_call = '\\' . __NAMESPACE__ . '\\' . $function_to_call;
        
        if (function_exists($function_to_call)) {
            // Call the function to activate the snippet
            call_user_func($function_to_call);
            write_log("✅ Snippet $snippet_id activated by calling $function_to_call.", false);
        } else {
            write_log("🚫 Function $function_to_call does not exist for snippet $snippet_id.", true);
        }
    } else {
        write_log("🚫 Snippet $snippet_id is not enabled.", false);
    }
}
}

function hws_alias_namespace_functions($from_namespace, $to_namespace = __NAMESPACE__) {
$user_functions = get_defined_functions()['user'];
$from_prefix = $from_namespace . '\\';

foreach ($user_functions as $fn) {
    if (strpos($fn, $from_prefix) === 0) {
        $fn_name = substr($fn, strlen($from_prefix));
        $alias   = $to_namespace . '\\' . $fn_name;

        if (!function_exists($alias)) {
            eval("namespace $to_namespace; function $fn_name() { return \\" . $fn . "(...func_get_args()); }");
        }
    }
}
}


function hws_import_tool($relative_path, $alias_classes = []) {
$base_path = WP_PLUGIN_DIR . '/hws-base-tools/';
$full_path = $base_path . ltrim($relative_path, '/');

if (!file_exists($full_path)) {
    add_action('admin_notices', function () use ($relative_path) {
        echo '<div class="notice notice-error"><p><strong>Scale My Podcast - Core Functionality</strong>: Required file <code>' . esc_html($relative_path) . '</code> is missing from <code>hws-base-tools</code>.</p></div>';
    });
    return false;
}

require_once $full_path; 

// Automatically alias any provided class names into current namespace
foreach ((array) $alias_classes as $class_name) {
    $from = 'hws_base_tools\\' . $class_name;
    $to   = __NAMESPACE__ . '\\' . $class_name;

    if (class_exists($from) && !class_exists($to)) {
        class_alias($from, $to);
    }
}

return true;
}

 


/**
 * Validate FTP credentials by attempting to connect and log in.
 *
 * @param string $host     FTP host (e.g. 'ftp.example.com').
 * @param string $username FTP username.
 * @param string $password FTP password.
 * @param int    $port     FTP port (default: 21).
 * @param int    $timeout  Connection timeout in seconds (default: 90).
 * @return bool True if login succeeds, false on any failure.
 */
if ( ! function_exists( 'validate_ftp_credentials' ) ) {
    function validate_ftp_credentials( $host, $username, $password, $port = 21, $timeout = 90 ) {
        // suppress warnings in case of connection failure
        $conn = @ftp_connect( $host, $port, $timeout );
        if ( ! $conn ) {
            return false;
        }

        $logged_in = @ftp_login( $conn, $username, $password );
        @ftp_close( $conn );

        return (bool) $logged_in;
    }
}

/**
 * Retrieve the current working directory on the FTP server.
 *
 * @param string $host     FTP host (e.g. 'ftp.example.com').
 * @param string $username FTP username.
 * @param string $password FTP password.
 * @param int    $port     FTP port (default: 21).
 * @param int    $timeout  Connection timeout in seconds (default: 90).
 * @return string|false The remote path on success, or false on any failure.
 */
if ( ! function_exists( 'get_ftp_remote_path' ) ) {
    function get_ftp_remote_path( $host, $username, $password, $port = 21, $timeout = 90 ) {
        // suppress warnings in case of connection failure
        $conn = @ftp_connect( $host, $port, $timeout );
        if ( ! $conn ) {
            return false;
        }

        $logged_in = @ftp_login( $conn, $username, $password );
        if ( ! $logged_in ) {
            @ftp_close( $conn );
            return false;
        }

        $remote_path = @ftp_pwd( $conn );
        @ftp_close( $conn );

        return $remote_path !== false ? $remote_path : false;
    }
}

/**
 * Check if a WordPress plugin is installed and active.
 *
 * @param string $plugin_path Plugin path (e.g., 'advanced-custom-fields-pro/acf.php').
 * @return array Array with two boolean values: [is_installed, is_active].
 */
function check_plugin_status($plugin_path) {
    // Check if plugin file exists (installed)
    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
    $is_installed = file_exists($plugin_file);
    
    // Check if plugin is active
    $is_active = false;
    if ($is_installed) {
        // Include plugin.php if not already loaded
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $is_active = is_plugin_active($plugin_path);
    }
    
    return [$is_installed, $is_active];
}

/**
 * Write to WordPress debug log if WP_DEBUG_LOG is enabled.
 *
 * @param string $message The message to log.
 * @param bool $force_error Whether to treat as error log (optional, default false).
 */
function write_log($message, $force_error = false) {
    // Only log if WP_DEBUG_LOG is enabled or if forced
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG || $force_error) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

/**
 * Display ACF field group structure information.
 *
 * @param array $group_ids Array of ACF field group IDs.
 * @return string HTML formatted structure information.
 */
function display_acf_structure($group_ids) {
    if (!function_exists('acf_get_field_group')) {
        return '<em>ACF not available</em>';
    }
    
    $output = '';
    
    foreach ((array) $group_ids as $group_id) {
        $group = acf_get_field_group($group_id);
        
        if (!$group) {
            $output .= "<p><strong>Group ID:</strong> {$group_id} (not found)</p>";
            continue;
        }
        
        $output .= "<div style='margin-bottom: 10px;'>";
        $output .= "<p><strong>Group:</strong> " . esc_html($group['title']) . " (ID: {$group_id})</p>";
        
        // Get fields for this group
        $fields = acf_get_fields($group_id);
        if ($fields) {
            $output .= "<ul style='margin-left: 20px;'>";
            foreach ($fields as $field) {
                $output .= "<li>" . esc_html($field['label']) . " (" . esc_html($field['name']) . ")</li>";
            }
            $output .= "</ul>";
        } else {
            $output .= "<p style='margin-left: 20px;'><em>No fields found</em></p>";
        }
        
        $output .= "</div>";
    }
    
    return $output ?: '<em>No field groups specified</em>';
}
