<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Labbook
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

if ( ! function_exists( 'labbook_the_post_title' ) ) :
	/**
	 * Print the post title.
	 *
	 * @param int|WP_Post|null $post   Post ID or post object. Defaults to global $post.
	 * @param bool             $url    Make title into permalink.
	 * @param bool             $icon   Add post icon, if present.
	 */
	function labbook_the_post_title( $post = null, $url = true, $icon = true ) {
		global $ssl_alp;

		$post = get_post( $post );

		echo '<h2 class="entry-title">';

		$post_read_classes = array();
		$read_class = '';
		$unread_class = '';
		$read_status = '';

		if ( $icon ) {
			if ( ! is_user_logged_in() || ! labbook_get_option( 'show_unread_flags' )
				|| ! labbook_ssl_alp_unread_flags_enabled()
				|| ! $ssl_alp->revisions->unread_flags_supported( $post )
			) {
				// No support for unread flags.
				if ( 'status' === get_post_format( $post ) ) {
					$icon_class = 'fa fa-info-circle';
					$icon_description = __( 'Status update', 'labbook' );
				} else {
					// Don't show icon.
					$icon_class = '';
				}
			} else {
				// Post read/unread status.
				$post_is_read = labbook_post_is_read( $post );

				// Default post read class.
				$post_read_classes[] = 'entry-title-link-' . $post->ID;

				if ( $post_is_read ) {
					$post_read_classes[] = 'entry-read';
				}

				$read_status = $post_is_read ? "true" : "false";

				if ( 'status' === get_post_format( $post ) ) {
					$icon_class = 'fa fa-info-circle labbook-read-button';
					$icon_description = __( 'Status update (click to toggle read status)', 'labbook' );
					$read_class = 'fa-info-circle';
					$unread_class = 'fa-info-circle';
				} else {
					if ( $post_is_read ) {
						// Read.
						$icon_class = 'fa fa-envelope-open labbook-read-button';
					} else {
						// Unread.
						$icon_class = 'fa fa-envelope labbook-read-button';
					}

					$icon_description = __( 'Post (click to toggle read status)', 'labbook' );
					$read_class = 'fa-envelope-open';
					$unread_class = 'fa-envelope';
				}
			}

			if ( ! empty( $icon_class ) ) {
				printf(
					'<i class="%1$s" title="%2$s" data-post-id="%3$s" data-read-status="%4$s" data-read-class="%5$s" data-unread-class="%6$s"></i>',
					esc_attr( $icon_class ),
					esc_attr( $icon_description ),
					esc_attr( $post->ID ),
					esc_attr( $read_status ),
					esc_attr( $read_class ),
					esc_attr( $unread_class )
				);
			}
		} // End if().

		if ( $url ) {
			// Wrap title in its permalink.
			the_title(
				sprintf(
					'<a href="%1$s" class="%2$s" rel="bookmark">',
					esc_url( get_permalink( $post ) ),
					esc_attr( implode( ' ', $post_read_classes ) )
				),
				'</a>'
			);
		} else {
			// Just display title.
			the_title();
		}

		echo '</h2>';
	}
endif;

if ( ! function_exists( 'labbook_get_post_date' ) ) :
	/**
	 * Get formatted post date.
	 *
	 * @param int|WP_Post|null $post     Post ID or post object. Defaults to global $post.
	 * @param bool             $modified Show the modified date.
	 * @param bool             $time     Show the time of day.
	 * @param bool             $icon     Show calendar icon.
	 * @return string
	 */
	function labbook_get_post_date( $post = null, $modified = false, $time = true, $icon = true ) {
		$datetime_fmt = labbook_get_date_format( $time );

		// ISO 8601 formatted date.
		$date_iso = $modified ? get_the_modified_date( 'c', $post ) : get_the_date( 'c', $post );

		// Date formatted to WordPress preference.
		$date_str = $modified ? get_the_modified_date( $datetime_fmt, $post ) : get_the_date( $datetime_fmt, $post );

		// How long ago.
		$human_date = $modified ? labbook_get_human_date( $post->post_modified ) : labbook_get_human_date( $post->post_date );

		// Different time class defending on whether we're showing publication or modification date.
		$time_class = $modified ? 'updated' : 'entry-date published';

		$time_str = sprintf(
			'<time class="%1$s" datetime="%2$s" title="%3$s">%4$s</time>',
			esc_attr( $time_class ),
			esc_attr( $date_iso ),
			esc_attr( $human_date ),
			esc_attr( $date_str )
		);

		if ( $icon ) {
			if ( $modified ) {
				$title = __( 'Modification date', 'labbook' );
			} else {
				$title = __( 'Publication date', 'labbook' );
			}

			// Add icons.
			$time_str = sprintf(
				'<i class="fa fa-calendar" title="%1$s" aria-hidden="true"></i>%2$s',
				esc_attr( $title ),
				$time_str
			);
		}

		return $time_str;
	}
endif;

if ( ! function_exists( 'labbook_get_date_format' ) ) :
	/**
	 * Get date and optional time format strings to pass to get_the_date or get_the_modified_date.
	 *
	 * @param  bool $time Add time format string.
	 * @return string
	 */
	function labbook_get_date_format( $time = true ) {
		$datetime_fmt = get_option( 'date_format' );

		if ( $time ) {
			// Combined date and time formats.
			$datetime_fmt = sprintf(
				/* translators: 1: date, 2: time; note that "\a\t" escapes "at" in PHP's date() function */
				__( '%1$s \a\t %2$s', 'labbook' ),
				esc_html( $datetime_fmt ),
				get_option( 'time_format' )
			);
		}

		return $datetime_fmt;
	}
