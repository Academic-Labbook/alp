<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Revision summary functionality
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

		// add edit summary box to block editor
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'add_edit_summary_control' );

		/**
		 * Save meta data into revisions.
		 *
		 * In Gutenberg, when an update to a post is made, the new revision is saved (see hook
		 * _wp_put_post_revision) before the post metadata is updated in the parent. That means we
		 * cannot simply intercept the new revision and store the latest meta data there. Instead,
		 * we hook into updated_postmeta which is fired after the parent's meta data is updated,
		 * then back-fill the data into the revision and delete it from the parent.
		 *
		 * NOTE: move_edit_summary_into_latest_revision removes and re-adds this action. If the
		 * priority or number of arguments is updated here, it must also be updated in the function
		 * body.
		 */
		$loader->add_action( 'updated_postmeta', $this, 'move_edit_summary_into_latest_revision', 10, 4 );

        // modify revision screen data
        $loader->add_filter( 'wp_prepare_revision_for_js', $this, 'prepare_revision_for_js', 10, 2 );

        // When restoring a revision, also restore that revisions's revisioned meta.
        $loader->add_action( 'wp_restore_post_revision', $this, 'restore_post_revision_meta', 10, 2 );

		// register revisions widget
		$loader->add_action( 'widgets_init', $this, 'register_revisions_widget' );
	}

    /*
	 * Check if edit summaries are enabled for, and the user has permission to
	 * view, the specified post.
	 */
	public function edit_summary_allowed( $post ) {
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

		// check if setting is enabled, and if user has permission
		// 'edit_post' capability == 'edit_posts', 'edit_page' == 'edit_pages', etc. (see wp-includes/capabilities.php)
		if ( ! get_option( "ssl_alp_enable_edit_summaries" ) || ! current_user_can( "edit_{$post->post_type}", $post->ID ) ) {
			// disabled for posts, or user not allowed to view
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
				'show_in_rest'		=>	true
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

		if ( $post->post_status == 'auto-draft' ) {
			// post is newly created, so don't show an edit summary box
			return;
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		}

		// enqueue block editor plugin script
		wp_enqueue_script(
			'ssl-alp-edit-summary-block-editor-js',
			SSL_ALP_BASE_URL . 'js/edit-summary/index.js',
			array( 'wp-edit-post', 'wp-plugins', 'wp-i18n', 'wp-element' ),
			$ssl_alp->get_version()
		);
	}

	/**
	 * Save meta data into revisions.
	 *
	 * In Gutenberg, when an update to a post is made, the new revision is saved (see hook
	 * _wp_put_post_revision) before the post metadata is updated in the parent. That means we
	 * cannot simply intercept the new revision and store the latest meta data there. Instead,
	 * we hook into updated_postmeta which is fired after the parent's meta data is updated,
	 * then back-fill the data into the revision and delete it from the parent.
	 *
	 * Note that this function is fired at least twice:
	 *   1. When Gutenberg sets the parent's edit summary, which fires this function via the
	 *      updated_postmeta action.
	 *   2. When this function sets the latest revision's meta value to the parent's edit summary,
	 *      before it empty's the parent's edit summary (in this case, the wp_is_post_revision
	 *      call prevents this function from emptying itself).
	 */
	public function move_edit_summary_into_latest_revision( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( $meta_key !== "ssl_alp_edit_summary" ) {
			// not the right meta key
			return;
		}

		$post = get_post( $post_id );

		if ( wp_is_post_revision( $post ) ) {
			// only fire on parents
			return;
		}

		// get latest revision
		$revision = $this->get_latest_revision( $post_id );

		if ( ! is_null( $revision ) ) {
			// revision found; check it matches current post
			if ( $post->post_content !== $revision->post_content ) {
				// revision was not created for the current post, but rather a previous one
				return;
			}

			// update revision meta
			// (use update_metadata to set revision's meta instead of parent's)
			update_metadata( 'post', $revision->ID, 'ssl_alp_edit_summary', $meta_value );
		}

		/**
		 * Delete edit summary from parent.
		 *
		 * This action has to be removed while making this update otherwise it will be called again.
		 *
		 * Note: don't delete the meta key, just make it empty, because Gutenberg expects it to
		 * exist and uses its (empty) value when showing the edit screen.
		 */
		remove_action( 'updated_postmeta', array( $this, 'move_edit_summary_into_latest_revision' ) );
		update_post_meta( $post_id, 'ssl_alp_edit_summary', '' );
		update_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id', 0 );
		add_action( 'updated_postmeta', array( $this, 'move_edit_summary_into_latest_revision' ), 10, 4 );
	}

	public function get_latest_revision( $post_id ) {
		$revisions = wp_get_post_revisions( $post_id, array(
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
		/**
		 * Clear parent post and latest revision edit summary.
		 *
		 * This is copied from the target revision by WordPress, but we don't
		 * want that.
		 *
		 * This implicitly uses `move_edit_summary_into_latest_revision` to do
		 * this, fired via the `updated_postmeta` hook.
		 */
		update_post_meta( $post_id, 'ssl_alp_edit_summary', '' );

		// get the revision created as part of the restoration (prior to this function firing)
		$latest_revision = $this->get_latest_revision( $post_id );

	    // set latest revision's revert id
		update_metadata( 'post', $latest_revision->ID, 'ssl_alp_edit_summary_revert_id', $revision_id );
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

		// get last $number revisions (don't need parents) grouped by author and parent id, ordered by date descending
		$object_ids = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT posts.post_author, posts.post_parent, MAX(posts.post_date) AS post_date
				FROM {$wpdb->posts} AS posts
				WHERE
					post_type = %s
					AND post_status = %s
					AND EXISTS(SELECT 1
						 FROM wp_posts
						 WHERE ID = posts.post_parent
						 AND post_type IN ({$supported_types_clause})
						 AND post_status = %s)
                GROUP BY posts.post_author, posts.post_parent
				ORDER BY post_date {$order}
				LIMIT %d
				",
				"revision",
				"inherit",
				"publish",
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
