<?php

namespace hws_jewel_trak_importer;

use Hexa\PluginCore\WpAdminAjax\AjaxGuard;

defined( 'ABSPATH' ) || exit;

const AJAX_NONCE_ACTION = 'hws_jewel_trak_importer_admin';

function create_ajax_nonce(): string {
    return wp_create_nonce( AJAX_NONCE_ACTION );
}

function require_admin_ajax(): void {
    AjaxGuard::require_access(
        'manage_options',
        AJAX_NONCE_ACTION,
        'nonce',
        capability_error: [ [ 'message' => 'Unauthorized.', 'code'    => 'unauthorized', ], 403 ],
        nonce_error: [ [ 'message' => 'Security check failed. Please refresh the page and try again.', 'code'    => 'invalid_nonce', ], 403 ]
    );
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
