<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Coauthor functionality.
 */
class SSL_ALP_Coauthors extends SSL_ALP_Module {
	protected $supported_post_types = array(
        'post'
	);

	protected $coauthor_term_slug_prefix = 'ssl-alp-coauthor-';

	protected $having_terms = '';

    /**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// register the authors widget
		$loader->add_action( 'widgets_init', $this, 'register_users_widget' );

		// register authors taxonomy
		$loader->add_action( 'init', $this, 'register_taxonomy' );

		// remove single author support from supported post types
		$loader->add_action( 'init', $this, 'remove_author_support' );

		// stop deletion of user's posts when their account is deleted (this is handled separately by `delete_user_action`)
		$loader->add_filter( 'post_types_to_delete_with_user', $this, 'filter_post_types_to_delete_with_user' );

		// create user term when user is created, updated or added to a blog on a network
		$loader->add_action( 'user_register', $this, 'add_coauthor_term', 10, 1 );
		$loader->add_action( 'add_user_to_blog', $this, 'add_coauthor_term', 10, 1 );
		$loader->add_action( 'profile_update', $this, 'update_coauthor_term', 10, 2 );
		// create user term when user logs in for the first time (required where ALP is not network active)
		$loader->add_action( 'wp_login', $this, 'check_coauthor_term_on_login', 10, 2 );

		// hooks to modify the published post number count on the Users WP List Table
		// these are required because the count_many_users_posts() function has no hooks
		$loader->add_filter( 'manage_users_columns', $this, 'filter_manage_users_columns' );
		$loader->add_filter( 'manage_users_custom_column', $this, 'filter_manage_users_custom_column', 10, 3 );

		// filter to allow coauthors to edit posts and stop users deleting coauthor terms
		$loader->add_filter( 'user_has_cap', $this, 'filter_user_has_cap', 10, 4 );

		// stop super admins deleting coauthor terms
		$loader->add_filter( 'map_meta_cap', $this, 'filter_capabilities', 10, 4 );

		// override the default "Mine" filter on the admin post list
		$loader->add_filter( 'views_edit-post', $this, 'filter_edit_post_views', 10, 1 );

		// Include coauthored posts in post counts.
		// Unfortunately, this doesn't filter results retrieved with `count_many_users_posts`, which
		// also doesn't have hooks to allow filtering; therefore know that this filter doesn't catch
		// every count event.
		$loader->add_filter( 'get_usernumposts', $this, 'filter_count_user_posts', 10, 2 );

		// modify SQL queries to include coauthors
		$loader->add_filter( 'posts_where', $this, 'posts_where_filter', 10, 2 );
		$loader->add_filter( 'posts_join', $this, 'posts_join_filter', 10, 2 );
		$loader->add_filter( 'posts_groupby', $this, 'posts_groupby_filter', 10, 2 );

		// set the current user as the default coauthor on new drafts
		$loader->add_action( 'save_post', $this, 'add_user_to_draft', 10, 2 );

		// check and if necessary update the primary author when post coauthor terms are set
		$loader->add_action( 'set_object_terms', $this, 'check_post_author', 10, 6 );

		// delete or reassign user terms from posts when a user is deleted on a single site installation
		$loader->add_action( 'delete_user', $this, 'delete_user_action', 10, 2 );
		// delete or reassign user terms from posts when a user is deleted on a network site installation
		$loader->add_action( 'remove_user_from_blog', $this, 'remove_user_from_blog', 10, 3 );

		// make sure we've correctly set author data on author pages
		$loader->add_action( 'posts_selection', $this, 'fix_author_page', 10, 0 ); // use posts_selection since it's after WP_Query has built the request and before it's queried any posts
		$loader->add_filter( 'the_author', $this, 'fix_author_page_filter', 10, 1 );

		// filters to send comment notification/moderation emails to multiple authors
		$loader->add_filter( 'comment_notification_recipients', $this, 'filter_comment_notification_email_recipients', 10, 2 );
		$loader->add_filter( 'comment_moderation_recipients', $this, 'filter_comment_moderation_email_recipients', 10, 2 );

		// filter the display of coauthor terms
		$loader->add_filter( 'ssl_alp_coauthor_name', $this, 'filter_coauthor_term_display', 10, 3 );
	}

    /**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_allow_multiple_authors',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);
    }

	/**
	 * Register settings fields
	 */
	public function register_settings_fields() {
        /**
         * Post multiple author settings field
         */

        add_settings_field(
			'ssl_alp_author_settings',
			__( 'Authors', 'ssl-alp' ),
			array( $this, 'author_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
	}

	public function author_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/author-settings-display.php';
	}

	/**
	 * Register users widget
	 */
	public function register_users_widget() {
		register_widget( 'SSL_ALP_Widget_Users' );
	}

	/**
	 * Register the 'ssl_alp_coauthor' taxonomy and add post type support
	 */
	public function register_taxonomy() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// Register new taxonomy so that we can store all of the relationships
		$args = array(
			'hierarchical'   		=> false,
			'label'          		=> __( 'Authors', 'ssl-alp' ),
			'query_var'      		=> false,
			'rewrite'        		=> false,
			'public'         		=> false,
			'sort'           		=> true, // remember order terms are added to posts
			'args'					=> array(
											 // default arguments used by `wp_get_object_terms`
											 // see https://core.trac.wordpress.org/ticket/40496
									   		'orderby' => 'term_order'
									   ),
			'show_ui'        		=> true, // show selector on edit page
			'show_in_menu'			=> false, // disable term edit page
			'show_in_rest'			=> true, // needed for block editor support
			'show_admin_column'		=> true,  // show associated terms in admin edit screen
			'update_count_callback'	=> array( $this, 'update_users_posts_count' ) // override count
		);

        // create coauthor taxonomy
		register_taxonomy( 'ssl_alp_coauthor', $this->supported_post_types, $args );
	}