endif;

if ( ! function_exists( 'labbook_get_human_date' ) ) :
	/**
	 * Get human formatted date, e.g. "3 hours ago".
	 *
	 * @param string $date_str Date format string.
	 * @return string
	 */
	function labbook_get_human_date( $date_str ) {
		$timestamp = strtotime( $date_str );

		return sprintf(
			/* translators: 1: time ago */
			__( '%s ago', 'labbook' ),
			human_time_diff( $timestamp, current_time( 'timestamp' ) )
		);
	}
endif;

if ( ! function_exists( 'labbook_the_post_meta' ) ) :
	/**
	 * Print HTML with meta information about post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_post_meta( $post = null ) {
		$post = get_post( $post );

		echo '<div class="byline">';

		// Print post ID.
		labbook_the_post_id_icon( $post );
		echo '&nbsp;&nbsp;';

		if ( 'post' === $post->post_type ) {
			labbook_the_authors( $post );
			echo '&nbsp;&nbsp;';
		}

		if ( labbook_get_option( 'show_edit_summaries' ) && labbook_get_post_edit_count( $post ) > 0 ) {
			// Print revisions link.
			labbook_the_revisions_link( $post );
			echo '&nbsp;&nbsp;';
		}

		if ( 'page' === $post->post_type ) {
			$permission = 'edit_page';
		} else {
			$permission = 'edit_post';
		}

		if ( current_user_can( $permission, $post ) ) {
			// Print edit post link.
			labbook_the_post_edit_link( $post );
			echo '&nbsp;&nbsp;';
		}

		echo '</div>';

		// Allowed tags in date HTML.
		$allowed_date_html = array(
			'time' => array(
				'class'    => array(),
				'datetime' => array(),
				'title'    => array(),
			),
			'i'    => array(
				'class'       => array(),
				'title'       => array(),
				'aria-hidden' => array(),
			),
		);

		if ( 'post' === $post->post_type ) {
			echo '<div class="posted-on">';
			echo wp_kses( labbook_get_post_date( $post ), $allowed_date_html );

			// Check post timestamps to see if the post has been modified.
			if ( get_the_time( 'U', $post ) !== get_the_modified_time( 'U', $post ) ) {
				printf(
					/* translators: 1: post modification time */
					esc_html__( ' (last edited %1$s)', 'labbook' ),
					wp_kses( labbook_get_post_date( $post, true ), $allowed_date_html )
				);
			}

			echo '</div>';
		}
	}
endif;

if ( ! function_exists( 'labbook_the_post_id_icon' ) ) :
	/**
	 * Print the post ID icon.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_post_id_icon( $post ) {
		$post = get_post( $post );

		printf(
			'<i class="fa fa-link" title="%1$s"></i><a href="%2$s" rel="bookmark">%3$s</a>',
			esc_html__( 'ID', 'labbook' ),
			esc_url( get_permalink( $post ) ),
			esc_html( $post->ID )
		);
	}
endif;

if ( ! function_exists( 'labbook_the_post_edit_link' ) ) :
	/**
	 * Print the post edit link.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_post_edit_link( $post = null ) {
		$post = get_post( $post );

		printf(
			'<i class="fa fa-edit" aria-hidden="true"></i><a href="%1$s">%2$s</a>',
			esc_url( get_edit_post_link( $post ) ),
			esc_html__( 'Edit', 'labbook' )
		);
	}
endif;

if ( ! function_exists( 'labbook_the_revisions_link' ) ) :
	/**
	 * Print the post revisions link.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_revisions_link( $post = null ) {
		global $ssl_alp;

		if ( ! labbook_ssl_alp_edit_summaries_enabled() ) {
			return;
		}

		$post = get_post( $post );

		// Check if edit summaries are available for this post.
		if ( ! $ssl_alp->revisions->edit_summary_allowed( $post, false ) ) {
			return;
		}

		$edit_count = labbook_get_post_edit_count( $post );

		if ( is_null( $edit_count ) ) {
			// Revisions not available.
			return;
		}

		/* translators: number of revisions */
		$edit_str = sprintf( _n( '%s revision', '%s revisions', $edit_count, 'labbook' ), $edit_count );

		printf(
			'<i class="fa fa-pencil" title="%1$s" aria-hidden="true"></i><a href="%2$s#post-revisions">%3$s</a>',
			esc_attr__( 'Number of edits made to the original post', 'labbook' ),
			esc_url( get_the_permalink( $post ) ),
			esc_html( $edit_str )
		);
	}
endif;

