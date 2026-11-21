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
        \Hexa\PluginCore\LogFiles\DebugLogger::write( $log, (bool) $full_debug, (bool) $display_stack );
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
