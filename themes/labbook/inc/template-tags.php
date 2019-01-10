<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Labbook
 */


if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'labbook_the_post_title' ) ) :
	/**
	 * Print the post title
	 */
	function labbook_the_post_title( $post = null, $url = true, $icon = true, $anchor = false ) {
		$post = get_post( $post );
		$title = get_the_title( $post );
		$permalink = esc_url( get_permalink( $post ) );

		$html = '';

		if ( $icon ) {
			// display icon, if present
			if ( 'status' === get_post_format( $post ) ) {
				$icon_class = 'fa fa-info-circle';
			} else {
				// don't show icon
				$icon_class = '';
			}

			if ( ! empty( $icon_class ) ) {
				$html .= sprintf( '<i class="%1$s"></i>', $icon_class );
			}
		}

		if ( $url ) {
			// wrap title in its permalink
			$html .= sprintf(
				'<a href="%1$s" rel="bookmark" >%2$s</a>',
				$permalink,
				$title
			);
		} else {
			// just display title
			$html .= $title;
		}

		if ( $anchor ) {
			// add hover anchor with permalink
			$html .= sprintf(
				'<a class="entry-link" href="%1$s"><i class="fa fa-link"></i></a>',
				$permalink
			);
		}

		// output header tag
		printf(
			'<h2 class="entry-title">%1$s</h2>',
			$html
		);
	}
endif;

if ( ! function_exists( 'labbook_get_post_date_html' ) ) :
	/**
	 * Format a post date
	 */
	function labbook_get_post_date_html( $post = null, $modified = false, $time = true, $icon = true, $url = true ) {
		$datetime_fmt = labbook_get_date_format( $time );

		// ISO 8601 formatted date
		$date_iso = $modified ? get_the_modified_date( 'c', $post ) : get_the_date( 'c', $post );

		// date formatted to WordPress preference
		$date_str = $modified ? get_the_modified_date( $datetime_fmt, $post ) : get_the_date( $datetime_fmt, $post );

		// how long ago
		$human_date = $modified ? labbook_get_human_date( $post->post_modified ) : labbook_get_human_date( $post->post_date );

		$time_str = sprintf(
			'<time class="%1$s" datetime="%2$s" title="%3$s">%4$s</time>',
			$modified ? "updated" : "entry-date published",
			esc_attr( $date_iso ),
			esc_html( $human_date ),
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
			if ( $modified ) {
				$title = __( 'Modification date', 'labbook' );
			} else {
				$title = __( 'Publication date', 'labbook' );
			}

			// add icons
			$time_str = sprintf(
				'<i class="fa fa-calendar" title="%1$s" aria-hidden="true"></i>%2$s',
				esc_html( $title ),
				$time_str
			);
		}

		return $time_str;
	}
endif;

if ( ! function_exists( 'labbook_get_date_format' ) ):
	/**
	 * Get date and optional time format strings to pass to get_the_date or get_the_modified_date
	 */
	function labbook_get_date_format( $time = true ) {
		$datetime_fmt = get_option( 'date_format' );

		if ( $time ) {
			// combined date and time formats
			$datetime_fmt = sprintf(
				/* translators: 1: date, 2: time; note that "\a\t" escapes "at" in PHP's date() function */
				__( '%1$s \a\t %2$s', 'labbook' ),
				$datetime_fmt,
				get_option( 'time_format' )
			);
		}

		return $datetime_fmt;
	}
endif;

if ( ! function_exists( 'labbook_get_human_date' ) ):
	/**
	 * Get human formatted date, e.g. "3 hours ago"
	 */
	function labbook_get_human_date( $date_str, $compare_timestamp = null ) {
		if ( is_null( $compare_timestamp ) ) {
			// use current time
			$compare_timestamp = current_time( 'timestamp' );
		}

		$timestamp = strtotime( $date_str );

		return sprintf(
			/* translators: 1: time ago */
			__( '%s ago', 'labbook' ),
			human_time_diff( $timestamp, $compare_timestamp )
		);
	}
