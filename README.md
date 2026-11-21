# hws-jewel-trak-importer
Hexa Web Systems' Wordpress plugin to pull content from JewelTrak's inventory system. 

## Features

- Updates target the JewelTrak plugin folder. The FTP status panel uses the site’s encrypted Hexa WP Core credentials.

- The settings dashboard names the importer field snippet JewelTrak Theme Options Fields. The original snippet ID and callback remain compatible with saved configurations.

- Hexa WP Core 3.64.0 registers and edits the existing JewelTrak importer settings, including the Theme Options form. Existing field keys and import settings stay in use.

## 4.2.2: public JewelTrak callbacks

- `import_products_csv` and `delete_products_csv` are registered for logged-out calls again (`wp_ajax_nopriv_`), because JewelTrak triggers them with a plain GET after each FTP upload. They take no request input: they only read `add_products.csv` / `delete_products.csv` from the site's private `products/` FTP folder.
- The generic function dispatcher, snippet toggle, and wp-config writer stay administrator- and nonce-only.

## Security release 4.2.1

- Removed anonymous registration for the generic function, product import, and product deletion AJAX actions.
- Added administrator capability and nonce checks to every mutating AJAX handler.
- Restricted generic function execution to the documented comment and user-registration operations; configuration writers and arbitrary callbacks are no longer dispatchable.
