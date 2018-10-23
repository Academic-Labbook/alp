<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Get our wp_nav_menu() fallback, wp_page_menu(), to show a home link.
 *
 * @param array $args Configuration arguments.
 * @return array
 */
function ssl_alp_page_menu_args( $args ) {
	$args['show_home'] = true;
	
	return $args;
}

add_filter( 'wp_page_menu_args', 'ssl_alp_page_menu_args' );

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function ssl_alp_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	return $classes;
}

add_filter( 'body_class', 'ssl_alp_body_classes' );