endif;

if ( ! function_exists( 'labbook_the_post_meta' ) ) :
	/**
	 * Print HTML with meta information about post
	 */
	function labbook_the_post_meta( $post = null ) {
		$post = get_post( $post );

		$byline_pieces = array();

		// post id
		$byline_pieces[] = labbook_get_post_id_icon( $post );

		// authors
		$authors = labbook_get_authors( $post );

		if ( !empty( $authors ) ) {
			$byline_pieces[] = $authors;
		}

		// show revisions link on posts and pages only
		if ( labbook_get_option( 'show_edit_summaries' ) && labbook_get_post_edit_count( $post ) > 0 ) {
			$byline_pieces[] = labbook_get_revisions_link( $post );
		}

		if ( current_user_can( 'edit_post', $post ) ) {
			// add edit post link
			$byline_pieces[] = labbook_get_post_edit_link( $post );
		}

		printf(
			'<div class="byline">%1$s</div>',
			implode( '&nbsp;&nbsp;', $byline_pieces )
		);

		$posted_on = labbook_get_post_date_html( $post );

		// check post timestamps to see if modified
		if ( get_the_time( 'U', $post ) !== get_the_modified_time( 'U', $post ) ) {
			$modified_on = labbook_get_post_date_html( $post, true );
			/* translators: 1: post modification date */
			$posted_on .= sprintf( __( ' (last edited %1$s)', 'labbook' ), $modified_on );
		}

		printf(
			'<div class="posted-on">%1$s</div>',
			$posted_on
		);
	}
endif;

if ( ! function_exists( 'labbook_the_page_meta' ) ) :
	/**
	 * Print HTML with meta information about page
	 */
	function labbook_the_page_meta( $page = null ) {
		$page = get_post( $page );

		$byline_pieces = array();

		if ( labbook_get_option( 'show_edit_summaries' ) ) {
			$byline_pieces[] = labbook_get_revisions_link( $page );
		}

		if ( current_user_can( 'edit_page', $page ) ) {
			// add edit post link
			$byline_pieces[] = labbook_get_post_edit_link( $page );
		}

		printf(
			'<div class="byline">%1$s</div>',
			implode( '&nbsp;&nbsp;', $byline_pieces )
		);
	}
endif;

if ( ! function_exists( 'labbook_get_post_id_icon' ) ) :
	function labbook_get_post_id_icon( $post ) {
		$post = get_post( $post );

		return sprintf(
			'<i class="fa fa-link" title="%1$s"></i>%2$s',
			esc_html__( 'ID', 'labbook' ),
			$post->ID
		);
	}
endif;

if ( ! function_exists( 'labbook_get_post_edit_link' ) ) :
	function labbook_get_post_edit_link( $post ) {
		$post = get_post( $post );

		return sprintf(
			'<i class="fa fa-edit" aria-hidden="true"></i><a href="%1$s">%2$s</a>',
			get_edit_post_link( $post ),
			__( 'Edit', 'labbook' )
		);
	}
endif;

if ( ! function_exists( 'labbook_get_revisions_link' ) ) :
	function labbook_get_revisions_link( $post = null ) {
		global $ssl_alp;

		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// required functionality not available
			return;
		}

		$post = get_post( $post );

		if ( ! $ssl_alp->revisions->edit_summary_allowed( $post ) ) {
			return;
		}

		$edit_count = labbook_get_post_edit_count( $post );

		if ( is_null( $edit_count ) ) {
			// revisions not available
			return;
		}

		$edit_str = sprintf( _n( '%s revision', '%s revisions', $edit_count, 'labbook' ), $edit_count );

		return sprintf(
			'<i class="fa fa-pencil" title="%1$s" aria-hidden="true"></i><a href="%2$s#post-revisions">%3$s</a>',
			esc_html__( 'Number of edits made to the original post', 'labbook' ),
			esc_url( get_the_permalink( $post ) ),
			$edit_str
		);
	}