	/**
	 * Remove author support (replaced by coauthor support).
	 */
	public function remove_author_support() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
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
	 * Filter post types to delete with user
	 */
	public function filter_post_types_to_delete_with_user( $types ) {
		// remove supported post types as we manually handle their deletion with users
		return array_diff( $types, $this->supported_post_types );
	}

	/**
	 * Whether or not coauthors are enabled for this post
	 */
	public function post_supports_coauthors( $post = null ) {
		$post = get_post( $post );
		$post_type = get_post_type( $post );

		return $this->is_post_type_enabled( $post_type );
	}

	/**
	 * Whether or not coauthors are enabled for this post type
	 */
	public function is_post_type_enabled( $post_type = null ) {
		if ( is_null( $post_type ) ) {
			// get current post type
			$post_type = get_post_type();
		}

		return in_array( $post_type, $this->supported_post_types );
	}

	public function get_coauthor_term( $user ) {
		if ( is_null( $user ) ) {
			return false;
		}

		// retrieve term associated with the user
		return get_term_by( 'slug', $this->get_coauthor_term_slug( $user ), 'ssl_alp_coauthor' );
	}

	/**
	 * Add author term. This is called when a user is created, but also when a user is
	 * added to a new blog.
	 */
	public function add_coauthor_term( $coauthor ) {
		if ( is_int( $coauthor ) ) {
			// get user by their id
			$coauthor = get_user_by( 'id', $coauthor );
		}

		if ( ! is_object( $coauthor ) ) {
			return;
		}

		// get coauthor term
		$term = $this->get_coauthor_term( $coauthor );

		if ( ! $term ) {
			// term doesn't yet exist
			$args = array(
				'slug'	=>	$this->get_coauthor_term_slug( $coauthor )
			);

			wp_insert_term( $coauthor->display_name, 'ssl_alp_coauthor', $args );
		} else {
			// update term
			$this->update_coauthor_term( $coauthor->ID, $coauthor );
		}
	}

	public function update_coauthor_term( $user_id, $old_user_object ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! is_object( $user ) ) {
			return;
		}

		// get coauthor term (uses nicename from old user)
		$term = $this->get_coauthor_term( $old_user_object );

		// updated term arguments
		$args = array(
			'name'	=>	$user->display_name,
			'slug'	=>	$this->get_coauthor_term_slug( $user )
		);