if ( ! function_exists( 'labbook_the_authors' ) ) :
	/**
	 * Print formatted author HTML.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @param bool             $icon Show author icon.
	 * @param bool             $url  Show author URLs.
	 */
	function labbook_the_authors( $post = null, $icon = true, $url = true ) {
		global $ssl_alp;

		$post = get_post( $post );

		if ( labbook_ssl_alp_coauthors_enabled() ) {
			$authors = $ssl_alp->coauthors->get_coauthors( $post );
		} else {
			// Fall back to the_author if plugin is disabled.
			$authors = array();

			// Get single author object.
			$author = get_user_by( 'id', $post->post_author );

			// If there is no author, $author == false.
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
			// No authors.
			return;
		}

		echo '<span class="authors">';

		if ( count( $author_html ) > 1 ) {
			// There are multiple authors.
			$icon_class = 'fa fa-users';

			// Get delimiters.
			$delimiter_between = _x( ', ', 'delimiter between coauthors except last', 'labbook' );
			$delimiter_between_last = _x( ' and ', 'delimiter between last two coauthors', 'labbook' );

			// Pop last author off.
			$last_author = array_pop( $author_html );

			// Implode author list.
			$author_list_html = implode( __( ', ', 'labbook' ), $author_html ) . $delimiter_between_last . $last_author;
		} else {
			// Single author.
			$icon_class = 'fa fa-user';

			$author_list_html = $author_html[0];
		}

		if ( $icon ) {
			printf(
				'<i class="%1$s" title="%2$s" aria-hidden="true"></i>',
				esc_attr( $icon_class ),
				esc_html__( 'Authors', 'labbook' )
			);
		}

		echo wp_kses(
			$author_list_html,
			array(
				'span' => array(
					'class' => array(),
				),
				'a'    => array(
					'href' => array(),
				),
			)
		);

		echo '</span>';
	}
endif;

if ( ! function_exists( 'labbook_format_author' ) ) :
	/**
	 * Get formatted author name.
	 *
	 * @param WP_Author $author The author.
	 * @param bool      $url    Show author URL.
	 * @return string
	 */
	function labbook_format_author( $author, $url = true ) {
		if ( is_null( $author ) ) {
			return;
		}

		$author_display = '<span class="author vcard">';

		if ( $url ) {
			// Wrap author in link to their posts.
			$author_display .= sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( get_author_posts_url( $author->ID ) ),
				esc_html( $author->display_name )
			);
		} else {
			$author_display .= esc_html( $author->display_name );
		}

		$author_display .= '</span>';

		return $author_display;
	}
endif;

if ( ! function_exists( 'labbook_the_footer' ) ) :
	/**
	 * Print the footer for the specified post.
	 *
	 * Cannot specify a custom post id here, as `get_comments_number_text` can't
	 * handle it. It always uses the current post.
	 */
	function labbook_the_footer() {
		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( __( ', ', 'labbook' ) );

		// Allowed category and tag HTML.
		$cat_tag_tags = array(
			'a' => array(
				'href' => array(),
				'rel'  => array(),
			),
		);

		if ( $categories_list ) {
			printf(
				'<span class="cat-links"><i class="fa fa-folder-open" aria-hidden="true"></i>%1$s</span>',
				wp_kses( $categories_list, $cat_tag_tags )
			);
			echo '&nbsp;&nbsp;';
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', __( ', ', 'labbook' ) );

		if ( $tags_list ) {
			printf(
				'<span class="tag-links"><i class="fa fa-tags" aria-hidden="true"></i>%1$s</span>',
				wp_kses( $tags_list, $cat_tag_tags )
			);
			echo '&nbsp;&nbsp;';
		}

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			// Show comments link.
			printf(
				'<span class="comments-link"><i class="fa fa-comment" aria-hidden="true"></i><a href="%1$s">%2$s</a></span>',
				esc_url( get_comments_link() ),
				esc_html( get_comments_number_text( __( 'Leave a comment', 'labbook' ) ) )
			);
			echo '&nbsp;&nbsp;';
		}
	}
endif;

