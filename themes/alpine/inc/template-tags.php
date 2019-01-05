<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Alpine
 */


if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

if ( ! function_exists( 'alpine_the_post_title' ) ) :
	/**
	 * Print the post title
	 */
	function alpine_the_post_title( $post = null, $url = true, $icon = true, $anchor = false ) {
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

if ( ! function_exists( 'alpine_get_post_date_html' ) ) :
	/**
	 * Format a post date
	 */
	function alpine_get_post_date_html( $post = null, $modified = false, $time = true, $icon = true, $url = true ) {
		$datetime_fmt = alpine_get_date_format( $time );

		// ISO 8601 formatted date
		$date_iso = $modified ? get_the_modified_date( 'c', $post ) : get_the_date( 'c', $post );

		// date formatted to WordPress preference
		$date_str = $modified ? get_the_modified_date( $datetime_fmt, $post ) : get_the_date( $datetime_fmt, $post );

		// how long ago
		$human_date = $modified ? alpine_get_human_date( $post->post_modified ) : alpine_get_human_date( $post->post_date );

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
			// add icons
			$time_str = '<i class="fa fa-calendar" aria-hidden="true"></i>' . $time_str;
		}

		return $time_str;
	}
endif;

if ( ! function_exists( 'alpine_get_date_format' ) ):
	/**
	 * Get date and optional time format strings to pass to get_the_date or get_the_modified_date
	 */
	function alpine_get_date_format( $time = true ) {
		$datetime_fmt = get_option( 'date_format' );

		if ( $time ) {
			// combined date and time formats
			$datetime_fmt = sprintf(
				/* translators: 1: date, 2: time; note that "\a\t" escapes "at" in PHP's date() function */
				__( '%1$s \a\t %2$s', 'alpine' ),
				$datetime_fmt,
				get_option( 'time_format' )
			);
		}

		return $datetime_fmt;
	}
endif;

if ( ! function_exists( 'alpine_get_human_date' ) ):
	/**
	 * Get human formatted date, e.g. "3 hours ago"
	 */
	function alpine_get_human_date( $date_str, $compare_timestamp = null ) {
		if ( is_null( $compare_timestamp ) ) {
			// use current time
			$compare_timestamp = current_time( 'timestamp' );
		}

		$timestamp = strtotime( $date_str );

		return sprintf(
			/* translators: 1: time ago */
			__( '%s ago', 'alpine' ),
			human_time_diff( $timestamp, $compare_timestamp )
		);
	}
endif;

if ( ! function_exists( 'alpine_the_post_meta' ) ) :
	/**
	 * Print HTML with meta information about post
	 */
	function alpine_the_post_meta( $post = null ) {
		$post = get_post( $post );
		$posted_on = alpine_get_post_date_html( $post );

		// check post timestamps to see if modified
		if ( get_the_time( 'U', $post ) !== get_the_modified_time( 'U', $post ) ) {
			$modified_on = alpine_get_post_date_html( $post, true );
			/* translators: 1: post modification date */
			$posted_on .= sprintf( __( ' (last edited %1$s)', 'alpine' ), $modified_on );
		}

		// post id and authors
		printf(
			'<div class="byline"><i class="fa fa-link"></i>%1$s&nbsp;&nbsp;%2$s</div>',
			$post->ID,
			alpine_get_authors( $post )
		);

		if ( is_plugin_active( 'ssl-alp/alp.php' ) ) {
			if ( get_option( 'ssl_alp_enable_post_edit_summaries' ) ) {
				$revision_count = alpine_get_revision_count();

				if ( $revision_count > 0 ) {
					$revision_str = sprintf( _n( '%s revision', '%s revisions', $revision_count, 'alpine' ), $revision_count );

					$posted_on .= sprintf(
						'&nbsp;&nbsp;<i class="fa fa-pencil" aria-hidden="true"></i><a href="%1$s#post-revisions">%2$s</a>',
						esc_url( get_the_permalink( $post ) ),
						$revision_str
					);
				}
			}
		}

		if ( current_user_can( 'edit_post', $post ) ) {
			// add edit post link
			$posted_on .= sprintf(
				'&nbsp;&nbsp;<i class="fa fa-edit" aria-hidden="true"></i><a href="%1$s">%2$s</a>',
				get_edit_post_link(),
				__( 'Edit', 'alpine' )
			);
		}

		printf(
			'<div class="posted-on">%1$s</div>',
			$posted_on
		);
	}
