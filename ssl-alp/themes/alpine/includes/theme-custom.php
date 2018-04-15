<?php
/**
 * Alpine custom functions.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'ssl_alp_custom_content_classes' ) ) :
	/**
	 * Modify content classes.
	 */
	function ssl_alp_custom_content_classes( $input ) {
		// not using special page template
		// get theme setting for layout instead
		$site_layout = ssl_alp_get_option( 'site_layout' );

		if ( 'content-sidebar' === $site_layout ) {
			$input[] = 'col-sm-2-left';
		} else if ( 'sidebar-content' === $site_layout ) {
			$input[] = 'col-sm-2-right';
		} else if ( 'full-width' === $site_layout ) {
			$input[] = 'col-sm-3';
		}

		// add extra small format as a fall-back for mobile
		$input[] = 'col-xs-3';

		return $input;
	}
endif;

add_filter( 'ssl_alp_filter_content_class', 'ssl_alp_custom_content_classes' );

if ( ! function_exists( 'ssl_alp_custom_sidebar_classes' ) ) :
	/**
	 * Modify sidebar classes.
	 */
	function ssl_alp_custom_sidebar_classes( $input ) {
		$site_layout = ssl_alp_get_option( 'site_layout' );

		if ( 'content-sidebar' === $site_layout ) {
			$input[] = 'col-sm-1-right';
		} else if ( 'sidebar-content' === $site_layout ) {
			$input[] = 'col-sm-1-left';
		} else if ( 'full-width' === $site_layout ) {
			$input[] = 'hidden';
		}

		return $input;
	}
endif;

add_filter( 'ssl_alp_filter_sidebar_class', 'ssl_alp_custom_sidebar_classes' );

if ( ! function_exists( 'ssl_alp_custom_excerpt_length' ) ) :
	/**
	 * Implement excerpt length.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length The number of words.
	 * @return int Excerpt length.
	 */
	function ssl_alp_custom_excerpt_length( $length ) {
		$excerpt_length = ssl_alp_get_option( 'excerpt_length' );

		if ( empty( $excerpt_length ) ) {
			$excerpt_length = $length;
		}

		return $excerpt_length;
	}
endif;

add_filter( 'excerpt_length', 'ssl_alp_custom_excerpt_length', 999 );

if ( ! function_exists( 'ssl_alp_excerpt_readmore' ) ) :
	/**
	 * Implement read more in excerpt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $more The string shown within the more link.
	 * @return string The excerpt.
	 */
	function ssl_alp_excerpt_readmore( $more ) {
		global $post;

		$read_more_text = ssl_alp_get_option( 'read_more_text' );

		if ( empty( $read_more_text ) ) {
			return $more;
		}

		$output = '... <a href="'. esc_url( get_permalink( $post->ID ) ) . '" class="readmore">' . esc_attr( $read_more_text )  . '<span class="screen-reader-text">' . esc_html( get_the_title() ) . '</span><span class="fa fa-angle-double-right" aria-hidden="true"></span></a>';

		return $output;
	}
endif;

add_filter( 'excerpt_more', 'ssl_alp_excerpt_readmore' );

if ( ! function_exists( 'ssl_alp_add_go_to_top' ) ) :
	/**
	 * Add go to top icon.
	 *
	 * @since 1.0.0
	 */
	function ssl_alp_add_go_to_top() {

		$go_to_top = ssl_alp_get_option( 'go_to_top' );

		if ( true !== $go_to_top ) {
			return;
		}

		echo '<a href="#" class="scrollup" id="btn-scrollup"><span class="fa-stack"> <i class="fa fa-square fa-stack-2x" aria-hidden="true"></i><i class="fa fa-angle-up fa-stack-1x fa-inverse" aria-hidden="true"></i></span><span class="screen-reader-text">' . __( 'Go to top', 'ssl-alp' ) . '</span></a>';
	}
endif;

add_action( 'wp_footer', 'ssl_alp_add_go_to_top' );