if ( ! function_exists( 'labbook_the_revisions' ) ) :
	/**
	 * Print revisions for the specified post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_revisions( $post = null ) {
		if ( ! labbook_get_option( 'show_edit_summaries' ) ) {
			// Display is unavailable.
			return;
		}

		$post = get_post( $post );

		if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// Post type not supported.
			return;
		}

		$current_page = get_query_var( 'revision_page', 1 );

		// Total revisions.
		$count = labbook_get_post_revision_count( $post );

		if ( is_null( $count ) ) {
			// Revisions not available.
			return;
		}

		$per_page = labbook_get_option( 'edit_summaries_per_page' );
		$pages = ceil( $count / $per_page );

		// Get list of revisions to this post.
		$revisions = labbook_get_revisions( $post, $current_page, $per_page );

		if ( is_null( $revisions ) || ! is_array( $revisions ) || 0 === count( $revisions ) ) {
			// No revisions to show.
			return;
		}

		echo '<div id="post-revisions">';
		echo '<h3>';
		esc_html_e( 'History', 'labbook' );
		echo '</h3>';

		?>

		<table>
			<colgroup>
				<col class="post-revision-abbr-col">
				<col class="post-revision-date-col">
				<col class="post-revision-author-col">
				<col class="post-revisions-info-col">
			</colgroup>
			<thead>
				<tr>
					<th scope="col"><abbr title="<?php esc_html_e( 'Revision ID', 'labbook' ); ?>"><?php echo esc_html_x( '#', 'Revision ID abbreviation', 'labbook' ); ?></abbr></th>
					<th scope="col"><?php esc_html_e( 'Date', 'labbook' ); ?></th>
					<th scope="col"><?php esc_html_e( 'User', 'labbook' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Information', 'labbook' ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php

		foreach ( $revisions as $revision ) {
			labbook_the_revision_description_row( $revision );
		}

		?>
			</tbody>
		</table>
		<?php

		if ( $pages > 1 ) {
			echo paginate_links(
				array(
					'base'    => get_permalink( $post ) . '%_%#post-revisions',
					'format'  => '?revision_page=%#%',
					'current' => $current_page,
					'total'   => $pages,
				)
			);
		}

		echo '</div>';
	}
endif;

if ( ! function_exists( 'labbook_the_revision_description_row' ) ) :
	/**
	 * Print description for the specified revision in a table row.
	 *
	 * @param int|WP_Post $revision The revision.
	 */
	function labbook_the_revision_description_row( $revision ) {
		global $ssl_alp;

		// Get revision object if id is specified.
		$revision = wp_get_post_revision( $revision );

		if ( is_null( $revision ) ) {
			return;
		}

		if ( 'revision' !== $revision->post_type ) {
			return;
		}

		// Get revision's edit summary.
		$edit_data = $ssl_alp->revisions->get_revision_edit_summary( $revision );

		// Revision abbreviation, e.g. r101, with link to diff.
		$abbr = labbook_get_revision_abbreviation( $revision );

		// Allowed revision abbreviation tags.
		$allowed_abbr_tags = array(
			'a' => array(
				'href'  => array(),
				'title' => array(),
			),
		);

		// Whether the revision is the latest update to the parent.
		$is_current = get_the_time( 'U', $revision ) === get_the_modified_time( 'U', $revision->parent );

		// Whether the revision is an autosave.
		$is_autosave = wp_is_post_autosave( $revision );

		// Whether the revision was created when the post was published.
		$is_original = labbook_revision_was_autogenerated_on_publication( $revision );

		if ( is_null( $abbr ) ) {
			// Invalid.
			return;
		}

		$author = get_user_by( 'ID', $revision->post_author );

		if ( $is_current ) {
			echo '<tr class="post-revision-current">';
		} elseif ( $is_autosave ) {
			echo '<tr class="post-revision-autosave">';
		} elseif ( $is_original ) {
			echo '<tr class="post-revision-original">';
		} else {
			echo '<tr>';
		}

		echo '<th>'; // Revision.

		// Print revision abbreviation.
		echo wp_kses( $abbr, $allowed_abbr_tags );

		echo '</th>';
		echo '<td class="post-revision-date">'; // Date.

		// Print date.
		printf(
			'<span title="%1$s">%2$s</span>',
			esc_attr( get_the_modified_date( labbook_get_date_format( true ), $revision ) ),
			esc_html( labbook_get_human_date( $revision->post_modified ) )
		);

		echo '</td>';
		echo '<td class="post-revision-author">'; // Author.

		if ( $author ) {
			// Print author link.
			echo labbook_format_author( $author );
		}

		echo '</td>';
		echo '<td>'; // Information.

		if ( ! empty( $edit_data['summary'] ) ) {
			// Print the edit summary.
			echo '<em>';
			echo esc_html( $edit_data['summary'] );
			echo '</em>';
			echo '&nbsp';
		}

		if ( ! empty( $edit_data['revert_id'] ) ) {
			// Revision was a revert.
			$source_abbr = labbook_get_revision_abbreviation( $edit_data['revert_id'] );

			if ( is_null( $source_abbr ) ) {
				// Source revision is invalid.
				/* translators: reverted to unknown revision */
				esc_html_e( 'reverted', 'labbook' );
			} else {
				printf(
					/* translators: 1: reverted revision ID */
					esc_html__( 'reverted to %1$s', 'labbook' ),
					wp_kses( $source_abbr, $allowed_abbr_tags )
				);
			}

			echo '&nbsp;';

			if ( ! empty( $edit_data['source_summary'] ) ) {
				// Add original edit summary.
				echo '<em>';
				printf(
					/* translators: 1: edit summary of post reverted to */
					esc_html__( '("%1$s")', 'labbook' ),
					esc_html( $edit_data['source_summary'] )
				);
				echo '</em>';
				echo '&nbsp;';
			}
		}

		if ( $is_current ) {
			echo '<strong>';
			/* translators: current revision */
			esc_html_e( '(current)', 'labbook' );
			echo '</strong>';
		} elseif ( $is_autosave ) {
			echo '<strong>';
			/* translators: autosaved post */
			esc_html_e( '(autosave)', 'labbook' );
			echo '</strong>';
		} elseif ( $is_original ) {
			echo '<strong>';
			/* translators: original published post */
			esc_html_e( '(original)', 'labbook' );
			echo '</strong>';
		}

		echo '</td>';
		echo '</tr>';
	}
endif;

if ( ! function_exists( 'labbook_get_revision_abbreviation' ) ) :
	/**
	 * Get abbreviated revision ID, with optional URL.
	 *
	 * If the specified revision doesn't exist, it may have been deleted but is still referenced by
	 * another revision edit summary. In this case, this function will will still show the
	 * non-existent revision ID but will not provide a URL to the diff screen.
	 *
	 * @param int  $revision Revision ID.
	 * @param bool $url      Print revision URL.
	 * @return string|null The revision abbreviation, or null if specified revision is invalid or
	 *                     not a valid revision.
	 */
	function labbook_get_revision_abbreviation( $revision, $url = true ) {
		global $ssl_alp;

		$revision = wp_get_post_revision( $revision );

		if ( is_null( $revision ) ) {
			// Invalid.
			return;
		}

		if ( 'revision' !== $revision->post_type ) {
			return;
		}

		// Revision post ID.
		$abbr = $revision->ID;

		// Add URL to diff if user can view.
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

			if ( ! empty( $edit_link ) && $ssl_alp->revisions->current_user_can_view_revision( $revision ) ) {
				$abbr = sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					esc_url( $edit_link ),
					esc_attr(
						sprintf(
							/* translators: 1: revision ID */
							__( 'View changes in revision %1$s', 'labbook' ),
							$revision->ID
						)
					),
					$abbr
				);
			}
		}

		return $abbr;
	}