endif;

if ( ! function_exists( 'labbook_get_authors' ) ) :
	/**
	 * Gets formatted author HTML
	 */
	function labbook_get_authors( $post = null, $icon = true, $url = true, $delimiter_between = null, $delimiter_between_last = null ) {
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
			$author = labbook_format_author( $author, $url );

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
					$delimiter_between = _x( ', ', 'delimiter between coauthors except last', 'labbook' );
				}
				if ( is_null( $delimiter_between_last ) ) {
					$delimiter_between_last = _x( ' and ', 'delimiter between last two coauthors', 'labbook' );
				}

				// pop last author off
				$last_author = array_pop( $author_html );

				// implode author list
				$author_list_html = implode( __( ', ', 'labbook' ), $author_html ) . $delimiter_between_last . $last_author;
			} else {
				// single author
				$icon_class = 'fa fa-user';

				$author_list_html = $author_html[0];
			}

			if ( $icon ) {
				$icon = sprintf(
					'<i class="%1$s" title="%2$s" aria-hidden="true"></i>',
					$icon_class,
					esc_html__( 'Authors', 'labbook' )
				);
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

if ( ! function_exists( 'labbook_format_author' ) ) :
	/**
	 * Gets formatted author name
	 */
	function labbook_format_author( $author, $url = true ) {
		if ( is_null( $author ) ) {
			return;
		}

		$author_html = $author->display_name;

		if ( $url ) {
			$author_url = esc_url( get_author_posts_url( $author->ID ) );

			// wrap author in link to their posts
			$author_html = sprintf(
				'<span class="author vcard"><a href="%1$s">%2$s</a></span>',
				$author_url,
				$author_html
			);
		}

		return $author_html;
	}
endif;

if ( ! function_exists( 'labbook_the_footer' ) ) :
	/**
	 * Prints the footer for the specified post.
	 *
	 * Cannot specify a custom post id here, as `get_comments_number_text` can't
	 * handle it. It always uses the current post.
	 */
	function labbook_the_footer() {
		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( esc_html__( ', ', 'labbook' ) );

		if ( $categories_list ) {
			printf(
				'<span class="cat-links">%1$s%2$s</span>',
				'<i class="fa fa-folder-open" aria-hidden="true"></i>',
				$categories_list
			);
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', esc_html__( ', ', 'labbook' ) );

		if ( $tags_list ) {
			printf(
				'<span class="tag-links">%1$s%2$s</span>',
				'<i class="fa fa-tags" aria-hidden="true"></i>',
				$tags_list
			);
		}

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			// show comments link
			printf(
				'<span class="comments-link">%1$s<a href="%2$s">%3$s</a></span>',
				'<i class="fa fa-comment" aria-hidden="true"></i>',
				get_comments_link(),
				get_comments_number_text(esc_html__( 'Leave a comment', 'labbook' ))
			);
		}
	}
endif;

if ( ! function_exists( 'labbook_the_revisions' ) ) :
	/**
	 * Prints revisions for the specified post
	 */
	function labbook_the_revisions( $post = null ) {
		if ( ! labbook_get_option( 'show_edit_summaries' ) ) {
			// display is unavailable
			return;
		}

		$post = get_post( $post );

		if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// post type not supported
			return;
		}

		$current_page = ( get_query_var( 'revision_page' ) ) ? get_query_var( 'revision_page' ) : 1;

		// total revisions
		$count = labbook_get_post_revision_count( $post );

		if ( is_null( $count ) ) {
			// revisions not available
			return;
		}

		$per_page = labbook_get_option( 'edit_summaries_per_page' );
		$pages = ceil( $count / $per_page );

		// get list of revisions to this post
		$revisions = labbook_get_revisions( $post, $current_page, $per_page );

		if ( is_null( $revisions ) || ! is_array( $revisions ) || count( $revisions ) == 0 ) {
			// no revisions to show
			return;
		}

		echo '<div id="post-revisions">';

		printf(
			'<h3>%1$s</h3>',
			esc_html( 'History', 'labbook' )
		);

		echo "<ul>";

		foreach ( $revisions as $revision ) {
			echo '<li>' . labbook_get_revision_description( $revision ) . '</li>';
		}

		echo "</ul>";

		if ( $pages > 1 ) {
			echo paginate_links( array(
				'format'		=> '?revision_page=%#%',
				'current'  		=> $current_page,
				'total'    		=> $pages
	  		) );
		}

		echo "</div>";
	}
