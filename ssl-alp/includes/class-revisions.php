<?php

/**
 * Revision summary functionality
 */
class SSL_ALP_Revisions extends SSL_ALP_Module {
	/**
	 * Register the stylesheets.
	 */
	public function enqueue_styles() {

	}

	/**
	 * Register JavaScript.
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Register settings
	 */
	public function register_settings() {
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
     * Register settings fields
     */
    public function register_settings_fields() {
        /**
		 * Post edit summary settings
		 */

        add_settings_field(
			'ssl_alp_edit_summary_settings',
			__( 'Edit summaries', 'ssl-alp' ),
			array( $this, 'edit_summary_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function edit_summary_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/edit-summary-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

         // register edit summary feature with posts and pages
        $loader->add_action( 'init', $this, 'add_edit_summary_support' );

        // add edit summary box to post and page edit screens
        $loader->add_action( 'post_submitbox_misc_actions', $this, 'add_edit_summary_textbox' );

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

        // save edit summary as custom meta data when post is updated (needs to
        // have priority < 10 so the meta data is added before the revision
        // copy is made
        $loader->add_action( 'post_updated', $this, 'save_post_edit_summary', 5, 2 );

        // show revisions screen in editor by default
		$loader->add_filter( 'default_hidden_meta_boxes', $this, 'unhide_revisions_meta_box', 10, 2 );
	}

    /**
	 * Add edit summary support to certain post types, so they can have
	 * relevant tools added to their edit pages.
	 */
	public function add_edit_summary_support() {
		// support any post type that uses revisions
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'revisions' ) ) {
				add_post_type_support( $post_type, 'ssl-alp-edit-summaries' );
			}
		}
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

		if ( ! post_type_supports( $post->post_type, 'ssl-alp-edit-summaries' ) ) {
			// unsupported post type
			return false;
		}

		// check if setting is enabled, and if user has permission
		// 'edit_post' capability == 'edit_posts', 'edit_page' == 'edit_pages', etc. (see wp-includes/capabilities.php)
		if ( ! get_option( "ssl_alp_{$post->post_type}_edit_summaries" ) || ! current_user_can( "edit_{$post->post_type}", $post->ID ) ) {
			// disabled for posts, or user not allowed to view
			return false;
		}

		return true;
	}

    /**
	 * Add edit summary textbox within the "Update" panel to posts and pages
	 */
	public function add_edit_summary_textbox( $post ) {
		if ( $post->post_status == 'auto-draft' ) {
			// post is newly created, so don't show an edit summary box
			return;
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		}

		// add a nonce to check later
		wp_nonce_field( 'ssl-alp-edit-summary', 'ssl_alp_edit_summary_nonce' );

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post-edit-summary-display.php';
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

		// get the stored meta value from the revision
		$revision_meta = get_post_meta( $revision->ID, 'edit_summary', true );

		if ( empty( $revision_meta ) || ! is_array( $revision_meta ) ) {
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
		if ( ! $this->edit_summary_allowed( $revision ) ) {
			// return as-is
			return $data;
		}

		// get revision edit summary
		$revision_meta = get_post_meta( $revision->ID, 'edit_summary', true );

		if ( empty( $revision_meta ) || ! is_array( $revision_meta ) ) {
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
	 * Restore the revision's meta values to the parent post.
	 */
	public function restore_post_revision_meta( $post_id, $revision_id ) {
		// Clear any existing metas
		delete_post_meta( $post_id, 'edit_summary' );

		// get the stored meta value from the revision we are reverting to
		$target_revision_meta = get_post_meta( $revision_id, 'edit_summary', true );

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

		if ( ! $this->edit_summary_allowed( $revision ) ) {
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

		if ( ! is_array( $parent_meta ) || ! is_array( $revision_meta ) ) {
			// invalid
		} elseif ( ! isset( $parent_meta["message"] ) || ! isset( $revision_meta["message"] ) ) {
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
		} elseif ( ! $this->edit_summary_allowed( $post ) ) {
			return;
		} elseif ( ! isset( $_POST['ssl_alp_edit_summary_nonce'] ) || ! wp_verify_nonce( $_POST['ssl_alp_edit_summary_nonce'], 'ssl-alp-edit-summary' ) ) {
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

    public function unhide_revisions_meta_box( $hidden, $screen ) {
		if ( ! post_type_supports( $screen->post_type, 'ssl-alp-edit-summaries' ) ) {
			// return as-is
			return $hidden;
		}

		// remove revisions from hidden list, if present
		if ( ( $key = array_search( 'revisionsdiv', $hidden ) ) !== false ) {
			unset( $hidden[$key] );
		}

		return $hidden;
	}
}