endif;

if ( ! function_exists( 'labbook_the_references' ) ) :
	/**
	 * Print post references.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_references( $post = null ) {
		global $ssl_alp;

		if ( ! labbook_get_option( 'show_crossreferences' ) || ! labbook_ssl_alp_crossreferences_enabled() ) {
			// Display is unavailable.
			return;
		}

		$post = get_post( $post );

		if ( ! $ssl_alp->references->is_supported( $post ) ) {
			// Post type not supported.
			return;
		}

		$ref_to_posts = $ssl_alp->references->get_reference_to_posts( $post );
		$ref_from_posts = $ssl_alp->references->get_reference_from_posts( $post );

		if ( ( ! is_array( $ref_to_posts ) || ! count( $ref_to_posts ) ) && ( ! is_array( $ref_from_posts ) || ! count( $ref_from_posts ) ) ) {
			// No references.
			return;
		}

		echo '<div id="post-references">';
		echo '<h3>';
		esc_html_e( 'Cross-references', 'labbook' );
		echo '</h3>';

		if ( $ref_to_posts ) {
			echo '<h4>';
			esc_html_e( 'Links to', 'labbook' );
			echo '</h4>';
			labbook_the_referenced_post_list( $ref_to_posts );
		}

		if ( $ref_from_posts ) {
			echo '<h4>';
			esc_html_e( 'Linked from', 'labbook' );
			echo '</h4>';
			labbook_the_referenced_post_list( $ref_from_posts );
		}

		echo '</div>';
	}
endif;

if ( ! function_exists( 'labbook_the_referenced_post_list' ) ) {
	/**
	 * Print list of reference links.
	 *
	 * @param array $referenced_posts The referenced posts.
	 */
	function labbook_the_referenced_post_list( $referenced_posts ) {
		echo '<ul>';

		foreach ( $referenced_posts as $referenced_post ) {
			// Get post.
			$referenced_post = get_post( $referenced_post );

			// Print reference post information.
			labbook_referenced_post_list_item( $referenced_post );
		}

		echo '</ul>';
	}
}

if ( ! function_exists( 'labbook_referenced_post_list_item' ) ) {
	/**
	 * Print link to the specified reference post.
	 *
	 * @param int|WP_Post|null $referenced_post The referenced post.
	 */
	function labbook_referenced_post_list_item( $referenced_post = null ) {
		global $ssl_alp;

		$referenced_post = get_post( $referenced_post );

		if ( is_null( $referenced_post ) ) {
			// Post doesn't exist.
			return;
		}

		echo '<li>';

		// Post title.
		$post_title = $referenced_post->post_title;

		// Wrap URL.
		printf(
			'<a href="%1$s">%2$s</a>',
			esc_url( get_permalink( $referenced_post ) ),
			esc_html( $post_title )
		);

		// Post date (only used if post type supports it).
		if ( $ssl_alp->references->show_date( $referenced_post ) ) {
			echo '&nbsp;';
			printf(
				'<span class="post-date">%1$s</span>',
				esc_html( get_the_date( get_option( 'date_format' ), $referenced_post ) )
			);
		}

		echo '</li>';
	}
} // End if().

if ( ! function_exists( 'labbook_the_page_breadcrumbs' ) ) :
	/**
	 * Print page breadcrumbs.
	 *
	 * @param int|WP_Post|null $page Post ID or post object. Defaults to global $post.
	 */
	function labbook_the_page_breadcrumbs( $page = null ) {
		if ( ! is_page( $page ) ) {
			// Not a page.
			return;
		}

		if ( ! labbook_get_option( 'show_page_breadcrumbs' ) ) {
			// Display is unavailable.
			return;
		}

		$breadcrumbs = labbook_get_page_breadcrumbs( $page );

		if ( ! count( $breadcrumbs ) ) {
			return;
		}

		echo '<ul>';

		foreach ( $breadcrumbs as $breadcrumb ) {
			echo '<li>';

			if ( ! empty( $breadcrumb['url'] ) ) {
				printf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $breadcrumb['url'] ),
					esc_html( $breadcrumb['title'] )
				);
			} else {
				echo esc_html( $breadcrumb['title'] );
			}

			echo '</li>';
		}

		echo '</ul>';
	}
endif;

