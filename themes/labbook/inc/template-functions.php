<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Labbook
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'labbook_get_option' ) ) :
	/**
	 * Get option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function labbook_get_option( $key, $default = '' ) {
		global $labbook_default_options;

		if ( empty( $key ) ) {
			return;
		}

		$default = ( array_key_exists( $key, $labbook_default_options ) ) ? $labbook_default_options[ $key ] : '';

		$theme_options = get_theme_mod( 'labbook_options', $labbook_default_options );
		$theme_options = array_merge( $labbook_default_options, $theme_options );

		$value = '';

		if ( array_key_exists( $key, $theme_options ) ) {
			$value = $theme_options[ $key ];
		}

		return $value;
	}
endif;

if ( ! function_exists( 'labbook_get_theme_option_defaults' ) ) :
	/**
	 * Get default theme options.
	 *
	 * @return array
	 */
	function labbook_get_theme_option_defaults() {
		return array(
			'content_layout'               		=> 'excerpt',
			'search_placeholder'           		=> esc_html__( 'Search...', 'labbook' ),
			'excerpt_length'               		=> 55, // WordPress default
			'copyright_text'               		=> '',
			'show_page_breadcrumbs'				=> true,
			'show_page_table_of_contents'		=> true,
			'table_of_contents_max_depth'		=> 4,
			'show_crossreferences'				=> true,
			'show_edit_summaries'				=> true,
			'edit_summaries_per_page'			=> 5,
			'show_powered_by'              		=> true,
			'show_privacy_policy'				=> true
		);
	}
endif;

if ( ! function_exists( 'labbook_get_options' ) ) :
	/**
	 * Get theme options.
	 *
	 * @since 1.8
	 */
	function labbook_get_options() {
		return get_theme_mod( 'labbook_options' );
	}
endif;

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function labbook_body_classes( $classes ) {
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
add_filter( 'body_class', 'labbook_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function labbook_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'labbook_pingback_header' );

if ( ! function_exists( 'labbook_custom_excerpt_length' ) ) :
	/**
	 * Implement excerpt length.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length The number of words.
	 * @return int Excerpt length.
	 */
	function labbook_custom_excerpt_length( $length ) {
		$excerpt_length = labbook_get_option( 'excerpt_length' );

		if ( empty( $excerpt_length ) ) {
			$excerpt_length = $length;
		}

		return $excerpt_length;
	}
endif;

add_filter( 'excerpt_length', 'labbook_custom_excerpt_length', 999 );

if ( ! function_exists( 'labbook_get_page_breadcrumbs' ) ) :
	/**
	 * Gets page breadcrumbs
	 */
	function labbook_get_page_breadcrumbs( $page = null ) {
		$page = get_post( $page );

		$ancestors = array();

		if ( $page->post_parent ) {
			// page is a child
			// get ancestors in reverse order
			$ancestors = array_reverse( get_post_ancestors( $page->ID ) );
		}

		// URL list with home
		$breadcrumbs = array(
			array(
				'title'	=>	__( 'Home', 'labbook' ),
				'url'	=>	get_home_url()
			)
		);

		// add ancestor titles and URLs
		foreach ( $ancestors as $ancestor ) {
			$breadcrumbs[] = array(
				'title'	=>	get_the_title( $ancestor ),
				'url'	=>	get_permalink( $ancestor )
			);
		}

		// add current page to end
		$breadcrumbs[] = array(
			'title'	=>	get_the_title( $page ),
			'url'	=>	''
		);

		return $breadcrumbs;
	}
endif;

if ( ! function_exists( 'labbook_add_revision_pagination_query_var' ) ) :
	function labbook_add_revision_pagination_query_var() {
		global $wp;

		// allow URL query variable for paginating revision lists on posts and pages
		$wp->add_query_var( 'revision_page' );
	}
endif;
add_action( 'init', 'labbook_add_revision_pagination_query_var' );

if ( ! function_exists( 'labbook_get_revisions' ) ) :
	/**
	 * Get list of revisions for the current or specified post
	 */
	function labbook_get_revisions( $post = null, $page = 1, $per_page = -1 ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return false;
		} elseif ( ! get_option( 'ssl_alp_enable_edit_summaries' ) ) {
			// tracking of edit summaries is disabled
			return false;
		}

		// get current post
		$post = get_post( $post );

		if  ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// post type not supported
			return;
		}

		// get revisions
		$revisions = wp_get_post_revisions(
			$post,
			array(
				'orderby'			=>	'date',
				'order'				=>	'DESC',
				'paged'				=>	$page,
				'posts_per_page'	=>	$per_page
			)
		);

		return $revisions;
	}
endif;

if ( ! function_exists( 'labbook_get_post_revision_count' ) ) :
	/**
	 * Get number of revisions to the specified post, including any
	 * autogenerated revisions.
	 */
	function labbook_get_post_revision_count( $post ) {
		global $ssl_alp;

		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		}

		// get number of revisions
		return $ssl_alp->revisions->get_post_edit_count( $post, false );
	}
endif;

if ( ! function_exists( 'labbook_get_post_edit_count' ) ) :
	/**
	 * Get number of edits made to the specified post. This counts the revisions
	 * present for the specified post, but ignores the revision created
	 * when the post was first made, if present.
	 */
	function labbook_get_post_edit_count( $post ) {
		global $ssl_alp;

		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		}

		// get number of revisions
		return $ssl_alp->revisions->get_post_edit_count( $post, true );
	}
endif;

if ( ! function_exists( 'labbook_revision_was_autogenerated_on_publication' ) ) :
	/**
	 * Get number of edits made to the specified post. This counts the revisions
	 * present for the specified post, but ignores the revision created
	 * when the post was first made, if present.
	 */
	function labbook_revision_was_autogenerated_on_publication( $revision ) {
		global $ssl_alp;

		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		}

		// get number of revisions
		return $ssl_alp->revisions->revision_was_autogenerated_on_publication( $revision );
	}
endif;
