<?php
/**
 * Post revision tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
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
	/**
	 * Maximum edit summary length.
	 *
	 * @var int
	 */
	protected static $edit_summary_max_length = 100;

	/**
	 * Unread flag term slug prefix.
	 *
	 * @var string
	 */
	protected static $unread_flag_term_slug_prefix = 'ssl-alp-unread-flag-';

	/**
	 * Supported post types for unread flags.
	 *
	 * @var array
	 */
	protected static $supported_unread_flag_post_types = array(
		'post',
		'ssl_alp_inventory',
	);

	/**
	 * Revisions list table.
	 *
	 * @var SSL_ALP_Revisions_List_Table
	 */
	protected $revisions_list_table;

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Enable edit summaries.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_edit_summaries',
			array(
				'type' => 'boolean',
			)
		);

		// Flag unread posts.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_flag_unread_posts',
			array(
				'type' => 'boolean',
			)
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings_fields() {
		/**
		 * Post edit summary settings.
		 */
		add_settings_field(
			'ssl_alp_edit_summary_settings',
			__( 'Revisions', 'ssl-alp' ),
			array( $this, 'revisions_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
	}

	/**
	 * Revisions settings partial.
	 */
	public function revisions_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/revisions-settings-display.php';
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		/**
		 * Revision support.
		 */

		// Register post meta for edit summaries.
		$loader->add_action( 'init', $this, 'register_edit_summary_post_meta' );

		// Register REST API endpoint for setting edit summaries with the block editor.
		$loader->add_action( 'rest_api_init', $this, 'rest_register_edit_summary_route' );

		// Add edit summary box to block editor.
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'add_edit_summary_control' );

		// Force all revisions to be saved.
		// This avoids confusion with block editor edit summary not being
		// cleared. In the future, we might also track when categories etc. are
		// changed.
		$loader->add_filter( 'wp_save_post_revision_post_has_changed', $this, 'force_revision_creation', 10, 0 );

		// Modify revision screen data to show edit summary.
		$loader->add_filter( 'wp_prepare_revision_for_js', $this, 'prepare_revision_for_js', 10, 2 );

		// When restoring a revision, point the new revision to the source revision.
		$loader->add_action( 'wp_restore_post_revision', $this, 'restore_post_revision_meta', 10, 2 );

		// Add admin revisions tables.
		$loader->add_action( 'admin_menu', $this, 'add_revisions_page' );

		// Save admin revisions table per page option.
		$loader->add_filter( 'set-screen-option', $this, 'save_revisions_per_page_option', 10, 3 );

		/**
		 * Revisions widget.
		 */

		// Register revisions widget.
		$loader->add_action( 'widgets_init', $this, 'register_revisions_widget' );

		/**
		 * Unread flag support.
		 */

		// Register taxonomy for unread flags.
		$loader->add_action( 'init', $this, 'register_unread_flag_taxonomy' );

		// Add rewrite rule to page with unread posts.
		$loader->add_action( 'init', $this, 'register_unread_post_rewrite_rules' );

		// Remove adjacent links in HTML head section, which can set prefetched posts as unread.
		$loader->add_action( 'init', $this, 'remove_adjacent_links_action' );

		// Add link to unread posts on toolbar.
		$loader->add_action( 'wp_before_admin_bar_render', $this, 'add_unread_posts_admin_bar_link' );

		// Set unread posts page user depending on query, and check permissions.
		$loader->add_action( 'pre_get_posts', $this, 'set_unread_posts_archive_page_user' );

		// Register REST API endpoints for unread flags.
		$loader->add_action( 'rest_api_init', $this, 'rest_register_unread_flag_routes' );

		// Set post to read when showing single post.
		$loader->add_action( 'the_post', $this, 'mark_post_as_read', 10, 1 );

		// Mark created/edited posts as unread for everyone except the author.
		$loader->add_action( 'post_updated', $this, 'mark_post_as_unread_for_users_after_update', 10, 3 );

		// Add "Unread" filter to admin post list.
		$loader->add_filter( 'views_edit-post', $this, 'filter_edit_post_views', 10, 1 );

		// Set the unread posts archive page name to the user's display name.
		$loader->add_filter( 'single_term_title', $this, 'set_unread_post_archive_title' );

		// Add bulk actions to mark posts as read/unread.
		$loader->add_filter( 'bulk_actions-edit-post', $this, 'register_read_unread_bulk_actions' );
		$loader->add_filter( 'handle_bulk_actions-edit-post', $this, 'handle_read_unread_bulk_actions', 10, 3 );
		$loader->add_action( 'admin_notices', $this, 'read_unread_admin_notice' );
	}

	/**
	 * Check if edit summaries are enabled for, and the user has permission to
	 * view, the specified post.
	 *
	 * @param int|WP_Post|null $post                  Post ID or post object. Defaults to global $post.
	 * @param bool             $check_edit_permission Only allow if user has edit permission for the post.
	 */
	public function edit_summary_allowed( $post = null, $check_edit_permission = true ) {
		// Get post as an object, if not already one.
		$post = get_post( $post );

		if ( 'revision' === $post->post_type ) {
			// This is a revision (and not necessarily correct post type) - check the parent post.
			return $this->edit_summary_allowed( get_post( $post->post_parent ) );
		} elseif ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			// Unsupported post type.
			return false;
		}

		// Check if edit summaries are enabled.
		if ( ! get_option( 'ssl_alp_enable_edit_summaries' ) ) {
			// Edit summaries disabled for posts.
			return false;
		}

		// Check if user has permission to edit the post, if we are to check this.
		$post_type = get_post_type_object( $post->post_type );
		if ( $check_edit_permission && ! current_user_can( "edit_{$post_type->capability_type}", $post->ID ) ) {
			// No permission.
			return false;
		}

		return true;
	}

	/**
	 * Get revision edit summary.
	 *
	 * @param int|WP_Post $revision The revision.
	 * @return array|null Array containing edit summary (or null if specified revision is invalid),
	 *                    the revert source post ID, and the revert source comment.
	 */
	public function get_revision_edit_summary( $revision ) {
		// Get revision object if id is specified.
		$revision = wp_get_post_revision( $revision );

		if ( is_null( $revision ) ) {
			return;
		}

		if ( 'revision' !== $revision->post_type ) {
			return;
		}

		// Get revision's edit summary.
		$edit_summary = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		$source_edit_summary = null;

		if ( ! empty( $edit_summary_revert_id ) ) {
			// Get original source revision.
			$source_revision = $this->get_source_revision( $revision );
			$source_edit_summary = get_post_meta( $source_revision->ID, 'ssl_alp_edit_summary', true );
		}

		return array(
			'summary'        => $edit_summary,
			'revert_id'      => $edit_summary_revert_id,
			'source_summary' => $source_edit_summary,
		);
	}

	/**
	 * Register post meta field for edit summaries
	 */
	public function register_edit_summary_post_meta() {
		// Edit summary.
		register_post_meta(
			'',
			'ssl_alp_edit_summary',
			array(
				'type'              => 'string',
				'description'       => __( 'Edit summary', 'ssl-alp' ),
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_edit_summary' ),
				'show_in_rest'      => false, // Edit summary submitted separately from parent post.
			)
		);

		// Revert target post ID.
		register_post_meta(
			'',
			'ssl_alp_edit_summary_revert_id',
			array(
				'type'              => 'integer',
				'description'       => __( 'Post revert id', 'ssl-alp' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize the specified edit summary.
	 *
	 * @param string $edit_summary Edit summary to sanitize.
	 */
	public function sanitize_edit_summary( $edit_summary ) {
		if ( ! is_string( $edit_summary ) ) {
			// Default to an empty string.
			$edit_summary = '';
		}

		// Strip tags.
		$edit_summary = wp_kses( $edit_summary, wp_kses_allowed_html( 'strip' ) );

		// Limit length.
		$max = self::$edit_summary_max_length;

		if ( strlen( $edit_summary ) > $max ) {
			// Trim extra characters beyond limit.
			$edit_summary = substr( $edit_summary, 0, $max );
		}

		return $edit_summary;
	}

	/**
	 * Add edit summary field to the block editor.
	 *
	 * @global $ssl_alp
	 */
	public function add_edit_summary_control() {
		global $ssl_alp;

		// Get post being edited.
		$post = get_post();

		if ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		}

		// Enqueue block editor plugin script.
		wp_enqueue_script(
			'ssl-alp-edit-summary-block-editor-js',
			SSL_ALP_BASE_URL . 'js/edit-summary/index.js',
			array(
				'wp-edit-post',
				'wp-plugins',
				'wp-i18n',
				'wp-element',
				'wp-compose',
			),
			$ssl_alp->get_version(),
			true
		);
	}

	/**
	 * Force revisions to be created whenever a post is updated, no matter what has changed.
	 */
	public function force_revision_creation() {
		return true;
	}

	/**
	 * Get latest revision for post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @return WP_Post|null The latest revision or null if no revision found.
	 */
	public function get_latest_revision( $post ) {
		if ( wp_is_post_revision( $post ) ) {
			$post = $post->post_parent;
		}

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		$revisions = $this->get_revisions(
			$post,
			array(
				// Default is to order by most recent.
				'numberposts' => 1,
			)
		);

		if ( empty( $revisions ) ) {
			// No revisions found.
			return;
		}

		// Return first value.
		return reset( $revisions );
	}

	/**
	 * Register REST API route for setting edit summary.
	 */
	public function rest_register_edit_summary_route() {
		register_rest_route(
			SSL_ALP_REST_ROUTE,
			'/update-revision-meta',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'rest_update_revision_meta' ),
				'args'     => array(
					'post_id' => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
						'sanitize_callback' => 'absint',
					),
					'key'     => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_revision_meta_key' ),
					),
					'value'   => array(
						'required'          => true,
						'sanitize_callback' => array( $this, 'sanitize_edit_summary' ),
					),
				),
			)
		);
	}

	/**
	 * Set edit summary received via REST API.
	 *
	 * @param WP_REST_Request $data REST request data.
	 * @return WP_Error|null Null if ok, or error if post is an autosave or the user lacks
	 *                       permission to edit it.
	 */
	public function rest_update_revision_meta( WP_REST_Request $data ) {
		if ( is_null( $data['post_id'] ) || is_null( $data['key'] ) || is_null( $data['value'] ) ) {
			// Invalid data.
			return;
		}

		if ( 'ssl_alp_edit_summary' !== $data['key'] ) {
			// Key is incorrect - ignore.
			return;
		}

		$revision_id = $data['post_id'];

		// Get revision.
		$post = get_post( $revision_id );

		if ( is_null( $post ) ) {
			return;
		} elseif ( wp_is_post_autosave( $post ) ) {
			return $this->update_revision_meta_post_is_autosave_error();
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return $this->update_revision_meta_no_permission_error();
		}

		$edit_summary = $data['value']; // Sanitized already by REST endpoint callback.

		// Update the revision's edit summary.
		update_metadata( 'post', $revision_id, 'ssl_alp_edit_summary', $edit_summary );
	}

	/**
	 * Validate that the key passed from REST to rest_update_revision_meta is valid.
	 *
	 * @param string $key Edit summary key.
	 * @return bool
	 */
	public function validate_revision_meta_key( $key ) {
		return 'ssl_alp_edit_summary' === $key;
	}

	/**
	 * Post is autosave error.
	 */
	public function update_revision_meta_post_is_autosave_error() {
		return new WP_Error(
			'post_is_autosave',
			__( 'The specified post is an autosave, and therefore cannot have its edit summary set.', 'ssl-alp' ),
			array(
				'status' => 400, // Bad request.
			)
		);
	}

	/**
	 * No permission error.
	 */
	public function update_revision_meta_no_permission_error() {
		return new WP_Error(
			'post_cannot_read',
			__( 'Sorry, you are not allowed to edit this post.', 'ssl-alp' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Add edit summary to revision screen.
	 *
	 * @param array   $data     Revision data.
	 * @param WP_Post $revision Revision object.
	 * @return array Revision data with edit summary added.
	 */
	public function prepare_revision_for_js( $data, $revision ) {
		if ( ! $this->edit_summary_allowed( $revision ) ) {
			return $data;
		}

		// Get the stored meta values from the revision.
		$revision_edit_summary           = get_post_meta( $revision->ID, 'ssl_alp_edit_summary', true );
		$revision_edit_summary_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		if ( empty( $revision_edit_summary ) && empty( $revision_edit_summary_revert_id ) ) {
			// No edit summary to add.
			return $data;
		}

		if ( ! empty( $revision_edit_summary_revert_id ) ) {
			// Revision post ID.
			$message = sprintf(
				/* translators: 1: abbreviated revision ID */
				esc_html__( 'reverted to r%1$s', 'ssl-alp' ),
				$revision_edit_summary_revert_id
			);

			// Get original revision.
			$source_revision = $this->get_source_revision( $revision_edit_summary_revert_id );

			if ( ! empty( $source_revision ) ) {
				// Get edit summary from that revision.
				$source_edit_summary = get_post_meta( $source_revision->ID, 'ssl_alp_edit_summary', true );

				if ( ! empty( $source_edit_summary ) ) {
					// Add original message.
					$message .= sprintf(
						/* translators: 1: revision message */
						__( ' ("%1$s")', 'ssl-alp' ),
						esc_html( $source_edit_summary )
					);
				}
			}
		} else {
			// Use this revision's edit summary.
			$message = sprintf(
				/* translators: 1: revision message */
				__( '"%1$s"', 'ssl-alp' ),
				esc_html( $revision_edit_summary )
			);
		}

		$data['timeAgo'] .= sprintf(
			/* translators: 1: edit summary */
			__( ' — %1$s', 'ssl-alp' ),
			$message
		);

		return $data;
	}

	/**
	 * Get the edit summary for a given revert ID. This will follow reverts
	 * recursively until the original is found.
	 *
	 * @param int|WP_Post $revision Revision to get edit summary for.
	 * @return string|null Edit summary or null if revision is invalid.
	 */
	public function get_source_revision( $revision ) {
		$revision = wp_get_post_revision( $revision );

		if ( is_null( $revision ) ) {
			return;
		}

		$prior_revert_id = get_post_meta( $revision->ID, 'ssl_alp_edit_summary_revert_id', true );

		if ( ! empty( $prior_revert_id ) ) {
			return $this->get_source_revision( $prior_revert_id );
		}

		// We're at the original.
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
	 *
	 * @param int $post_id     Parent post ID.
	 * @param int $revision_id Revision ID.
	 */
	public function restore_post_revision_meta( $post_id, $revision_id ) {
		// Clear any existing meta on the parent post.
		delete_post_meta( $post_id, 'ssl_alp_edit_summary' );
		delete_post_meta( $post_id, 'ssl_alp_edit_summary_revert_id' );

		// Get the revision created as part of the restoration (prior to this function firing).
		$latest_revision = $this->get_latest_revision( $post_id );

		if ( is_null( $latest_revision ) ) {
			// No new revision found.
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
	 * @param int|WP_Post|null $post                 Post ID or post object. Defaults to global
	 *                                               $post.
	 * @param array|null       $args                 Arguments for retrieving post revisions.
	 * @param bool             $include_autosaves    Include autosave revisions.
	 * @param bool             $only_since_published Only retrieve revisions since publication
	 *                                               (inclusive).
	 */
	public function get_revisions( $post = null, $args = null, $include_autosaves = false, $only_since_published = true ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		// Get revisions in descending chronological order, regardless of whether they are enabled.
		$defaults = array(
			'order'         => 'DESC',
			'orderby'       => 'date ID',
			'check_enabled' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( $only_since_published ) {
			// Add date query.
			$args = wp_parse_args(
				array(
					'date_query' => array(
						'after'     => $post->post_date,
						'inclusive' => true, // Include autogenerated revision on publication.
					),
				),
				$args
			);
		}

		$revisions = wp_get_post_revisions( $post, $args );

		if ( ! $include_autosaves ) {
			// Remove autosaves.
			foreach ( $revisions as $key => $revision ) {
				if ( wp_is_post_autosave( $revision ) ) {
					unset( $revisions[ $key ] );
				}
			}
		}

		return $revisions;
	}

	/**
	 * Get number of edits made to post since it was published.
	 *
	 * @param int|WP_Post $post                 The post.
	 * @param bool        $ignore_autogenerated Ignore drafts, autosaves and autogenerated revisions
	 *                                          created at the same time as the post.
	 */
	public function get_post_edit_count( $post, $ignore_autogenerated = true ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		if ( ! wp_revisions_enabled( $post ) ) {
			return;
		}

		// Get revisions.
		$revisions = $this->get_revisions( $post );

		// First guess.
		$edit_count = count( $revisions );

		if ( $edit_count > 0 && $ignore_autogenerated ) {
			// Latest revision.
			$latest_revision = reset( $revisions );

			// Published post publication date.
			$parent_publication_date = strtotime( $post->post_date );

			// Running draft revisions count.
			$ignore_count = 0;

			// Loop in reverse order until we find the first published version.
			foreach ( array_reverse( $revisions ) as $revision ) {
				if ( strtotime( $revision->post_date ) > $parent_publication_date ) {
					// This revision was made after publication.
					if ( wp_is_post_autosave( $revision ) ) {
						// This is an autosave.
						$ignore_count++;
					}
				} else {
					$ignore_count++;
				}
			}

			// Subtract the draft and autogenerated publication revisions from the edit count.
			$edit_count -= $ignore_count;
		}

		return $edit_count;
	}

	/**
	 * Check if the specified revision was autogenerated when its parent was published.
	 *
	 * @param int|WP_Post $revision Revision object.
	 * @return bool
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
	 * Add revisions pages to admin menu.
	 */
	public function add_revisions_page() {
		$post_hook_suffix = add_posts_page(
			__( 'Revisions', 'ssl-alp' ),
			__( 'Revisions', 'ssl-alp' ),
			'read',
			SSL_ALP_POST_REVISIONS_MENU_SLUG,
			array( $this, 'output_admin_revisions_page' )
		);

		$page_hook_suffix = add_pages_page(
			__( 'Revisions', 'ssl-alp' ),
			__( 'Revisions', 'ssl-alp' ),
			'read',
			SSL_ALP_PAGE_REVISIONS_MENU_SLUG,
			array( $this, 'output_admin_revisions_page' )
		);

		if ( $post_hook_suffix ) {
			// Add callback for loading the page.
			add_action( "load-{$post_hook_suffix}", array( $this, 'load_post_revisions_page_screen_options' ) );
		}

		if ( $page_hook_suffix ) {
			// Add callback for loading the page.
			add_action( "load-{$page_hook_suffix}", array( $this, 'load_page_revisions_page_screen_options' ) );
		}
	}

	/**
	 * Load post revisions page screen options.
	 */
	public function load_post_revisions_page_screen_options() {
		return $this->load_revisions_page_screen_options( 'post' );
	}

	/**
	 * Load page revisions page screen options.
	 */
	public function load_page_revisions_page_screen_options() {
		return $this->load_revisions_page_screen_options( 'page' );
	}

	/**
	 * Load revisions page screen options.
	 *
	 * @param string $post_type Post type. Either 'post' or 'page'.
	 */
	private function load_revisions_page_screen_options( $post_type ) {
		$arguments = array(
			'label'   => __( 'Revisions Per Page', 'ssl-alp' ),
			'default' => 20,
			'option'  => 'ssl_alp_revisions_per_page',
		);

		add_screen_option( 'per_page', $arguments );

		/*
		 * Instantiate the revisions list table. Creating the instance here allow the core
		 * WP_List_Table class to automatically load the table columns in the screen options panel.
		 */
		$this->revisions_list_table = new SSL_ALP_Revisions_List_Table( $post_type );
	}

	/**
	 * Callback function for the admin revisions page.
	 */
	public function output_admin_revisions_page() {
		// Check user has permissions.
		if ( ! current_user_can( 'read' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to view revisions.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		$this->revisions_list_table->prepare_items();

		// Render revisions table.
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/revisions/revisions-table-display.php';
	}

	/**
	 * Save revisions per page screen option when saved by the user.
	 *
	 * @param bool   $keep   Whether to save or skip saving the screen option value. Default false.
	 * @param string $option The option name.
	 * @param int    $value  The number of rows to use.
	 */
	public function save_revisions_per_page_option( $keep, $option, $value ) {
		if ( 'ssl_alp_revisions_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Register revisions widget.
	 */
	public function register_revisions_widget() {
		register_widget( 'SSL_ALP_Revisions_Widget' );
	}

	/**
	 * Get revisions on published posts, grouped by author and post.
	 *
	 * Repeated edits made to posts by the same author are returned only once. The revisions
	 * auto-generated on publication are also ignored.
	 *
	 * @param int    $number Maximum number of revisions to get.
	 * @param string $order  Sort order.
	 * @return array
	 * @global $wpdb
	 */
	public function get_author_grouped_published_revisions( $number, $order = 'DESC' ) {
		global $wpdb;

		$number = absint( $number );
		$order  = esc_sql( ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC' );

		// Get post types that support edit summaries, and filter for SQL.
		$supported_post_types   = get_post_types_by_support( 'revisions' );
		$supported_post_types   = array_map( 'esc_sql', $supported_post_types );
		$supported_types_clause = "'" . implode( "', '", $supported_post_types ) . "'";

		// Reference posts cache key.
		$cache_key = 'ssl-alp-revisions-' . $number . '-' . $order;

		$object_ids = wp_cache_get( $cache_key );

		if ( false === $object_ids ) {
			/**
			 * Get last $number revisions (don't need parents) grouped by author and parent id, ordered
			 * by date descending, where number is > 1 if the revision was made by the original author
			 * (this prevents the original published post showing up as a revision), or > 0 if the
			 * revision was made by someone else. Ignore autosaves.
			 *
			 * Note: `post_date` is the most recent revision found in each group.
			 */
			$object_ids = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT posts.post_author, posts.post_parent, MAX(posts.post_date) AS post_date,
						COUNT(1) AS number, parent_posts.post_author AS parent_author
					FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->posts} AS parent_posts ON posts.post_parent = parent_posts.ID
					WHERE
						posts.post_type = 'revision'
						AND LOCATE(CONCAT(posts.post_parent, '-autosave'), posts.post_name) = 0
						AND posts.post_status = 'inherit'
						AND posts.post_date > parent_posts.post_date
						AND parent_posts.post_status = 'publish'
						AND parent_posts.post_type IN ({$supported_types_clause})
					GROUP BY posts.post_author, posts.post_parent
					HAVING (number > 1) OR (posts.post_author <> parent_posts.post_author)
					ORDER BY post_date {$order}
					LIMIT %d",
					$number
				)
			);

			wp_cache_set( $cache_key, $object_ids );
		}

		return $object_ids;
	}

	/**
	 * Check if the specified user can view the specified revisions.
	 *
	 * @param WP_Post $revision Revision to check.
	 * @return bool
	 */
	public function current_user_can_view_revision( $revision ) {
		// Taken from revision.php for viewing revisions.
		return ( current_user_can( 'read_post', $revision->ID ) && current_user_can( 'edit_post', $revision->post_parent ) );
	}

	/**
	 * Get unread flag term name.
	 *
	 * @param WP_User $user User object.
	 * @return string
	 */
	private function get_unread_flag_term_name( $user ) {
		return $user->user_nicename;
	}

	/**
	 * Get unread flag term slug.
	 *
	 * Note: the rewrite rule also uses this structure, and must be updated if
	 * this structure is also updated.
	 *
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return string|null
	 */
	private function get_unread_flag_term_slug( $user = null ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			if ( is_numeric( $user ) ) {
				// Get user by their ID.
				$user = get_user_by( 'id', $user );
			} elseif ( is_user_logged_in() ) {
				// Try to get logged in user.
				$user = wp_get_current_user();
			}
		}

		if ( ! is_a( $user, 'WP_User' ) ) {
			// Invalid user.
			return;
		}

		return self::$unread_flag_term_slug_prefix . $user->user_nicename;
	}

	/**
	 * Get user from unread flag term.
	 *
	 * @param WP_Term $term Unread flag term.
	 * @return WP_User|false
	 */
	private function get_user_from_unread_flag_term( $term ) {
		if ( substr( $term->slug, 0, strlen( self::$unread_flag_term_slug_prefix ) ) !== self::$unread_flag_term_slug_prefix ) {
			// The slug doesn't contain the prefix.
			return false;
		}

		// Remove prefix.
		$user_nicename = substr( $term->slug, strlen( self::$unread_flag_term_slug_prefix ) );

		return get_user_by( 'slug', $user_nicename );
	}

	/**
	 * Get unread flag term for specified user.
	 *
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return WP_Term|false
	 */
	private function get_user_unread_flag_term( $user = null ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			if ( is_numeric( $user ) ) {
				// Get user by their ID.
				$user = get_user_by( 'id', $user );
			} elseif ( is_user_logged_in() ) {
				// Try to get logged in user.
				$user = wp_get_current_user();
			}
		}

		if ( ! is_a( $user, 'WP_User' ) ) {
			// Invalid user.
			return false;
		}

		$term_name = $this->get_unread_flag_term_name( $user );
		$term      = get_term_by( 'name', $term_name, 'ssl_alp_unread_flag' );

		if ( ! $term ) {
			// Term doesn't yet exist.
			$args = array(
				'slug' => $this->get_unread_flag_term_slug( $user ),
			);

			// Insert term using slugified username as term name.
			$new_term_data = wp_insert_term( $term_name, 'ssl_alp_unread_flag', $args );

			if ( is_wp_error( $new_term_data ) ) {
				return false;
			}

			$term = get_term_by( 'id', $new_term_data['term_id'], 'ssl_alp_unread_flag' );
		}

		return $term;
	}

	/**
	 * Register taxonomy for unread flags.
	 */
	public function register_unread_flag_taxonomy() {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		// Register new taxonomy so that we can store all of the relationships.
		$args = array(
			'label'             => __( 'Unread', 'ssl-alp' ),
			'query_var'         => false,
			'rewrite'           => false,    // Rewrites are handled elsewhere.
			'public'            => true,     // Allow public display.
			'show_ui'           => false,    // Disallow editing of tags.
			'show_in_menu'      => false,    // Hide tag editor from admin post menu.
			'show_in_nav_menus' => false,
			'show_in_rest'      => false,    // Flags set via custom endpoint instead.
			'query_var'         => 'unread', // Allow ?unread=... query.
		);

		// Create read flag taxonomy for posts.
		register_taxonomy( 'ssl_alp_unread_flag', 'post', $args );
	}

	/**
	 * Add rewrite rule to display unread posts for each user.
	 */
	public function register_unread_post_rewrite_rules() {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		$this->add_unread_post_rewrite_rules();
	}

	/**
	 * Remove adjacent links from HTTP head section.
	 *
	 * These sometimes cause `the_post` action to be fired twice across two
	 * requests when viewing a single post: first for the post in question, and
	 * second for the "prefetched" next post that WordPress adds a link to in
	 * the HTTP head section. This results in the next adjacent being set as
	 * read even when the user may not have read it.
	 *
	 * An alternative approach to avoiding this unwanted behaviour would be to
	 * detect the prefetch request, e.g. using a header parameter (see e.g.
	 * [1]), but this is fragile and depends on the non-standardised header
	 * parameter being set by browsers.
	 *
	 * [1] https://developer.mozilla.org/en-US/docs/Web/HTTP/Link_prefetching_FAQ#As_a_server_admin.2C_can_I_distinguish_prefetch_requests_from_normal_requests.3F
	 */
	public function remove_adjacent_links_action() {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	/**
	 * Register rewrite rules for unread flags.
	 */
	public static function add_unread_post_rewrite_rules() {
		add_rewrite_rule(
			'^unread/?(.*)/?$',
			'index.php?unread=' . self::$unread_flag_term_slug_prefix . '$matches[1]',
			'top' // Required to avoid page matching rule.
		);
	}

	/**
	 * Add link to unread posts page in admin bar.
	 *
	 * @global $wp_admin_bar
	 */
	public function add_unread_posts_admin_bar_link() {
		global $wp_admin_bar;

		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		if ( get_option( 'permalink_structure' ) ) {
			$url = get_site_url( null, 'unread/' );
		} else {
			$url = get_site_url( null, '?unread=' . $this->get_unread_flag_term_slug() );
		}

		$wp_admin_bar->add_menu(
			array(
				'parent' => 'top-secondary', // On the right side.
				'title'  => __( 'Unread Posts', 'ssl-alp' ),
				'id'     => 'ssl-alp-unread-posts-link',
				'href'   => $url,
				'meta'   => array(
					'title' => esc_html__( 'View unread posts', 'ssl-alp' ),
				),
			)
		);
	}

	/**
	 * Set unread posts page user depending on query, and check permissions.
	 */
	public function set_unread_posts_archive_page_user() {
		global $wp_query;

		if ( ! is_tax() || empty( get_query_var( 'unread' ) ) ) {
			// Not the unread posts page.
			return;
		}

		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		if ( ! is_user_logged_in() ) {
			// Cannot show useful unread posts page.
			$wp_query->set_404();
		} else {
			if ( get_query_var( 'unread' ) === self::$unread_flag_term_slug_prefix ) {
				// No unread term is set (it defaults to the slug prefix) - use current user's slug.
				set_query_var( 'unread', $this->get_unread_flag_term_slug() );
			} else {
				// An unread archive slug is set, but does the user have permission to view it?
				$term = get_term_by( 'slug', get_query_var( 'unread' ), 'ssl_alp_unread_flag' );

				if ( $term ) {
					$user = $this->get_user_from_unread_flag_term( $term );

					if ( ! $this->check_unread_flag_permission( $user ) ) {
						// User cannot view this page.
						$wp_query->set_404();
					}
				} else {
					$wp_query->set_404();
				}
			}
		}
	}

	/**
	 * Register REST API route for setting unread flag.
	 *
	 * This provides a simple interface to set a user's post unread status. It
	 * bypasses the endpoint defined by the taxonomy (when enabled by
	 * `show_in_rest`) because otherwise the user would need edit permission to
	 * set the unread flag term.
	 */
	public function rest_register_unread_flag_routes() {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		register_rest_route(
			SSL_ALP_REST_ROUTE,
			'/post-read-status',
			array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'rest_get_post_read_status' ),
					'args'     => array(
						'post_id' => array(
							'required'          => true,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
						'user_id' => array(
							'required'          => false,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'rest_set_post_read_status' ),
					'args'     => array(
						'read'    => array(
							'required'          => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'post_id' => array(
							'required'          => true,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
						'user_id' => array(
							'required'          => false,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Check user permission to edit an unread flag.
	 *
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return bool false if $target_user is not the current user and the
	 *              current user is not able to edit users, true otherwise.
	 */
	private function check_unread_flag_permission( $user = null ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			if ( is_numeric( $user ) ) {
				// Get user by their ID.
				$user = get_user_by( 'id', $user );
			} elseif ( is_user_logged_in() ) {
				// Try to get logged in user.
				$user = wp_get_current_user();
			}
		}

		if ( ! is_a( $user, 'WP_User' ) ) {
			// Invalid user.
			return false;
		}

		if ( wp_get_current_user()->ID !== $user->ID && ! current_user_can( 'edit_users' ) ) {
			// No permission to edit another user's flag.
			return false;
		}

		return true;
	}

	/**
	 * Get post read status via REST API.
	 *
	 * @param WP_REST_Request $data REST request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_post_read_status( WP_REST_Request $data ) {
		if ( is_null( $data['post_id'] ) ) {
			// Invalid data.
			return rest_ensure_response( $this->unread_flag_invalid_data_error() );
		}

		$post = get_post( $data['post_id'] );

		if ( is_null( $post ) ) {
			return rest_ensure_response( $this->unread_flag_post_not_found_error() );
		}

		if ( is_numeric( $data['user_id'] ) ) {
			// Get user by their ID.
			$user = get_user_by( 'id', $data['user_id'] );
		} else {
			if ( ! is_user_logged_in() ) {
				return rest_ensure_response( $this->unread_flag_no_permission_error() );
			}

			// Try to get logged in user.
			$user = wp_get_current_user();
		}

		if ( ! $user ) {
			// Invalid user.
			return rest_ensure_response( $this->unread_flag_user_not_found_error() );
		}

		if ( ! $this->check_unread_flag_permission( $user ) ) {
			// No permission.
			return rest_ensure_response( $this->unread_flag_no_permission_error() );
		}

		$response = $this->get_post_read_status( $post, $user );

		if ( ! is_wp_error( $response ) ) {
			// Make response an array with new flag.
			$response = array(
				'read' => $response,
			);
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Set post read status via REST API.
	 *
	 * @param WP_REST_Request $data REST request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_set_post_read_status( WP_REST_Request $data ) {
		if ( is_null( $data['post_id'] ) || is_null( $data['read'] ) ) {
			// Invalid data.
			return rest_ensure_response( $this->unread_flag_invalid_data_error() );
		}

		$post = get_post( $data['post_id'] );

		if ( is_null( $post ) ) {
			return rest_ensure_response( $this->unread_flag_post_not_found_error() );
		}

		if ( is_numeric( $data['user_id'] ) ) {
			// Get user by their ID.
			$user = get_user_by( 'id', $data['user_id'] );
		} else {
			if ( ! is_user_logged_in() ) {
				return rest_ensure_response( $this->unread_flag_no_permission_error() );
			}

			// Try to get logged in user.
			$user = wp_get_current_user();
		}

		if ( ! $user ) {
			// Invalid user.
			return rest_ensure_response( $this->unread_flag_user_not_found_error() );
		}

		if ( ! $this->check_unread_flag_permission( $user ) ) {
			// No permission.
			return rest_ensure_response( $this->unread_flag_no_permission_error() );
		}

		$response = $this->set_post_read_status( $data['read'], $post, $user );

		if ( ! is_wp_error( $response ) ) {
			// Make response an array with new flag.
			$response = array(
				'read' => $response,
			);
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Invalid data REST error.
	 */
	private function unread_flag_invalid_data_error() {
		return new WP_Error(
			'post_unread_flag_invalid_data',
			__( 'The specified data is invalid.', 'ssl-alp' ),
			array(
				'status' => 400, // Bad request.
			)
		);
	}

	/**
	 * No permission REST error.
	 */
	private function unread_flag_no_permission_error() {
		return new WP_Error(
			'post_unread_flag_cannot_read',
			__( 'Sorry, you are not allowed to view this unread flag.', 'ssl-alp' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Post not found REST error.
	 */
	private function unread_flag_post_not_found_error() {
		return new WP_Error(
			'post_unread_flag_post_not_found',
			__( 'Post not found.', 'ssl-alp' ),
			array(
				'status' => 400,
			)
		);
	}

	/**
	 * Post is not of the correct type (e.g. page).
	 */
	private function unread_flag_unsupported_post_type_error() {
		return new WP_Error(
			'post_unread_flag_post_type_unsupported',
			__( 'Post type not supported.', 'ssl-alp' ),
			array(
				'status' => 400,
			)
		);
	}

	/**
	 * User not found REST error.
	 */
	private function unread_flag_user_not_found_error() {
		return new WP_Error(
			'post_unread_flag_invalid_user',
			__( 'User not found.', 'ssl-alp' ),
			array(
				'status' => 400,
			)
		);
	}

	/**
	 * Term not found REST error.
	 */
	private function unread_flag_term_not_found_error() {
		return new WP_Error(
			'post_unread_flag_term_not_found',
			__( 'User unread flag term not found.', 'ssl-alp' ),
			array(
				'status' => 500,
			)
		);
	}

	/**
	 * Unknown REST error.
	 */
	private function unread_flag_unknown_error() {
		return new WP_Error(
			'post_unread_flag_unknown_error',
			__( 'Unknown error.', 'ssl-alp' ),
			array(
				'status' => 500,
			)
		);
	}

	/**
	 * Set post read status for users, optionally ignoring one.
	 *
	 * @param bool             $read        Read status to set.
	 * @param int|WP_Post|null $post        Post ID or post object. Defaults to global $post.
	 * @param int|WP_User|null $ignore_user User ID or user object to ignore. Defaults to none.
	 */
	private function set_users_post_read_status( $read, $post, $ignore_user = null ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		// Get post.
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		// Get all users.
		$users = get_users();

		if ( is_numeric( $ignore_user ) ) {
			// Get user by their ID.
			$ignore_user = get_user_by( 'id', $ignore_user );
		}

		$should_ignore = is_a( $ignore_user, 'WP_User' );

		// Set each user's read status.
		foreach ( $users as $user ) {
			if ( $should_ignore && $user->ID === $ignore_user->ID ) {
				// Ignore user.
				continue;
			}

			$this->set_post_read_status( (bool) $read, $post, $user );
		}
	}

	/**
	 * Mark post as unread for all users except the editor after publication or
	 * an update, if changes have been made.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  New post.
	 * @param WP_Post $post_before Previous post (the auto-draft in the case of new posts).
	 */
	public function mark_post_as_unread_for_users_after_update( $post_id, $post_after, $post_before ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		if ( 'publish' !== get_post_status( $post_after ) ) {
			// Don't change anything on unpublished posts.
			return;
		}

		// Prior post statuses which don't need a change check.
		$no_check_statuses = array(
			'draft',
			'auto-draft',
			'pending',
			'private',
			'future',
		);

		if ( ! in_array( get_post_status( $post_before ), $no_check_statuses, true ) ) {
			// The post has been updated from a previous version - check if it
			// has changed sufficiently to mark as unread.
			if ( ! class_exists( 'Text_Diff', false ) ) {
				require ABSPATH . WPINC . '/wp-diff.php';
			}

			// Compute text difference between old and new posts.
			$diff = $this->get_post_text_differences( $post_after, $post_before );

			// Post content is deemed changed if there are new or deleted lines.
			$changed = $diff['added'] > 1 || $diff['removed'] > 1;

			if ( ! $changed ) {
				return;
			}
		}

		// Set post as unread for all users except current user.
		$this->set_users_post_read_status( false, $post_after, wp_get_current_user() );
	}

	/**
	 * Get the number of added and deleted lines between two revisions of a post.
	 *
	 * @param WP_Post      $new_revision New revision.
	 * @param WP_Post|null $old_revision Previous revision. If not specified, the revision
	 *                                   immediately prior to $post_after is used.
	 * @return array|null Array containing lines added and removed, or null if difference can't be
	 *                    determined.
	 */
	public function get_post_text_differences( $new_revision, $old_revision = null ) {
		if ( ! class_exists( 'Text_Diff', false ) ) {
			require ABSPATH . WPINC . '/wp-diff.php';
		}

		if ( is_null( $old_revision ) ) {
			// Get previous revision.
			$revisions = $this->get_revisions(
				$new_revision->post_parent,
				array(
					'order'       => 'DESC',
					'orderby'     => 'date ID',
					'numberposts' => 1,
					'date_query'  => array(
						'before'    => $new_revision->post_date,
						'inclusive' => false,
					),
				),
				true,
				false
			);

			if ( empty( $revisions ) ) {
				// Can't get previous revision.
				return;
			}

			$old_revision = reset( $revisions );
		}

		$old_string = normalize_whitespace( $old_revision->post_content );
		$new_string = normalize_whitespace( $new_revision->post_content );

		$old_lines = explode( "\n", $old_string );
		$new_lines = explode( "\n", $new_string );

		// Compute text difference between old and new posts.
		$diff = new Text_Diff( $old_lines, $new_lines );

		return array(
			'added'   => $diff->countAddedLines(),
			'removed' => $diff->countDeletedLines(),
		);
	}

	/**
	 * Get post read status for specified post and user.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return bool|WP_Error
	 */
	public function get_post_read_status( $post, $user = null ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $this->unread_flag_no_permission_error();
		}

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return $this->unread_flag_post_not_found_error();
		}

		if ( ! is_a( $user, 'WP_User' ) ) {
			if ( is_numeric( $user ) ) {
				// Get user by their ID.
				$user = get_user_by( 'id', $user );
			} else {
				if ( ! is_user_logged_in() ) {
					return $this->unread_flag_no_permission_error();
				}

				// Try to get logged in user.
				$user = wp_get_current_user();
			}
		}

		if ( ! $user ) {
			// Invalid user.
			return $this->unread_flag_user_not_found_error();
		}

		$user_unread_flag_term = $this->get_user_unread_flag_term( $user );

		if ( ! $user_unread_flag_term ) {
			// No term found. Not much we can do.
			return $this->unread_flag_term_not_found_error();
		}

		// The term is assigned to the post if the post is unread.
		return ! has_term( $user_unread_flag_term->name, 'ssl_alp_unread_flag', $post );
	}

	/**
	 * Set post read status.
	 *
	 * @param bool             $read Read status to set.
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return bool|WP_Error New read status, or error.
	 */
	public function set_post_read_status( $read, $post = null, $user = null ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $this->unread_flag_no_permission_error();
		}

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return $this->unread_flag_post_not_found_error();
		}

		if ( ! $this->unread_flags_supported( $post ) ) {
			// Not a post.
			return $this->unread_flag_unsupported_post_type_error();
		}

		if ( ! is_a( $user, 'WP_User' ) ) {
			if ( is_numeric( $user ) ) {
				// Get user by their ID.
				$user = get_user_by( 'id', $user );
			} else {
				if ( ! is_user_logged_in() ) {
					return $this->unread_flag_no_permission_error();
				}

				// Try to get logged in user.
				$user = wp_get_current_user();
			}
		}

		if ( ! $user ) {
			// Invalid user.
			return $this->unread_flag_user_not_found_error();
		}

		$user_unread_flag_term = $this->get_user_unread_flag_term( $user );

		if ( ! $user_unread_flag_term ) {
			// No term found. Not much we can do.
			return $this->unread_flag_term_not_found_error();
		}

		if ( $read ) {
			// Remove unread flag.
			$success = wp_remove_object_terms( $post->ID, array( $user_unread_flag_term->name ), 'ssl_alp_unread_flag' );
		} else {
			// Set unread flag.
			$success = wp_set_post_terms( $post->ID, array( $user_unread_flag_term->name ), 'ssl_alp_unread_flag', true );
		}

		if ( is_wp_error( $success ) ) {
			return $success;
		} elseif ( ! $success ) {
			// Unknown error from wp_set_post_terms or wp_remove_object_terms.
			return $this->unread_flag_unknown_error();
		}

		return $read;
	}

	/**
	 * Check if a post has unread flag support.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @return bool|null Whether unread flags are supported. Null if post is invalid.
	 */
	public function unread_flags_supported( $post ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		return in_array( $post->post_type, self::$supported_unread_flag_post_types, true );
	}

	/**
	 * Set post as read (by deleting the unread flag) for the specified user.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @param int|WP_User|null $user User ID or user object. Defaults to currently logged in user.
	 * @return bool|WP_Error
	 */
	public function mark_post_as_read( $post, $user = null ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		if ( ! is_single( $post ) ) {
			return;
		}

		return $this->set_post_read_status( true, $post, $user );
	}

	/**
	 * Filter the views listed on the admin post list to add "Unread" view.
	 *
	 * @param array $views Post list views.
	 * @return array Post list views with new column added.
	 */
	public function filter_edit_post_views( $views ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $views;
		}

		// Current user.
		$user = wp_get_current_user();

		// Unread flag term.
		$unread_flag_term = $this->get_user_unread_flag_term( $user );

		// Number of unread posts.
		$unread_count = $unread_flag_term->count;

		// Get current user's unread flag term slug.
		$unread_flag_slug = $unread_flag_term->slug;

		// Build URL arguments.
		$unread_flag_args = array(
			'taxonomy' => 'ssl_alp_unread_flag',
			'term'     => $unread_flag_slug,
		);

		$request_tax  = get_query_var( 'taxonomy' );
		$request_term = get_query_var( 'term' );

		// Check if the current page is the "Mine" view.
		if ( 'ssl_alp_unread_flag' === $request_tax && $unread_flag_slug === $request_term ) {
			$class = 'current';
		} else {
			$class = '';
		}

		// "Unread" view.
		$unread = sprintf(
			'<a class="%s" href="%s">%s <span class="count">(%s)</span></a>',
			esc_attr( $class ),
			esc_url( add_query_arg( array_map( 'rawurlencode', $unread_flag_args ), admin_url( 'edit.php' ) ) ),
			esc_html__( 'Unread', 'ssl_alp' ),
			esc_html( $unread_count )
		);

		// Add after first item.
		array_splice( $views, 1, 0, array( $unread ) );

		return $views;
	}

	/**
	 * Filter the unread post archive page title to show the user's display name
	 * instead of the user nicename (the term name).
	 *
	 * @param string $title The archive page title.
	 */
	public function set_unread_post_archive_title( $title ) {
		if ( ! is_tax( 'ssl_alp_unread_flag' ) ) {
			// Not this taxonomy's page.
			return $title;
		}

		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $title;
		}

		$term = get_queried_object();
		$tax  = get_taxonomy( $term->taxonomy );

		if ( 'ssl_alp_unread_flag' !== $tax->name ) {
			return $title;
		}

		$user = $this->get_user_from_unread_flag_term( $term );

		if ( $user ) {
			// Use the user's display name.
			$title = $user->display_name;
		}

		return $title;
	}

	/**
	 * Register "Mark as read" and "Mark as unread" actions on admin post list.
	 *
	 * @param array $actions Previous actions.
	 * @return array New actions.
	 */
	public function register_read_unread_bulk_actions( $actions ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $actions;
		}

		$actions['mark_as_read']   = __( 'Mark as Read', 'ssl_alp' );
		$actions['mark_as_unread'] = __( 'Mark as Unread', 'ssl_alp' );

		return $actions;
	}

	/**
	 * Handle read/unread bulk action.
	 *
	 * @param array  $redirect_to Redirect query args.
	 * @param string $doaction    Action.
	 * @param array  $post_ids    Array of post IDs.
	 * @return array Updated redirect query args.
	 */
	public function handle_read_unread_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return $redirect_to;
		}

		if ( 'mark_as_read' === $doaction ) {
			$read_status = true;

			// Update query arguments.
			$redirect_to = remove_query_arg( 'posts_marked_unread', $redirect_to );
			$redirect_to = add_query_arg( 'posts_marked_read', count( $post_ids ), $redirect_to );
		} elseif ( 'mark_as_unread' === $doaction ) {
			$read_status = false;

			// Update query arguments.
			$redirect_to = remove_query_arg( 'posts_marked_read', $redirect_to );
			$redirect_to = add_query_arg( 'posts_marked_unread', count( $post_ids ), $redirect_to );
		} else {
			// Not an action we want to process.
			return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {
			$this->set_post_read_status( $read_status, $post_id );
		}

		return $redirect_to;
	}

	/**
	 * Add admin notice after posts have been set as read or unread.
	 */
	public function read_unread_admin_notice() {
		if ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
			// Unread flags disabled.
			return;
		}

		if ( ! empty( $_REQUEST['posts_marked_read'] ) ) {
			$count = absint( $_REQUEST['posts_marked_read'] );

			echo '<div id="message" class="updated"><p>';

			printf(
				esc_html(
					/* translators: number of posts flagged as read */
					_n(
						'Set %s post as read.',
						'Set %s posts as read.',
						$count,
						'ssl_alp'
					)
				),
				$count
			);

			echo '</p></div>';
		} elseif ( ! empty( $_REQUEST['posts_marked_unread'] ) ) {
			$count = absint( $_REQUEST['posts_marked_unread'] );

			echo '<div id="message" class="updated"><p>';

			printf(
				esc_html(
					/* translators: number of posts flagged as unread */
					_n(
						'Set %s post as unread.',
						'Set %s posts as unread.',
						$count,
						'ssl_alp'
					)
				),
				number_format_i18n( $count )
			);

			echo '</p></div>';
		} else {
			return;
		}
	}
}
