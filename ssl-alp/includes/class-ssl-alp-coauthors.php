<?php
/**
 * Coauthor tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Coauthor functionality.
 */
class SSL_ALP_Coauthors extends SSL_ALP_Module {
	/**
	 * Post types with coauthor support.
	 *
	 * @var array
	 */
	protected $supported_post_types = array(
		'post',
	);

	/**
	 * Coauthor term slug prefix.
	 *
	 * @var string
	 */
	protected $coauthor_term_slug_prefix = 'ssl-alp-coauthor-';

	/**
	 * HAVING SQL clause.
	 *
	 * @var string
	 */
	protected $having_terms = '';

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_allow_multiple_authors',
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
		 * Post multiple author settings field.
		 */
		add_settings_field(
			'ssl_alp_author_settings',
			__( 'Authors', 'ssl-alp' ),
			array( $this, 'author_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		/**
		 * Core coauthor behaviour.
		 */

		// Register authors taxonomy.
		$loader->add_action( 'init', $this, 'register_taxonomy' );

		// Remove single author support from supported post types.
		$loader->add_action( 'init', $this, 'remove_author_support' );

		// Set the current user as the default coauthor on new drafts.
		$loader->add_action( 'save_post', $this, 'add_user_to_draft', 10, 2 );

		// Disallow creation of new terms directly (this is temporarily disabled by
		// `associate_inventory_post_with_term`).
		// NOTE: if this line is changed, the enable_disallow_insert_term_filter and
		// disable_disallow_insert_term_filter functions must also be updated.
		$loader->add_filter( 'pre_insert_term', $this, 'disallow_insert_term', 10, 2 );

		// Delete any invalid coauthors when post terms are set.
		$loader->add_action( 'added_term_relationship', $this, 'reject_invalid_coauthor_terms', 10, 3 );

		// Check and if necessary update the primary author when post coauthor terms are set.
		$loader->add_action( 'set_object_terms', $this, 'check_post_author', 10, 4 );

		// Modify SQL queries to include coauthors where appropriate.
		$loader->add_filter( 'posts_where', $this, 'posts_where_filter', 10, 2 );
		$loader->add_filter( 'posts_join', $this, 'posts_join_filter', 10, 2 );
		$loader->add_filter( 'posts_groupby', $this, 'posts_groupby_filter', 10, 2 );

		// Allow public coauthor query vars.
		$loader->add_filter( 'query_vars', $this, 'whitelist_search_query_vars' );

		// Support coauthor querystrings in WP_Query.
		$loader->add_action( 'parse_tax_query', $this, 'parse_query_vars' );

		// Filter to send comment notification/moderation emails to multiple authors.
		$loader->add_filter( 'comment_notification_recipients', $this, 'filter_comment_notification_email_recipients', 10, 2 );
		$loader->add_filter( 'comment_moderation_recipients', $this, 'filter_comment_moderation_email_recipients', 10, 2 );

		/**
		 * User administration hooks.
		 */

		// Create user term when user logs in for the first time (required where ALP is not network active).
		$loader->add_action( 'wp_login', $this, 'check_coauthor_term_on_login', 10, 2 );

		// Stop deletion of user's posts when their account is deleted (this is handled separately by `delete_user_action`).
		$loader->add_filter( 'post_types_to_delete_with_user', $this, 'filter_post_types_to_delete_with_user' );

		// Create user term when user is created, updated or added to a blog on a network.
		$loader->add_action( 'user_register', $this, 'add_coauthor_term', 10, 1 );
		$loader->add_action( 'add_user_to_blog', $this, 'add_coauthor_term', 10, 1 );
		$loader->add_action( 'profile_update', $this, 'update_coauthor_term', 10, 2 );

		// Delete or reassign user terms from posts when a user is deleted on a single site installation.
		$loader->add_action( 'delete_user', $this, 'delete_user_action', 10, 2 );

		// Delete or reassign user terms from posts when a user is deleted on a network site installation.
		$loader->add_action( 'remove_user_from_blog', $this, 'remove_user_from_blog', 10, 3 );

		// Filter to allow coauthors to edit posts.
		$loader->add_filter( 'user_has_cap', $this, 'filter_user_has_cap', 10, 4 );

		/**
		 * Display hooks.
		 */

		// Hooks to modify the published post number count on the Users WP List Table.
		// These are required because the count_many_users_posts() function has no hooks.
		$loader->add_filter( 'manage_users_columns', $this, 'filter_manage_users_columns' );
		$loader->add_filter( 'manage_users_custom_column', $this, 'filter_manage_users_custom_column', 10, 3 );

		// Override the default "Mine" filter on the admin post list.
		$loader->add_filter( 'views_edit-post', $this, 'filter_edit_post_views', 10, 1 );

		// Include coauthored posts in post counts.
		// Unfortunately, this doesn't filter results retrieved with `count_many_users_posts`, which
		// also doesn't have hooks to allow filtering; therefore know that this filter doesn't catch
		// every count event.
		$loader->add_filter( 'get_usernumposts', $this, 'filter_count_user_posts', 10, 2 );

		// Make sure we've correctly set author data on author pages.
		// Use posts_selection since it's after WP_Query has built the request and before it's queried any posts.
		$loader->add_action( 'posts_selection', $this, 'fix_author_page', 10, 0 );
		$loader->add_filter( 'the_author', $this, 'fix_author_page_filter', 10, 1 );

		// Filter the display of coauthor terms in the admin post list.
		$loader->add_filter( 'ssl-alp-coauthor_name', $this, 'filter_coauthor_term_display', 10, 3 );

		/**
		 * Authors widget.
		 */

		// Register the authors widget.
		$loader->add_action( 'widgets_init', $this, 'register_users_widget' );
	}

	/**
	 * Author settings partial.
	 */
	public function author_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/author-settings-display.php';
	}

	public function enable_disallow_insert_term_filter() {
		add_filter( 'pre_insert_term', array( $this, 'disallow_insert_term' ), 10, 2 );
	}

	public function disable_disallow_insert_term_filter() {
		remove_filter( 'pre_insert_term', array( $this, 'disallow_insert_term' ), 10, 2 );
	}

	/**
	 * Register users widget.
	 */
	public function register_users_widget() {
		register_widget( 'SSL_ALP_Coauthors_Widget' );
	}