endif;

if ( ! function_exists( 'alpine_the_page_meta' ) ) :
	/**
	 * Print HTML with meta information about page
	 */
	function alpine_the_page_meta( $page = null ) {
		$page = get_post( $page );

		echo '<div class="breadcrumbs">';
		alpine_the_page_breadcrumbs();
		echo '</div>';

		if ( current_user_can( 'edit_page', $page ) ) {
			// add edit page link
			printf(
				'<div class="posted-on"><i class="fa fa-edit" aria-hidden="true"></i><a href="%1$s">%2$s</a></div>',
				get_edit_post_link(),
				__( 'Edit', 'alpine' )
			);
		}
	}
endif;

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

if ( ! function_exists( 'alpine_format_author' ) ) :
	/**
	 * Gets formatted author name
	 */
	function alpine_format_author( $author, $url = true ) {
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

if ( ! function_exists( 'alpine_the_footer' ) ) :
	/**
	 * Prints the footer for the specified post.
	 *
	 * Cannot specify a custom post id here, as `get_comments_number_text` can't
	 * handle it. It always uses the current post.
	 */
	function alpine_the_footer() {
		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( esc_html__( ', ', 'alpine' ) );

		if ( $categories_list ) {
			printf(
				'<span class="cat-links">%1$s%2$s</span>',
				'<i class="fa fa-folder-open" aria-hidden="true"></i>',
				$categories_list
			);
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', esc_html__( ', ', 'alpine' ) );

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
				get_comments_number_text(esc_html__( 'Leave a comment', 'alpine' ))
			);
		}
	}
endif;

if ( ! function_exists( 'alpine_the_revisions' ) ) :
	/**
	 * Prints revisions for the specified post
	 */
	function alpine_the_revisions( $post = null ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_post_edit_summaries' ) ) {
			return;
		}

		$post = get_post( $post );

		if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// post type not supported
			return;
		}

		if ( ! $current_page = get_query_var( 'paged' ) ) {
			$current_page = 1;
		}

		// total revisions
		$count = alpine_get_revision_count( $post );
		$per_page = 10;
		$pages = ceil( $count / $per_page );

		// get list of revisions to this post
		$revisions = alpine_get_revisions( $post, $current_page, $per_page );

		if ( is_null( $revisions ) || ! is_array( $revisions ) || count( $revisions ) == 0 ) {
			// no revisions to show
			return;
		}

		echo '<div id="post-revisions">';
		echo '<h3>';

		printf( // WPCS: XSS OK.
			/* translators: 1: revision count number, 2: title. */
			esc_html( _nx( '%1$s revision', '%1$s revisions', $count, 'revisions title', 'alpine' ) ),
			number_format_i18n( $count )
		);

		echo "</h3>";
		echo "<ul>";

		foreach ( $revisions as $revision ) {
			echo '<li>' . alpine_get_revision_description( $revision ) . '</li>';
		}

		echo "</ul>";

		if ( $pages > 1 ) {
			echo paginate_links( array(
				'base'     => get_pagenum_link() . '%_%',
				'format'   => '&paged=%#%',
				'current'  => $current_page,
				'total'    => $pages
	  		) );
		}

		echo "</div>";
	}
endif;