		// set term name
		wp_update_term( $term->term_id, 'ssl_alp_coauthor', $args );
	}

	private function get_coauthor_term_slug( $user ) {
		// use nicename as this can be extracted from the slug later (it doesn't contain spaces)
		return $this->coauthor_term_slug_prefix . $user->user_nicename;
	}

	private function get_user_from_coauthor_term( $term ) {
		if ( substr( $term->slug, 0, strlen( $this->coauthor_term_slug_prefix ) ) != $this->coauthor_term_slug_prefix ) {
			// the slug doesn't contain the prefix
			return false;
		}

		// remove prefix
		$user_nicename = substr( $term->slug, strlen( $this->coauthor_term_slug_prefix ) );

		return get_user_by( 'slug', $user_nicename );
	}

	public function delete_coauthor_term( $user ) {
		if ( is_null( $user ) ) {
			return false;
		}

		// get the user's term
		$term = $this->get_coauthor_term( $user );

		if ( $term ) {
			// delete user's term
			wp_delete_term( $term->term_id, 'ssl_alp_coauthor' );
		}
	}

	/**
	 * Check that the coauthor term exists on login. Used for the edge case
	 * where ALP is used on multisite, but only active on a blog. In this
	 * case, when a user is created the logic runs in terms of the network,
	 * and ALP hooks are not therefore loaded. This code runs when the created
	 * user logs in, creating the coauthor term.
	 */
	public function check_coauthor_term_on_login( $user_login, $user ) {
		$this->add_coauthor_term( $user );
	}

	/**
	 * Rebuild coauthor terms
	 */
	public function rebuild_coauthors() {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// get all user IDs
		$users = get_users();

		foreach ( $users as $user ) {
			$this->add_coauthor_term( $user->ID );
		}
	}

	/**
	 * Filter terms displayed by the taxonomy meta box
	 */
	public function filter_terms_to_edit( $terms_to_edit, $taxonomy ) {
		global $post;

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( $taxonomy !== 'ssl_alp_coauthor' ) {
			// not our taxonomy
			return $terms_to_edit;
		}

		// get terms in order
		$terms = wp_get_object_terms(
			$post->ID,
			'ssl_alp_coauthor',
			array(
				'orderby'	=>	'term_order',
				'order'		=>	'ASC'
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
	 * Unset the post count column because it's going to be inaccurate; instead
     * provide our own
	 */
	function filter_manage_users_columns( $columns ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $columns;
		}

		$new_columns = array();

		// unset and add our column while retaining the order of the columns
		foreach ( $columns as $column_name => $column_title ) {
			if ( 'posts' == $column_name ) {
				$new_columns[ 'ssl-alp-coauthors-post-count' ] = __( 'Posts', 'ssl-alp' );
			} else {
				$new_columns[ $column_name ] = $column_title;
			}
		}

		return $new_columns;
	}

	/**
	 * Provide an accurate count when looking up the number of published posts for a user
	 */
	function filter_manage_users_custom_column( $value, $column_name, $user_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $value;
		}

		if ( 'ssl-alp-coauthors-post-count' != $column_name ) {
			// not the column we want to modify
			return $value;
		}

		// filter count_user_posts() so it provides an accurate number
		$post_count = count_user_posts( $user_id );
		$user = get_user_by( 'id', $user_id );

		if ( $post_count > 0 ) {
			$value .= sprintf(
				'<a href="edit.php?taxonomy=ssl_alp_coauthor&amp;term=%1$s" title="%2$s" class="edit">%3$d</a>',
				$this->get_coauthor_term_slug( $user ),
				esc_attr__( 'View posts by this author', 'ssl-alp' ),
				$post_count
			);
		} else {
			// no link to empty post page
			$value .= 0;
		}

		return $value;
	}

	/**
	 * Allows coauthors to edit the post they're coauthors of, and stop
	 * users deleting coauthor terms.
	 */
	function filter_user_has_cap( $all_capabilities, $unused, $args, $user ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $all_capabilities;
		}

		$requested_capability = $args[0];

		if ( in_array( $requested_capability, array( 'edit_term', 'delete_term' ) ) ) {
			// disallow in all circumstances
			$all_capabilities['edit_term'] = false;
			$all_capabilities['delete_term'] = false;

			return $all_capabilities;
		}

		// assume post
		$post_id = isset( $args[2] ) ? $args[2] : 0;
		$post_type = get_post_type_object( get_post_type( $post_id ) );

		if ( ! $post_type || 'revision' == $post_type->name ) {
			return $all_capabilities;
		}

		$unfiltered_capabilities = array(
			$post_type->cap->edit_post,
			'edit_post', // Need to filter this too, unfortunately: http://core.trac.wordpress.org/ticket/22415
			$post_type->cap->edit_others_posts, // This as well: http://core.trac.wordpress.org/ticket/22417
		);

		if ( ! in_array( $requested_capability, $unfiltered_capabilities ) ) {
			// capability is not one we want to change
			// this is the case if the user is a researcher or admin, for example
			// (they can already edit other posts)
			return $all_capabilities;
		} elseif ( ! is_user_logged_in() || ! $this->is_coauthor_for_post( $user, $post_id ) ) {
			// user isn't coauthor of the specified post
			return $all_capabilities;
		}

		$post_status = get_post_status( $post_id );

		if ( 'publish' == $post_status &&
			( isset( $post_type->cap->edit_published_posts ) && ! empty( $user->all_capabilities[ $post_type->cap->edit_published_posts ] ) ) ) {
			// allow edit of published posts for this call
			$all_capabilities[ $post_type->cap->edit_published_posts ] = true;
		} elseif ( 'private' == $post_status &&
			( isset( $post_type->cap->edit_private_posts ) && ! empty( $user->all_capabilities[ $post_type->cap->edit_private_posts ] ) ) ) {
			// allow edit of private posts for this call
			$all_capabilities[ $post_type->cap->edit_private_posts ] = true;
		}

		// allow edit of others posts for this call
		$all_capabilities[ $post_type->cap->edit_others_posts ] = true;

		return $all_capabilities;
	}

	/**
	 * Filter capabilities of super admins to stop them editing or deleting coauthor terms.
	 * 
	 * Coauthor terms are essential to the correct operation of the coauthors system.
	 */
	function filter_capabilities( $caps, $cap, $user_id, $args ) {
		// construct list of capabilities based on post type
		$filtered_caps = array(
			// terms
			'edit_term',
			'delete_term'
		);

		if ( ! in_array( $cap, $filtered_caps ) ) {
			// this is not a capability we need to filter
			return $caps;
		}

		// get term
		$term = get_term( $args[0] );

		if ( is_null( $term ) ) {
			return $caps;
		}

		$taxonomy = get_taxonomy( $term->taxonomy );
		
		if ( 'ssl_alp_coauthor' == $taxonomy->name ) {
			// disallow
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Filter the views listed on the admin post list to add a "Mine" view
	 */
	function filter_edit_post_views( $views ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $views;
		}

		// current user
		$user = wp_get_current_user();

		// get post count
		$post_count = $this->get_user_post_count( $user );

		if ( is_null( $post_count ) ) {
			// user has no posts
			$post_count = 0;
		}

		// get current user's coauthor term slug
		$coauthor_slug = $this->get_coauthor_term_slug( $user );

		// build URL arguments
		$mine_args = array(
			'taxonomy'	=> 'ssl_alp_coauthor',
			'term'		=>	$coauthor_slug
		);

		// check if the current page is the "Mine" view
		if ( ! empty( $_REQUEST['taxonomy'] ) && $_REQUEST['taxonomy'] === 'ssl_alp_coauthor' && ! empty( $_REQUEST['term'] ) && $_REQUEST['term'] == $coauthor_slug ) {
			$class = ' class="current"';
		} else {
			$class = '';
		}

		// flip views
		$views = array_reverse( $views );
		// get "All" view off end
		$all_view = array_pop( $views );

		// add "Mine" view to end
		$views['mine'] = sprintf(
			'<a%s href="%s">%s <span class="count">(%s)</span></a>',
			$class,
			esc_url( add_query_arg( array_map( 'rawurlencode', $mine_args ), admin_url( 'edit.php' ) ) ),
			__( 'Mine', 'ssl_alp' ),
			$post_count
		);

		// add "All" view to end, and stop it from showing up if "Mine" is enabled
		$views['all'] = str_replace( $class, '', $all_view );

		// flip back round
		$views = array_reverse( $views );

		return $views;
	}

	/**
	 * When we update the terms at all, we should update the published post
     * count for each author
	 */
	function update_users_posts_count( $tt_ids, $taxonomy ) {
		global $wpdb;

		$tt_ids = implode( ', ', array_map( 'intval', $tt_ids ) );
		$term_ids = $wpdb->get_results( "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ($tt_ids)" );

		foreach ( (array) $term_ids as $term_id_result ) {
			$term = get_term_by( 'id', $term_id_result->term_id, 'ssl_alp_coauthor' );
			$this->update_author_term_post_count( $term );
		}

		$tt_ids = explode( ', ', $tt_ids );
		clean_term_cache( $tt_ids, '', false );
	}

	/**
	 * Update the post count associated with an author term
	 */
	public function update_author_term_post_count( $term ) {
		global $wpdb;

		$coauthor = $this->get_user_from_coauthor_term( $term );

		if ( ! $coauthor ) {
			return new WP_Error( 'missing-coauthor', __( 'No co-author exists for that term', 'ssl-alp' ) );
		}

		$query = "SELECT COUNT({$wpdb->posts}.ID) FROM {$wpdb->posts}";
		$query .= " LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
		$query .= " LEFT JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

		$having_terms_and_authors = $having_terms = $wpdb->prepare( "{$wpdb->term_taxonomy}.term_id = %d", $term->term_id );

		$having_terms_and_authors .= $wpdb->prepare( " OR {$wpdb->posts}.post_author = %d", $coauthor->ID );

		$post_types = array_map( 'sanitize_key', $this->supported_post_types );
		$post_types = "'" . implode( "','", $post_types ) . "'";

		$query .= " WHERE ({$having_terms_and_authors}) AND {$wpdb->posts}.post_type IN ({$post_types}) AND {$wpdb->posts}.post_status = 'publish'";

		$query .= $wpdb->prepare( " GROUP BY {$wpdb->posts}.ID HAVING MAX( IF ( {$wpdb->term_taxonomy}.taxonomy = '%s', IF ( {$having_terms},2,1 ),0 ) ) <> 1 ", 'ssl_alp_coauthor' );

		$count = $wpdb->query( $query );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
	}

	/**
	 * Filter the count_user_posts() core function to include our correct count
	 */
	function filter_count_user_posts( $count, $user_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $count;
		}

		$real_count = $this->get_user_post_count( $user_id );

		if ( is_null( $real_count ) ) {
			// use default
			$real_count = $count;
		}

		return $real_count;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_join_filter( $join, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $join;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
            // not an author query, so return unmodified
            return $join;
        }

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl_alp_coauthor' ) ) {
            // not a valid post type, so return unmodified
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
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_where_filter( $where, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $where;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
            // not an author query, so return unmodified
            return $where;
		}

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl_alp_coauthor' ) ) {
            // not a valid post type, so return unmodified
			return $where;
		}

		if ( $query->get( 'author_name' ) ) {
			// author_name is actually user_nicename
			$author_nicename = $query->get( 'author_name' );

			if ( is_null( $author_nicename ) ) {
				// no author defined
				return $where;
			}

			// user_nicename == slug
			$coauthor = get_user_by( 'slug', $author_nicename );
		} else {
			$author_data = get_userdata( $query->get( 'author' ) );

			if ( ! is_object( $author_data ) ) {
				// no author defined
				return $where;
			}

			$coauthor = get_user_by( 'login', $author_data->user_login );
		}

		$terms = array();

		if ( $author_term = $this->get_coauthor_term( $coauthor ) ) {
			$terms[] = $author_term;
		}

		if ( ! empty( $terms ) ) {
			$terms_implode = '';
			$this->having_terms = '';

			foreach ( $terms as $term ) {
				$terms_implode .= '(' . $wpdb->term_taxonomy . '.taxonomy = \''. 'ssl_alp_coauthor'.'\' AND '. $wpdb->term_taxonomy .'.term_id = \''. $term->term_id .'\') OR ';
				$this->having_terms .= ' ' . $wpdb->term_taxonomy .'.term_id = \''. $term->term_id .'\' OR ';
			}

			$terms_implode = rtrim( $terms_implode, ' OR' );
			$this->having_terms = rtrim( $this->having_terms, ' OR' );

			// match "wp_posts.post_author = [number]" or "wp_posts.post_author IN ([list of numbers])"
			// and append "OR (wp_term_taxonomy.taxonomy = 'ssl_alp_coauthor' AND wp_term_taxonomy.term_id = '6')"
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
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_groupby_filter( $groupby, $query ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $groupby;
		}

		global $wpdb;

		if ( ! $query->is_author() ) {
            // not an author query, so return unmodified
            return $groupby;
        }

		if ( ! empty( $query->query_vars['post_type'] ) && ! is_object_in_taxonomy( $query->query_vars['post_type'], 'ssl_alp_coauthor' ) ) {
            // not a valid post type, so return unmodified
			return $groupby;
		}

		if ( $this->having_terms ) {
			$having = 'MAX( IF ( ' . $wpdb->term_taxonomy . '.taxonomy = \''. 'ssl_alp_coauthor'.'\', IF ( ' . $this->having_terms . ',2,1 ),0 ) ) <> 1 ';
			$groupby = $wpdb->posts . '.ID HAVING ' . $having;
		}

		return $groupby;
	}

	/**
	 * Checks if the specified post is an autodraft, i.e. a new post.
	 */
	private function is_post_autodraft( $post ) {
    	return $post->post_status === "auto-draft";
	}

	/**
	 * Set the current user as the author in the coauthor meta box when a new post draft
	 * is created.
	 */
	function add_user_to_draft( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( ! $this->is_post_autodraft( $post ) ) {
			// not a draft
			return;
		} elseif ( ! $this->post_supports_coauthors( $post ) ) {
			return;
		}

		// get updated coauthors
		$coauthors = array( wp_get_current_user() );

		$this->set_coauthors( $post, $coauthors );
	}

	/**
	 * Check and if necessary update the primary author when post coauthor terms are set.
	 *
	 * This checks that WordPress core's post author is consistent with the coauthor order.
	 * If a post is edited to move the post author to a non-first coauthor position, this
	 * function changes the post author to whoever is now first.
	 */
	function check_post_author( $post_id, $term_ids, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		global $wpdb;

		if ( $taxonomy !== "ssl_alp_coauthor" ) {
			return;
		}

		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// get post
		$post = get_post( $post_id );

		if ( wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( ! $this->post_supports_coauthors( $post ) ) {
			return;
		}

		// get post's previous author (the post's author in the database row)
		$existing_primary_author = get_user_by( 'id', $post->post_author );

		$terms = array();

		foreach ($term_ids as $term_id) {
			$terms[] = get_term( $term_id, "ssl_alp_coauthor" );
		}

		$first_term = reset( $terms );

		if ( empty( $terms ) || is_wp_error( $first_term ) ) {
			// empty terms - remove the author
			$new_primary_author_id = 0;
		} else {
			$new_primary_author = $this->get_user_from_coauthor_term( $first_term );
			$new_primary_author_id = $new_primary_author->ID;

			if ( ! empty( $existing_primary_author ) && $existing_primary_author->ID === $new_primary_author_id ) {
				// primary author hasn't changed
				return;
			}
		}

		// update primary author
		$wpdb->update( $wpdb->posts, array( 'post_author' => $new_primary_author_id ), array( 'ID' => $post->ID ) );
		clean_post_cache( $post->ID );
	}

	public function get_coauthors( $post = null ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			// no post
			return;
		}

		// empty coauthors list
		$coauthors = array();

		// get this post's terms
		$coauthor_terms = $this->get_coauthor_terms_for_post( $post );

		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			// this post has coauthors
			foreach ( $coauthor_terms as $coauthor_term ) {
				$post_author = $this->get_user_from_coauthor_term( $coauthor_term );

				// in case the user has been deleted while plugin was deactivated
				if ( ! empty( $post_author ) ) {
					$coauthors[] = $post_author;
				}
			}
		}

		// get the post's primary author
		$post_author = get_user_by( 'id', $post->post_author );

		// try to ensure at least the post's primary author is in the list of coauthors
		if ( ! empty( $post_author ) && ! in_array( $post_author, $coauthors ) ) {
			// post primary author exists but isn't listed as a coauthor, so add them to the start of the coauthors array
			array_unshift( $coauthors, $post_author );
		}

		return $coauthors;
	}

	/**
	 * Set one or more coauthors for a post. The first specified coauthor is set as
	 * the post's primary author.
	 */
	public function set_coauthors( $post, $coauthors ) {
		global $wpdb;

		$post = get_post( $post );

		if ( ! is_array( $coauthors ) ) {
			// invalid input
			return;
		}

		// get post's previous author (the post's author in the database row)
		$existing_primary_author = get_user_by( 'id', $post->post_author );

		// deduplicate
		$coauthors = array_unique( $coauthors, SORT_REGULAR );

		// remove invalid coauthors
		foreach ( $coauthors as $coauthor ) {
			if ( ! is_object( $coauthor ) ) {
				// invalid user specified
				// remove
				unset( $coauthors[ array_search( $coauthor, $coauthors ) ] );
			}
		}

		// create list of term ids
		$coauthor_terms = array_map( array( $this, 'get_coauthor_term' ), $coauthors );
		$coauthor_term_ids = wp_list_pluck( $coauthor_terms, 'term_id' );

		// update post's coauthors
		wp_set_post_terms( $post->ID, $coauthor_term_ids, 'ssl_alp_coauthor', false );
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
	 */
	function delete_user_action( $delete_id, $reassign_id = null ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		global $wpdb;

		// get user to be deleted
		$delete_user = get_user_by( 'id', $delete_id );

		if ( ! is_object( $delete_user ) ) {
			// do nothing
			return;
		}

		// supported post type as SQL list
		$post_type_sql = implode( "', '", $this->supported_post_types );

		if ( is_null( $reassign_id ) ) {
			// users posts will not be reassigned, but we must make sure that their posts
			// are not deleted where there are other authors

			// get user's primary posts (bypass coauthor filtering)
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type IN ('$post_type_sql')", $delete_user->ID ) );

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					$post = get_post( $post_id );

					// check if post has multiple coauthors
					$coauthors = $this->get_coauthors( $post );

					if ( count( $coauthors ) > 1 ) {
						// this post has multiple authors; remove the deleted user
						unset( $coauthors[ array_search( $delete_user, $coauthors ) ] );

						// set coauthors (this changes the primary author)
						$this->set_coauthors( $post, $coauthors );
					} else {
						// this post only has one author, who is the user being deleted
						// remove them as author
						$wpdb->update( $wpdb->posts, array( 'post_author' => null ), array( 'ID' => $post_id ) );
						clean_post_cache( $post_id );

						// delete (or trash, if enabled) the post
						wp_delete_post( $post_id );
					}
				}
			}
		} else {
			// user's posts are to be reassigned

			// get user to reassign posts to
			$reassign_user = get_user_by( 'id', $reassign_id );

			if ( is_object( $reassign_user ) ) {
				// get all posts user is author of
				$coauthored_posts = $this->get_coauthor_posts( $delete_user );

				if ( count( $coauthored_posts ) ) {
					foreach ( $coauthored_posts as $coauthored_post ) {
						// get existing coauthors of this post
						$coauthors = $this->get_coauthors( $coauthored_post );

						// get indices of users in coauthor list
						$delete_user_key = array_search( $delete_user, $coauthors );
						$reassign_user_key = array_search( $reassign_user, $coauthors );

						if ( $reassign_user_key ) {
							// reassign user is already a coauthor
							if ( $reassign_user_key < $delete_user_key ) {
								// reassign user is higher placed than delete user,
								// so leave them where they are and delete the deleted user
								unset( $coauthors[ $delete_user_key ] );
							} else {
								// bump reassign user up to higher position
								unset( $coauthors[ $reassign_user_key] );
								$coauthors[ $delete_user_key ] = $reassign_user;
							}
						} else {
							// change coauthor to reassigned user in place
							$coauthors[ $delete_user_key ] = $reassign_user;
						}

						// update
						$this->set_coauthors( $coauthored_post, $coauthors );
					}
				}
			}
		}

		// delete user's term
		$this->delete_coauthor_term( $delete_user );
	}

	/**
	 * Fires when a user is removed from a blog on a network installation.
	 */
	public function remove_user_from_blog( $remove_id, $blog_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// annoyingly, $reassign_id is not passed to this call, so detect it from the post data
		if ( ! empty( $_POST['blog'] ) && is_array( $_POST['blog'] ) ) {
			// post data from `dodelete` case in `wp-admin/network/users.php` is present

			// array of blog ids to respective reassign users
			$reassign_users = $_POST['blog'][$remove_id];

			// get reassign user for this blog
			$reassign_id = $reassign_users[$blog_id];
		} else {
			$reassign_id = null;
		}

		// reassign posts
		$this->delete_user_action( $remove_id, $reassign_id );
	}

	public function get_coauthor_posts( $user ) {
		$posts = array();

		// find user term
		$user_term = $this->get_coauthor_term( $user );

		if ( ! $user_term ) {
			return null;
		}

		// get objects associated with term (assume everything is a post)
		$term_objects = get_objects_in_term( $user_term->term_id, 'ssl_alp_coauthor' );

		foreach ( $term_objects as $post_id ) {
			// get_objects_in_term returns strings
			$post_id = (int) $post_id;

			$post = get_post( $post_id );

			if ( ! is_null( $post ) ) {
				$posts[] = $post;
			}
		}

		return $posts;
	}

	public function get_user_post_count( $user ) {
		if ( is_int( $user ) ) {
			// get user by their id
			$user = get_user_by( 'id', $user );
		}

		if ( ! is_object( $user ) ) {
			return null;
		}

		// find user term
		$user_term = $this->get_coauthor_term( $user );

		if ( ! $user_term ) {
			// no term
			return null;
		}

		return $user_term->count;
	}

	/**
	 * Checks to see if the current user can set authors or not
	 */
	function current_user_can_set_authors( $post = null ) {
        // super admins can do anything
		if ( function_exists( 'is_super_admin' ) && is_super_admin() ) {
			return true;
		}

		$current_user = wp_get_current_user();

		if ( ! isset( $current_user ) ) {
			// no user logged in
			return false;
		}

        return $current_user->has_cap( 'edit_others_posts' );
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
			// coauthors disabled
			return;
		}

		if ( ! is_author() ) {
			// page is not an author page
			return;
		}

		$author_id = absint( get_query_var( 'author' ) );
		$author_name = sanitize_title( get_query_var( 'author_name' ) );

		if ( isset( $author_id ) ) {
			// get author by id
			$author = get_user_by( 'id', $author_id );
		} elseif ( isset( $author_name ) ) {
			// get author by specified name
			$author = get_user_by( 'slug', $author_name );
		} else {
			// no query variable was specified; not much we can do
			return;
		}

		if ( is_object( $author ) ) {
			// override the authordata global with the requested author, in case the
			// first post's primary author is not the requested author
			$authordata = $author;
			$term = $this->get_coauthor_term( $authordata );
		}

		if ( ( is_object( $authordata ) ) || ( ! empty( $term ) ) ) {
			// update the query to the requested author
			$wp_query->queried_object = $authordata;
			$wp_query->queried_object_id = $authordata->ID;
		} else {
			$wp_query->queried_object = null;
			$wp_query->queried_object_id = null;
			$wp_query->is_author = false;
			$wp_query->is_archive = false;
			$wp_query->is_404 = false;
		}
	}

	public function fix_author_page_filter( $author_name ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $author_name;
		}

		if ( ! is_author() ) {
			// page is not an author page
			return $author_name;
		}

		global $wp_query;

		// set author from query
		return $wp_query->queried_object->display_name;
	}

    /**
     * Filter array of comment notification email addresses
     */
    public function filter_comment_notification_email_recipients( $recipients, $comment_id ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $recipients;
		}

    	$comment = get_comment( $comment_id );
    	$post = get_post( $comment->comment_post_ID );

    	if ( isset( $post ) ) {
    		$coauthors = $this->get_coauthors( $post );
    		$extra_recipients = array();

    		foreach ( $coauthors as $user ) {
    			if ( ! empty( $user->user_email ) ) {
    				$extra_recipients[] = $user->user_email;
    			}
    		}

    		$recipients = array_unique( array_merge( $recipients, $extra_recipients ) );
    	}

    	return $recipients;
    }

    /**
     * Filter array of comment moderation email addresses
     */
    public function filter_comment_moderation_email_recipients( $recipients, $comment ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// coauthors disabled
			return $recipients;
		}

    	$comment = get_comment( $comment );
    	$post = get_post( $comment->comment_post_ID );

    	if ( isset( $post ) ) {
    		$coauthors = $this->get_coauthors( $post );
    		$extra_recipients = array();

    		foreach ( $coauthors as $user ) {
    			if ( ! empty( $user->user_email ) ) {
    				$extra_recipients[] = $user->user_email;
    			}
    		}

    		$recipients = array_unique( array_merge( $recipients, $extra_recipients ) );
    	}

    	return $recipients;
	}

	/**
	 * Retrieve a list of coauthor terms for a single post.
	 *
	 * Grabs a correctly ordered list of authors for a single post.
	 */
	public function get_coauthor_terms_for_post( $post = null ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return array();
		}

		$coauthor_terms = wp_get_object_terms(
			$post->ID,
			'ssl_alp_coauthor',
			array(
				'orderby' => 'term_order',
				'order' => 'ASC',
			)
		);

		// this usually happens if the taxonomy doesn't exist, which should never happen, but you never know.
		if ( is_wp_error( $coauthor_terms ) ) {
			return array();
		}

		return $coauthor_terms;
	}

	/**
	 * Checks to see if the the specified user is author of the current global post or post (if specified)
	 * @param object|int $user
	 * @param int $post_id
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
		} else if ( isset( $user->user_login ) ) {
			$user = $user->user_login;
		} else {
			return false;
		}

		foreach ( $coauthors as $coauthor ) {
			if ( $user == $coauthor->user_login ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Override default `count_many_users_posts` if coauthors are enabled
	 */
	public function count_many_users_posts( $user_ids ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// return standard counts
			return count_many_users_posts( $user_ids );
		}

		/**
		 * Unfortunately, WordPress doesn't provide a hook for overriding the
		 * behaviour of count_many_users_posts, and so it cannot inject
		 * coauthor posts. Instead, we just have to query it manually here.
		 */

		// list of counts by user id
		$counts = array();

		foreach ( $user_ids as $user_id ) {
			$counts[$user_id] = $this->get_user_post_count( intval( $user_id ) );
		}

		return $counts;
	}

	/**
	 * Filter display of coauthor terms on e.g. admin post list
	 */
	public function filter_coauthor_term_display( $value, $term_id, $context ) {
		if ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
			// don't modify
			return $value;
		} elseif ( 'display' !== $context ) {
			// don't change non-display contexts
			return $value;
		}

		$term = get_term_by( 'id', $term_id, 'ssl_alp_coauthor' );

		// the term name is the user's login
		$user = $this->get_user_from_coauthor_term( $term );

		if ( ! $user ) {
			// fall back to default
			return $value;
		}

		return $user->display_name;
	}
}

