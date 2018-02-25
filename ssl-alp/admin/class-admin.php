<?php

/**
 * The admin-specific functionality of the plugin.
 */
class SSL_ALP_Admin {
	/**
	 * The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register JavaScript for the admin area.
	 */
	public function enqueue_scripts() {

	}

	private function edit_summary_enabled( $post ) {
		if ( $post->post_type == 'post' ) {
			if ( !get_option( 'ssl_alp_post_edit_summaries', true) ) {
				// disabled for posts
				return false;
			}
		} elseif ( $post->post_type == 'page') {
			if ( !get_option( 'ssl_alp_page_edit_summaries', true ) ) {
				// disabled for pages
				return false;
			}
		} else {
			// invalid post type
			return false;
		}

		return true;
	}

	private function edit_summary_permission( $post ) {
		if ( $post->post_type == 'post' ) {
			if ( !current_user_can( 'edit_post', $post->ID ) ) {
				// no permission
				return false;
			}
		} elseif ( $post->post_type == 'page') {
			if ( !current_user_can( 'edit_page', $post->ID ) ) {
				// no permission
				return false;
			}
		} else {
			// invalid post type
			return false;
		}

		return true;
	}

	/**
	 * Add edit summary textbox within the "Update" panel to posts and pages
	 */
	public function add_edit_summary_textbox($post) {
		if ( !$this->edit_summary_enabled( $post ) || !$this->edit_summary_permission ( $post ) ) {
			return;
		} elseif ( $post->post_status == 'auto-draft' ) {
			// post is newly created, so don't show an edit summary box
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
		error_log("restoring post " . $post_id . " revisioned meta fields from " . $revision_id);

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

		if ( !$this->edit_summary_enabled( $revision ) || !$this->edit_summary_permission ( $revision ) ) {
			return;
		}

		error_log("saving revisioned meta fields");

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

		error_log("checking if revisioned meta fields have changed");

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
		} elseif ( !$this->edit_summary_enabled( $post ) || !$this->edit_summary_permission ( $post ) ) {
			return;
		} elseif ( !isset( $_POST['ssl_alp_edit_summary_nonce'] ) || !wp_verify_nonce( $_POST['ssl_alp_edit_summary_nonce'], 'ssl-alp-edit-summary' ) ) {
			// no or invalid nonce
			return;
		}

		// skip when nonce is not set or invalid (as post edits can be made
		// by other functions that don't create POST data)
		error_log("saving post edit summary");

		// sanitise edit summary input
		$edit_summary = sanitize_text_field( $_POST['ssl_alp_revision_post_edit_summary'] );
		// limit length
		$max = get_option( 'ssl_alp_edit_summary_max_length', 100 );
		if ( strlen( $edit_summary ) > $max ) {
			$edit_summary = substr( $edit_summary, 0, $max );
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
			__( 'Edit summaries' ),
			array( $this, 'edit_summary_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

		/**
		 * Access settings
		 */

		 register_setting(
 			'ssl-alp-admin-options',
 			'ssl_alp_require_login',
 			array(
 				'type'		=>	'boolean',
 				'default'	=>	true
 			)
 		);

		/**
		 * Categories and tags settings
		 */

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_disable_post_tags',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		/**
		 * Authors settings
		 */

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_multiple_authors',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		/**
		 * Edit summary settings
		 */

		register_setting(
 			'ssl-alp-admin-options',
 			'ssl_alp_post_edit_summaries',
 			array(
 				'type'		=>	'boolean',
 				'default'	=>	true
 			)
 		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_page_edit_summaries',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_edit_summary_max_length',
			array(
				'type'		=>	'integer',
				'default'	=>	100
			)
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
}
