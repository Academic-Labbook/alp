<?php
/**
 * Custom template tags for this theme.
 *
 * @package ssl-alp
 */

if ( ! function_exists( 'ssl_alpine_get_the_post_date_html' ) ) :
	/**
	 * Format a post date
	 */
	function ssl_alpine_get_the_post_date_html( $post = null, $modified = false, $time = true, $icon = true, $url = true ) {
		$datetime_fmt = ssl_alpine_get_date_format( $time );

		// ISO 8601 formatted date
		$date_iso = $modified ? get_the_modified_date( 'c', $post ) : get_the_date( 'c', $post );

		// date formatted to WordPress preference
		$date_str = $modified ? get_the_modified_date( $datetime_fmt, $post ) : get_the_date( $datetime_fmt, $post );

		$time_str = sprintf(
			'<time class="%1$s" datetime="%2$s">%3$s</time>',
			$modified ? "updated" : "entry-date published",
			esc_attr( $date_iso ),
			esc_html( $date_str )
		);

		if ( $url ) {
			// year/month/day dates
			$year = $modified ? get_the_modified_time( 'Y', $post ) : get_post_time( 'Y', $post );
			$month = $modified ? get_the_modified_time( 'm', $post ) : get_post_time( 'm', $post );
			$day = $modified ? get_the_modified_time( 'j', $post ) : get_post_time( 'j', $post );

			$day_url = esc_url( get_day_link( $year, $month, $day ) );

			// wrap URL to date page
			$time_str = sprintf(
				'<a href="%1$s" rel="bookmark">%2$s</a>',
				$day_url,
				$time_str
			);
		}

		if ( $icon ) {
			// add icons
			$time_str = '<i class="fa fa-calendar" aria-hidden="true"></i>' . $time_str;
		}

		return $time_str;
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_date_format' ) ):
	/**
	 * Get date and optionall time format strings to pass to get_the_date or get_the_modified_date
	 */
	function ssl_alpine_get_date_format( $time = true ) {
		$datetime_fmt = get_option( 'date_format' );

		if ( $time ) {
			// combined date and time formats
			$datetime_fmt = sprintf(
				/* translators: 1: date, 2: time; note that "\a\t" escapes "at" in PHP's date() function */
				__( '%1$s \a\t %2$s', 'ssl-alp' ),
				$datetime_fmt,
				get_option( 'time_format' )
			);
		}

		return $datetime_fmt;
	}
endif;

if ( ! function_exists( 'ssl_alpine_the_post_meta' ) ) :
	/**
	 * Print HTML with meta information such as post date/time, author(s) and
	 * revisions
	 */
	function ssl_alpine_the_post_meta( $post = null ) {
		$post = get_post( $post );
		$posted_on = ssl_alpine_get_the_post_date_html( $post );

		// check post timestamps to see if modified
		if ( get_the_time( 'U', $post ) !== get_the_modified_time( 'U', $post ) ) {
			$modified_on = ssl_alpine_get_the_post_date_html( $post, true );
			/* translators: 1: post modification date */
			$posted_on .= sprintf( __( ' (last edited %1$s)', 'ssl-alp' ), $modified_on );
		}

		printf(
			'<div class="byline"><i class="fa fa-link"></i> %1$s&nbsp;&nbsp;%2$s</div>',
			$post->ID,
			ssl_alpine_get_the_authors( $post )
		);

		if ( is_plugin_active( 'ssl-alp/alp.php' ) ) {
			if ( get_option( 'ssl_alp_post_edit_summaries' ) ) {
				$revision_count = ssl_alpine_get_the_revision_count();

				if ( $revision_count > 0 ) {
					$revision_str = sprintf( _n( '%s revision', '%s revisions', $revision_count, 'ssl-alp' ), $revision_count );

					$posted_on .= sprintf(
						'&nbsp;&nbsp;<span class="revision-count"><i class="fa fa-pencil" aria-hidden="true"></i><a href="%1$s#post-revisions">%2$s</a></span>',
						esc_url( get_the_permalink( $post ) ),
						$revision_str
					);
				}
			}
		}

		printf(
			'<div class="posted-on">%1$s</div>',
			$posted_on
		);
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_the_authors' ) ) :
	/**
	 * Gets formatted author HTML
	 */
	function ssl_alpine_get_the_authors( $post = null, $icon = true, $url = true, $delimiter_between = null, $delimiter_between_last = null ) {
		$post = get_post( $post );

		if ( is_plugin_active( 'ssl-alp/alp.php' ) && get_option( 'ssl_alp_multiple_authors' ) ) {
			$authors = get_coauthors( $post->ID );
		} else {
			// fall back to the_author if plugin is disabled
			$authors = array( get_user_by( 'id', $post->post_author ) );
		}

		$author_html = array();

		foreach ( $authors as $author ) {
			$author_html[] = ssl_alpine_format_author( $author, $url, $icon );
		}

		if ( count( $author_html ) > 1 ) {
			// get delimiters
			if ( is_null( $delimiter_between ) ) {
				$delimiter_between = _x( ', ', 'delimiter between coauthors except last', 'ssl-alp' );
			}
			if ( is_null( $delimiter_between_last ) ) {
				$delimiter_between_last = _x( ' and ', 'delimiter between last two coauthors', 'ssl-alp' );
			}

			// pop last author off
			$last_author = array_pop( $author_html );

			// implode author list
			$author_html = implode( __( ', ', 'ssl-alp' ), $author_html ) . $delimiter_between_last . $last_author;
		} else {
			$author_html = $author_html[0];
		}

		return $author_html;
	}
endif;

if ( ! function_exists( 'ssl_alpine_format_author' ) ) :
	/**
	 * Gets formatted author name
	 */
	function ssl_alpine_format_author( $author, $icon = true, $url = true ) {
		$author_html = $author->display_name;

		if ( $url ) {
			$author_url = esc_url( get_author_posts_url( $author->ID ) );

			// wrap author in link to their posts
			$author_html = sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span>', $author_url, $author_html );
		}

		if ( $icon ) {
			// use fa-users when more than one user is defined
			$author_html = '<i class="fa fa-user" aria-hidden="true"></i> ' . $author_html;
		}

		return $author_html;
	}
endif;

if ( ! function_exists( 'ssl_alpine_the_revisions' ) ) :
	/**
	 * Prints formatted author HTML
	 */
	function ssl_alpine_the_revisions( $post = null ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_post_edit_summaries' ) ) {
			return;
		}

		$post = get_post( $post );

		if ( ! post_type_supports( $post->post_type, 'ssl-alp-edit-summaries' ) ) {
			// post type not supported
			return;
		}

		// get list of revisions to this post
		$revisions = ssl_alpine_get_revisions( $post );

		if ( is_null( $revisions ) || ! is_array( $revisions ) || count( $revisions ) == 0 ) {
			// no revisions to show
			return;
		}

		echo '<div id="post-revisions"><h3>Revisions</h3><ul>';

		foreach ( $revisions as $revision ) {
			echo '<li>' . ssl_alpine_get_revision_description( $revision ) . '</li>';
		}

		echo "</ul></div>";
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_revisions' ) ) :
	/**
	 * Get list of revisions for the current or specified post
	 */
	function ssl_alpine_get_revisions( $post = null ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_post_edit_summaries' ) ) {
			return;
		}

		// get current post
		$post = get_post( $post );

		if  ( ! post_type_supports( $post->post_type, 'ssl-alp-edit-summaries' ) ) {
			// post type not supported
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

		return $revisions;
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_revision_description' ) ) :
	/**
	 * Prints description for the specified revision
	 */
	function ssl_alpine_get_revision_description( $revision ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_post_edit_summaries' ) ) {
			return;
		}

		// get revision object if id is specified
		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// get revision's edit summary
		$revision_meta = get_post_meta( $revision->ID, 'edit_summary', true );

		// default message
		$message = " " . ssl_alpine_get_revision_abbreviation( $revision );

		// check that we have a revision summary array, and that it has set fields
		if ( ! empty( $revision_meta ) && is_array( $revision_meta ) && ( ! empty( $revision_meta["message"] ) || ( $revision_meta["reverted"] > 0 ) ) ) {
			if ( $revision_meta["reverted"] > 0 ) {
				// revision was a revert
				// /* translators: 1: revision ID/URL */
				$message .= sprintf(
					__( ': reverted to %1$s', 'ssl-alp' ),
					ssl_alpine_get_revision_abbreviation( $revision_meta["reverted"] )
				);

				// add summary
				if ( ! empty ( $revision_meta["message"] ) ) {
					$message .= sprintf(
						/* translators: 1: revision message */
						__(' (<em>"%1$s"</em>)', 'ssl-alp' ),
						$revision_meta["message"]
					);
				}
			} else {
				if ( ! empty ( $revision_meta["message"] ) ) {
					/* translators: 1: revision message */
					$message .= sprintf( __( ': <em>"%1$s"</em>', 'ssl-alp' ), esc_html( $revision_meta["message"] ) );
				}
			}
		}

		$revision_time = sprintf(
			/* translators: 1: revision date, 2: time ago */
			__( '<span title="%1$s">%2$s ago</span>', 'ssl-alp' ),
			get_the_modified_date( ssl_alpine_get_date_format( true ), $revision ),
			human_time_diff( strtotime( $revision->post_modified ), current_time( 'timestamp' ) )
		);

		$author_display_name = get_the_author_meta( 'display_name', $revision->post_author );

		$description = sprintf(
			'%1$s %2$s, %3$s, %4$s',
			get_avatar( $revision->post_author, 18, null, $author_display_name ),
			$author_display_name,
			$revision_time,
			$message
		);

		// check if this revision is the current one
		if ( get_the_time( 'U', $revision ) == get_the_modified_time( 'U', $revision->parent ) ) {
			$description .= __( ' <strong>(current)</strong>', 'ssl-alp' );
		}

		return $description;
	}
endif;

if ( ! function_exists( 'ssl_alpine_get_revision_abbreviation' ) ) :
	/**
	 * Gets abbreviated revision ID, with optional URL
	 */
	function ssl_alpine_get_revision_abbreviation( $revision, $url = true ) {
		// get revision object if id is specified
		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// revision post ID
		$revision_id = sprintf(
			_x('r%1$s', 'abbreviated revision ID text', 'ssl-alp' ),
			$revision->ID
		);

		error_log($revision->ID);

		// add URL to diff if user can view
		if ( $url ) {
			if ( current_user_can( 'edit_post', $revision->ID ) ) {
				$revision_id = sprintf(
					'<a href="%1$s">%2$s</a>',
					get_edit_post_link( $revision->ID ),
					$revision_id
				);
			}
		}

		return $revision_id;
	}
endif;

if ( ! function_exists( 'ssl_alpine_the_references' ) ) :
	/**
	 * Prints HTML with post references
	 */
	function ssl_alpine_the_references( $post = null ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// cross-references are disabled
			return;
		}

		$post = get_post( $post );

		if ( ! post_type_supports( $post->post_type, 'ssl-alp-crossreferences' ) ) {
			// post type not supported
			return;
		}

		$ref_to_terms = get_the_terms( $post, 'ssl_alp_crossreference' );
		$ref_from_posts = ssl_alpine_get_reference_from_posts( $post );

		if ( ! $ref_to_terms && ! $ref_from_posts ) {
			// no references
			return;
		}

		printf( '<div id="post-references"><h3>%1$s</h3>', __( 'Cross-references', 'ssl-alp' ));

		if ( $ref_to_terms ) {
			printf( '<h4>%1$s</h4><ul>', __( 'Links to', 'ssl-alp' ) );

			foreach ( $ref_to_terms as $term ) {
				// get post ID
				$post_id = get_term_meta( $term->term_id, 'reference-to-post-id', 'ssl_alp_crossreference' );

				// get the referenced post
				$referenced_post = get_post( $post_id );

				// print reference post information
				ssl_alpine_the_referenced_post_list_item( $referenced_post );
			}

			echo '</ul>';
		}

		if ( $ref_from_posts ) {
			printf( '<h4>%1$s</h4><ul>', __( 'Linked from', 'ssl-alp' ));

			foreach ( $ref_from_posts as $referenced_post ) {
				// get post
				$referenced_post = get_post( $referenced_post );

				// print reference post information
				ssl_alpine_the_referenced_post_list_item( $referenced_post );
			}

			echo '</ul>';
		}

		echo '</div>';
	}
endif;

if ( ! function_exists( 'ssl_alpine_the_referenced_post_list_item' ) ) {
	/**
	 * Prints HTML link to the specified reference post
	 */
	function ssl_alpine_the_referenced_post_list_item( $referenced_post = null, $url = true ) {
		global $ssl_alp;

		$referenced_post = get_post( $referenced_post );

		if ( is_null( $referenced_post ) ) {
			// post doesn't exist
			// TODO: remove relationship?
			return;
		}

		// post title
		$post_title = $referenced_post->post_title;

		if ( $url ) {
			// wrap URL
			$post_title = sprintf( '<a href="%1$s">%2$s</a>', get_permalink( $referenced_post ), $post_title );
		}

		// post date
		// only used if post type supports it
		if ( $ssl_alp->references->show_date( $referenced_post ) ) {
			$post_date = sprintf(
				' <span class="post-date">%1$s</span>',
				get_the_date( get_option( 'date_format' ), $referenced_post )
			);
		} else {
			$post_date = '';
		}

		printf( '<li>%1$s%2$s</li>', $post_title, $post_date );
	}
}

if ( ! function_exists( 'ssl_alpine_get_reference_from_posts' ) ) :
	/**
	 * Gets the "reference from" terms for the specified post. Optionally the
	 * date order can be changed.
	 */
	function ssl_alpine_get_reference_from_posts( $post = null, $date_order = 'DESC' ) {
		global $wpdb;

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		$date_order = ( strtoupper( $date_order ) == 'ASC' ) ? 'ASC' : 'DESC';

		// query for terms that reference this post
		$object_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT posts.ID
				FROM {$wpdb->termmeta} AS termmeta
				INNER JOIN {$wpdb->term_relationships} AS term_relationships
					ON termmeta.term_id = term_relationships.term_taxonomy_id
				INNER JOIN {$wpdb->posts} AS posts
					ON term_relationships.object_id = posts.ID
				INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy
					ON termmeta.term_id = term_taxonomy.term_id
				WHERE
					termmeta.meta_key = %s
					AND termmeta.meta_value = %d
					AND term_taxonomy.taxonomy = %s
				ORDER BY
					posts.post_date {$date_order}
				",
				'reference-to-post-id',
				$post->ID,
				'ssl_alp_crossreference'
			)
		);

		// get the term objects associated with the term IDs
		return array_map( 'get_post', $object_ids );
	}
