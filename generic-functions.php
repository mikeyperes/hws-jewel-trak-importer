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
