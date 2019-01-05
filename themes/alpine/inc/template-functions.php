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

if ( ! function_exists( 'alpine_get_option' ) ) :
	/**
	 * Get option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function alpine_get_option( $key, $default = '' ) {
		global $alpine_default_options;

		if ( empty( $key ) ) {
			return;
		}

		$default = ( array_key_exists( $key, $alpine_default_options ) ) ? $alpine_default_options[ $key ] : '';

		$theme_options = get_theme_mod( 'alpine_options', $alpine_default_options );
		$theme_options = array_merge( $alpine_default_options, $theme_options );

		$value = '';

		if ( array_key_exists( $key, $theme_options ) ) {
			$value = $theme_options[ $key ];
		}

		return $value;
	}
endif;

if ( ! function_exists( 'alpine_get_theme_option_defaults' ) ) :
	/**
	 * Get default theme options.
	 *
	 * @return array
	 */
	function alpine_get_theme_option_defaults() {
		return array(
			'content_layout'               		=> 'excerpt',
			'search_placeholder'           		=> esc_html__( 'Search...', 'alpine' ),
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

if ( ! function_exists( 'alpine_get_options' ) ) :
	/**
	 * Get theme options.
	 *
	 * @since 1.8
	 */
	function alpine_get_options() {
		return get_theme_mod( 'alpine_options' );
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

if ( ! function_exists( 'alpine_custom_excerpt_length' ) ) :
	/**
	 * Implement excerpt length.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length The number of words.
	 * @return int Excerpt length.
	 */
	function alpine_custom_excerpt_length( $length ) {
		$excerpt_length = alpine_get_option( 'excerpt_length' );

		if ( empty( $excerpt_length ) ) {
			$excerpt_length = $length;
		}

		return $excerpt_length;
	}
endif;

add_filter( 'excerpt_length', 'alpine_custom_excerpt_length', 999 );

if ( ! function_exists( 'alpine_get_authors' ) ) :
	/**
	 * Gets formatted author HTML
	 */
	function alpine_get_authors( $post = null, $icon = true, $url = true, $delimiter_between = null, $delimiter_between_last = null ) {
		global $ssl_alp;

		$post = get_post( $post );

		if ( is_plugin_active( 'ssl-alp/alp.php' ) && get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			$authors = $ssl_alp->coauthors->get_coauthors( $post );
		} else {
			// fall back to the_author if plugin is disabled
			$authors = array();

			// get single author object
			$author = get_user_by( 'id', $post->post_author );

			// if there is no author, $author == false
			if ( $author ) {
				$authors[] = $author;
			}
		}

		$author_html = array();

		foreach ( $authors as $author ) {
			$author = alpine_format_author( $author, $url );

			if ( ! is_null( $author ) ) {
				$author_html[] = $author;
			}
		}

		if ( ! count( $author_html ) ) {
			// no authors
			$author_list_html = "";
		} else {
			if ( count( $author_html ) > 1 ) {
				// multiple authors
				$icon_class = 'fa fa-users';

				// get delimiters
				if ( is_null( $delimiter_between ) ) {
					$delimiter_between = _x( ', ', 'delimiter between coauthors except last', 'alpine' );
				}
				if ( is_null( $delimiter_between_last ) ) {
					$delimiter_between_last = _x( ' and ', 'delimiter between last two coauthors', 'alpine' );
				}

				// pop last author off
				$last_author = array_pop( $author_html );

				// implode author list
				$author_list_html = implode( __( ', ', 'alpine' ), $author_html ) . $delimiter_between_last . $last_author;
			} else {
				// single author
				$icon_class = 'fa fa-user';

				$author_list_html = $author_html[0];
			}

			if ( $icon ) {
				$icon = sprintf( '<i class="%1$s" aria-hidden="true"></i>', $icon_class );
			} else {
				$icon = '';
			}

			// add icon and author span
			$author_list_html = sprintf(
				'<span class="authors">%1$s%2$s</span>',
				$icon,
				$author_list_html
			);
		}

		return $author_list_html;
	}
endif;

if ( ! function_exists( 'alpine_get_page_breadcrumbs' ) ) :
	/**
	 * Gets page breadcrumbs
	 */
	function alpine_get_page_breadcrumbs( $page = null ) {
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
				'title'	=>	__( 'Home', 'alpine' ),
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

		return $breadcrumbs;
	}
endif;

if ( ! function_exists( 'alpine_get_revisions' ) ) :
	/**
	 * Get list of revisions for the current or specified post
	 */
	function alpine_get_revisions( $post = null, $page = 1, $per_page = -1 ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return false;
		} elseif ( ! get_option( 'ssl_alp_enable_post_edit_summaries' ) ) {
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

if ( ! function_exists( 'alpine_get_revision_count' ) ) :
	function alpine_get_revision_count( $post = null ) {
		// get current post
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			// post doesn't exist
			return;
		}

		// get revisions
		$revisions = wp_get_post_revisions(
			$post,
			array(
				'orderby'	=>	'date',
				'order'		=>	'DESC'
			)
		);

		$count = count( $revisions );

		if ( $count <= 0 ) {
			// no posts found
			return 0;
		} else {
			// subtract 1 to exclude the original post
			return count( $revisions ) - 1;
		}
	}
endif;
