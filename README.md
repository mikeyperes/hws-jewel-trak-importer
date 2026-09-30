# hws-jewel-trak-importer
Hexa Web Systems' Wordpress plugin to pull content from JewelTrak's inventory system. 

## Security release 4.2.1

- Removed anonymous registration for the generic function, product import, and product deletion AJAX actions.
- Added administrator capability and nonce checks to every mutating AJAX handler.
- Restricted generic function execution to the documented comment and user-registration operations; configuration writers and arbitrary callbacks are no longer dispatchable.
