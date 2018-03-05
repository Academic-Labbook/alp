<?php

/**
 * The admin-specific functionality of the plugin.
 */
class SSL_ALP_Admin extends SSL_ALP_Base {
	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'ssl-alp-admin-css', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register JavaScript for the admin area.
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Add edit summary support to certain post types, so they can have
	 * relevant tools added to their edit pages.
	 */
	public function add_edit_summary_support() {
		add_post_type_support( 'post', 'ssl-alp-edit-summaries' );
		add_post_type_support( 'page', 'ssl-alp-edit-summaries' );
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
		}

		if ( !post_type_supports( $post->post_type, 'ssl-alp-edit-summaries' ) ) {
			// unsupported post type
			return false;
		}

		// check if setting is enabled, and if user has permission
		// 'edit_post' capability == 'edit_posts', 'edit_page' == 'edit_pages', etc. (see wp-includes/capabilities.php)
		if ( !get_option( 'ssl_alp_{$post->post_type}_edit_summaries', true) || !current_user_can( 'edit_{$post->post_type}', $post->ID ) ) {
			// disabled for posts, or user not allowed to view
			error_log("can't");
			return false;
		}

		return true;
	}

	/*
	 * Inject edit summary into revision author/date listings
	 */
	public function add_revision_title_edit_summary( $revision_date_author, $revision ) {
		if ( !is_admin() ) {
			return $revision_date_author;
		}

		$screen = get_current_screen();

		if ( $screen->base !== 'post' ) {
			return $text;
		}

		if ( !$this->edit_summary_allowed( $revision ) ) {
			// return as-is
			return $revision_date_author;
		}

		// get the stored meta value from the revision
		// (use get_metadata instead of get_post_meta so we get the *revision's* data, not the parent's)
		$revision_meta = get_metadata( 'post', $revision->ID, 'edit_summary', true );

		if ( empty( $revision_meta ) || !is_array( $revision_meta ) ) {
			// empty or invalid
			return $revision_date_author;
		}

		if ( $revision_meta["reverted"] !== 0 ) {
			// this revision was a revert to previous revision
			/* translators: %s: revision URL; %d: revision ID */
			$revision_date_author .= __( ', reverted to <a href="%s">%d</a>', 'ssl-alp' );

			// get reverted revision URL
			$revision_url = get_edit_post_link( $revision_meta["reverted"] );

			// add URL
			$revision_date_author = sprintf( $revision_date_author, $revision_url, $revision_meta["reverted"] );
		}

		// add message
		/* translators: %s: revision edit summary */
		$revision_date_author .= " &mdash; " . sprintf( __( "<em>\"%s\"</em>", 'ssl-alp' ), esc_html( $revision_meta["message"] ) );

		return $revision_date_author;
	}

	/**
	 * Add edit summary to revision screen
	 */
	public function prepare_revision_for_js( $data, $revision ) {
		// get revision edit summary
		$revision_meta = get_metadata( 'post', $revision->ID, 'edit_summary', true );

		if ( empty( $revision_meta ) || !is_array( $revision_meta ) ) {
			// empty or invalid
			return $data;
		}

		$edit_summary = esc_html( $revision_meta["message"] );

		if ( $revision_meta["reverted"] !== 0 ) {
			// this revision was a revert to previous revision
			/* translators: %d: revision ID */
			$data['timeAgo'] .= __( ', reverted to %d', 'ssl-alp' );

			// add URL
			$data['timeAgo'] = sprintf( $data['timeAgo'], $revision_meta["reverted"] );
		}

		/* translators: 1: time ago; 2: edit summary */
		$data['timeAgo'] = sprintf( __( '%1$s — "%2$s"', 'ssl-alp' ), $data['timeAgo'], $edit_summary );

		return $data;
	}

	/**
	 * Add edit summary textbox within the "Update" panel to posts and pages
	 */
	public function add_edit_summary_textbox( $post ) {
		if ( $post->post_status == 'auto-draft' ) {
			// post is newly created, so don't show an edit summary box
			return;
		} elseif ( !$this->edit_summary_allowed( $post ) ) {
			return;
		}

		// add a nonce to check later
		wp_nonce_field( 'ssl-alp-edit-summary', 'ssl_alp_edit_summary_nonce' );

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/post-edit-summary-display.php';
	}

	/**
	 * Restore the revision's meta values to the parent post.
	 */
	public function restore_post_revision_meta( $post_id, $revision_id ) {
		// Clear any existing metas
		delete_post_meta( $post_id, 'edit_summary' );

		// get the stored meta value from the revision we are reverting to
		$target_revision_meta = get_metadata( 'post', $revision_id, 'edit_summary', true );

		// add revision's meta value to parent
		add_post_meta( $post_id, 'edit_summary', $target_revision_meta );

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

		// create latest revision's edit summary
		$latest_edit_summary = array(
			"reverted"	=> $revision_id,
			"message"	=>	$target_revision_meta["message"]
		);

	   /*
		* Update meta data
		*
		* Use the underlying update_meta() function instead of
		* update_meta() to ensure metadata is updated on the revision post
		* and not its parent.
		*/
		update_metadata( 'post', $latest_revision_meta->ID, 'edit_summary', $latest_edit_summary );
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
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$revision = get_post( $revision_id );

		if ( !$this->edit_summary_allowed( $revision ) ) {
			return;
		}

		$post_id  = $revision->post_parent;

		// save revisioned meta field
		// get edit summary from revision's parent
		$edit_summary = get_post_meta( $post_id, 'edit_summary', true );

		if ( 0 !== sizeof( $edit_summary ) && is_array( $edit_summary ) ) {				/*
			 * Add parent's custom meta data to revision
			 *
			 * Use the underlying add_metadata() function instead of
			 * add_post_meta() to ensure metadata is added to the revision post
			 * and not its parent.
			 */
			add_metadata( 'post', $revision_id, 'edit_summary', $edit_summary );
		}
	}

	/**
	 * Check whether revisioned post meta fields have changed.
	 */
	public function check_revisioned_meta_fields_have_changed( $post_has_changed, WP_Post $last_revision, WP_Post $post ) {
		// skip when autosaving, as custom post data is noted included in $_POST during autosaves (annoying)
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_has_changed;
		}

		// get parent post meta
		$parent_meta = get_post_meta( $post->ID, 'edit_summary' );

		// get revision meta
		$revision_meta = get_post_meta( $last_revision->ID, 'edit_summary' );

		if ( !is_array( $parent_meta ) || !is_array( $revision_meta ) ) {
			// invalid
		} elseif ( !isset( $parent_meta["message"] ) || !isset( $revision_meta["message"] ) ) {
			// invalid
		} else {
			// check if message has changed
			if ( $parent_meta["message"] !== $revision_meta["message"] ) {
				$post_has_changed = true;
			}
		}

		return $post_has_changed;
	}

	/**
	 * Save post edit summary as meta data attached to that post. Due to the
	 * use of a nonce, which only appears when the post is being updated, this
	 * does not show a message for *new* posts.
	 */
	public function save_post_edit_summary( $post_id, $post ) {
		// skip when autosaving, as custom post data is noted included in $_POST during autosaves (annoying)
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		} elseif ( !$this->edit_summary_allowed( $post ) ) {
			return;
		} elseif ( !isset( $_POST['ssl_alp_edit_summary_nonce'] ) || !wp_verify_nonce( $_POST['ssl_alp_edit_summary_nonce'], 'ssl-alp-edit-summary' ) ) {
			// no or invalid nonce
			return;
		}

		// sanitise edit summary input
		$message = sanitize_text_field( $_POST['ssl_alp_revision_post_edit_summary'] );
		// limit length
		$max = get_option( 'ssl_alp_edit_summary_max_length', 100 );
		if ( strlen( $message ) > $max ) {
			$message = substr( $message, 0, $max );
		}

		# construct meta data array
		$edit_summary = array(
			"reverted"	=> 0,
			"message"	=> $message
		);

		// update the post's edit summary
		update_post_meta( $post_id, 'edit_summary', $edit_summary );
	}

	/**
     * Register the settings page.
     */
	public function add_admin_menu() {
		add_options_page(
			'Academic Labbook',
			__('Academic Labbook', 'ssl-alp'),
			'manage_options',
			'ssl-alp-admin-options',
			array($this, 'create_admin_interface')
		);
	}

	/**
	 * Callback function for the settings page.
	 */
	public function create_admin_interface() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';
	}

	/**
	 * Create settings sections.
	 */
	public function settings_api_init() {
		/**
		 * Settings sections
		 */

		 add_settings_section(
 			'ssl_alp_site_settings_section', // id
 			__( 'Site Settings', 'ssl-alp' ), // title
 			array( $this, 'site_settings_section_callback' ), // callback
 			'ssl-alp-admin-options' // page
		);

	 	add_settings_section(
			'ssl_alp_post_settings_section', // id
			__( 'Post Settings', 'ssl-alp' ), // title
			array( $this, 'post_settings_section_callback' ), // callback
			'ssl-alp-admin-options' // page
		);

		add_settings_section(
			'ssl_alp_mathematics_settings_section', // id
			__( 'Mathematics Settings', 'ssl-alp' ), // title
			array( $this, 'mathematics_settings_section_callback' ), // callback
			'ssl-alp-admin-options' // page
		);

		/**
		 * Site settings fields
		 */

	 	add_settings_field(
			'ssl_alp_access_settings', // id
			__( 'Access', 'ssl-alp' ), // title
			array( $this, 'access_settings_callback' ), // callback
			'ssl-alp-admin-options', // page
			'ssl_alp_site_settings_section' // section
		);

		/**
		 * Post settings fields
		 */

	 	add_settings_field(
			'ssl_alp_category_settings', // id
			__( 'Categories and tags', 'ssl-alp' ), // title
			array( $this, 'category_settings_callback' ), // callback
			'ssl-alp-admin-options', // page
			'ssl_alp_post_settings_section' // section
		);

		add_settings_field(
			'ssl_alp_author_settings',
			__( 'Authors', 'ssl-alp' ),
			array( $this, 'author_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

		add_settings_field(
			'ssl_alp_edit_summary_settings',
			__( 'Edit summaries', 'ssl-alp' ),
			array( $this, 'edit_summary_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

		add_settings_field(
			'ssl_alp_journal_reference_settings',
			__( 'Journal references', 'ssl-alp' ),
			array( $this, 'journal_reference_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

		/*
		 * Mathematics settings fields
		 */

		add_settings_field(
			'ssl_alp_enable_mathematics_settings',
			__( 'Display', 'ssl-alp' ),
			array( $this, 'enable_latex_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_mathematics_settings_section'
		);

		add_settings_field(
			'ssl_alp_mathjax_url_settings',
			__( 'MathJax JavaScript URL', 'ssl-alp' ),
			array( $this, 'mathjax_javascript_url_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_mathematics_settings_section'
		);
	}

	/**
	 * Callback functions for settings
	 */

    /*
	 * Site settings
	 */

	public function site_settings_section_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/site/site-settings-section-display.php';
	}

	public function access_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/site/access-settings-display.php';
	}

	/*
	 * Post settings
	 */
	public function post_settings_section_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/post-settings-section-display.php';
	}

	public function category_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/category-settings-display.php';
	}

	public function author_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/author-settings-display.php';
	}

	public function edit_summary_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/edit-summary-settings-display.php';
	}

	public function journal_reference_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/post/journal-reference-settings-display.php';
	}

	/*
	 * Mathematics settings
	 */

	public function mathematics_settings_section_callback() {
 		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/maths/mathematics-settings-section-display.php';
 	}

	public function enable_latex_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/maths/enable-latex-settings-display.php';
	}

	public function mathjax_javascript_url_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/maths/mathjax-javascript-url-settings-display.php';
	}
}
