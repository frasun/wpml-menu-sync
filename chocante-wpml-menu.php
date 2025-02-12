<?php
/**
 * Plugin Name: WPML Menu
 * Description: Automatically translate menu items.
 * Version: 1.0.0
 * Author: Chocante
 * Text Domain: chocante-wpml-menu
 * Domain Path: /languages
 * Requires Plugins: sitepress-multilingual-cms, wpml-string-translation
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Chocante_WPML_Menu
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'chocante_wpml_menu_modify_items' ) ) {
	/**
	 * Use translated menu item title
	 * Hook: https://developer.wordpress.org/reference/hooks/wp_nav_menu_objects/
	 *
	 * @param array    $sorted_menu_items Array of wp_nav_menu_items.
	 * @param stdClass $args An object containing wp_nav_menu() arguments.
	 */
	function chocante_wpml_menu_modify_items( $sorted_menu_items, $args ) {
		$default_language = apply_filters( 'wpml_default_language', null );
		$current_language = apply_filters( 'wpml_current_language', null );

		if ( $current_language === $default_language ) {
			return $sorted_menu_items;
		}

		$cached_menu_items = wp_cache_get( "chocante_menu_{$args->menu_id}-{$current_language}", 'chocante', false, $menu_found );

		if ( false === $menu_found ) {
			foreach ( $sorted_menu_items as &$item ) {
				if ( 'wpml_ls_menu_item' === $item->type ) {
					continue;
				}

				$item_title = apply_filters( 'wpml_translate_single_string', $item->title, "Menu - {$args->menu->name}", "Menu Item Label {$item->ID}" );

				switch ( $item->type ) {
					case 'custom':
						$item->url = apply_filters( 'wpml_translate_single_string', $item->url, "Menu - {$args->menu->name}", "Menu Item URL {$item->ID}" );
						break;
					case 'taxonomy':
						$wpml_object_id = apply_filters( 'wpml_object_id', $item->object_id, $item->object );
						$term           = get_term_by( 'id', $wpml_object_id, $item->object );

						if ( $term ) {
							$item->url = get_term_link( $term->term_id, $item->object );

							if ( $item_title === $item->title ) {
								$item_title = $term->name;
							}
						}
						break;
					default:
						$wpml_object_id = apply_filters( 'wpml_object_id', $item->object_id, $item->object );
						$item->url      = get_the_permalink( $wpml_object_id );
				}

				$item->title = $item_title;
			}

			$cached_menu_items = $sorted_menu_items;

			wp_cache_set( "chocante_menu_{$args->menu_id}-{$current_language}", 'chocante' );
		}

		return $cached_menu_items;
	}
}

add_filter( 'wp_nav_menu_objects', 'chocante_wpml_menu_modify_items', 10, 2 );

if ( ! function_exists( 'chocante_wpml_menu_register_strings' ) ) {
	/**
	 * Manage string translation when adding new or updating existing menu items
	 * Hook: https://developer.wordpress.org/reference/functions/wp_update_nav_menu_item/
	 *
	 * @param int   $menu_id The ID of the menu. If 0, makes the menu item a draft orphan.
	 * @param int   $menu_item_db_id The ID of the menu item. If 0, creates a new menu item.
	 * @param array $args The menu item’s data.
	 */
	function chocante_wpml_menu_register_strings( $menu_id, $menu_item_db_id, $args ) {
		if ( 0 === $menu_id || 0 === $menu_item_db_id ) {
			return;
		}

		if ( '' !== $args['menu-item-title'] ) {
			$menu = wp_get_nav_menu_object( $menu_id );

			if ( ! $menu ) {
				return;
			}

			$current_language = apply_filters( 'wpml_current_language', null );

			do_action( 'wpml_register_single_string', "Menu - {$menu->name}", "Menu Item Label {$menu_item_db_id}", $args['menu-item-title'], false, $current_language );
		}

		if ( 'custom' === $args['menu-item-type'] && '' !== $args['menu-item-url'] ) {
			if ( ! isset( $menu ) ) {
				$menu = wp_get_nav_menu_object( $menu_id );

				if ( ! $menu ) {
					return;
				}
			}

			if ( ! isset( $current_language ) ) {
				$current_language = apply_filters( 'wpml_current_language', null );
			}

			do_action( 'wpml_register_single_string', "Menu - {$menu->name}", "Menu Item URL {$menu_item_db_id}", $args['menu-item-url'], false, $current_language );
		}
	}
}

add_action( 'wp_update_nav_menu_item', 'chocante_wpml_menu_register_strings', 10, 3 );

/**
 * Hides WPML Sync Menu page from admin menu
 */
function chocante_wpml_menu_hide_menu_sync() {
	remove_submenu_page( 'tm/menu/main.php', 'sitepress-multilingual-cms/menu/menu-sync/menus-sync.php' );
}

add_action( 'admin_init', 'chocante_wpml_menu_hide_menu_sync' );
