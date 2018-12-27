<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Alpine
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'ssl_alpine_get_option' ) ) :
	/**
	 * Get option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function ssl_alpine_get_option( $key, $default = '' ) {
		global $ssl_alpine_default_options;

		if ( empty( $key ) ) {
			return;
		}

		$default = ( array_key_exists( $key, $ssl_alpine_default_options ) ) ? $ssl_alpine_default_options[ $key ] : '';

		$theme_options = get_theme_mod( 'ssl_alpine_options', $ssl_alpine_default_options );
		$theme_options = array_merge( $ssl_alpine_default_options, $theme_options );

		$value = '';

		if ( array_key_exists( $key, $theme_options ) ) {
			$value = $theme_options[ $key ];
		}

		return $value;
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_theme_option_defaults' ) ) :
	/**
	 * Get default theme options.
	 *
	 * @return array
	 */
	function ssl_alpine_get_theme_option_defaults() {
		return array(
			'content_layout'               => 'excerpt',
			'search_placeholder'           => esc_html__( 'Search...', 'ssl-alpine' ),
			'excerpt_length'               => 55, // WordPress default
			'copyright_text'               => '',
			'powered_by'                   => true
		);
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_options' ) ) :
	/**
	 * Get theme options.
	 *
	 * @since 1.8
	 */
	function ssl_alpine_get_options() {
		return get_theme_mod( 'ssl_alpine_options' );
	}
endif;

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function alpine_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'alpine_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function alpine_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'alpine_pingback_header' );

if ( ! function_exists( 'ssl_alpine_custom_excerpt_length' ) ) :
	/**
	 * Implement excerpt length.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length The number of words.
	 * @return int Excerpt length.
	 */
	function ssl_alpine_custom_excerpt_length( $length ) {
		$excerpt_length = ssl_alpine_get_option( 'excerpt_length' );

		if ( empty( $excerpt_length ) ) {
			$excerpt_length = $length;
		}

		return $excerpt_length;
	}
endif;

add_filter( 'excerpt_length', 'ssl_alpine_custom_excerpt_length', 999 );
