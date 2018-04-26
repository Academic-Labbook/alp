<?php
/**
 * Custom theme functions.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'ssl_alp_get_option' ) ) :
	/**
	 * Get option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function ssl_alp_get_option( $key, $default = '' ) {
		global $ssl_alp_default_options;

		if ( empty( $key ) ) {
			return;
		}

		$default = ( array_key_exists( $key, $ssl_alp_default_options ) ) ? $ssl_alp_default_options[ $key ] : '';

		$theme_options = get_theme_mod( 'ssl_alp_options', $ssl_alp_default_options );
		$theme_options = array_merge( $ssl_alp_default_options, $theme_options );

		$value = '';

		if ( array_key_exists( $key, $theme_options ) ) {
			$value = $theme_options[ $key ];
		}

		return $value;
	}
endif;

if ( ! function_exists( 'ssl_alp_get_theme_option_defaults' ) ) :
	/**
	 * Get default theme options.
	 *
	 * @return array
	 */
	function ssl_alp_get_theme_option_defaults() {
		return array(
			'site_layout'                  => 'content-sidebar',
			'page_specific_sidebar'		   => true,
			'content_layout'               => 'excerpt',
			'read_more_text'               => esc_html__( 'Read more', 'ssl-alp' ),
			'search_placeholder'           => esc_html__( 'Search...', 'ssl-alp' ),
			'excerpt_length'               => 55, // WordPress default
			'pagination_type'              => 'default',
			'copyright_text'               => '',
			'powered_by'                   => true
		);
	}
endif;

if ( ! function_exists( 'ssl_alp_get_options' ) ) :
	/**
	 * Get theme options.
	 *
	 * @since 1.8
	 */
	function ssl_alp_get_options() {
		return get_theme_mod( 'ssl_alp_options' );
	}
endif;

/**
 * Render content class.
 *
 * @since 1.0.0
 *
 * @param  string|array $class Class to be added.
 */
function ssl_alp_content_class( $class = '' ) {
	$classes = array();

	if ( ! empty( $class ) ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}

		$classes = array_merge( $classes, $class );
	} else {
		// Ensure that we always coerce class to being an array.
		$class = array();
	}

	$classes = array_map( 'esc_attr', $classes );
	$classes = apply_filters( 'ssl_alp_filter_content_class', $classes, $class );

	echo 'class="' . join( ' ', $classes ) . '"'; // WPCS: XSS OK.
}

/**
 * Render sidebar class.
 *
 * @since 1.0.0
 *
 * @param  string|array $class Class to be added.
 */
function ssl_alp_sidebar_class( $class = '' ) {
	$classes = array();

	if ( ! empty( $class ) ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}

		$classes = array_merge( $classes, $class );
	} else {
		// Ensure that we always coerce class to being an array.
		$class = array();
	}

	$classes = array_map( 'esc_attr', $classes );
	$classes = apply_filters( 'ssl_alp_filter_sidebar_class', $classes, $class );

	echo 'class="' . join( ' ', $classes ) . '"'; // WPCS: XSS OK.
}

if ( ! function_exists( 'ssl_alp_primary_menu_fallback' ) ) :

	/**
	 * Primary menu callback.
	 *
	 * @since 1.0.0
	 */
	function ssl_alp_primary_menu_fallback() {
		echo '<ul>';
		echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . __( 'Home', 'ssl-alp' ) . '</a></li>';

		$args = array(
			'number'       => 8,
			'hierarchical' => false,
			'sort_column'  => 'menu_order, post_title',
		);

		$pages = get_pages( $args );

		if ( is_array( $pages ) && ! empty( $pages ) ) {
			foreach ( $pages as $page ) {
				echo '<li><a href="' . esc_url( get_permalink( $page->ID ) ) . '">' . esc_html( get_the_title( $page->ID ) ) . '</a></li>';
			}
		}

		echo '</ul>';
	}
endif;
