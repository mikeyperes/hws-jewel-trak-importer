<?php
/** Import rules: exact SKU matches, configurable price column, SubClass sub-categories, attribute creation, Save as Meta. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['t'] = [ 'options' => [], 'terms' => [], 'next_term' => 100, 'set_terms' => [], 'meta' => [], 'deleted' => [], 'attributes_created' => [], 'taxonomies' => [ 'product_cat' => true ], 'products' => [] ];

class WP_Error { public function __construct( public string $code = '', public string $message = '' ) {} public function get_error_message(): string { return $this->message; } }
class WC_Product_Simple {
	public array $data = [];
	public function __construct( public int $id = 0 ) {}
	public function __call( string $name, array $args ) { if ( str_starts_with( $name, 'set_' ) ) { $this->data[ substr( $name, 4 ) ] = $args[0]; } return null; }
	public function get_sku(): string { return (string) ( $this->data['sku'] ?? '' ); }
	public function save(): int { $this->id = $this->id ?: 500; $GLOBALS['t']['products'][ $this->id ] = $this; return $this->id; }
}
function add_action( ...$a ) { return true; }
function add_filter( ...$a ) { return true; }
function get_field( $selector, $context = false, $format = true ) { return $GLOBALS['t']['options'][ $selector ] ?? null; }
function get_option( $name, $default = false ) { return $GLOBALS['t']['options'][ $name ] ?? $default; }
function current_time( $type ) { return '2026-09-27 00:00:00'; }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wc_get_products( array $args ) { return [ 10, 11 ]; }
function wc_get_product( $id ) { $p = new WC_Product_Simple( (int) $id ); $p->set_sku( 10 === (int) $id ? 'RING-1' : 'RING-10' ); return $p; }
function wc_get_product_id_by_sku( $sku ) { return 0; }
function wp_delete_post( $id, $force ) { $GLOBALS['t']['deleted'][] = $id; }
function get_term_by( $field, $value, $taxonomy ) { foreach ( $GLOBALS['t']['terms'] as $id => $term ) { if ( $term['taxonomy'] === $taxonomy && ( 'name' === $field ? $term['name'] === $value : $id === (int) $value ) ) { return (object) [ 'term_id' => $id, 'parent' => $term['parent'] ]; } } return false; }
function get_terms( array $args ) { $out = []; foreach ( $GLOBALS['t']['terms'] as $id => $term ) { if ( $term['taxonomy'] === $args['taxonomy'] && $term['name'] === $args['name'] && $term['parent'] === $args['parent'] ) { $out[] = (object) [ 'term_id' => $id ]; } } return $out; }
function wp_insert_term( $name, $taxonomy, $args = [] ) { $id = $GLOBALS['t']['next_term']++; $GLOBALS['t']['terms'][ $id ] = [ 'name' => $name, 'taxonomy' => $taxonomy, 'parent' => (int) ( $args['parent'] ?? 0 ) ]; return [ 'term_id' => $id ]; }
function wp_set_object_terms( $id, $terms, $taxonomy ) { $GLOBALS['t']['set_terms'][ $taxonomy ] = $terms; return $terms; }
function taxonomy_exists( $taxonomy ) { return ! empty( $GLOBALS['t']['taxonomies'][ $taxonomy ] ); }
function wc_create_attribute( array $args ) { $GLOBALS['t']['attributes_created'][] = $args; return 1; }
function delete_transient( $key ) { return true; }
function register_taxonomy( $taxonomy, $types, $args ) { $GLOBALS['t']['taxonomies'][ $taxonomy ] = true; }
function wc_attribute_taxonomy_name( $slug ) { return 'pa_' . $slug; }
function sanitize_title( $value ) { return strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', (string) $value ), '-' ) ); }
function update_post_meta( $id, $key, $value ) { $GLOBALS['t']['meta'][ $key ] = $value; return true; }

require dirname( __DIR__ ) . '/lib/hexa-wordpress-plugin-core/tests/support/fields.php';
require dirname( __DIR__ ) . '/fallback-functions.php';
require dirname( __DIR__ ) . '/snippet-run-product-import.php';

use function hws_jewel_trak_importer\handle_product_import;
use function hws_jewel_trak_importer\handle_product_attributes;

$fail = static function ( string $message ): void { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); };
$row = [ 'Sku' => 'RING-1', 'Title' => 'Ring', 'Description' => 'd', 'RetailPrice' => '900', 'WholesalePrice' => '450', 'Quantity' => 1, 'Status' => 'instock', 'Category' => 'Rings', 'SubClass' => 'Bands|Solitaire', 'MetalType' => 'Gold|Platinum', 'Carat Weight' => '1.2' ];

handle_product_import( $row );
[] === $GLOBALS['t']['deleted'] || $fail( 'A partial SKU match (RING-10 for RING-1) must not be treated as a duplicate.' );
'900' === ( $GLOBALS['t']['products'][10]->data['regular_price'] ?? null ) || $fail( 'By default the regular price comes from RetailPrice and the exact match is updated.' );

$GLOBALS['t']['options']['regular_price_column'] = 'WholesalePrice';
handle_product_import( $row );
'450' === $GLOBALS['t']['products'][10]->data['regular_price'] || $fail( 'The Regular price column setting selects WholesalePrice.' );

$rings = get_term_by( 'name', 'Rings', 'product_cat' )->term_id;
$subs = array_values( array_filter( array_keys( $GLOBALS['t']['terms'] ), static fn( int $id ): bool => $GLOBALS['t']['terms'][ $id ]['parent'] === $rings ) );
2 === count( $subs ) || $fail( 'SubClass values become sub-categories of the first listed category.' );
[] === array_diff( $subs, $GLOBALS['t']['set_terms']['product_cat'] ) || $fail( 'The product is assigned to its SubClass sub-categories.' );
handle_product_import( $row );
2 === count( array_filter( $GLOBALS['t']['terms'], static fn( array $t ): bool => $t['parent'] === $rings ) ) || $fail( 'Existing sub-categories are reused, not duplicated.' );

$GLOBALS['t']['options']['product_custom_fields'] = [ [ 'display_header' => 'Carat Weight', 'csv_header' => 'Carat Weight', 'type' => 'text', 'visible' => 1, 'save_as_meta' => 1 ] ];
handle_product_attributes( 10, $row );
$created = $GLOBALS['t']['attributes_created'][0] ?? [];
'metaltype' === ( $created['slug'] ?? '' ) && 'Metal Type' === ( $created['name'] ?? '' ) || $fail( 'A missing global attribute is created with wc_create_attribute( slug, name ).' );
2 === count( $GLOBALS['t']['set_terms']['pa_metaltype'] ?? [] ) || $fail( 'Attribute values are assigned by term ID, creating missing terms.' );
'1.2' === ( $GLOBALS['t']['meta']['_carat_weight'] ?? null ) || $fail( 'Save as Meta stores the value as _<display_header> product meta.' );
isset( $GLOBALS['t']['meta']['_product_attributes']['Carat Weight'], $GLOBALS['t']['meta']['_product_attributes']['pa_metaltype'] ) || $fail( 'Static and dynamic attributes are saved to _product_attributes.' );

echo "PASS: JewelTrak import rules (exact SKU, price column, SubClass, attributes, Save as Meta).\n";