class SSL_ALP_Widget_Users extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'ssl-alp-users', // base ID
			esc_html__( 'Users', 'ssl-alp' ), // name
			array(
				'description' => __( "A list of users and their post counts.", 'ssl-alp' )
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
		global $ssl_alp;

		echo $args['before_widget'];

		// default title
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Users' );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// show dropdown by default
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : true;

		// default dropdown ID
		$dropdown_id = 'ssl_alp_users_dropdown';

		if ( $dropdown ) {
			// unfortunately wp_dropdown_users doesn't support displaying post counts,
			// so we have to do it ourselves

			// get users
			$users = get_users(
				array(
					'fields'	=>	array(
						'ID',
						'display_name'
					),
					'order'		=>	'ASC',
					'orderby'	=>	'display_name'
				)
			);

			// get user post counts
			$user_ids = array_map( create_function( '$user', 'return $user->ID;' ), $users );
			$post_counts = $ssl_alp->coauthors->count_many_users_posts( $user_ids );

			if ( ! empty( $users ) ) {
				// enqueue script to take the user to the author's page
				wp_enqueue_script( 'ssl-alp-user-widget-js', SSL_ALP_BASE_URL . 'js/user-widget.js', array( 'jquery' ), $ssl_alp->get_version(), true );

				// set element to handle click events for
				wp_localize_script( 'ssl-alp-user-widget-js', 'ssl_alp_dropdown_id', esc_js( $dropdown_id ) );

				// enclose dropdown in a form so we can handle redirect to user page
				printf( '<form action="%s" method="get">', esc_url( home_url() ) );

				// make select name 'author' so the form redirects to the selected user page
				printf( '<select name="author" id="%s">\n', esc_html( $dropdown_id ) );

				// print default
				printf(
					'\t<option value="#">%1$s</option>',
					__( 'Select User', 'ssl-alp' )
				);

				foreach ( (array) $users as $user ) {
					$name = esc_html( $user->display_name );
					$post_count = $post_counts[$user->ID];

					printf(
						_x( '\t<option value="%1$s">%2$s (%3$d)</option>\n', 'User list', 'ssl-alp' ),
						$user->ID,
						$name,
						$post_count
					);
				}

				echo '</select>';
				echo '</form>';
			}
		} else {
			echo '<ul>';

			wp_list_authors(
				array(
					'optioncount'	=>	true,
					'show'			=>	false // use display_name
				)
			);

			echo '</ul>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : true;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
			<label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown' ); ?></label>
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

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['dropdown'] = ! empty( $new_instance['dropdown'] ) ? true : false;

		return $instance;
	}
}