if ( ! function_exists( 'labbook_the_toc' ) ) :
	/**
	 * Print the table of contents.
	 *
	 * @param Labbook_TOC_Menu_Level $contents   The table of contents hierarchy.
	 * @param int                    $max_levels Maximum heading level to display.
	 */
	function labbook_the_toc( $contents, $max_levels ) {
		if ( ! is_int( $max_levels ) || $max_levels < 0 ) {
			// Invalid.
			return;
		}

		if ( empty( $contents ) ) {
			// Invalid.
			return;
		}

		$menu_data = $contents->get_menu_data();

		if ( is_array( $menu_data ) ) {
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_attr( '#' . $menu_data['id'] ),
				esc_html( $menu_data['title'] )
			);
		}

		if ( $max_levels > 0 ) {
			// Next level still visible - get children.
			$children = $contents->get_child_menus();

			if ( count( $children ) ) {
				echo '<ul>';

				foreach ( $children as $child ) {
					// Show sublevel.
					echo '<li>';
					labbook_the_toc( $child, $max_levels - 1 );
					echo '</li>';
				}

				echo '</ul>';
			}
		}
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_form' ) ) :
	/**
	 * Print the advanced search form.
	 */
	function labbook_the_advanced_search_form() {
		global $ssl_alp;

		if ( ! labbook_ssl_alp_advanced_search_enabled() ) {
			// Show standard search form.
			get_search_form();

			return;
		}

		printf(
			'<form role="search" method="get" id="advanced-search-form" class="advanced-search-form" action="%1$s">',
			esc_url( home_url( '/' ) )
		);

		echo '<div class="advanced-search hentry">';

		printf(
			'<h3>%1$s</h3>',
			esc_html__( 'Keywords', 'labbook' )
		);

		echo '<div>';

		printf(
			'<label class="screen-reader-text" for="s">%1$s</label>',
			esc_html_x( 'Search for:', 'label', 'labbook' )
		);

		printf(
			'<input type="text" value="%1$s" name="s" id="s" placeholder="%2$s" class="search-field" />',
			get_search_query(),
			esc_attr( __( 'Search...', 'labbook' ) )
		);

		printf(
			'<input type="submit" class="search-submit screen-reader-text" id="searchsubmit" value="%1$s" />',
			esc_attr_x( 'Search', 'submit button', 'labbook' )
		);

		echo '<p class="advanced-search-hint">';
		echo wp_kses(
			__( 'Matches words and phrases in titles, excerpts and content. Match exact phrases by wrapping them in double quotes, e.g. <code>"lab work"</code>. Exclude words by prepending hyphens, e.g. <code>-word</code>.', 'labbook' ),
			array(
				'code' => array(),
			)
		);
		echo '</p>';

		echo '</div>';

		printf(
			'<h3>%1$s</h3>',
			esc_html__( 'Publication date', 'labbook' )
		);

		labbook_the_advanced_search_date_fieldset();

		printf(
			'<h3>%1$s</h3>',
			esc_html__( 'Authors', 'labbook' )
		);

		labbook_the_advanced_search_author_filter_table();

		printf(
			'<h3>%1$s</h3>',
			esc_html__( 'Categories', 'labbook' )
		);

		labbook_the_advanced_search_category_filter_table();

		printf(
			'<h3>%1$s</h3>',
			esc_html__( 'Tags', 'labbook' )
		);

		labbook_the_advanced_search_tag_filter_table();

		echo '<p class="advanced-search-hint">';
		echo wp_kses(
			__( 'You can select multiple items from the lists above using <kbd>Ctrl</kbd>.', 'labbook' ),
			array(
				'kbd' => array(),
			)
		);
		echo '</p>';

		printf(
			'<input type="submit" value="%1$s"/>',
			esc_html__( 'Search', 'labbook' )
		);

		echo '</div>';
		echo '</form>';
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_dropdown' ) ) :
	/**
	 * Print advanced search select dropdown.
	 *
	 * @param string            $name     The select name.
	 * @param array             $items    The items to display. Keys are option values, values are text.
	 * @param string|array|null $selected The value(s) of the selected item(s), or null.
	 * @param bool              $blank    Include a blank entry at the start.
	 * @param bool|null         $multiple Multiple select mode.
	 * @param int|null          $size     Entry size.
	 */
	function labbook_the_advanced_search_dropdown( $name, $items, $selected = null, $blank = true, $multiple = null, $size = null ) {
		printf(
			'<select name="%1$s"%2$s%3$s>',
			$name,
			( true === $multiple ) ? ' multiple="true"' : '',
			( ! is_null( $size ) ) ? ' size="' . absint( $size ) . '"' : ''
		);

		if ( $blank ) {
			echo '<option value=""></option>';
		}

		foreach ( $items as $value => $item ) {
			$item_selected = false;

			if ( ! is_null( $selected ) ) {
				if ( is_array( $selected ) ) {
					if ( in_array( $value, $selected ) ) { // Fuzzy compare required.
						$item_selected = true;
					}
				} elseif ( $value == $selected ) { // Fuzzy compare required.
					$item_selected = true;
				}
			}

			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				$item_selected ? ' selected="true"' : '',
				esc_html( $item )
			);
		}

		echo '</select>';
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_term_multiselect' ) ) :
	/**
	 * Print advanced search term select list.
	 *
	 * @param string $name  The select name.
	 * @param array  $items The items to display. Keys are option values, values are text.
	 * @param array  $args  Extra arguments.
	 */
	function labbook_the_advanced_search_term_multiselect( $name, $items, $args = array() ) {
		$defaults = array(
			'name_field'     => 'name',
			'value_field'    => 'term_id',
			'value_callback' => null,
			'count_field'    => 'count',
			'count_callback' => null,
			'show_count'     => true,
			'selected'       => null,
			'multiple'       => true,
			'size'           => 10,
			'depth'          => 0, // No limit.
		);

		$args = wp_parse_args( $args, $defaults );

		$walker = new Labbook_Search_Term_Walker();

		printf(
			'<select name="%1$s"%2$s%3$s>',
			$name,
			( true === $args['multiple'] ) ? ' multiple="true"' : '',
			( ! is_null( $args['size'] ) ) ? ' size="' . absint( $args['size'] ) . '"' : ''
		);

		$depth = intval( $args['depth'] );

		// Remove used arguments.
		unset( $args['multiple'] );
		unset( $args['size'] );
		unset( $args['depth'] );

		// Create hierarchical list.
		echo $walker->walk( $items, $depth, $args );

		echo '</select>';
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_date_fieldset' ) ) :
	/**
	 * Print the advanced search date fieldset.
	 */
	function labbook_the_advanced_search_date_fieldset() {
		// Get oldest post to work out date ranges.
		$oldest_posts = get_posts(
			array(
				'numberposts' => 1,
				'order'       => 'ASC',
				'orderby'     => 'date',
			)
		);

		$current_year = absint( date( 'Y' ) );

		if ( ! empty( $oldest_posts ) ) {
			$oldest_year = absint( date( 'Y', strtotime( $oldest_posts[0]->post_date ) ) );
		} else {
			// No posts. Use current year.
			$oldest_year = $current_year;
		}

		// Date ranges, converted to string for ease of comparison.
		$year_range  = range( $oldest_year, $current_year );
		$month_range = range( 1, 12 );
		$day_range   = range( 1, 31 );

		// Make arrays with keys == values.
		$years = array_combine( $year_range, $year_range );
		$months = array_combine( $month_range, $month_range );
		$days = array_combine( $day_range, $day_range );

		// Selected dates.
		$selected_after_year   = get_query_var( 'ssl_alp_after_year' );
		$selected_after_month  = get_query_var( 'ssl_alp_after_month' );
		$selected_after_day    = get_query_var( 'ssl_alp_after_day' );
		$selected_before_year  = get_query_var( 'ssl_alp_before_year' );
		$selected_before_month = get_query_var( 'ssl_alp_before_month' );
		$selected_before_day   = get_query_var( 'ssl_alp_before_day' );

		echo '<fieldset class="advanced-search-date-range">';

		esc_html_e( 'From', 'labbook' );
		echo '&nbsp;';

		labbook_the_advanced_search_dropdown( 'ssl_alp_after_year', $years, $selected_after_year );
		labbook_the_advanced_search_dropdown( 'ssl_alp_after_month', $months, $selected_after_month );
		labbook_the_advanced_search_dropdown( 'ssl_alp_after_day', $days, $selected_after_day );

		echo '&nbsp;';
		esc_html_e( 'to', 'labbook' );
		echo '&nbsp;';

		labbook_the_advanced_search_dropdown( 'ssl_alp_before_year', $years, $selected_before_year );
		labbook_the_advanced_search_dropdown( 'ssl_alp_before_month', $months, $selected_before_month );
		labbook_the_advanced_search_dropdown( 'ssl_alp_before_day', $days, $selected_before_day );

		echo '</fieldset>';
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_author_filter_table' ) ) :
	/**
	 * Print advanced search author filter table.
	 */
	function labbook_the_advanced_search_author_filter_table() {
		global $ssl_alp;

		echo '<table class="advanced-search-criteria">';

		if ( labbook_ssl_alp_coauthors_enabled() ) {
			// Get users with coauthored posts.
			$authors = get_users(
				array(
					'order'   => 'ASC',
					'orderby' => 'display_name',
				)
			);

			// Remove users with zero post counts. This matches the behaviour of wp_list_authors.
			foreach ( (array) $authors as $id => $author ) {
				$post_count = $ssl_alp->coauthors->get_user_post_count( $author );

				if ( is_null( $post_count ) || 0 === absint( $post_count ) ) {
					unset( $authors[ $id ] );
				}
			}

			// Selected filter criteria.
			$selected_coauthor_and    = get_query_var( 'ssl_alp_coauthor__and', array() );
			$selected_coauthor_in     = get_query_var( 'ssl_alp_coauthor__in', array() );
			$selected_coauthor_not_in = get_query_var( 'ssl_alp_coauthor__not_in', array() );

			printf(
				'<tr><th>%1$s</th><th>%2$s</th><th>%3$s</th></tr>',
				esc_attr( 'Posts with all of these authors', 'labbook' ),
				esc_attr( 'Posts with any of these authors', 'labbook' ),
				esc_attr( 'Posts with none of these authors', 'labbook' )
			);

			echo '<tr>';
			echo '<td>';
			labbook_the_advanced_search_term_multiselect(
				'ssl_alp_coauthor__and[]',
				$authors,
				array(
					'name_field'     => 'display_name',
					'value_callback' => 'labbook_get_coauthor_term_id',
					'count_callback' => 'labbook_get_coauthor_post_count',
					'depth'          => -1, // Flat.
					'selected'       => $selected_coauthor_and,
				)
			);
			echo '</td>';
			echo '<td>';
			labbook_the_advanced_search_term_multiselect(
				'ssl_alp_coauthor__in[]',
				$authors,
				array(
					'name_field'     => 'display_name',
					'value_callback' => 'labbook_get_coauthor_term_id',
					'count_callback' => 'labbook_get_coauthor_post_count',
					'depth'          => -1, // Flat.
					'selected'       => $selected_coauthor_in,
				)
			);
			echo '</td>';
			echo '<td>';
			labbook_the_advanced_search_term_multiselect(
				'ssl_alp_coauthor__not_in[]',
				$authors,
				array(
					'name_field'     => 'display_name',
					'value_callback' => 'labbook_get_coauthor_term_id',
					'count_callback' => 'labbook_get_coauthor_post_count',
					'depth'          => -1, // Flat.
					'selected'       => $selected_coauthor_not_in,
				)
			);
			echo '</td>';
			echo '</tr>';
		} else {
			// Get users with published posts.
			$authors = get_users(
				array(
					'has_published_posts' => true,
					'order'               => 'ASC',
					'orderby'             => 'display_name',
				)
			);

			// Use core querystrings.
			$selected_author_in     = get_query_var( 'author__in', array() );
			$selected_author_not_in = get_query_var( 'author__not_in', array() );

			printf(
				'<tr><th>%1$s</th><th>%2$s</th></tr>',
				esc_attr( 'Posts with any of these authors', 'labbook' ),
				esc_attr( 'Posts with none of these authors', 'labbook' )
			);

			echo '<tr>';
			echo '<td>';
			labbook_the_advanced_search_term_multiselect(
				'author__in[]',
				$authors,
				array(
					'name_field'     => 'display_name',
					'value_field'    => 'ID',
					'count_field'    => 'labbook_get_author_post_count',
					'depth'          => -1, // Flat.
					'selected'       => $selected_author_in,
				)
			);
			echo '</td>';
			echo '<td>';
			labbook_the_advanced_search_term_multiselect(
				'author__not_in[]',
				$authors,
				array(
					'name_field'     => 'display_name',
					'value_field'    => 'ID',
					'count_field'    => 'labbook_get_author_post_count',
					'depth'          => -1, // Flat.
					'selected'       => $selected_author_not_in,
				)
			);
			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}
endif;

if ( ! function_exists( 'labbook_get_coauthor_term_id' ) ) :
	/**
	 * Get term ID for specified user.
	 *
	 * @param WP_User $user User object.
	 */
	function labbook_get_coauthor_term_id( $user ) {
		global $ssl_alp;

		$term = $ssl_alp->coauthors->get_coauthor_term( $user );

		return $term->term_id;
	}
endif;

if ( ! function_exists( 'labbook_get_coauthor_post_count' ) ) :
	/**
	 * Get coauthor post count.
	 *
	 * @param WP_User $user User object.
	 */
	function labbook_get_coauthor_post_count( $user ) {
		global $ssl_alp;

		return $ssl_alp->coauthors->get_user_post_count( $user );
	}
endif;

if ( ! function_exists( 'labbook_get_author_post_count' ) ) :
	/**
	 * Get author post count.
	 *
	 * @param WP_User $user User object.
	 */
	function labbook_get_author_post_count( $user ) {
		return count_user_posts( $user->ID );
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_category_filter_table' ) ) :
	/**
	 * Print advanced search category filter table.
	 */
	function labbook_the_advanced_search_category_filter_table() {
		echo '<table class="advanced-search-criteria">';

		$categories = get_categories();

		// Get term querystrings.
		$selected_category_and    = get_query_var( 'category__and', array() );
		$selected_category_in     = get_query_var( 'category__in', array() );
		$selected_category_not_in = get_query_var( 'category__not_in', array() );

		printf(
			'<tr><th>%1$s</th><th>%2$s</th><th>%3$s</th></tr>',
			esc_attr( 'Posts with all of these categories', 'labbook' ),
			esc_attr( 'Posts with any of these categories', 'labbook' ),
			esc_attr( 'Posts with none of these categories', 'labbook' )
		);

		echo '<tr>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'category__and[]',
			$categories,
			array(
				'selected' => $selected_category_and,
			)
		);
		echo '</td>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'category__in[]',
			$categories,
			array(
				'selected' => $selected_category_in,
			)
		);
		echo '</td>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'category__not_in[]',
			$categories,
			array(
				'selected' => $selected_category_not_in,
			)
		);
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}
endif;

if ( ! function_exists( 'labbook_the_advanced_search_tag_filter_table' ) ) :
	/**
	 * Print advanced search tag filter table.
	 */
	function labbook_the_advanced_search_tag_filter_table() {
		echo '<table class="advanced-search-criteria">';

		$tags = get_tags();

		// Get term querystrings.
		$selected_tag_and    = get_query_var( 'tag__and', array() );
		$selected_tag_in     = get_query_var( 'tag__in', array() );
		$selected_tag_not_in = get_query_var( 'tag__not_in', array() );

		printf(
			'<tr><th>%1$s</th><th>%2$s</th><th>%3$s</th></tr>',
			esc_attr( 'Posts with all of these tags', 'labbook' ),
			esc_attr( 'Posts with any of these tags', 'labbook' ),
			esc_attr( 'Posts with none of these tags', 'labbook' )
		);

		echo '<tr>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'tag__and[]',
			$tags,
			array(
				'selected' => $selected_tag_and,
			)
		);
		echo '</td>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'tag__in[]',
			$tags,
			array(
				'selected' => $selected_tag_in,
			)
		);
		echo '</td>';
		echo '<td>';
		labbook_the_advanced_search_term_multiselect(
			'tag__not_in[]',
			$tags,
			array(
				'selected' => $selected_tag_not_in,
			)
		);
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}
endif;
