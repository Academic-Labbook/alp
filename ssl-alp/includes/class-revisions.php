<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Post and page edit summary functionality.
 *
 * Edit summaries are stored as post meta on post revisions. In the block editor, when a new
 * revision is created (after clicking "Update"), some JavaScript calls out to a REST endpoint
 * defined here to set its edit summary.
 *
 * It is not possible to use WordPress's built-in support for setting post meta directly from the
 * block editor (avoiding the need for a custom REST endpoint), because it is only possible to set
 * post meta on the parent post itself, not revisions. This approach was previously attempted using
 * a hook on `updated_postmeta`, but this hook fired unreliably.
 */
class SSL_ALP_Revisions extends SSL_ALP_Module {
	protected static $edit_summary_max_length = 100;

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_edit_summaries',
			array(
				'type'		=>	'boolean'
			)
		);
	}

    /**
     * Register settings fields
     */
    public function register_settings_fields() {
        /**
		 * Post edit summary settings
		 */

        add_settings_field(
			'ssl_alp_edit_summary_settings',
			__( 'Revisions', 'ssl-alp' ),
			array( $this, 'revisions_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
    }

    public function revisions_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/revisions-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// register post meta for edit summaries
		$loader->add_action( 'init', $this, 'register_edit_summary_post_meta' );

		// register REST API endpoint for setting edit summaries with the block editor
		$loader->add_action( 'rest_api_init', $this, 'rest_register_edit_summary_route' );

		// add edit summary box to block editor
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'add_edit_summary_control' );

		/**
		 * Force all revisions to be saved.
		 *
		 * This avoids confusion with block editor edit summary not being cleared. In the future,
		 * we might also track when categories etc. are changed.
		 */
		$loader->add_filter( 'wp_save_post_revision_post_has_changed', $this, 'force_revision_creation', 10, 0 );

        // modify revision screen data to show edit summary
        $loader->add_filter( 'wp_prepare_revision_for_js', $this, 'prepare_revision_for_js', 10, 2 );

        // when restoring a revision, point the new revision to the source revision
        $loader->add_action( 'wp_restore_post_revision', $this, 'restore_post_revision_meta', 10, 2 );

		// register revisions widget
		$loader->add_action( 'widgets_init', $this, 'register_revisions_widget' );
	}

    /*
	 * Check if edit summaries are enabled for, and the user has permission to
	 * view, the specified post.
	 *
	 * @param int|WP_Post|null $post                  Post ID or post object. Defaults to global $post.
	 * @param bool             $check_edit_permission Only allow if user has edit permission for the post.
	 */
	public function edit_summary_allowed( $post, $check_edit_permission = true ) {
		// get post as an object, if not already one
		$post = get_post( $post );

		if ( $post->post_type == 'revision' ) {
			// this is a revision of another post type
			// check the parent post
			return $this->edit_summary_allowed( get_post( $post->post_parent ) );
		} elseif  ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// unsupported post type
			return false;
		}

		// Check if edit summaries are enabled.
		if ( ! get_option( "ssl_alp_enable_edit_summaries" ) ) {
			// Edit summaries disabled for posts.
			return false;
		}

		// Check if user has permission to edit the post, if we are to check this.
		if ( $check_edit_permission && ! current_user_can( "edit_{$post->post_type}", $post->ID ) ) {
			// No permission.
			return false;
		}

		return true;
	}

	/**
	 * Register post meta field for edit summaries
	 */
	public function register_edit_summary_post_meta() {
		// edit summary
		register_post_meta(
			'',
			'ssl_alp_edit_summary',
			array(
				'type'				=>	'string',
				'description'		=>	'Edit summary',
				'single'			=>	true,
				'sanitize_callback'	=>	array( $this, 'sanitize_edit_summary' ),
				'show_in_rest'		=>	false // edit summary submitted separately from parent post
			)
		);

		// revert post id
		register_post_meta(
			'',
			'ssl_alp_edit_summary_revert_id',
			array(
				'type'				=>	'integer',
				'description'		=>	'Post revert id',
				'single'			=>	true,
				'sanitize_callback'	=>	'absint',
				'show_in_rest'		=>	false
			)
		);
	}

	/**
	 * Sanitize the specified edit summary.
	 */
	public function sanitize_edit_summary( $edit_summary ) {
		if ( ! is_string( $edit_summary ) ) {
			// default to an empty string
			$edit_summary = "";
		}

		// strip tags
		$edit_summary = wp_kses( $edit_summary, wp_kses_allowed_html( 'strip' ) );

		// limit length
		$max = self::$edit_summary_max_length;

		if ( strlen( $edit_summary ) > $max ) {
			// trim extra characters beyond limit
			$edit_summary = substr( $edit_summary, 0, $max );
		}

		return $edit_summary;
	}

    /**
	 * Add edit summary field to the block editor
	 */
	public function add_edit_summary_control() {
		global $ssl_alp;

		// get post
		$post = get_post();

		if ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		}

		// enqueue block editor plugin script
		wp_enqueue_script(
			'ssl-alp-edit-summary-block-editor-js',
			SSL_ALP_BASE_URL . 'js/edit-summary/index.js',
			array(
				'wp-edit-post',
				'wp-plugins',
				'wp-i18n',
				'wp-element',
				'wp-compose'
			),
			$ssl_alp->get_version()
		);
	}

	public function force_revision_creation() {
		return true;
	}

	public function get_latest_revision( $post_id ) {
		$revisions = $this->get_revisions( $post_id, array(
			// default is to order by most recent
			'numberposts'	=>	1
		) );

		if ( empty( $revisions) ) {
			// no revisions found
			return;
		}

		// return first value
		return reset( $revisions );
	}

	/**
	 * Register REST API route for setting edit summary
	 */
	public function rest_register_edit_summary_route() {
		register_rest_route(
			SSL_ALP_REST_ROUTE,
			'/update-revision-meta',
			array(
				'methods'	=>	'POST',
				'callback'	=>	array( $this, 'rest_update_revision_meta' ),
				'args'		=>	array(
					'id'	=>	array(
						'required'			=> true,
						'validate_callback'	=> function( $param, $request, $key ) {
							return is_numeric( $param );
						},
						'sanitize_callback'	=> 'absint'
					),
					'key'	=>	array(
						'required'			=> true,
						'validate_callback'	=> array( $this, 'validate_revision_meta_key' )
					),
					'value'	=>	array(
						'required'			=> true,
						'sanitize_callback'	=> array( $this, 'sanitize_edit_summary' )
					)
				)
			)
		);
	}

	/**
	 * Set edit summary received via REST API
	 */
	public function rest_update_revision_meta( WP_REST_Request $data ) {
		if ( is_null( $data['id'] ) || is_null( $data['key'] ) || is_null( $data['value'] ) ) {
			// invalid
			return;
		}

		if ( 'ssl_alp_edit_summary' !== $data['key'] ) {
			// ignore
			return;
		}

		$revision_id = $data['id'];

		// get post
		$post = get_post( $revision_id );

		if ( wp_is_post_autosave( $post ) ) {
			return new WP_Error(
				'post_is_autosave',
				__( 'The specified post is an autosave, and therefore cannot have its edit summary set.', 'ssl-alp' ),
				array(
					'status'	=>	400 // bad request
				)
			);
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return new WP_Error(
				'post_cannot_read',
				__( 'Sorry, you are not allowed to edit this post.', 'ssl-alp' ),
				array(
					'status'	=>	rest_authorization_required_code()
				)
			);
		}

		$edit_summary = $data['value']; // sanitized already by REST endpoint callback

		// update the revision's edit summary
		update_metadata( 'post', $revision_id, 'ssl_alp_edit_summary', $edit_summary );
	}

	/**
	 * Validate that the key passed from REST to rest_update_revision_meta is valid.
	 */
	public function validate_revision_meta_key( $key ) {
		return 'ssl_alp_edit_summary' === $key;
	}

    /**
	 * Add edit summary to revision screen
	 */
	public function prepare_revision_for_js( $data, $revision ) {
		if ( ! $this->edit_summary_allowed( $revision ) ) {
			// return as-is
			return $data;
		}

		// get the stored meta values from the revision
		$revision_edit_summary = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$revision_edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		if ( empty( $revision_edit_summary ) && empty( $revision_edit_summary_revert_id) ) {
			// no edit summary to add
			return $data;
		}

		if ( !empty( $revision_edit_summary_revert_id ) ) {
			// revision post ID
			/* translators: 1: abbreviated revision id */
			$message = sprintf(
				esc_html__( 'reverted to r%1$s', 'ssl-alp' ),
				$revision_edit_summary_revert_id
			);

			// get original revision
			$source_revision = $this->get_source_revision( $revision_edit_summary_revert_id );

			if ( !empty( $source_revision ) ) {
				// get edit summary from that revision
				$source_edit_summary = get_post_meta( $source_revision->ID, 'ssl_alp_edit_summary', true );

				if ( !empty( $source_edit_summary ) ) {
					// add original message
					$message .= sprintf(
						/* translators: 1: revision message */
						__(' ("%1$s")', 'ssl-alp' ),
						esc_html( $source_edit_summary )
					);
				}
			}
		} else {
			// use this revision's edit summary
			$message = sprintf(
				/* translators: 1: revision message */
				__( '"%1$s"', 'ssl-alp' ),
				esc_html( $revision_edit_summary )
			);
		}

		/* translators: 1: edit summary */
		$data['timeAgo'] .= sprintf(
			__( ' — %1$s', 'ssl-alp' ),
			$message
		);

		return $data;
	}

	/**
	 * Get the edit summary for a given revert id. This will follow reverts
	 * recursively until the original is found.
	 */
	public function get_source_revision( $revision ) {
		$revision = wp_get_post_revision( $revision );

		if ( is_null( $revision ) ) {
			return;
		}

		$prior_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		if ( !empty( $prior_revert_id ) ) {
			return $this->get_source_revision( $prior_revert_id );
		}

		// we're at the original
		return $revision;
	}

    /**
	 * Restore the revision's meta values to the parent post.
	 *
	 * WordPress doesn't actually "restore" old revisions, it simply copies the
	 * fields into the parent and makes a new revision equal to the parent.
	 * This is fired after the copy has been made, but $revision_id still
	 * represents the original revision used as the source and not the newly
	 * created revision.
	 */
	public function restore_post_revision_meta( $post_id, $revision_id ) {
		// clear any existing meta on the parent post
		delete_post_meta( $post_id, 'ssl_alp_edit_summary' );
		delete_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id' );

		// get the revision created as part of the restoration (prior to this function firing)
		$latest_revision = $this->get_latest_revision( $post_id );

		if ( is_null( $latest_revision ) ) {
			// no new revision found
			return;
		}

	    /**
		 * Update new revision meta data.
		 *
		 * Clear any edit summaries present, and set its revert id to the source revision's id.
		 *
		 * Use the underlying update_meta() function instead of
		 * update_meta() to ensure metadata is updated on the revision post
		 * and not its parent.
		 */
		update_metadata( 'post', $latest_revision->ID, 'ssl_alp_edit_summary', '' );
		update_metadata( 'post', $latest_revision->ID, 'ssl_alp_edit_summary_revert_id', $revision_id );
	}

	/**
	 * Get post revisions.
	 *
	 * @param int|WP_Post|null $post 			  Post ID or post object. Defaults to global $post.
	 * @param array|null       $args 			  Arguments for retrieving post revisions.
	 * @param bool             $include_autosaves Include autosaves.
	 */
	public function get_revisions( $post = null, $args = null, $include_autosaves = false ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		// Get revisions in descending chronological order, regardless of whether they are enabled.
		$defaults = array( 'order' => 'DESC', 'orderby' => 'date ID', 'check_enabled' => false );
    	$args = wp_parse_args( $args, $defaults );
		$revisions = wp_get_post_revisions( $post, $args );

		if ( ! $include_autosaves ) {
			foreach ( $revisions as $index => $revision ) {
				if ( wp_is_post_autosave( $revision ) ) {
					// This is an autosave.
					unset( $revisions[ $index ] );
				}
			}
		}

		return $revisions;
	}

	/**
	 * Get number of edits made to post since it was published.
	 *
	 * @param int|WP_Post $post          The post.
	 * @param bool        $ignore_drafts Ignore draft versions and autogenerated revisions created
	 * 								     at the same time as the post.
	 */
	public function get_post_edit_count( $post, $ignore_drafts = true ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		if ( ! wp_revisions_enabled( $post ) ) {
			return;
		}

		// Get revisions (default descending date order).
		$revisions = $this->get_revisions( $post );

		// First guess.
		$edit_count = count( $revisions );

		if ( $edit_count > 0 && $ignore_drafts ) {
			// Latest revision.
			$latest_revision = reset( $revisions );

			// Published post publication date.
			$parent_publication_date = strtotime( $post->post_date );

			// Running draft revisions count.
			$draft_count = 0;

			// Loop in reverse order until we find the first published version.
			foreach ( array_reverse( $revisions ) as $revision ) {
				$draft_count++;

				if ( $parent_publication_date <= strtotime( $revision->post_date ) ) {
					// We've reached the published revision.
					break;
				}
			}

			// Subtract the draft and autogenerated publication revisions from the edit count.
			$edit_count -= $draft_count;
		}

		return $edit_count;
	}

	/**
	 * Check if the specified revision was autogenerated when its parent was published.
	 */
	public function revision_was_autogenerated_on_publication( $revision ) {
		$revision = get_post( $revision );

		if ( is_null( $revision ) ) {
			// Invalid.
			return;
		}

		if ( ! wp_is_post_revision( $revision ) ) {
			// Invalid.
			return;
		}

		if ( wp_is_post_autosave( $revision ) ) {
			return false;
		}

		$parent = get_post( $revision->post_parent );

		if ( is_null( $parent ) ) {
			// Invalid.
			return;
		}

		return $revision->post_date === $parent->post_date;
	}

	/**
	 * Register revisions widget
	 */
	public function register_revisions_widget() {
		register_widget( 'SSL_ALP_Widget_Revisions' );
	}

	/**
	 * Get recent revisions, grouped by author and post.
	 *
	 * Repeated edits made to posts by the same author are returned only once.
	 */
	public function get_recent_revisions( $number, $order = 'DESC' ) {
		global $wpdb;

		$order = ( strtoupper( $order ) == 'ASC' ) ? 'ASC' : 'DESC';
		$number = absint( $number );

		// get post types that support edit summaries, and filter for SQL
		$supported_post_types = get_post_types_by_support( 'revisions' );
		$supported_post_types = array_map( 'esc_sql', $supported_post_types );
		$supported_types_clause = '"' . implode( '", "', $supported_post_types ) . '"';

		/**
		 * Get last $number revisions (don't need parents) grouped by author and parent id, ordered
		 * by date descending, where number is > 1 if the revision was made by the original author
		 * (this prevents the original published post showing up as a revision), or > 0 if the
		 * revision was made by someone else.
		 *
		 * Note: `post_date` is the most recent revision found in each group.
		 */
		$object_ids = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT posts.post_author, posts.post_parent, MAX(posts.post_date) AS post_date,
					COUNT(1) AS number, parent_posts.post_author AS parent_author
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->posts} AS parent_posts ON posts.post_parent = parent_posts.ID
				WHERE
					posts.post_type = 'revision'
					AND posts.post_status = 'inherit'
					AND parent_posts.post_type IN ({$supported_types_clause})
					AND parent_posts.post_status = 'publish'
				GROUP BY posts.post_author, posts.post_parent
				HAVING (number > 1) OR (posts.post_author <> parent_posts.post_author)
				ORDER BY post_date {$order}
				LIMIT %d
				",
				$number
			)
		);

		return $object_ids;
	}

	/**
	 * Checks if the specified user can view the specified revisions
	 */
	public function current_user_can_view_revision( $revision ) {
		// taken from revision.php for viewing revisions
		return ( current_user_can( 'read_post', $revision->ID ) && current_user_can( 'edit_post', $revision->post_parent ) );
	}
}