	/**
	 * Register the coauthor taxonomy and add post type support.
	 */
	public function register_taxonomy() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		// Register new taxonomy so that we can store all of the relationships.
		$args = array(
			'hierarchical'          => false,
			'label'                 => __( 'Authors', 'ssl-alp' ),
			'query_var'             => false,
			'rewrite'               => false,
			'public'                => false,
			'sort'                  => true,  // Remember order terms are added to posts.
			'args'                  => array(
				// Default arguments used by `wp_get_object_terms`, see https://core.trac.wordpress.org/ticket/40496.
				'orderby' => 'term_order',
			),
			'capabilities' => array(
				'manage_terms'  =>   'do_not_allow',
				'edit_terms'    =>   'do_not_allow',
				'delete_terms'  =>   'do_not_allow',
				'assign_terms'  =>   'edit_posts', // Needed to allow assignment in block editor.
			),
			'show_ui'               => true,  // Show selector on edit page.
			'show_in_menu'          => false, // Hide term edit page.
			'show_in_rest'          => true,  // Needed for block editor support.
			'show_admin_column'     => true,  // Show associated terms in admin edit screen.
			'update_count_callback' => array( $this, 'update_users_posts_count' ), // Override count.
		);

		// Create coauthor taxonomy.
		register_taxonomy( 'ssl-alp-coauthor', $this->supported_post_types, $args );
	}

	/**
	 * Remove author support (replaced by coauthor support).
	 */
	public function remove_author_support() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		foreach ( $this->supported_post_types as $post_type ) {
			/**
			 * Note that because author support is removed here, `delete_user_action`
			 * must handle additional work. If the call below is altered, so too must
			 * `delete_user_action` be.
			 */
			remove_post_type_support( $post_type, 'author' );
		}
	}

	/**
	 * Filter post types to delete with user. We don't want to automatically delete posts that
	 * support coauthors as we may wish to instead reassign their authors.
	 *
	 * @param array $types Post types.
	 * @return array Post types with supported coauthor types removed.
	 */
	public function filter_post_types_to_delete_with_user( $types ) {
		// Remove supported post types as we manually handle their deletion with users.
		return array_diff( $types, $this->supported_post_types );
	}

	/**
	 * Check if coauthors are enabled for this post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @return bool
	 */
	public function post_supports_coauthors( $post = null ) {
		$post      = get_post( $post );
		$post_type = get_post_type( $post );

		return $this->is_post_type_enabled( $post_type );
	}

	/**
	 * Check if coauthors are enabled for post type.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public function is_post_type_enabled( $post_type = null ) {
		if ( is_null( $post_type ) ) {
			// Get current post type.
			$post_type = get_post_type();
		}

		return in_array( $post_type, $this->supported_post_types, true );
	}

	/**
	 * Get coauthor term.
	 *
	 * @param int|WP_User $user User ID or object.
	 * @return WP_Term|false User term or false if term not found. Note that returning false when
	 *                       the term is not found matches core behaviour.
	 */
	public function get_coauthor_term( $user ) {
		if ( is_null( $user ) ) {
			return false;
		}

		// Retrieve term associated with the user.
		return get_term_by( 'slug', $this->get_coauthor_term_slug( $user ), 'ssl-alp-coauthor' );
	}

	/**
	 * Add author term. This is called when a user is created, but also when a user is
	 * added to a new blog.
	 *
	 * @param int|WP_User $coauthor User ID or object.
	 */
	public function add_coauthor_term( $coauthor ) {
		if ( is_int( $coauthor ) ) {
			// Get user by their ID.
			$coauthor = get_user_by( 'id', $coauthor );
		}

		if ( ! is_object( $coauthor ) ) {
			return;
		}

		// Get coauthor term.
		$term = $this->get_coauthor_term( $coauthor );

		if ( ! $term ) {
			// Term doesn't yet exist.
			$args = array(
				'slug' => $this->get_coauthor_term_slug( $coauthor ),
			);

			// Temporarily disable the filter that blocks creation of terms in the ssl-alp-coauthor
			// taxonomy.
			$this->disable_disallow_insert_term_filter();

			wp_insert_term( $coauthor->display_name, 'ssl-alp-coauthor', $args );

			// Re-enable the filter.
			$this->enable_disallow_insert_term_filter();
		} else {
			// Update term.
			$this->update_coauthor_term( $coauthor->ID, $coauthor );
		}
	}

	/**
	 * Update coauthor term.
	 *
	 * @param int     $user_id         User ID.
	 * @param WP_User $old_user_object Old user object.
	 */
	public function update_coauthor_term( $user_id, $old_user_object ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! is_object( $user ) ) {
			return;
		}

		// Get coauthor term (uses nicename from old user).
		$term = $this->get_coauthor_term( $old_user_object );

		// Create updated term arguments.
		$args = array(
			'name' => $user->display_name,
			'slug' => $this->get_coauthor_term_slug( $user ),
		);

		// Temporarily disable the filter that blocks creation of terms in the ssl-alp-coauthor
		// taxonomy.
		$this->disable_disallow_insert_term_filter();

		// Set term name.
		wp_update_term( $term->term_id, 'ssl-alp-coauthor', $args );

		// Re-enable the filter.
		$this->enable_disallow_insert_term_filter();
	}

	/**
	 * Get coauthor term slug.
	 *
	 * @param WP_User $user User object.
	 * @return string
	 */
	private function get_coauthor_term_slug( $user ) {
		// Use nicename as this can be extracted from the slug later (it doesn't contain spaces).
		return $this->coauthor_term_slug_prefix . $user->user_nicename;
	}

	/**
	 * Get user from coauthor term.
	 *
	 * @param WP_Term $term Coauthor term.
	 *
	 * @return WP_User
	 */
	private function get_user_from_coauthor_term( $term ) {
		if ( substr( $term->slug, 0, strlen( $this->coauthor_term_slug_prefix ) ) !== $this->coauthor_term_slug_prefix ) {
			// The slug doesn't contain the prefix.
			return false;
		}

		// Remove prefix.
		$user_nicename = substr( $term->slug, strlen( $this->coauthor_term_slug_prefix ) );

		return get_user_by( 'slug', $user_nicename );
	}

	/**
	 * Delete coauthor term.
	 *
	 * @param WP_User $user User object.
	 */
	public function delete_coauthor_term( $user ) {
		if ( is_null( $user ) ) {
			return false;
		}

		// Get the user's term.
		$term = $this->get_coauthor_term( $user );

		if ( $term ) {
			// Delete user's term.
			wp_delete_term( $term->term_id, 'ssl-alp-coauthor' );
		}
	}

	/**
	 * Check that the coauthor term exists on login. Used for the edge case where ALP is used on
	 * multisite, but only active on a blog. In this case, when a user is created the logic runs in
	 * terms of the network, and ALP hooks are not therefore loaded. This code runs when the created
	 * user logs in, creating the coauthor term.
	 *
	 * @param string  $user_login User login.
	 * @param WP_User $user       User object.
	 */
	public function check_coauthor_term_on_login( $user_login, $user ) {
		$this->add_coauthor_term( $user );
	}

	/**
	 * Rebuild coauthor terms.
	 *
	 * This adds coauthor terms for all users, generating them if necessary, and assigns posts
	 * authored by those users to those coauthor terms.
	 */
	public function rebuild_coauthors() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		// Allow unlimited execution time.
		ini_set( 'max_execution_time', 0 );

		// Get all user IDs.
		$users = get_users();

		foreach ( $users as $user ) {
			// Add coauthor terms.
			$this->add_coauthor_term( $user->ID );

			// Remove user from cache to help reduce memory usage.
			clean_user_cache( $user );
		}

		// Get all posts.
		$posts = get_posts(
			array(
				'post_type'   => $this->supported_post_types,
				'post_status' => get_post_stati(),
				'nopaging'    => true,
			)
		);

		// Set coauthor terms on each of the posts. The get_coauthors() function will return at a
		// minimum the existing post author if no additional coauthors are tagged against the post,
		// which is the case for sites with existing posts and users before this plugin is enabled;
		// therefore, this loop effectively populates the coauthor term taxonomy relationships to
		// each post.
		foreach ( $posts as $post ) {
			if ( wp_is_post_autosave( $post ) || $this->is_post_autodraft( $post ) ) {
				continue;
			}

			$coauthors = $this->get_coauthors( $post );
			$this->set_coauthors( $post, $coauthors );
		}
	}

	/**
	 * Filter terms displayed by the taxonomy meta box.
	 *
	 * @param array  $terms_to_edit Terms displayed by default.
	 * @param string $taxonomy      Taxonomy name.
	 * @global $post
	 */
	public function filter_terms_to_edit( $terms_to_edit, $taxonomy ) {
		global $post;

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		if ( 'ssl-alp-coauthor' !== $taxonomy ) {
			// Not our taxonomy.
			return $terms_to_edit;
		}

		// Get terms in descending chronological order.
		$terms = wp_get_object_terms(
			$post->ID,
			'ssl-alp-coauthor',
			array(
				'orderby' => 'term_order',
				'order'   => 'ASC',
			)
		);

		if ( ! $terms ) {
			return false;
		}

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$term_names = array();

		foreach ( $terms as $term ) {
			$term_names[] = $term->name;
		}

		return esc_attr( join( ',', $term_names ) );
	}

	/**
	 * Unset the post count column because it's going to be inaccurate; instead provide our own.
	 *
	 * @param array $columns Post columns.
	 * @return array Post columns without post count column.
	 */
	public function filter_manage_users_columns( $columns ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $columns;
		}

		$new_columns = array();

		// Unset and add our column while retaining the order of the columns.
		foreach ( $columns as $column_name => $column_title ) {
			if ( 'posts' === $column_name ) {
				$new_columns['ssl-alp-coauthors-post-count'] = __( 'Posts', 'ssl-alp' );
			} else {
				$new_columns[ $column_name ] = $column_title;
			}
		}

		return $new_columns;
	}

	/**
	 * Provide an accurate count when looking up the number of published posts for a user.
	 *
	 * @param string $column_value Column value.
	 * @param string $column_name  Column name.
	 * @param int    $user_id      User ID.
	 */
	public function filter_manage_users_custom_column( $column_value, $column_name, $user_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $column_value;
		}

		if ( 'ssl-alp-coauthors-post-count' !== $column_name ) {
			// Not the column we want to modify.
			return $column_value;
		}

		// Filter count_user_posts() so it provides an accurate number.
		$post_count = count_user_posts( $user_id );
		$user       = get_user_by( 'id', $user_id );

		if ( $post_count > 0 ) {
			$column_value .= sprintf(
				'<a href="%1$s" title="%2$s" class="edit">%3$d</a>',
				esc_url( 'edit.php?taxonomy=ssl-alp-coauthor&amp;term=' . $this->get_coauthor_term_slug( $user ) ),
				esc_attr__( 'View posts by this author', 'ssl-alp' ),
				esc_html( $post_count )
			);
		} else {
			// No link to empty post page.
			$column_value .= 0;
		}

		return $column_value;
	}

	/**
	 * Allows coauthors to edit the post they're coauthors of.
	 *
	 * @param array   $all_capabilities All user capabilities.
	 * @param mixed   $unused           Unused.
	 * @param array   $args             Capability arguments.
	 * @param WP_User $user             User object.
	 */
	public function filter_user_has_cap( $all_capabilities, $unused, $args, $user ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $all_capabilities;
		}

		$requested_capability = $args[0];

		// Assume post.
		$post = get_post( isset( $args[2] ) ? $args[2] : 0 );

		if ( is_null( $post ) ) {
			return $all_capabilities;
		}

		$post_type = get_post_type_object( get_post_type( $post ) );

		if ( ! $post_type || 'revision' === $post_type->name ) {
			return $all_capabilities;
		}

		$unfiltered_capabilities = array(
			$post_type->cap->edit_post,
			'edit_post', // Need to filter this too, unfortunately: http://core.trac.wordpress.org/ticket/22415.
			$post_type->cap->edit_others_posts, // This as well: http://core.trac.wordpress.org/ticket/22417.
		);

		if ( ! in_array( $requested_capability, $unfiltered_capabilities, true ) ) {
			// Capability is not one we want to change. This is the case if the user is a researcher
			// or admin, for example (they can already edit other posts).
			return $all_capabilities;
		} elseif ( ! is_user_logged_in() || ! $this->is_coauthor_for_post( $user, $post ) ) {
			// User isn't coauthor of the specified post.
			return $all_capabilities;
		}

		$post_status = get_post_status( $post );

		if ( 'publish' === $post_status &&
			( isset( $post_type->cap->edit_published_posts ) && ! empty( $user->all_capabilities[ $post_type->cap->edit_published_posts ] ) ) ) {
			// Allow edit of published posts for this call.
			$all_capabilities[ $post_type->cap->edit_published_posts ] = true;
		} elseif ( 'private' === $post_status &&
			( isset( $post_type->cap->edit_private_posts ) && ! empty( $user->all_capabilities[ $post_type->cap->edit_private_posts ] ) ) ) {
			// Allow edit of private posts for this call.
			$all_capabilities[ $post_type->cap->edit_private_posts ] = true;
		}

		// Allow edit of others posts for this call.
		$all_capabilities[ $post_type->cap->edit_others_posts ] = true;

		return $all_capabilities;
	}

	/**
	 * Filter the views listed on the admin post list to add a "Mine" view.
	 *
	 * @param array $views Post list views.
	 * @return array Post list views with "Mine" column added.
	 */
	public function filter_edit_post_views( $views ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $views;
		}

		// Current user.
		$user = wp_get_current_user();

		// Get post count.
		$post_count = $this->get_user_post_count( $user );

		if ( is_null( $post_count ) ) {
			// User has no posts.
			$post_count = 0;
		}

		// Get current user's coauthor term slug.
		$coauthor_slug = $this->get_coauthor_term_slug( $user );

		// Build URL arguments.
		$mine_args = array(
			'taxonomy' => 'ssl-alp-coauthor',
			'term'     => $coauthor_slug,
		);

		$request_tax  = get_query_var( 'taxonomy' );
		$request_term = get_query_var( 'term' );

		// Check if the current page is the "Mine" view.
		if ( 'ssl-alp-coauthor' === $request_tax && $coauthor_slug === $request_term ) {
			$class = 'current';
		} else {
			$class = '';
		}

		// Flip views.
		$views = array_reverse( $views );
		// Get "All" view off end.
		$all_view = array_pop( $views );

		// Add "Mine" view to end.
		$views['mine'] = sprintf(
			'<a class="%s" href="%s">%s <span class="count">(%s)</span></a>',
			esc_attr( $class ),
			esc_url( add_query_arg( array_map( 'rawurlencode', $mine_args ), admin_url( 'edit.php' ) ) ),
			esc_html__( 'Mine', 'ssl_alp' ),
			esc_html( $post_count )
		);

		// Add "All" view to end, and stop it from showing up if "Mine" is enabled.
		$views['all'] = str_replace( $class, '', $all_view );

		// Flip back round.
		$views = array_reverse( $views );

		return $views;
	}

	/**
	 * When we update the terms at all, we should update the published post
	 * count for each author.
	 *
	 * @param array       $tt_ids   Term taxonomy IDs.
	 * @param WP_Taxonomy $taxonomy Taxonomy.
	 */
	public function update_users_posts_count( $tt_ids, $taxonomy ) {
		if ( 'ssl-alp-coauthor' !== $taxonomy->name ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'         => $taxonomy->name,
				'term_taxonomy_id' => $tt_ids,
				'hide_empty'       => false,
			)
		);

		foreach ( (array) $terms as $term ) {
			$this->update_author_term_post_count( $term );
		}
	}

	/**
	 * Update the post count associated with an author term.
	 *
	 * @param WP_Term $term Term.
	 * @global $wpdb
	 */
	public function update_author_term_post_count( $term ) {
		global $wpdb;

		$coauthor = $this->get_user_from_coauthor_term( $term );

		if ( ! $coauthor ) {
			return new WP_Error( 'missing-coauthor', __( 'No co-author exists for that term', 'ssl-alp' ) );
		}

		$query  = "SELECT COUNT({$wpdb->posts}.ID) FROM {$wpdb->posts}";
		$query .= " LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
		$query .= " LEFT JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

		$having_terms             = $wpdb->prepare( "{$wpdb->term_taxonomy}.term_id = %d", $term->term_id );
		$having_terms_and_authors = $having_terms;

		$having_terms_and_authors .= $wpdb->prepare( " OR {$wpdb->posts}.post_author = %d", $coauthor->ID );

		$post_types = array_map( 'sanitize_key', $this->supported_post_types );
		$post_types = "'" . implode( "','", $post_types ) . "'";

		$query .= " WHERE ({$having_terms_and_authors}) AND {$wpdb->posts}.post_type IN ({$post_types}) AND {$wpdb->posts}.post_status = 'publish'";

		$query .= $wpdb->prepare(
			"
			GROUP BY {$wpdb->posts}.ID
			HAVING MAX( IF ( {$wpdb->term_taxonomy}.taxonomy = %s, IF ( {$having_terms}, 2, 1 ), 0 ) ) <> 1
			",
			'ssl-alp-coauthor'
		);

		$count = $wpdb->query( $query );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );

		// Invalidate term cache.
		clean_term_cache( $term->term_id, 'ssl-alp-coauthor', false );
	}

	/**
	 * Filter the count_user_posts() core function to include our correct count.
	 *
	 * @param int $count   Post count.
	 * @param int $user_id User ID.
	 * @return int Real post count.
	 */
	public function filter_count_user_posts( $count, $user_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $count;
		}

		$real_count = $this->get_user_post_count( $user_id );

		if ( is_null( $real_count ) ) {
			// Use the default.
			$real_count = $count;
		}

		return $real_count;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored.
	 *
	 * @param string $join  JOIN SQL clause.
	 * @param string $query SQL query.
	 * @return string
	 */
	public function posts_join_filter( $join, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $join;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
			// Not an author query, so return unmodified.
			return $join;
		}

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl-alp-coauthor' ) ) {
			// Not a valid post type, so return unmodified.
			return $join;
		}

		if ( empty( $this->having_terms ) ) {
			return $join;
		}

		$term_relationship_join = " LEFT JOIN {$wpdb->term_relationships} AS tr1 ON ({$wpdb->posts}.ID = tr1.object_id)";

		$term_taxonomy_join = " LEFT JOIN {$wpdb->term_taxonomy} ON ( tr1.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

		$join .= $term_relationship_join;
		$join .= $term_taxonomy_join;

		return $join;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored.
	 *
	 * @param string $where WHERE SQL clause.
	 * @param string $query SQL query.
	 * @return string
	 */
	public function posts_where_filter( $where, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $where;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
			// Not an author query, so return unmodified.
			return $where;
		}

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl-alp-coauthor' ) ) {
			// Not a valid post type, so return unmodified.
			return $where;
		}

		if ( $query->get( 'author_name' ) ) {
			// author_name is actually user_nicename.
			$author_nicename = $query->get( 'author_name' );

			if ( is_null( $author_nicename ) ) {
				// No author defined.
				return $where;
			}

			// user_nicename == slug.
			$coauthor = get_user_by( 'slug', $author_nicename );
		} else {
			$author_data = get_userdata( $query->get( 'author' ) );

			if ( ! is_object( $author_data ) ) {
				// No author defined.
				return $where;
			}

			$coauthor = get_user_by( 'login', $author_data->user_login );
		}

		$terms       = array();
		$author_term = $this->get_coauthor_term( $coauthor );

		if ( $author_term ) {
			$terms[] = $author_term;
		}

		if ( ! empty( $terms ) ) {
			$terms_implode      = '';
			$this->having_terms = '';

			foreach ( $terms as $term ) {
				$terms_implode      .= "({$wpdb->term_taxonomy}.taxonomy = 'ssl-alp-coauthor' AND {$wpdb->term_taxonomy}.term_id = '{$term->term_id}') OR ";
				$this->having_terms .= " {$wpdb->term_taxonomy}.term_id = '{$term->term_id}' OR ";
			}

			$terms_implode      = rtrim( $terms_implode, ' OR' );
			$this->having_terms = rtrim( $this->having_terms, ' OR' );

			// Match "wp_posts.post_author = [number]" or "wp_posts.post_author IN ([list of numbers])"
			// and append "OR (wp_term_taxonomy.taxonomy = 'ssl-alp-coauthor' AND wp_term_taxonomy.term_id = '6')".
			$where = preg_replace(
				'/(\b(?:' . $wpdb->posts . '\.)?post_author\s*(?:=|IN)\s*\(?(\d+)\)?)/',
				'($1 OR ' . $terms_implode . ')',
				$where,
				1
			);
		}

		return $where;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored.
	 *
	 * @param string $groupby GROUP BY SQL clause.
	 * @param string $query   SQL query.
	 * @return string
	 */
	public function posts_groupby_filter( $groupby, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $groupby;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
			// Not an author query, so return unmodified.
			return $groupby;
		}

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl-alp-coauthor' ) ) {
			// Not a valid post type, so return unmodified.
			return $groupby;
		}

		if ( $this->having_terms ) {
			$having  = "MAX( IF ( {$wpdb->term_taxonomy}.taxonomy = 'ssl-alp-coauthor', IF ( {$this->having_terms}, 2, 1 ), 0 ) ) <> 1 ";
			$groupby = "{$wpdb->posts}.ID HAVING {$having}";
		}

		return $groupby;
	}

	/**
	 * Checks if the specified post is an autodraft, i.e. a new post.
	 *
	 * @param WP_Post $post Post object.
	 */
	private function is_post_autodraft( $post ) {
		return 'auto-draft' === $post->post_status;
	}

	/**
	 * Set the current user as the author in the coauthor meta box when a new post draft
	 * is created.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function add_user_to_draft( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		if ( ! $this->is_post_autodraft( $post ) ) {
			// Not a draft.
			return;
		} elseif ( ! $this->post_supports_coauthors( $post ) ) {
			return;
		}

		// Get updated coauthors.
		$coauthors = array( wp_get_current_user() );

		$this->set_coauthors( $post, $coauthors );
	}

	/**
	 * Disallow the creation of new terms under normal circumstances.
	 *
	 * This is to avoid users being able to create terms in the coauthor taxonomy directly; terms
	 * should only be created when a new user is created.
	 *
	 * This filter is disabled temporarily by `enable_disallow_insert_term_filter` to allow creation
	 * of new terms in acceptable circumstances, then reenabled by
	 * `enable_disallow_insert_term_filter`.
	 *
	 * @param string $term     The term.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return string|WP_Error $term The term, or error.
	 */
	public function disallow_insert_term( $term, $taxonomy ) {
		if ( 'ssl-alp-coauthor' !== $taxonomy ) {
			return $term;
		}

		// Return an error in all circumstances.
		return new WP_Error(
			'disallow_insert_term',
			__( 'Your role does not have permission to add terms to this taxonomy', 'ssl-alp' )
		);
	}

	/**
	 * Delete invalid coauthors when a post is saved.
	 *
	 * Unfortunately there is no way to filter terms before they are set on a post, so this function
	 * deletes them afterwards instead.
	 *
	 * @param int    $object_id Object ID.
	 * @param int    $tt_id     Term taxonomy ID.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function reject_invalid_coauthor_terms( $object_id, $tt_id, $taxonomy ) {
		if ( 'ssl-alp-coauthor' !== $taxonomy ) {
			return;
		}

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		$term = get_term_by( 'term_taxonomy_id', $tt_id, 'ssl-alp-coauthor' );

		if ( ! $term ) {
			// Nothing to do here.
			return;
		}

		// Check term is valid.
		$coauthor = $this->get_user_from_coauthor_term( $term );

		if ( ! $coauthor ) {
			// This is not a valid coauthor term - delete it.
			wp_delete_term( $term->term_id, 'ssl-alp-coauthor' );
		}
	}

	/**
	 * Check and if necessary update the primary author when post coauthor terms are set.
	 *
	 * This checks that WordPress core's post author is consistent with the coauthor order.
	 * If a post is edited to move the post author to a non-first coauthor position, this
	 * function changes the post author to whoever is now first.
	 *
	 * @param int    $post_id  Post ID.
	 * @param array  $term_ids Term IDs.
	 * @param array  $tt_ids   Term taxonomy IDs.
	 * @param string $taxonomy Taxonomy name.
	 * @global $wpdb
	 */
	public function check_post_author( $post_id, $term_ids, $tt_ids, $taxonomy ) {
		global $wpdb;

		if ( 'ssl-alp-coauthor' !== $taxonomy ) {
			return;
		}

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		// Get post.
		$post = get_post( $post_id );

		if ( wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( ! $this->post_supports_coauthors( $post ) ) {
			return;
		}

		// Get post's previous author (the post's author in the database row).
		$existing_primary_author = get_user_by( 'id', $post->post_author );

		$terms = array();

		foreach ( $term_ids as $term_id ) {
			$terms[] = get_term( $term_id, 'ssl-alp-coauthor' );
		}

		$first_term = reset( $terms );

		if ( empty( $terms ) || is_wp_error( $first_term ) ) {
			// Empty terms - remove the author.
			$new_primary_author_id = 0;
		} else {
			$new_primary_author    = $this->get_user_from_coauthor_term( $first_term );
			$new_primary_author_id = $new_primary_author->ID;

			if ( ! empty( $existing_primary_author ) && $existing_primary_author->ID === $new_primary_author_id ) {
				// Primary author hasn't changed.
				return;
			}
		}

		// Update primary author.
		wp_update_post(
			array(
				'ID'          => $post->ID,
				'post_author' => $new_primary_author_id,
			)
		);

		clean_post_cache( $post->ID );
	}

	/**
	 * Get coauthors.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	public function get_coauthors( $post = null ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			// No post.
			return;
		}

		// Empty coauthors list.
		$coauthors = array();

		// Get this post's terms.
		$coauthor_terms = $this->get_coauthor_terms_for_post( $post );

		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			// This post has coauthors.
			foreach ( $coauthor_terms as $coauthor_term ) {
				$post_author = $this->get_user_from_coauthor_term( $coauthor_term );

				// In case the user has been deleted while plugin was deactivated.
				if ( ! empty( $post_author ) ) {
					$coauthors[] = $post_author;
				}
			}
		}

		// Get the post's primary author.
		$post_author = get_user_by( 'ID', $post->post_author );

		// Try to ensure at least the post's primary author is in the list of coauthors.
		if ( ! empty( $post_author ) && ! in_array( $post_author, $coauthors, false ) ) { // Fuzzy comparison required.
			// Post primary author exists but isn't listed as a coauthor,
			// so add them to the start of the coauthors array.
			array_unshift( $coauthors, $post_author );
		}

		return $coauthors;
	}

	/**
	 * Set one or more coauthors for a post. The first specified coauthor is set as
	 * the post's primary author.
	 *
	 * @param int|WP_Post|null $post      Post ID or post object. Defaults to global $post.
	 * @param array            $coauthors Updated coauthors.
	 * @global $wpdb
	 */
	public function set_coauthors( $post, $coauthors ) {
		global $wpdb;

		$post = get_post( $post );

		if ( ! is_array( $coauthors ) ) {
			// Invalid input.
			return;
		}

		// Get post's previous author (the post's author in the database row).
		$existing_primary_author = get_user_by( 'id', $post->post_author );

		// Deduplicate.
		$coauthors = array_unique( $coauthors, SORT_REGULAR );

		// Remove invalid coauthors.
		foreach ( $coauthors as $coauthor ) {
			if ( ! is_object( $coauthor ) ) {
				// Invalid user specified - remove.
				unset( $coauthors[ array_search( $coauthor, $coauthors ) ] );
			}
		}

		// Create list of term IDs.
		$coauthor_terms    = array_map( array( $this, 'get_coauthor_term' ), $coauthors );
		$coauthor_term_ids = wp_list_pluck( $coauthor_terms, 'term_id' );

		// Update post's coauthors.
		wp_set_post_terms( $post->ID, $coauthor_term_ids, 'ssl-alp-coauthor', false );
	}

	/**
	 * Whitelist coauthor query vars.
	 *
	 * This allows coauthored posts to be queried publicly.
	 *
	 * @param string[] $public_query_vars Array of public query vars.
	 */
	public function whitelist_search_query_vars( $public_query_vars ) {
		global $ssl_alp;

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $public_query_vars;
		}

		if ( ! $ssl_alp->search->current_user_can_advanced_search() ) {
			// Advanced search disabled.
			return $public_query_vars;
		}

		// Custom query vars to make public. These are sanitised and handled by
		// `parse_query_vars`.
		$custom_query_vars = array(
			'ssl_alp_coauthor__and',
			'ssl_alp_coauthor__in',
			'ssl_alp_coauthor__not_in',
		);

		// Merge new query vars into existing ones.
		return wp_parse_args( $custom_query_vars, $public_query_vars );
	}

	/**
	 * Sanitise coauthor querystrings and inject them as taxonomy filters into WP_Query.
	 *
	 * This detects values submitted through the custom search function and turns them into the
	 * filters expected by WP_Query.
	 *
	 * @param WP_Query $query The query.
	 */
	public function parse_query_vars( $query ) {
		global $ssl_alp;

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		if ( ! $ssl_alp->search->current_user_can_advanced_search() ) {
			// Advanced search disabled.
			return;
		}

		// Taxonomy query.
		$tax_query = array();

		// Sanitize submitted values.
		$ssl_alp->core->sanitize_querystring( $query, 'ssl_alp_coauthor__and' );
		$ssl_alp->core->sanitize_querystring( $query, 'ssl_alp_coauthor__in' );
		$ssl_alp->core->sanitize_querystring( $query, 'ssl_alp_coauthor__not_in' );

		// Get coauthor query vars.
		$coauthor_and    = $query->get( 'ssl_alp_coauthor__and' );
		$coauthor_in     = $query->get( 'ssl_alp_coauthor__in' );
		$coauthor_not_in = $query->get( 'ssl_alp_coauthor__not_in' );

		if ( ! empty( $coauthor_and ) && 1 === count( $coauthor_and ) ) {
			// There is only one AND term specified, so merge it into IN.
			$coauthor_in[] = absint( reset( $coauthor_and ) );
			$coauthor_and  = array();

			// Update querystring. This matches core behaviour for categories
			// (but bizarrely not for tags: https://core.trac.wordpress.org/ticket/46459).
			$query->set( 'ssl_alp_coauthor__and', $coauthor_and );
			$query->set( 'ssl_alp_coauthor__in', $coauthor_in );
		}

		if ( ! empty( $coauthor_and ) ) {
			// Coauthor AND search criterion specified.
			$tax_query[] = array(
				// Note, this is different from how parse_tax_query handles
				// e.g. tag__and because we have to inject the tax query after
				// WP_Tax_Query has already been instantiated, which normally
				// applies the relation as part of its instantiation.
				'relation' => 'AND',
				array(
					'taxonomy'         => 'ssl-alp-coauthor',
					'terms'            => $coauthor_and,
					'field'            => 'term_id',
					'operator'         => 'AND',
					'include_children' => false,
				),
			);
		}

		if ( ! empty( $coauthor_in ) ) {
			// Coauthor IN search criterion specified.
			$tax_query[] = array(
				'taxonomy'         => 'ssl-alp-coauthor',
				'terms'            => $coauthor_in,
				'field'            => 'term_id',
				'include_children' => false,
			);
		}

		if ( ! empty( $coauthor_not_in ) ) {
			// Coauthor NOT IN search criterion specified.
			$tax_query[] = array(
				'taxonomy'         => 'ssl-alp-coauthor',
				'terms'            => $coauthor_not_in,
				'field'            => 'term_id',
				'operator'         => 'NOT IN',
				'include_children' => false,
			);
		}

		// Sanitize new taxonomy filters.
		$tax_query = $query->tax_query->sanitize_query( $tax_query );

		// Merge new taxonomy filters into existing ones.
		$query->tax_query->queries = wp_parse_args( $tax_query, $query->tax_query->queries );
	}

	/**
	 * Action taken when user is deleted. This function does the deleting/reassigning of
	 * posts instead of `wp_delete_user`. Since this plugin disables (single) author
	 * support for posts, it must delete posts here instead of leaving it to
	 * `wp_delete_user`.
	 *
	 * When reassigning a user, the deleted user is replaced with the reassign user in the
	 * deleted user's posts if they are either not already a coauthor of each post, or are
	 * a coauthor with lower position than the deleted user. If the reassigned user has a
	 * higher position, they are left in that position.
	 *
	 * @param int $delete_id   User ID to delete.
	 * @param int $reassign_id User ID to reassign deleted user's posts to.
	 */
	public function delete_user_action( $delete_id, $reassign_id = null ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		global $wpdb;

		// Get user to be deleted.
		$delete_user = get_user_by( 'id', $delete_id );

		if ( ! is_object( $delete_user ) ) {
			// Do nothing.
			return;
		}

		// Supported post type as SQL list.
		$post_type_sql = implode( "', '", $this->supported_post_types );

		if ( is_null( $reassign_id ) ) {
			// Users posts will not be reassigned, but we must make sure that their posts are not
			// deleted where there are other authors get user's primary posts (bypass coauthor
			// filtering).
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT ID
					FROM {$wpdb->posts}
					WHERE
						post_author = %d AND
						post_type IN ('{$post_type_sql}')
					",
					$delete_user->ID
				)
			);

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					$post = get_post( $post_id );

					// Check if post has multiple coauthors.
					$coauthors = $this->get_coauthors( $post );

					if ( count( $coauthors ) > 1 ) {
						// This post has multiple authors; remove the deleted user.
						unset( $coauthors[ array_search( $delete_user, $coauthors ) ] );

						// Set coauthors (this changes the primary author).
						$this->set_coauthors( $post, $coauthors );
					} else {
						// This post only has one author, who is the user being deleted - remove
						// them as author.
						$wpdb->update(
							$wpdb->posts,
							array(
								'post_author' => null,
							),
							array(
								'ID' => $post_id,
							)
						);

						clean_post_cache( $post_id );

						// Delete (or trash, if enabled) the post.
						wp_delete_post( $post_id );
					}
				}
			}
		} else {
			// User's posts are to be reassigned - get user to reassign posts to.
			$reassign_user = get_user_by( 'id', $reassign_id );

			if ( is_object( $reassign_user ) ) {
				// Get all posts user is author of.
				$coauthored_posts = $this->get_coauthor_posts( $delete_user );

				if ( count( $coauthored_posts ) ) {
					foreach ( $coauthored_posts as $coauthored_post ) {
						// Get existing coauthors of this post.
						$coauthors = $this->get_coauthors( $coauthored_post );

						// Get indices of users in coauthor list.
						$delete_user_key   = array_search( $delete_user, $coauthors );
						$reassign_user_key = array_search( $reassign_user, $coauthors );

						if ( $reassign_user_key ) {
							// Reassign user is already a coauthor.
							if ( $reassign_user_key < $delete_user_key ) {
								// Reassign user is higher placed than delete user, so leave them
								// where they are and delete the deleted user.
								unset( $coauthors[ $delete_user_key ] );
							} else {
								// Bump reassign user up to higher position.
								unset( $coauthors[ $reassign_user_key ] );
								$coauthors[ $delete_user_key ] = $reassign_user;
							}
						} else {
							// Change coauthor to reassigned user in place.
							$coauthors[ $delete_user_key ] = $reassign_user;
						}

						// Update.
						$this->set_coauthors( $coauthored_post, $coauthors );
					}
				}
			}
		}

		// Delete user's term.
		$this->delete_coauthor_term( $delete_user );
	}

	/**
	 * Fires when a user is removed from a blog on a network installation.
	 *
	 * @param int $remove_id User ID.
	 * @param int $blog_id   Blog ID.
	 */
	public function remove_user_from_blog( $remove_id, $blog_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		// Annoyingly, $reassign_id is not passed to this call, so detect it from the post data.
		if ( ! empty( $_POST['delete'] ) && 'reassign' === $_POST['delete'][ $blog_id ][ $remove_id ]
				&& ! empty( $_POST['blog'] ) && is_array( $_POST['blog'] ) ) {
			// Post data from `dodelete` case in `wp-admin/network/users.php` is present, and
			// admin wishes to reassign user content array of blog ids to respective reassign users.
			$reassign_users = wp_unslash( $_POST['blog'][ $remove_id ] );

			// Get reassign user for this blog.
			$reassign_id = $reassign_users[ $blog_id ];
		} else {
			$reassign_id = null;
		}

		// Reassign posts.
		$this->delete_user_action( $remove_id, $reassign_id );
	}

	/**
	 * Get coauthor posts.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return array Coauthored posts.
	 */
	public function get_coauthor_posts( $user ) {
		$posts = array();

		// Find user term.
		$user_term = $this->get_coauthor_term( $user );

		if ( ! $user_term ) {
			return null;
		}

		// Get objects associated with term (assume everything is a post).
		$term_objects = get_objects_in_term( $user_term->term_id, 'ssl-alp-coauthor' );

		foreach ( $term_objects as $post_id ) {
			// get_objects_in_term returns strings.
			$post_id = (int) $post_id;

			$post = get_post( $post_id );

			if ( ! is_null( $post ) ) {
				$posts[] = $post;
			}
		}

		return $posts;
	}

	/**
	 * Get user post count.
	 *
	 * @param int|WP_User $user User ID or object.
	 *
	 * @return int Post count.
	 */
	public function get_user_post_count( $user ) {
		if ( is_int( $user ) ) {
			// Get user by their ID.
			$user = get_user_by( 'id', $user );
		}

		if ( ! is_object( $user ) ) {
			return null;
		}

		// Find user term.
		$user_term = $this->get_coauthor_term( $user );

		if ( ! $user_term ) {
			// No term.
			return null;
		}

		return $user_term->count;
	}

	/**
	 * Fix for author pages 404ing or not properly displaying on author pages
	 *
	 * If an author has no posts, we only want to force the queried object to be
	 * the author if they're a member of the blog.
	 *
	 * If the author does have posts, it doesn't matter that they're not an author.
	 *
	 * Alternatively, on an author page, if the first story has coauthors and
	 * the first author is NOT the same as the author for the archive,
	 * the query_var is changed.
	 */
	public function fix_author_page() {
		global $wp_query, $authordata;

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return;
		}

		if ( ! is_author() ) {
			// Page is not an author page.
			return;
		}

		$author_id   = absint( get_query_var( 'author' ) );
		$author_name = sanitize_title( get_query_var( 'author_name' ) );

		if ( isset( $author_id ) ) {
			// Get author by ID.
			$author = get_user_by( 'id', $author_id );
		} elseif ( isset( $author_name ) ) {
			// Get author by specified name.
			$author = get_user_by( 'slug', $author_name );
		} else {
			// No query variable was specified; not much we can do.
			return;
		}

		if ( is_object( $author ) ) {
			// Override the authordata global with the requested author, in case the first post's
			// primary author is not the requested author.
			$authordata = $author;
			$term       = $this->get_coauthor_term( $authordata );
		}

		if ( ( is_object( $authordata ) ) || ( ! empty( $term ) ) ) {
			// Update the query to the requested author.
			$wp_query->queried_object    = $authordata;
			$wp_query->queried_object_id = $authordata->ID;
		} else {
			$wp_query->queried_object    = null;
			$wp_query->queried_object_id = null;
			$wp_query->is_author         = false;
			$wp_query->is_archive        = false;
			$wp_query->is_404            = false;
		}
	}

	/**
	 * Fix author page filter to show author name instead of their coauthor term name.
	 *
	 * @param string $author_name Author name.
	 * @return string
	 */
	public function fix_author_page_filter( $author_name ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $author_name;
		}

		if ( ! is_author() ) {
			// Page is not an author page.
			return $author_name;
		}

		global $wp_query;

		// Set author from query.
		return $wp_query->queried_object->display_name;
	}

	/**
	 * Add coauthor emails to comment email recipients.
	 *
	 * @param array $recipients Recipient email addresses.
	 * @param int   $comment_id Comment ID.
	 * @return array Recipients including coauthors.
	 */
	private function add_coauthors_to_comment_email_recipients( $recipients, $comment_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $recipients;
		}

		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );

		if ( ! isset( $post ) ) {
			// No post found, so return default.
			return $recipients;
		}

		$coauthors              = $this->get_coauthors( $post );
		$extra_recipient_emails = array();

		foreach ( $coauthors as $user ) {
			if ( empty( $user->user_email ) ) {
				// No email to notify.
				continue;
			}

			// Add coauthor to email list.
			$extra_recipient_emails[] = $user->user_email;
		}

		// Merge in extra recipients.
		$recipients = array_unique( array_merge( $recipients, $extra_recipient_emails ) );

		return $recipients;
	}

	/**
	 * Filter comment notification email recipients to add coauthors, but avoid notifying the
	 * commenter if they are also a coauthor.
	 *
	 * @param array $recipients Recipient email addresses.
	 * @param int   $comment_id Comment ID.
	 * @return array Recipients including coauthors.
	 */
	public function filter_comment_notification_email_recipients( $recipients, $comment_id ) {
		// Pass through to shared recipient function.
		$recipients = $this->add_coauthors_to_comment_email_recipients( $recipients, $comment_id );

		$comment = get_comment( $comment_id );

		// Determine whether to notify the author of a comment if they are a
		// coauthor of the post.
		$notify_author = apply_filters( 'comment_notification_notify_author', false, $comment->comment_ID );

		if ( ! $notify_author ) {
			foreach ( $recipients as $key => $email ) {
				if ( $email === $comment->comment_author_email ) {
					// Don't notify comment author.
					unset( $recipients[ $key ] );

					break;
				}
			}
		}

		return $recipients;
	}

	/**
	 * Filter array of comment moderation email addresses to add coauthors of
	 * post where comment was made.
	 *
	 * @param array $recipients Recipient email addresses.
	 * @param int   $comment_id Comment ID.
	 *
	 * @return array
	 */
	public function filter_comment_moderation_email_recipients( $recipients, $comment_id ) {
		// Pass through to shared recipient function.
		return $this->add_coauthors_to_comment_email_recipients( $recipients, $comment_id );
	}

	/**
	 * Retrieve a list of coauthor terms for a single post.
	 *
	 * Grabs a correctly ordered list of authors for a single post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 *
	 * @return array
	 */
	public function get_coauthor_terms_for_post( $post = null ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return array();
		}

		$coauthor_terms = wp_get_object_terms(
			$post->ID,
			'ssl-alp-coauthor',
			array(
				'orderby' => 'term_order',
				'order'   => 'ASC',
			)
		);

		// This usually happens if the taxonomy doesn't exist, which should never happen, but you
		// never know.
		if ( is_wp_error( $coauthor_terms ) ) {
			return array();
		}

		return $coauthor_terms;
	}

	/**
	 * Checks to see if the the specified user is author of the current global post or post (if
	 * specified).
	 *
	 * @param object|int       $user User object.
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 */
	public function is_coauthor_for_post( $user, $post = null ) {
		$post = get_post( $post );

		if ( ! isset( $post ) ) {
			return false;
		}

		if ( ! isset( $user ) ) {
			return false;
		}

		$coauthors = $this->get_coauthors( $post );

		if ( is_numeric( $user ) ) {
			$user = get_userdata( $user );
			$user = $user->user_login;
		} elseif ( isset( $user->user_login ) ) {
			$user = $user->user_login;
		} else {
			return false;
		}

		foreach ( $coauthors as $coauthor ) {
			if ( $user === $coauthor->user_login ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Override default `count_many_users_posts` if coauthors are enabled.
	 *
	 * @param array $user_ids User IDs.
	 *
	 * @return array
	 */
	public function count_many_users_posts( $user_ids ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Return standard counts.
			return count_many_users_posts( $user_ids );
		}

		/**
		 * Unfortunately, WordPress doesn't provide a hook for overriding the
		 * behaviour of count_many_users_posts, and so it cannot inject
		 * coauthor posts. Instead, we just have to query it manually here.
		 */

		// List of counts by user ID.
		$counts = array();

		foreach ( $user_ids as $user_id ) {
			$counts[ $user_id ] = $this->get_user_post_count( intval( $user_id ) );
		}

		return $counts;
	}

	/**
	 * Filter display of coauthor terms on e.g. admin post list.
	 *
	 * @param string $value   Post list value.
	 * @param int    $term_id Term ID.
	 * @param string $context Post list context.
	 */
	public function filter_coauthor_term_display( $value, $term_id, $context ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// Coauthors disabled.
			return $value;
		} elseif ( 'display' !== $context ) {
			// Don't change non-display contexts.
			return $value;
		}

		$term = get_term_by( 'id', $term_id, 'ssl-alp-coauthor' );

		// Get user from term.
		$user = $this->get_user_from_coauthor_term( $term );

		if ( ! $user ) {
			// Fall back to default.
			return $value;
		}

		return $user->display_name;
	}
}