endif;

if ( ! function_exists( 'labbook_get_revision_description' ) ) :
	/**
	 * Prints description for the specified revision
	 */
	function labbook_get_revision_description( $revision ) {
		global $ssl_alp;

		// get revision object if id is specified
		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// get revision's edit summary
		$revision_edit_summary = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$revision_edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		// default message
		$message = " " . labbook_get_revision_abbreviation( $revision );

		if ( wp_is_post_autosave( $revision ) ) {
			// this is an autosave
			$message .= __( ' <strong>(autosave)</strong>', 'labbook' );
		} elseif ( labbook_revision_was_autogenerated_on_publication( $revision ) ) {
			// this revision was created when the post was published
			$message .= __( ' <strong>(published)</strong>', 'labbook' );
		} elseif ( !empty( $revision_edit_summary_revert_id ) ) {
			// revision was a revert
			// /* translators: 1: revision ID/URL */
			$message .= sprintf(
				__( ': reverted to %1$s', 'labbook' ),
				labbook_get_revision_abbreviation( $revision_edit_summary_revert_id )
			);

			// get original source revision
			$source_revision = $ssl_alp->revisions->get_source_revision( $revision );
			$source_edit_summary = get_post_meta( $source_revision->ID, 'ssl_alp_edit_summary', true );

			if ( !empty( $source_edit_summary ) ) {
				// add original edit summary
				$source_edit_summary = sprintf(
					__( '"%1$s"', 'labbook' ),
					$source_edit_summary
				);

				$message .= sprintf(
					' (<em>%1$s</em>)',
					esc_html( $source_edit_summary )
				);
			}
		} elseif ( !empty( $revision_edit_summary ) ) {
			/* translators: 1: revision message */
			$message .= sprintf(
				__( ': <em>"%1$s"</em>', 'labbook' ),
				esc_html( $revision_edit_summary )
			);
		}

		$revision_time = sprintf(
			'<span title="%1$s">%2$s</span>',
			get_the_modified_date( labbook_get_date_format( true ), $revision ),
			labbook_get_human_date( $revision->post_modified )
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
			$description .= __( ' <strong>(current)</strong>', 'labbook' );
		}

		return $description;
	}
endif;

if ( ! function_exists( 'labbook_get_revision_abbreviation' ) ) :
	/**
	 * Gets abbreviated revision ID, with optional URL
	 */
	function labbook_get_revision_abbreviation( $revision, $url = true ) {
		global $ssl_alp;

		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// revision post ID
		$abbr = sprintf(
			_x('r%1$s', 'abbreviated revision ID text', 'labbook' ),
			$revision->ID
		);

		// add URL to diff if user can view
		if ( $url ) {
			/**
			 * Note: interns are not shown the edit link below (it is empty) because they fail
			 * the edit_post permission check against the *revision* here. This is a subtle bug
			 * that would take a lot of effort to fix.
			 *
			 * Instead, interns simply aren't shown the revision link (but they still see the edit
			 * link).
			 */
			$edit_link = get_edit_post_link( $revision->ID );

			if ( !empty( $edit_link) && $ssl_alp->revisions->current_user_can_view_revision( $revision ) ) {
				$abbr = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $edit_link ),
					$abbr
				);
			}
		}

		return $abbr;
	}
endif;

