# WPML Menu — WordPress Plugin

Automatically translates nav menu item labels and URLs using WPML String Translation,
without duplicating menus per language.

Registers menu item titles and custom URLs as WPML strings on save,
then resolves translations at render time via `wp_nav_menu_objects`.
Handles custom links, taxonomy terms (with translated URLs), and post/page items.
Translated menus are cached per menu ID and language via `wp_cache`.
Also hides the WPML Menu Sync admin page to prevent accidental use.

## Requires
- WPML (sitepress-multilingual-cms)
- WPML String Translation
