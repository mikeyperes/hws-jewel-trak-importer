<?php

declare(strict_types=1);

namespace {
    define( 'ABSPATH', __DIR__ . '/' );

    final class AjaxTestResponse extends \RuntimeException {
        public function __construct( public bool $success, public mixed $data, public int $status ) {
            parent::__construct( 'AJAX response' );
        }
    }

    $GLOBALS['ajax_test'] = [
        'allowed' => false,
        'nonce'   => false,
        'hooks'   => [],
        'calls'   => 0,
    ];

    function add_action( string $hook, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        $GLOBALS['ajax_test']['hooks'][] = $hook;
        return true;
    }

    function current_user_can( string $capability ): bool {
        return 'manage_options' === $capability && $GLOBALS['ajax_test']['allowed'];
    }

    function check_ajax_referer( string $action, string $field, bool $die = true ): int|false {
        return $GLOBALS['ajax_test']['nonce'] ? 1 : false;
    }

    function wp_create_nonce( string $action ): string {
        return 'fixture-nonce';
    }

    function wp_send_json_error( mixed $data = null, int $status = 200 ): never {
        throw new AjaxTestResponse( false, $data, $status );
    }

    function wp_send_json_success( mixed $data = null, int $status = 200 ): never {
        throw new AjaxTestResponse( true, $data, $status );
    }

    function wp_die(): never {
        throw new AjaxTestResponse( true, null, 200 );
    }

    function sanitize_text_field( mixed $value ): string {
        return trim( (string) $value );
    }

    function wp_unslash( mixed $value ): mixed {
        return $value;
    }

    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
    }

    function add_query_arg( array $args, string $url ): string {
        return $url . '?' . http_build_query( $args );
    }

    function wp_json_encode( mixed $value, int $flags = 0 ): string|false {
        return json_encode( $value, $flags );
    }
}

namespace hws_jewel_trak_importer {
    function write_log( mixed ...$args ): void {}

    function enable_user_registration( mixed $state ): string {
        $GLOBALS['ajax_test']['calls']++;
        return (string) $state;
    }
}

namespace {
    $root = dirname( __DIR__ );
    require $root . '/ajax-security.php';
    require $root . '/settings-event-handling.php';
    require $root . '/snippet-run-product-import.php';
    require $root . '/snippet-run-product-delete.php';

    $fail = static function ( string $message ): void {
        fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
        exit( 1 );
    };

    \hws_jewel_trak_importer\enable_product_importer();
    \hws_jewel_trak_importer\enable_product_importer_process_deletes();

    $hooks = $GLOBALS['ajax_test']['hooks'];
    in_array( 'wp_ajax_hws_jewel_trak_importer_execute_function', $hooks, true ) || $fail( 'The authenticated generic action was not registered.' );
    // JewelTrak triggers import and delete logged out after each FTP upload.
    foreach ( [
        'wp_ajax_import_products_csv',
        'wp_ajax_nopriv_import_products_csv',
        'wp_ajax_delete_products_csv',
        'wp_ajax_nopriv_delete_products_csv',
    ] as $callback_hook ) {
        in_array( $callback_hook, $hooks, true ) || $fail( 'A JewelTrak callback action was not registered: ' . $callback_hook );
    }

    ! in_array( 'wp_ajax_nopriv_hws_jewel_trak_importer_execute_function', $hooks, true ) || $fail( 'The generic dispatcher is registered for anonymous calls.' );

    $invoke_dispatch = static function ( bool $allowed, bool $nonce, string $method ) use ( $fail ): AjaxTestResponse {
        $GLOBALS['ajax_test']['allowed'] = $allowed;
        $GLOBALS['ajax_test']['nonce']   = $nonce;
        $_POST = [
            'method' => $method,
            'state'  => 'enabled',
            'nonce'  => 'fixture-nonce',
        ];

        try {
            \hws_jewel_trak_importer\handle_execute_function_ajax();
        } catch ( AjaxTestResponse $response ) {
            return $response;
        }

        $fail( 'The AJAX dispatcher returned without a JSON response.' );
    };

    $response = $invoke_dispatch( false, true, 'enable_user_registration' );
    ( ! $response->success && 403 === $response->status && 0 === $GLOBALS['ajax_test']['calls'] ) || $fail( 'A user without manage_options reached the dispatcher.' );

    $response = $invoke_dispatch( true, false, 'enable_user_registration' );
    ( ! $response->success && 403 === $response->status && 0 === $GLOBALS['ajax_test']['calls'] ) || $fail( 'A request without a valid nonce reached the dispatcher.' );

    $response = $invoke_dispatch( true, true, 'toggle_php_ini_value' );
    ( ! $response->success && 400 === $response->status && 0 === $GLOBALS['ajax_test']['calls'] ) || $fail( 'A non-allowlisted function reached the dispatcher.' );

    $response = $invoke_dispatch( true, true, 'enable_user_registration' );
    ( $response->success && 1 === $GLOBALS['ajax_test']['calls'] ) || $fail( 'An authorized allowlisted operation did not run.' );

    // A logged-out caller with no nonce reaches the CSV step instead of a 403.
    foreach ( [
        'hws_jewel_trak_importer\\import_products_from_csv',
        'hws_jewel_trak_importer\\delete_products_ajax',
    ] as $handler ) {
        $GLOBALS['ajax_test']['allowed'] = false;
        $GLOBALS['ajax_test']['nonce']   = false;

        ob_start();
        try {
            $handler();
            ob_end_clean();
            $fail( 'A JewelTrak callback handler returned without terminating.' );
        } catch ( AjaxTestResponse $response ) {
            $body = (string) ob_get_clean();
            ( 403 !== $response->status && '' !== $body ) || $fail( 'A JewelTrak callback handler rejected a logged-out call: ' . $handler );
        }
    }

    echo "PASS: JewelTrak import/delete callbacks accept logged-out calls; the generic dispatcher still requires manage_options, a nonce, and an allowlisted operation.\n";
}