if ( ! function_exists( 'labbook_the_references' ) ) :
	/**
	 * Prints HTML with post references
	 */
	function labbook_the_references( $post = null ) {
		global $ssl_alp;

		if ( ! labbook_get_option( 'show_crossreferences' ) ) {
			// display is unavailable
			return;
		} elseif ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// tracking of cross-references are disabled
			return;
		}

		$post = get_post( $post );

		if ( ! $ssl_alp->references->is_supported( $post ) ) {
			// post type not supported
			return;
		}

		$ref_to_posts = $ssl_alp->references->get_reference_to_posts( $post );
		$ref_from_posts = $ssl_alp->references->get_reference_from_posts( $post );

		if ( ( ! is_array( $ref_to_posts ) || ! count( $ref_to_posts ) ) && ( ! is_array( $ref_from_posts ) || ! count( $ref_from_posts ) ) ) {
			// no references
			return;
		}

		printf( '<div id="post-references"><h3>%1$s</h3>', __( 'Cross-references', 'labbook' ));

		if ( $ref_to_posts ) {
			printf( '<h4>%1$s</h4>', __( 'Links to', 'labbook' ) );
			labbook_the_referenced_post_list( $ref_to_posts );
		}

		if ( $ref_from_posts ) {
			printf( '<h4>%1$s</h4>', __( 'Linked from', 'labbook' ));
			labbook_the_referenced_post_list( $ref_from_posts );
		}

		echo '</div>';
	}
endif;

if ( ! function_exists( 'labbook_the_referenced_post_list' ) ) {
	/**
	 * Prints list of reference links
	 */
	function labbook_the_referenced_post_list( $referenced_posts ) {
		echo '<ul>';

		foreach ( $referenced_posts as $referenced_post ) {
			// get post
			$referenced_post = get_post( $referenced_post );

			// print reference post information
			labbook_referenced_post_list_item( $referenced_post );
		}

		echo '</ul>';
	}
}

if ( ! function_exists( 'labbook_referenced_post_list_item' ) ) {
	/**
	 * Prints HTML link to the specified reference post
	 */
	function labbook_referenced_post_list_item( $referenced_post = null, $url = true ) {
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

if ( ! function_exists( 'labbook_the_page_breadcrumbs' ) ) :
	/**
	 * Print page breadcrumbs
	 */
	function labbook_the_page_breadcrumbs( $page = null ) {
		if ( ! labbook_get_option( 'show_page_breadcrumbs' ) ) {
			// display is unavailable
			return;
		}

		$breadcrumbs = labbook_get_page_breadcrumbs( $page );

		if ( ! count( $breadcrumbs ) ) {
			return;
		}

		echo '<ul>';

		foreach ( $breadcrumbs as $breadcrumb ) {
			$title = esc_html( $breadcrumb['title'] );

			if ( ! empty( $breadcrumb['url'] ) ) {
				$title = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $breadcrumb['url'] ),
					$title
				);
			}

			printf(
				'<li>%1$s</li>',
				$title
			);
		}

		echo '</ul>';
	}
endif;

if ( ! function_exists( 'labbook_the_toc' ) ) :
    function labbook_the_toc( $contents, $max_levels ) {
        if ( $max_levels < 0 ) {
            // beyond the maximum level setting
            return;
        }

        if ( is_null( $contents ) ) {
            return;
        }

        $menu_data = $contents->get_menu_data();

        if ( is_array( $menu_data ) ) {
            printf(
                '<a href="#%1$s">%2$s</a>',
                esc_html( $menu_data['id'] ),
                esc_html( $menu_data['title'] )
            );
        }

        if ( $max_levels > 0 ) {
            // next level still visible
            // get children
            $children = $contents->get_child_menus();

            if ( count( $children ) ) {
                echo '<ul>';

                foreach ( $children as $child ) {
                    // show sublevel
                    echo '<li>';
                    labbook_the_toc( $child, $max_levels - 1 );
                    echo '</li>';
                }

                echo '</ul>';
            }
        }
    }
endif;