endif;

/**
 * Returns true if a blog has more than 1 category.
 *
 * @return bool
 */
function ssl_alp_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'ssl_alp_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,
			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'ssl_alp_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so ssl_alp_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so ssl_alp_categorized_blog should return false.
		return false;
	}
}

/**
 * Flush out the transients used in ssl_alp_categorized_blog.
 */
function ssl_alpine_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'ssl_alp_categories' );
}

add_action( 'edit_category', 'ssl_alpine_category_transient_flusher' );
add_action( 'save_post',     'ssl_alpine_category_transient_flusher' );

if ( ! function_exists( 'ssl_alpine_get_the_revision_count' ) ) :
	function ssl_alpine_get_the_revision_count( $post = null ) {
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

		return count( $revisions );
	}
endif;

if ( ! function_exists( 'ssl_alp_paging_nav' ) ) :
	/**
	 * Display navigation to next/previous set of posts when applicable.
	 */
	function ssl_alp_paging_nav() {
		// Don't print empty markup if there's only one page.
		if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
			return;
		}

		$pagination_type = esc_attr( ssl_alp_get_option( 'pagination_type' ) );

		switch ( $pagination_type ) {
			case 'numeric':
				if ( function_exists( 'wp_pagenavi' ) ) {
					wp_pagenavi();
				} else {
					the_posts_pagination( array(
						'mid_size'           => 2,
						'prev_text'          => '<span class="meta-nav"><i class="fa fa-chevron-left" aria-hidden="true"></i></span> ' . __( 'Previous page', 'ssl-alp' ),
						'next_text'          => __( 'Next page', 'ssl-alp' ) . ' <span class="meta-nav"><i class="fa fa-chevron-right" aria-hidden="true"></i></span>',
						'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'ssl-alp' ) . ' </span>',
					) );
				}
				break;

			case 'default':
				the_posts_navigation( array(
					'prev_text' => '<span class="meta-nav"><i class="fa fa-chevron-left" aria-hidden="true"></i></span> ' . __( 'Older posts', 'ssl-alp' ),
					'next_text' => __( 'Newer posts', 'ssl-alp' ) . ' <span class="meta-nav"><i class="fa fa-chevron-right" aria-hidden="true"></i></span>',
					) );
				break;

			default:
				break;
		}
	}
endif;