class SSL_ALP_Widget_Revisions extends WP_Widget {
	const DEFAULT_NUMBER = 10;
	const DEFAULT_GROUP = true;

	public function __construct() {
		parent::__construct(
			'ssl-alp-revisions', // base ID
			esc_html__( 'Recent Revisions', 'ssl-alp' ), // name
			array(
				'description' => __( "Your site's most recent revisions", 'ssl-alp' )
			)
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		// default title
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Recent Revisions', 'ssl-alp' );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// number of revisions to display
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : self::DEFAULT_NUMBER;

		if ( ! $number ) {
			$number = self::DEFAULT_NUMBER;
		}

		// print revisions
		$this->the_revisions( $number );

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : self::DEFAULT_NUMBER;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of revisions to show:', 'ssl-alp' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" />
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number'] = absint( $new_instance['number'] );

		return $instance;
	}

	/**
	 * Print the revision list
	 */
	private function the_revisions( $number ) {
		$revisions = $this->get_revisions( $number );

		if ( ! count( $revisions ) ) {
			echo '<p>There are no revisions yet.</p>';
		} else {
			echo '<ul id="recent-revisions-list" class="list-unstyled">';

			foreach ( $revisions as $revision ) {
				// get the revision's parent
				$parent = get_post ( $revision->post_parent );

				// revision author
				$author = get_the_author_meta( 'display_name', $revision->post_author );

				// human revision date
				$post_date = sprintf(
					/* translators: 1: time ago */
					__( '%s ago', 'ssl-alp' ),
					human_time_diff( strtotime( $revision->post_date ) )
				);

				// title with URL, with human date on hover
				$post_title = sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					get_permalink( $parent->ID ),
					$post_date,
					esc_html( $parent->post_title )
				);

				printf(
					'<li class="recent-revision">%s on %s</li>',
					esc_html( $author ),
					$post_title
				);
			}

			echo '</ul>';
		}
	}

	/**
	 * Get revisions
	 */
	private function get_revisions( $number ) {
		global $ssl_alp;

		// pass through to main revisions class
		return $ssl_alp->revisions->get_recent_revisions( $number );
	}
}
