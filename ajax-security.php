<?php

namespace hws_jewel_trak_importer;

defined( 'ABSPATH' ) || exit;

const AJAX_NONCE_ACTION = 'hws_jewel_trak_importer_admin';

function create_ajax_nonce(): string {
    return wp_create_nonce( AJAX_NONCE_ACTION );
}

function require_admin_ajax(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            [
                'message' => 'Unauthorized.',
                'code'    => 'unauthorized',
            ],
            403
        );
    }

    if ( ! check_ajax_referer( AJAX_NONCE_ACTION, 'nonce', false ) ) {
        wp_send_json_error(
            [
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code'    => 'invalid_nonce',
            ],
            403
        );
    }
}

function protected_admin_ajax_url( string $action ): string {
    return add_query_arg(
        [
            'action' => $action,
            'nonce'  => create_ajax_nonce(),
        ],
        admin_url( 'admin-ajax.php' )
    );
}