if ( ! function_exists( 'alpine_get_revisions' ) ) :
	/**
	 * Get list of revisions for the current or specified post
	 */
	function alpine_get_revisions( $post = null, $page = 1, $per_page = -1 ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_post_edit_summaries' ) ) {
			return;
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

if ( ! function_exists( 'alpine_get_revision_description' ) ) :
	/**
	 * Prints description for the specified revision
	 */
	function alpine_get_revision_description( $revision ) {
		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_post_edit_summaries' ) ) {
			return;
		}

		// get revision object if id is specified
		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// get revision's edit summary
		$revision_edit_summary = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$revision_edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		// default message
		$message = " " . alpine_get_revision_abbreviation( $revision );

		if ( wp_is_post_autosave( $revision ) ) {
			// this is an autosave
			$message .= __( ': [Autosave]', 'alpine' );
		} else {
			// check that we have a revision summary
			if ( ( ! empty( $revision_edit_summary ) && is_string( $revision_edit_summary ) ) || ( ! empty( $revision_edit_summary_revert_id ) && ( $revision_edit_summary_revert_id > 0 ) ) ) {
				if ( $revision_edit_summary_revert_id > 0 ) {
					// revision was a revert
					// /* translators: 1: revision ID/URL */
					$message .= sprintf(
						__( ': reverted to %1$s', 'alpine' ),
						alpine_get_revision_abbreviation( $revision_edit_summary_revert_id )
					);

					// add summary
					if ( ! empty ( $revision_edit_summary ) ) {
						$message .= sprintf(
							/* translators: 1: revision message */
							__(' (<em>"%1$s"</em>)', 'alpine' ),
							$revision_edit_summary
						);
					}
				} else {
					if ( ! empty ( $revision_edit_summary ) ) {
						/* translators: 1: revision message */
						$message .= sprintf( __( ': <em>"%1$s"</em>', 'alpine' ), esc_html( $revision_edit_summary ) );
					}
				}
			}
		}

		$revision_time = sprintf(
			'<span title="%1$s">%2$s</span>',
			get_the_modified_date( alpine_get_date_format( true ), $revision ),
			alpine_get_human_date( $revision->post_modified )
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
			$description .= __( ' <strong>(current)</strong>', 'alpine' );
		}

		return $description;
	}
endif;

if ( ! function_exists( 'alpine_get_revision_abbreviation' ) ) :
	/**
	 * Gets abbreviated revision ID, with optional URL
	 */
	function alpine_get_revision_abbreviation( $revision, $url = true ) {
		global $ssl_alp;

		// get revision object if id is specified
		$revision = wp_get_post_revision( $revision );

		if  ( 'revision' !== $revision->post_type ) {
			return;
		}

		// revision post ID
		$revision_id = sprintf(
			_x('r%1$s', 'abbreviated revision ID text', 'alpine' ),
			$revision->ID
		);

		// add URL to diff if user can view
		if ( $url ) {
			if ( $ssl_alp->revisions->current_user_can_view_revision( $revision ) ) {
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

if ( ! function_exists( 'alpine_the_references' ) ) :
	/**
	 * Prints HTML with post references
	 */
	function alpine_the_references( $post = null ) {
		global $ssl_alp;

		if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
			// plugin is disabled
			return;
		} elseif ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// cross-references are disabled
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

		printf( '<div id="post-references"><h3>%1$s</h3>', __( 'Cross-references', 'alpine' ));

		if ( $ref_to_posts ) {
			printf( '<h4>%1$s</h4>', __( 'Links to', 'alpine' ) );
			alpine_the_referenced_post_list( $ref_to_posts );
		}

		if ( $ref_from_posts ) {
			printf( '<h4>%1$s</h4>', __( 'Linked from', 'alpine' ));
			alpine_the_referenced_post_list( $ref_from_posts );
		}

		echo '</div>';
	}
endif;

if ( ! function_exists( 'alpine_the_referenced_post_list' ) ) {
	/**
	 * Prints list of reference links
	 */
	function alpine_the_referenced_post_list( $referenced_posts ) {
		echo '<ul>';

		foreach ( $referenced_posts as $referenced_post ) {
			// get post
			$referenced_post = get_post( $referenced_post );

			// print reference post information
			alpine_referenced_post_list_item( $referenced_post );
		}

		echo '</ul>';
	}
}

if ( ! function_exists( 'alpine_referenced_post_list_item' ) ) {
	/**
	 * Prints HTML link to the specified reference post
	 */
	function alpine_referenced_post_list_item( $referenced_post = null, $url = true ) {
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

if ( ! function_exists( 'alpine_the_page_breadcrumbs' ) ) :
	/**
	 * Print page breadcrumbs
	 */
	function alpine_the_page_breadcrumbs( $page = null ) {
		$breadcrumbs = alpine_get_page_breadcrumbs( $page );

		if ( ! count( $breadcrumbs ) ) {
			return;
		}

		echo '<ul>';

		foreach ( $breadcrumbs as $breadcrumb ) {
			printf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( $breadcrumb['url'] ),
				esc_html( $breadcrumb['title'] )
			);
		}

		echo '</ul>';
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

if ( ! function_exists( 'alpine_the_toc' ) ) :
    function alpine_the_toc( $contents, $max_levels ) {
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
                    alpine_the_toc( $child, $max_levels - 1 );
                    echo '</li>';
                }

                echo '</ul>';
            }
        }
    }
endif;