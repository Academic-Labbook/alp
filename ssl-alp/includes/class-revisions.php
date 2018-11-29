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
 			'ssl_alp_enable_post_edit_summaries',
 			array(
 				'type'		=>	'boolean'
 			)
 		);

		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_page_edit_summaries',
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

		// register REST API endpoint for setting edit summaries
		$loader->add_action( 'rest_api_init', $this, 'register_edit_summary_rest_api_route' );

		// add edit summary box to block editor
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'add_edit_summary_control' );

        // add edit summary to revision history list under posts/pages/etc.
        $loader->add_filter( 'wp_post_revision_title_expanded', $this, 'add_revision_title_edit_summary', 10, 2 );
        // modify revision screen data
        $loader->add_filter( 'wp_prepare_revision_for_js', $this, 'prepare_revision_for_js', 10, 2 );

        // When restoring a revision, also restore that revisions's revisioned meta.
        $loader->add_action( 'wp_restore_post_revision', $this, 'restore_post_revision_meta', 10, 2 );
        // When creating a revision, also save any revisioned meta.
        $loader->add_action( '_wp_put_post_revision', $this, 'save_revisioned_meta_fields' );

        // When revisioned post meta has changed, trigger a revision save.
        $loader->add_filter( 'wp_save_post_revision_post_has_changed', $this, 'check_revisioned_meta_fields_have_changed', 10, 3 );

		// register revisions widget
		$loader->add_action( 'widgets_init', $this, 'register_revisions_widget' );
	}

    /*
	 * Check if edit summaries are enabled for, and the user has permission to
	 * view, the specified post.
	 */
	private function edit_summary_allowed( $post ) {
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
		if ( ! get_option( "ssl_alp_enable_{$post->post_type}_edit_summaries" ) || ! current_user_can( "edit_{$post->post_type}", $post->ID ) ) {
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
		register_meta(
			'post',
			'ssl_alp_edit_summary',
			array(
				'object_subtype'	=>	'post',
				'type'				=>	'string',
				'description'		=>	'Edit summary',
				'single'			=>	false,
				'sanitize_callback'	=>	array( $this, 'sanitize_edit_summary' ),
				'show_in_rest'		=>	true
			)
		);

		// revert post id
		register_meta(
			'post',
			'ssl_alp_edit_summary_revert_id',
			array(
				'object_subtype'	=>	'post',
				'type'				=>	'integer',
				'description'		=>	'Post revert id',
				'single'			=>	false,
				'sanitize_callback'	=>	'absint',
				'show_in_rest'		=>	true
			)
		);
	}

	/**
	 * Register REST API route for setting edit summary
	 */
	public function register_edit_summary_rest_api_route() {
		register_rest_route(
			SSL_ALP_REST_ROUTE,
			'/update-meta',
			array(
				'methods'	=>	'POST',
				'callback'	=>	array( $this, 'set_edit_summary' ),
				'args'		=>	array(
					'id'	=>	array(
						'sanitize_callback'	=>	'absint'
					)
				)
			)
		);
	}

	/**
	 * Set edit summary received via REST API
	 */
	public function set_edit_summary( WP_REST_Request $data ) {
		if ( is_null( $data['id'] ) || is_null( $data['key'] ) || is_null( $data['value'] ) ) {
			// invalid
			return;
		}

		if ( 'ssl_alp_edit_summary' !== $data['key'] ) {
			// not edit summary
			return;
		}

		$revision_id = $data['id'];

		// get post
		$post = get_post( $revision_id );
		
		// skip when autosaving, as custom post data is noted included in $_POST during autosaves (annoying)
		if ( wp_is_post_autosave( $post ) ) {
			return;
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		}

		$edit_summary = $this->sanitize_edit_summary( $data['value'] );

		// update the post's edit summary
		update_metadata( 'post', $revision_id, 'ssl_alp_edit_summary', $edit_summary );
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

    /*
	 * Inject edit summary into revision author/date listings
	 */
	public function add_revision_title_edit_summary( $revision_date_author, $revision ) {
		if ( ! is_admin() ) {
			return $revision_date_author;
		}

		$screen = get_current_screen();

		if ( $screen->base !== 'post' ) {
			return $text;
		}

		if ( ! $this->edit_summary_allowed( $revision ) ) {
			// return as-is
			return $revision_date_author;
		}

		// get the stored meta values from the revision
		$revision_edit_summary = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$revision_edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		if ( empty( $revision_edit_summary ) || ! is_string( $revision_edit_summary ) ) {
			// empty or invalid
			return $revision_date_author;
		}

		if ( ! empty( $revision_edit_summary_reverted ) && is_int( $revision_edit_summary_revert_id ) ) {
			// this revision was a revert to previous revision
			/* translators: %s: revision URL; %d: revision ID */
			$revision_date_author .= __( ', reverted to <a href="%s">%d</a>', 'ssl-alp' );

			// get reverted revision URL
			$revision_url = get_edit_post_link( $revision_edit_summary_revert_id );

			// add URL
			$revision_date_author = sprintf( $revision_date_author, $revision_url, $revision_edit_summary_revert_id );
		}

		// add message
		$revision_message = sanitize_text_field( $revision_edit_summary );

		if ( ! empty( $revision_message ) ) {
			/* translators: %s: revision edit summary */
			$revision_date_author .= " &mdash; " . sprintf( __( "<em>\"%s\"</em>", 'ssl-alp' ), $revision_message );
		}

		return $revision_date_author;
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

		if ( empty( $revision_edit_summary ) || ! is_string( $revision_edit_summary ) ) {
			// empty or invalid
			return $data;
		}

		if ( ! empty( $revision_edit_summary_reverted ) && is_int( $revision_edit_summary_revert_id ) ) {
			// this revision was a revert to previous revision
			/* translators: %d: revision ID */
			$data['timeAgo'] .= __( ', reverted to %d', 'ssl-alp' );

			// add reverted ID
			$data['timeAgo'] = sprintf( $data['timeAgo'], $revision_edit_summary_revert_id );
		}

		// add message
		$edit_summary = sanitize_text_field( $revision_edit_summary );

		/* translators: 1: time ago; 2: edit summary */
		$data['timeAgo'] = sprintf( __( '%1$s — "%2$s"', 'ssl-alp' ), $data['timeAgo'], $edit_summary );

		return $data;
	}

    /**
	 * Restore the revision's meta values to the parent post.
	 */
	public function restore_post_revision_meta( $post_id, $revision_id ) {
		// Clear any existing metas
		delete_post_meta( $post_id, 'ssl_alp_edit_summary' );
		delete_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id' );

		// get the stored meta value from the revision we are reverting to
		$target_revision_edit_summary = get_post_meta( $revision_id, 'ssl_alp_edit_summary', true );
		$target_revision_edit_summary_revert_id = get_post_meta( $revision_id, 'ssl_alp_edit_summary_revert_id', true );

		// add revision's meta value to parent
		add_post_meta( $post_id, 'ssl_alp_edit_summary', $target_revision_edit_summary );
		add_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id', $target_revision_edit_summary_revert_id );

		// get latest revisions, so we can change the latest's revision flag
		$latest_revisions = wp_get_post_revisions( $post_id,
		 	array(
				"numberposts"	=>	1,
				"order"			=>	"DESC",
				"orderby"		=>	"date ID"

			)
		);

		// get latest revision
		$latest_revision_meta = array_shift( $latest_revisions );

	   /*
		* Update meta data
		*
		* Use the underlying update_meta() function instead of
		* update_meta() to ensure metadata is updated on the revision post
		* and not its parent.
		*/
		update_metadata( 'post', $latest_revision_meta->ID, 'ssl_alp_edit_summary', $target_revision_edit_summary );
		update_metadata( 'post', $latest_revision_meta->ID, 'ssl_alp_edit_summary_revert_id', $revision_id );
	}

    /**
	 * Save the parent's meta fields to the revision.
	 *
	 * This should run when a post is created/updated, and WordPress creates the
	 * revision that contains the new/updated post data. This function takes the
	 * custom meta data inserted into the new/updated post, and copies it into
	 * the new revision as well.
	 */
	public function save_revisioned_meta_fields( $revision_id ) {
		// skip when autosaving, as custom post data is noted included in $_POST during autosaves (annoying)
		if ( wp_is_post_autosave( $revision_id ) ) {
			return;
		}

		$revision = get_post( $revision_id );

		if ( ! $this->edit_summary_allowed( $revision ) ) {
			return;
		}

		$post_id = $revision->post_parent;

		// save revisioned meta field
		// get edit summary from revision's parent
		$edit_summary = get_post_meta( $post_id, 'ssl_alp_edit_summary', true );
		$edit_summary_revert_id = get_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id', true );

		if ( empty( $edit_summary ) || ! is_string( $edit_summary ) ) {
			// empty or invalid
			return;
		}

	   /*
		* Add parent's custom meta data to revision
		*
		* Use the underlying add_metadata() function instead of
		* add_post_meta() to ensure metadata is added to the revision post
		* and not its parent.
		*/
		add_metadata( 'post', $revision_id, 'ssl_alp_edit_summary', $edit_summary );
		add_metadata( 'post', $revision_id, 'ssl_alp_edit_summary_revert_id', $edit_summary_revert_id );
	}

    /**
	 * Check whether revisioned post meta fields have changed.
	 */
	public function check_revisioned_meta_fields_have_changed( $post_has_changed, WP_Post $last_revision, WP_Post $post ) {
		// skip when autosaving, as custom post data is annoyingly not included in $_POST during autosaves
		if ( wp_is_post_autosave( $post ) ) {
			return $post_has_changed;
		}

		// get parent post meta
		$parent_edit_summary = get_post_meta( $post->ID, 'ssl_alp_edit_summary' );

		// get revision meta
		$revision_edit_summary = get_post_meta( $last_revision->ID, 'ssl_alp_edit_summary' );

		if ( empty( $parent_edit_summary ) || empty( $revision_edit_summary ) ) {
			// invalid
		} elseif ( ! is_string( $parent_edit_summary ) || ! is_string( $revision_edit_summary ) ) {
			// invalid
		} else {
			// check if message has changed
			if ( $parent_edit_summary !== $revision_edit_summary ) {
				$post_has_changed = true;
			}
		}

		return $post_has_changed;
	}

	/**
	 * Register revisions widget
	 */
	public function register_revisions_widget() {
		register_widget( 'SSL_ALP_Widget_Revisions' );
	}

	/**
	 * Get revisions, optionally grouping by object
	 */
	public function get_recent_revisions( $number, $order = 'DESC' ) {
		global $wpdb;

		$order = ( strtoupper( $order ) == 'ASC' ) ? 'ASC' : 'DESC';
		$number = absint( $number );

		// get post types that support edit summaries, and filter for SQL
		$supported_post_types = get_post_types_by_support( 'revisions' );
		$supported_post_types = array_map( 'esc_sql', $supported_post_types );
		$supported_types_clause = '"' . implode( '", "', $supported_post_types ) . '"';

		// get last $number revisions (don't need parents) grouped by parent id, ordered by date descending
		$object_ids = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT posts.post_author, posts.post_parent, MAX(posts.post_date) AS post_date, COUNT(1) - 1 AS repeats
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
				// check if there are extra revisions from this author for this post
				if ( $revision->repeats > 0 ) {
					$extra_revisions = sprintf(
						' (<span title="%s">+%s</span>)',
						/* translators: %s: number of additional revisions made by this author */
						sprintf( _n( '%s additional edit', '%s additional edits', $revision->repeats, 'ssl-alp' ), number_format_i18n( $revision->repeats ) ),
						number_format_i18n( $revision->repeats )
					);
				} else {
					$extra_revisions = "";
				}

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
					'<li class="recent-revision">%s on %s%s</li>',
					$author,
					$post_title,
					$extra_revisions,
					$post_date
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
