<?php namespace hws_jewel_trak_importer;

defined( 'ABSPATH' ) || exit;

/*
 * Helpers this plugin historically took from HWS Base Tools' legacy
 * generic-functions.php (copied into this namespace by
 * hws_alias_namespace_functions()). Base Tools 13 no longer ships that file,
 * so each helper is defined here only when Base Tools did not supply it.
 */

if ( ! function_exists( __NAMESPACE__ . '\\write_log' ) ) {
    function write_log( $log, $full_debug = false, $display_stack = false ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG || ! $full_debug ) {
            return;
        }
        $message = is_array( $log ) || is_object( $log ) ? print_r( $log, true ) : (string) $log; // phpcs:ignore WordPress.PHP.DevelopmentFunctions
        if ( $display_stack ) {
            foreach ( array_slice( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 1 ) as $index => $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions
                $message .= sprintf( "\nStack #%d → %s%s() in %s on line %s", $index + 1, isset( $frame['class'] ) ? $frame['class'] . $frame['type'] : '', $frame['function'] ?? 'N/A', $frame['file'] ?? 'N/A', $frame['line'] ?? 'N/A' );
            }
        }
        error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
    }
}

if ( ! function_exists( __NAMESPACE__ . '\\display_acf_structure' ) ) {
    /** A short HTML outline of field groups: title, key and each field's label, name and type. */
    function display_acf_structure( $group_keys ) {
        $output = '';
        foreach ( (array) $group_keys as $group_key ) {
            $group = \Hexa\PluginCore\Fields\FieldGroups::get_group( (string) $group_key );
            if ( empty( $group ) ) {
                continue;
            }
            $output .= '<div><strong>' . esc_html( (string) $group['title'] ) . '</strong> <code>' . esc_html( (string) $group['key'] ) . '</code><ul>';
            foreach ( \Hexa\PluginCore\Fields\FieldGroups::fields( $group ) as $field ) {
                $output .= '<li>' . esc_html( (string) ( $field['label'] ?? '' ) ) . ' <code>' . esc_html( (string) ( $field['name'] ?? '' ) ) . '</code> (' . esc_html( (string) ( $field['type'] ?? '' ) ) . ')</li>';
            }
            $output .= '</ul></div>';
        }
        return $output;
    }
}
