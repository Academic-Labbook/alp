<?php

/**
 * Coauthor functionality.
 */
class SSL_ALP_Coauthors extends SSL_ALP_Module {
	protected $supported_post_types = array(
        'post'
    );

	/**
	 * Fields of WP_User to search against to find coauthors to add to posts
	 * in the admin post edit screen
	 */
	protected $ajax_search_fields = array(
        'display_name',
        'first_name',
        'last_name',
        'user_login',
        'ID',
        'user_email'
    );

	protected $having_terms = '';

    /**
	 * Register stylesheets
	 */
	public function enqueue_admin_styles() {
        wp_enqueue_style( 'ssl-alp-coauthors-css', SSL_ALP_BASE_URL . 'css/coauthors.css', array(), $this->get_version(), 'all' );
	}

    /**
	 * Register admin scripts
	 */
	public function enqueue_admin_scripts() {
		if ( ! $this->is_post_type_enabled() || ! $this->current_user_can_set_authors() ) {
			return;
		}

		wp_enqueue_script( 'ssl-alp-coauthors-js', SSL_ALP_BASE_URL . 'js/coauthors.js', array( 'jquery', 'jquery-ui-sortable', 'suggest' ), $this->get_version(), true );

		$js_strings = array(
			'edit_label' => __( 'Edit', 'ssl-alp' ),
			'delete_label' => __( 'Remove', 'ssl-alp' ),
			'confirm_delete' => __( 'Are you sure you want to remove this author?', 'ssl-alp' ),
			'input_box_title' => __( 'Click to change this author, or drag to change their position', 'ssl-alp' ),
			'search_box_text' => __( 'Search for an author', 'ssl-alp' ),
			'help_text' => __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'ssl-alp' ),
		);

		wp_localize_script( 'ssl-alp-coauthors-js', 'coAuthorsPlusStrings', $js_strings );
	}

    /**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// register the authors widget regardless of settings
		$loader->add_action( 'widgets_init', $this, 'register_users_widget' );

		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled; no point continuing
			return;
		}

		// Register our models
		$loader->add_action( 'init', $this, 'action_init', 100 );

        // Add necessary JS variables
		$loader->add_action( 'admin_head', $this, 'js_vars' );

		// Hooks to add additional coauthors to author column to Edit page
		$loader->add_filter( 'manage_posts_columns', $this, '_filter_manage_posts_columns' );
		$loader->add_filter( 'manage_pages_columns', $this, '_filter_manage_posts_columns' );
		$loader->add_action( 'manage_posts_custom_column', $this, '_filter_manage_posts_custom_column' );
		$loader->add_action( 'manage_pages_custom_column', $this, '_filter_manage_posts_custom_column' );

		// Add quick-edit author select field
		$loader->add_action( 'quick_edit_custom_box', $this, '_action_quick_edit_custom_box', 10, 2 );

		// Hooks to modify the published post number count on the Users WP List Table
		$loader->add_filter( 'manage_users_columns', $this, '_filter_manage_users_columns' );
		$loader->add_filter( 'manage_users_custom_column', $this, '_filter_manage_users_custom_column', 10, 3 );

		// Apply some targeted filters
		$loader->add_action( 'load-edit.php', $this, 'load_edit' );

		// Modify SQL queries to include coauthors
		$loader->add_filter( 'posts_where', $this, 'posts_where_filter', 10, 2 );
		$loader->add_filter( 'posts_join', $this, 'posts_join_filter', 10, 2 );
		$loader->add_filter( 'posts_groupby', $this, 'posts_groupby_filter', 10, 2 );

		// Action to set users when a post is saved
		$loader->add_action( 'save_post', $this, 'coauthors_update_post', 10, 2 );
		// Filter to set the post_author field when wp_insert_post is called
		$loader->add_filter( 'wp_insert_post_data', $this, 'coauthors_set_post_author_field', 10, 2 );

		// Action to reassign posts when a user is deleted
		$loader->add_action( 'delete_user', $this, 'delete_user_action' );

		// Include coauthored posts in post counts.
		// Unfortunately, this doesn't filter results retrieved with `count_many_users_posts`, which
		// also doesn't have hooks to allow filtering; therefore know that this filter doesn't catch
		// every count event.
		$loader->add_filter( 'get_usernumposts', $this, 'filter_count_user_posts', 10, 2 );

		// Action to set up author auto-suggest
		$loader->add_action( 'wp_ajax_coauthors_ajax_suggest', $this, 'ajax_suggest' );

		// Filter to allow coauthors to edit posts
		$loader->add_filter( 'user_has_cap', $this, 'filter_user_has_cap', 10, 3 );

		// Handle the custom author meta box
		$loader->add_action( 'add_meta_boxes', $this, 'add_coauthors_box' );
		$loader->add_action( 'add_meta_boxes', $this, 'remove_authors_box' );

		// Removes the author dropdown from the post quick edit
		$loader->add_action( 'admin_head', $this, 'remove_quick_edit_authors_box' );

		// Restricts WordPress from blowing away term order on bulk edit
		$loader->add_filter( 'wp_get_object_terms', $this, 'filter_wp_get_object_terms', 10, 4 );

		// Make sure we've correctly set author data on author pages
		$loader->add_filter( 'posts_selection', $this, 'fix_author_page' ); // use posts_selection since it's after WP_Query has built the request and before it's queried any posts
		$loader->add_action( 'the_post', $this, 'fix_author_page' );

		// save revisions if coauthors have changed
		$loader->add_filter( 'wp_save_post_revision_post_has_changed', $this, 'check_coauthors_have_changed', 10, 3 );

		// filters to send comment notification/moderation emails to multiple authors
		$loader->add_filter( 'comment_notification_recipients', $this, 'filter_comment_notification_email_recipients', 10, 2 );
		$loader->add_filter( 'comment_moderation_recipients', $this, 'filter_comment_moderation_email_recipients', 10, 2 );

		// delete coauthor cache on post save and delete
		$loader->add_action( 'save_post', $this, 'clear_cache' );
		$loader->add_action( 'delete_post', $this, 'clear_cache' );
		$loader->add_action( 'set_object_terms', $this, 'clear_cache_on_terms_set', 10, 6 );

		// update term description when user profile is edited
		$loader->add_action( 'profile_update', $this, 'update_author_term' );
	}

    /**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_multiple_authors',
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
			'ssl-alp-admin-options',
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
		register_widget( 'SSL_ALP_Users' );
	}

	/**
	 * Register the 'ssl_alp_coauthor' taxonomy and add post type support
	 */
	public function action_init() {
		// Register new taxonomy so that we can store all of the relationships
		$args = array(
			'hierarchical'   => false,
			'label'          => false,
			'query_var'      => false,
			'rewrite'        => false,
			'public'         => false,
			'sort'           => true,
			'args'           => array( 'orderby' => 'term_order' ),
			'show_ui'        => false,
		);

		// callback to update user post counts
		$args['update_count_callback'] = array( $this, '_update_users_posts_count' );

        // create coauthor taxonomy
		register_taxonomy( 'ssl_alp_coauthor', $this->supported_post_types, $args );
	}

	/**
	 * Get a coauthor object by a specific type of key
	 */
	public function get_coauthor_by( $key, $value, $force = false ) {
		switch ( $key ) {
			case 'id':
			case 'login':
			case 'user_login':
			case 'email':
			case 'user_nicename':
			case 'user_email':
				if ( 'user_login' == $key ) {
					$key = 'login';
				}

				if ( 'user_email' == $key ) {
					$key = 'email';
				}

				if ( 'user_nicename' == $key ) {
					$key = 'slug';
				}

				// Ensure we aren't doing the lookup by the prefixed value
				if ( 'login' == $key || 'slug' == $key ) {
					$value = preg_replace( '#^cap\-#', '', $value );
				}

				$user = get_user_by( $key, $value );

				if ( ! $user ) {
					return false;
				}

				return $user;

				break;
		}

		return false;
	}

	/**
	 * Whether or not coauthors are enabled for this post type
	 */
	public function is_post_type_enabled( $post_type = null ) {
		if ( ! $post_type ) {
			// post type was not specified directly, so get it
			$post_type = get_post_type();
		}

		return (bool) in_array( $post_type, $this->supported_post_types );
	}

	/**
	 * Removes the standard WordPress Author box.
	 * We don't need it because the Co-Authors one is way cooler.
	 */
	public function remove_authors_box() {
		if ( ! $this->is_post_type_enabled() ) {
            return;
        }

        remove_meta_box( 'authordiv', get_post_type(), 'normal' );
	}

	/**
	 * Adds a custom Authors box
	 */
	public function add_coauthors_box() {
		if ( ! $this->is_post_type_enabled() || ! $this->current_user_can_set_authors() ) {
            return;
        }

		add_meta_box(
            'ssl_alp_coauthorsdiv',
            __( 'Authors', 'ssl-alp' ),
            array( $this, 'coauthors_meta_box' ),
            get_post_type(),
            'normal',
            'high'
        );
	}

	/**
	 * Callback for adding the custom author box
	 */
	public function coauthors_meta_box( $post ) {
        $current_screen = get_current_screen();

		// @daniel, $post->ID and $post->post_author are always set when a new post is created due to auto draft,
		// and the else case below was always able to properly assign users based on wp_posts.post_author
		if ( ! $post->ID || 0 === $post->ID || ( ! $post->post_author ) || ( 'post' === $current_screen->base && 'add' === $current_screen->action ) ) {
			$coauthors = array();

			// Use the current logged in user
			if ( empty( $coauthors ) ) {
				$coauthors[] = wp_get_current_user();
			}
		} else {
			$coauthors = get_coauthors( $post->ID );
		}

		$count = 0;

		if ( ! empty( $coauthors ) ) :
			?>
			<div id="coauthors-readonly" class="hide-if-js">
				<ul>
				<?php
				foreach ( $coauthors as $coauthor ) :
					$count++;
					?>
					<li>
						<?php echo get_avatar( $coauthor->user_email, 25 ); ?>
						<span id="<?php echo esc_attr( 'coauthor-readonly-' . $count ); ?>" class="coauthor-tag">
							<input type="text" name="coauthorsinput[]" readonly="readonly" value="<?php echo esc_attr( $coauthor->display_name ); ?>" />
							<input type="text" name="coauthors[]" value="<?php echo esc_attr( $coauthor->user_login ); ?>" />
							<input type="text" name="coauthorsemails[]" value="<?php echo esc_attr( $coauthor->user_email ); ?>" />
							<input type="text" name="coauthorsnicenames[]" value="<?php echo esc_attr( $coauthor->user_nicename ); ?>" />
						</span>
					</li>
					<?php
				endforeach;
				?>
				</ul>
				<div class="clear"></div>
				<p><?php echo wp_kses( __( '<strong>Note:</strong> To edit post authors, please enable JavaScript or use a JavaScript-capable browser', 'ssl-alp' ), array( 'strong' => array() ) ); ?></p>
			</div>
			<?php
		endif;
		?>

		<div id="coauthors-edit" class="hide-if-no-js">
			<p><?php echo wp_kses( __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'ssl-alp' ), array( 'strong' => array() ) ); ?></p>
		</div>

		<?php wp_nonce_field( 'coauthors-edit', 'ssl-alp-coauthors-nonce' ); ?>

		<?php
	}

	/**
	 * Removes the author dropdown from the post quick edit
	 */
	function remove_quick_edit_authors_box() {
		global $pagenow;

		if ( 'edit.php' == $pagenow && $this->is_post_type_enabled() ) {
			remove_post_type_support( get_post_type(), 'author' );
		}
	}

	/**
	 * Add coauthors to author column on edit pages
	 *
	 * @param array $post_columns
	 */
	function _filter_manage_posts_columns( $posts_columns ) {
		$new_columns = array();

		if ( ! $this->is_post_type_enabled() ) {
			return $posts_columns;
		}

		foreach ( $posts_columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['coauthors'] = __( 'Authors', 'ssl-alp' );
			}

			if ( 'author' === $key ) {
				unset( $new_columns[ $key ] );
			}
		}

		return $new_columns;
	}

	/**
	 * Insert coauthors into post rows on Edit Page
	 *
	 * @param string $column_name
	 */
	function _filter_manage_posts_custom_column( $column_name ) {
		if ( 'coauthors' !== $column_name ) {
            return;
        }

		global $post;
		$authors = get_coauthors( $post->ID );

		$count = 1;

		foreach ( $authors as $author ) {
			$args = array(
				'author_name' => $author->user_nicename
			);

			if ( 'post' != $post->post_type ) {
				$args['post_type'] = $post->post_type;
			}

			$author_filter_url = add_query_arg( array_map( 'rawurlencode', $args ), admin_url( 'edit.php' ) );

			?>
			<a href="<?php echo esc_url( $author_filter_url ); ?>"
			data-user_nicename="<?php echo esc_attr( $author->user_nicename ) ?>"
			data-user_email="<?php echo esc_attr( $author->user_email ) ?>"
			data-display_name="<?php echo esc_attr( $author->display_name ) ?>"
			data-user_login="<?php echo esc_attr( $author->user_login ) ?>"
			><?php echo esc_html( $author->display_name ); ?></a><?php echo ( $count < count( $authors ) ) ? ',' : ''; ?>
			<?php
			$count++;
		}
	}

	/**
	 * Unset the post count column because it's going to be inaccurate; instead
     * provide our own
	 */
	function _filter_manage_users_columns( $columns ) {
		$new_columns = array();

		// unset and add our column while retaining the order of the columns
		foreach ( $columns as $column_name => $column_title ) {
			if ( 'posts' == $column_name ) {
				$new_columns['ssl_alp_coauthors_post_count'] = __( 'Posts', 'ssl-alp' );
			} else {
				$new_columns[ $column_name ] = $column_title;
			}
		}

		return $new_columns;
	}

	/**
	 * Provide an accurate count when looking up the number of published posts for a user
	 */
	function _filter_manage_users_custom_column( $value, $column_name, $user_id ) {
		if ( 'ssl_alp_coauthors_post_count' != $column_name ) {
			return $value;
		}

		// filter count_user_posts() so it provides an accurate number
		$numposts = count_user_posts( $user_id );
		$user = get_user_by( 'id', $user_id );

		if ( $numposts > 0 ) {
			$value .= "<a href='edit.php?author_name=$user->user_nicename' title='" . esc_attr__( 'View posts by this author', 'ssl-alp' ) . "' class='edit'>";
			$value .= $numposts;
			$value .= '</a>';
		} else {
			$value .= 0;
		}

		return $value;
	}

	/**
	 * Quick Edit co-authors box.
	 */
	function _action_quick_edit_custom_box( $column_name, $post_type ) {
		if ( 'coauthors' != $column_name || ! $this->is_post_type_enabled( $post_type ) || ! $this->current_user_can_set_authors() ) {
			return;
		}

		?>
		<label class="inline-edit-group inline-edit-coauthors">
			<span class="title"><?php esc_html_e( 'Authors', 'ssl-alp' ) ?></span>
			<div id="coauthors-edit" class="hide-if-no-js">
				<p><?php echo wp_kses( __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'ssl-alp' ), array( 'strong' => array() ) ); ?></p>
			</div>
			<?php wp_nonce_field( 'coauthors-edit', 'ssl-alp-coauthors-nonce' ); ?>
		</label>
		<?php
	}

	/**
	 * When we update the terms at all, we should update the published post
     * count for each author
	 */
	function _update_users_posts_count( $tt_ids, $taxonomy ) {
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

		$coauthor = $this->get_coauthor_by( 'user_nicename', $term->slug );

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

		wp_cache_delete( 'ssl-alp-author-term-' . $coauthor->user_nicename, 'ssl-alp' );
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_join_filter( $join, $query ) {
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

		// check to see that JOIN hasn't already been added
		$term_relationship_inner_join = " INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
		$term_relationship_left_join = " LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";

		$term_taxonomy_join  = " INNER JOIN {$wpdb->term_relationships} AS tr1 ON ({$wpdb->posts}.ID = tr1.object_id)";
		$term_taxonomy_join .= " INNER JOIN {$wpdb->term_taxonomy} ON ( tr1.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id )";

		// 4.6+ uses a LEFT JOIN for taxonomy queries so we need to check for both
		if ( false === strpos( $join, trim( $term_relationship_inner_join ) )
			&& false === strpos( $join, trim( $term_relationship_left_join ) ) ) {
			$join .= $term_relationship_left_join;
		}

		if ( false === strpos( $join, trim( $term_taxonomy_join ) ) ) {
			$join .= str_replace( 'INNER JOIN', 'LEFT JOIN', $term_taxonomy_join );
		}

		return $join;
	}

	/**
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_where_filter( $where, $query ) {
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
			$author_name = sanitize_title( $query->get( 'author_name' ) );
		} else {
			$author_data = get_userdata( $query->get( 'author' ) );
			if ( is_object( $author_data ) ) {
				$author_name = $author_data->user_nicename;
			} else {
				return $where;
			}
		}

		$terms = array();
		$coauthor = $this->get_coauthor_by( 'user_nicename', $author_name );

		if ( $author_term = $this->get_author_term( $coauthor ) ) {
			$terms[] = $author_term;
		}

		$maybe_both_query = '$1 OR';

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
	 * Filters post data before saving to db to set post_author
	 */
	function coauthors_set_post_author_field( $data, $postarr ) {
		if ( defined( 'DOING_AUTOSAVE' ) && ! DOING_AUTOSAVE ) {
			return $data;
		}

		if ( ! $this->is_post_type_enabled( $data['post_type'] ) ) {
			return $data;
		}

		// This action happens when a post is saved while editing a post
		if ( isset( $_REQUEST['ssl-alp-coauthors-nonce'] ) && isset( $_POST['coauthors'] ) && is_array( $_POST['coauthors'] ) ) {
			$author = sanitize_text_field( $_POST['coauthors'][0] );

			if ( $author ) {
				$author_data = $this->get_coauthor_by( 'user_nicename', $author );
				$data['post_author'] = $author_data->ID;
			}
		}

		// If for some reason we don't have the coauthors fields set
		if ( ! isset( $data['post_author'] ) ) {
			$user = wp_get_current_user();
			$data['post_author'] = $user->ID;
		}

		return $data;
	}

	/**
	 * Update a post's co-authors on the 'save_post' hook
	 *
	 * @param $post_ID
	 */
	function coauthors_update_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && ! DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $this->is_post_type_enabled( $post->post_type ) ) {
			return;
		}

		if ( $this->current_user_can_set_authors( $post ) ) {
			// check nonce
			if ( isset( $_POST['ssl-alp-coauthors-nonce'] ) && isset( $_POST['coauthors'] ) ) {
				check_admin_referer( 'coauthors-edit', 'ssl-alp-coauthors-nonce' );

				$coauthors = (array) $_POST['coauthors'];
				$coauthors = array_map( 'sanitize_text_field', $coauthors );
				$this->add_coauthors( $post_id, $coauthors );
			}
		} else {
			// If the user can't set authors and a co-author isn't currently set, we need to explicity set one
			if ( ! $this->has_author_terms( $post_id ) ) {
				$user = get_userdata( $post->post_author );
				if ( $user ) {
					$this->add_coauthors( $post_id, array( $user->user_login ) );
				}
			}
		}
	}

	function has_author_terms( $post_id ) {
		$terms = wp_get_object_terms( $post_id, 'ssl_alp_coauthor', array( 'fields' => 'ids' ) );

		return ! empty( $terms ) && ! is_wp_error( $terms );
	}

	/**
	 * Add one or more co-authors as bylines for a post
	 */
	public function add_coauthors( $post_id, $coauthors, $append = false ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$insert = false;
        $current_user = wp_get_current_user();

		// Best way to persist order
		if ( $append ) {
			$existing_coauthors = wp_list_pluck( get_coauthors( $post_id ), 'user_login' );
		} else {
			$existing_coauthors = array();
		}

		// at least one co-author is always required
		if ( empty( $coauthors ) ) {
			$coauthors = array( $current_user->user_login );
		}

		// set the coauthors
		$coauthors = array_unique( array_merge( $existing_coauthors, $coauthors ) );
		$coauthor_objects = array();

		foreach ( $coauthors as &$author_name ) {
			$author = $this->get_coauthor_by( 'user_nicename', $author_name );
			$coauthor_objects[] = $author;
			$term = $this->update_author_term( $author );
			$author_name = $term->slug;
		}

		wp_set_post_terms( $post_id, $coauthors, 'ssl_alp_coauthor', false );

		// If the original post_author is no longer assigned,
		// update to the first WP_User $coauthor
		$post_author_user = get_user_by( 'id', get_post( $post_id )->post_author );

		if ( empty( $post_author_user ) || ! in_array( $post_author_user->user_login, $coauthors ) ) {
			foreach ( $coauthor_objects as $coauthor_object ) {
				$new_author = $coauthor_object;
				break;
			}

			if ( empty( $new_author ) ) {
                // no WP_Users assigned to the post
				return false;
			}

			$wpdb->update( $wpdb->posts, array( 'post_author' => $new_author->ID ), array( 'ID' => $post_id ) );
			clean_post_cache( $post_id );
		}

		return true;
	}

	/**
	 * Action taken when user is deleted.
	 * - User term is removed from all associated posts
	 * - Option to specify alternate user in place for each post
	 */
	function delete_user_action( $delete_id ) {
		global $wpdb;

		$reassign_id = isset( $_POST['reassign_user'] ) ? absint( $_POST['reassign_user'] ) : false;

		// If reassign posts, do that -- use coauthors_update_post
		if ( $reassign_id ) {
			// Get posts belonging to deleted author
			$reassign_user = get_user_by( 'id', $reassign_id );

			// Set to new author
			if ( is_object( $reassign_user ) ) {
				$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $delete_id ) );

				if ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						$this->add_coauthors( $post_id, array( $reassign_user->user_login ), true );
					}
				}
			}
		}

		$delete_user = get_user_by( 'id', $delete_id );

		if ( is_object( $delete_user ) ) {
			// Delete term
			wp_delete_term( $delete_user->user_login, 'ssl_alp_coauthor' );
		}
	}

	/**
	 * Restrict WordPress from blowing away author order when bulk editing terms
	 */
	function filter_wp_get_object_terms( $terms, $object_ids, $taxonomies, $args ) {
		if ( ! isset( $_REQUEST['bulk_edit'] ) || "'author'" !== $taxonomies ) {
            // not bulk editing authors
			return $terms;
		}

		global $wpdb;

		$orderby = 'ORDER BY tr.term_order';
		$order = 'ASC';
		$object_ids = (int) $object_ids;
		$query = $wpdb->prepare( "SELECT t.name, t.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN (%s) AND tr.object_id IN (%s) $orderby $order", 'ssl_alp_coauthor', $object_ids );
		$raw_coauthors = $wpdb->get_results( $query );
		$terms = array();

		foreach ( $raw_coauthors as $author ) {
			if ( true === is_array( $args ) && true === isset( $args['fields'] ) ) {
				switch ( $args['fields'] ) {
					case 'names' :
						$terms[] = $author->name;
						break;
					case 'tt_ids' :
						$terms[] = $author->term_taxonomy_id;
						break;
					case 'all' :
					default :
						$terms[] = get_term( $author->term_id, 'ssl_alp_coauthor' );
						break;
				}
			} else {
				$terms[] = get_term( $author->term_id, 'ssl_alp_coauthor' );
			}
		}

		return $terms;
	}

	/**
	 * Filter the count_users_posts() core function to include our correct count
	 */
	function filter_count_user_posts( $count, $user_id ) {
		$user = get_userdata( $user_id );
		$user = $this->get_coauthor_by( 'user_nicename', $user->user_nicename );

		$term = $this->get_author_term( $user );

		// Only modify the count if the author already exists as a term
		if ( $term && ! is_wp_error( $term ) ) {
			$count = $term->count;
		}

		return $count;
	}

	/**
	 * Checks to see if the current user can set authors or not
	 */
	function current_user_can_set_authors( $post = null ) {
		global $typenow;

        // super admins can do anything
		if ( function_exists( 'is_super_admin' ) && is_super_admin() ) {
			return true;
		}

        $post = get_post( $post );

		if ( ! $post ) {
			return false;
		}

		// TODO: need to fix this; shouldn't just say no if don't have post_type
		if ( ! $post->post_type ) {
			return false;
		}

		$post_type_object = get_post_type_object( $post->post_type );
		$current_user = wp_get_current_user();

		if ( ! $current_user ) {
			return false;
		}

        $can_edit_others_posts = $current_user->allcaps['edit_others_posts'];
		$can_set_authors = isset( $can_edit_others_posts ) ? $can_edit_others_posts : false;

		return $can_set_authors;
	}

	/**
	 * Fix for author pages 404ing or not properly displaying on author pages
	 *
	 * If an author has no posts, we only want to force the queried object to be
	 * the author if they're a member of the blog.
	 *
	 * If the author does have posts, it doesn't matter that they're not an author.
	 *
	 * Alternatively, on an author archive, if the first story has coauthors and
	 * the first author is NOT the same as the author for the archive,
	 * the query_var is changed.
	 */
	public function fix_author_page() {
		if ( ! is_author() ) {
			return;
		}

		$author_id = absint( get_query_var( 'author' ) );
		$author_name = sanitize_title( get_query_var( 'author_name' ) );

		if ( isset( $author_id ) ) {
			// get author by id
			$author = $this->get_coauthor_by( 'id', $author_id );
		} elseif ( isset( $author_name ) ) {
			// get author by specified name
			$author = $this->get_coauthor_by( 'user_nicename', $author_name );
		} else {
			// no query variable was specified; not much we can do
			return;
		}

		global $wp_query, $authordata;

		if ( is_object( $author ) ) {
			$authordata = $author;
			$term = $this->get_author_term( $authordata );
		}

		if ( ( is_object( $authordata ) ) || ( ! empty( $term ) && $term->count ) ) {
			$wp_query->queried_object = $authordata;
			$wp_query->queried_object_id = $authordata->ID;
		} else {
			$wp_query->queried_object = $wp_query->queried_object_id = null;
			$wp_query->is_author = $wp_query->is_archive = false;
			$wp_query->is_404 = false;
		}
	}

    /**
     * Filter array of comment notification email addresses
     */
    public function filter_comment_notification_email_recipients( $recipients, $comment_id ) {
    	$comment = get_comment( $comment_id );
    	$post_id = $comment->comment_post_ID;

    	if ( isset( $post_id ) ) {
    		$coauthors = get_coauthors( $post_id );
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
    	$comment = get_comment( $comment );
    	$post_id = $comment->comment_post_ID;

    	if ( isset( $post_id ) ) {
    		$coauthors = get_coauthors( $post_id );
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
	 * Main function that handles search-as-you-type for adding authors
	 */
	public function ajax_suggest() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ssl-alp-coauthors-search' ) ) {
			exit();
		}

		if ( empty( $_REQUEST['q'] ) ) {
			exit();
		}

		$search = sanitize_text_field( strtolower( $_REQUEST['q'] ) );
		$ignore = array_map( 'sanitize_text_field', explode( ',', $_REQUEST['existing_authors'] ) );

		$authors = $this->search_authors( $search, $ignore );

		foreach ( $authors as $author ) {
			echo esc_html( $author->ID . ' | ' . $author->user_login . ' | ' . $author->display_name . ' | ' . $author->user_email . ' | ' . $author->user_nicename ) . "\n";
		}

		exit();
	}

	/**
	 * Get matching authors based on a search value
	 */
	public function search_authors( $search = '', $ignored_authors = array() ) {
        // Since 2.7, we're searching against the term description for the fields
		// instead of the user details. If the term is missing, we probably need to
		// backfill with user details. Let's do this first... easier than running
		// an upgrade script that could break on a lot of users
		$args = array(
			'count_total' => false,
			'search' => sprintf( '*%s*', $search ),
			'search_fields' => array(
				'ID',
				'display_name',
				'user_email',
				'user_login',
			),
			'fields' => 'all_with_meta',
		);

		$found_users = get_users( $args );

		foreach ( $found_users as $found_user ) {
			$term = $this->get_author_term( $found_user );

			if ( empty( $term ) || empty( $term->description ) ) {
				$this->update_author_term( $found_user );
			}
		}

		$args = array(
			'search' => $search,
			'get' => 'all',
			'number' => 10,
		);

		add_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );
		$found_terms = get_terms( 'ssl_alp_coauthor', $args );
		remove_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );

		if ( empty( $found_terms ) ) {
			return array();
		}

		// get the co-author objects
		$found_users = array();

		foreach ( $found_terms as $found_term ) {
			$found_user = $this->get_coauthor_by( 'user_nicename', $found_term->slug );

			if ( ! empty( $found_user ) ) {
				$found_users[ $found_user->user_login ] = $found_user;
			}
		}

		foreach ( $found_users as $key => $found_user ) {
			if ( in_array( $found_user->user_login, $ignored_authors ) ) {
				unset( $found_users[ $key ] );
			}
		}

		return (array) $found_users;
	}

	/**
	 * Modify get_terms() to LIKE against the term description instead of the
     * term name
	 */
	function filter_terms_clauses( $pieces ) {
		$pieces['where'] = str_replace( 't.name LIKE', 'tt.description LIKE', $pieces['where'] );

		return $pieces;
	}

	/**
	 * load-edit.php is when the screen has been set up
	 */
	function load_edit() {
		$screen = get_current_screen();

		if ( in_array( $screen->post_type, $this->supported_post_types ) ) {
			add_filter( 'views_' . $screen->id, array( $this, 'filter_views' ) );
		}
	}

	/**
	 * Filter the view links that appear at the top of the Manage Posts view
	 */
	function filter_views( $views ) {
		if ( array_key_exists( 'mine', $views ) ) {
			return $views;
		}

		$views = array_reverse( $views );
		$all_view = array_pop( $views );
		$mine_args = array(
			'author_name'           => wp_get_current_user()->user_nicename,
		);

		if ( 'post' != get_post_type() ) {
			$mine_args['post_type'] = get_post_type();
		}

		if ( ! empty( $_REQUEST['author_name'] ) && wp_get_current_user()->user_nicename == $_REQUEST['author_name'] ) {
			$class = ' class="current"';
		} else {
			$class = '';
		}

		$views['mine'] = $view_mine = '<a' . $class . ' href="' . esc_url( add_query_arg( array_map( 'rawurlencode', $mine_args ), admin_url( 'edit.php' ) ) ) . '">' . __( 'Mine', 'ssl-alp' ) . '</a>';

		$views['all'] = str_replace( $class, '', $all_view );
		$views = array_reverse( $views );

		return $views;
	}

	/**
	 * Adds necessary JavaScript variables to admin head section
	 */
	public function js_vars() {
		if ( ! $this->is_post_type_enabled() || ! $this-> current_user_can_set_authors() ) {
			return;
		}
		?>
			<script type="text/javascript">
				// AJAX link used for the autosuggest
				var coAuthorsPlus_ajax_suggest_link = <?php
				echo wp_json_encode(
					add_query_arg(
						array(
							'action' => 'coauthors_ajax_suggest',
							'post_type' => rawurlencode( get_post_type() ),
						),
						wp_nonce_url( 'admin-ajax.php', 'ssl-alp-coauthors-search' )
					)
				); ?>;
			</script>
		<?php
	}

	/**
	 * Allows coauthors to edit the post they're coauthors of
	 */
	function filter_user_has_cap( $allcaps, $caps, $args ) {
		$cap = $args[0];
		$user_id = isset( $args[1] ) ? $args[1] : 0;
		$post_id = isset( $args[2] ) ? $args[2] : 0;

		$obj = get_post_type_object( get_post_type( $post_id ) );

		if ( ! $obj || 'revision' == $obj->name ) {
			return $allcaps;
		}

		$caps_to_modify = array(
			$obj->cap->edit_post,
			'edit_post', // Need to filter this too, unfortunately: http://core.trac.wordpress.org/ticket/22415
			$obj->cap->edit_others_posts, // This as well: http://core.trac.wordpress.org/ticket/22417
		);

		if ( ! in_array( $cap, $caps_to_modify ) ) {
			return $allcaps;
		}

		// We won't be doing any modification if they aren't already a co-author on the post
		if ( ! is_user_logged_in() || ! is_coauthor_for_post( $user_id, $post_id ) ) {
			return $allcaps;
		}

		$current_user = wp_get_current_user();

		if ( 'publish' == get_post_status( $post_id ) &&
			( isset( $obj->cap->edit_published_posts ) && ! empty( $current_user->allcaps[ $obj->cap->edit_published_posts ] ) ) ) {
			$allcaps[ $obj->cap->edit_published_posts ] = true;
		} elseif ( 'private' == get_post_status( $post_id ) &&
			( isset( $obj->cap->edit_private_posts ) && ! empty( $current_user->allcaps[ $obj->cap->edit_private_posts ] ) ) ) {
			$allcaps[ $obj->cap->edit_private_posts ] = true;
		}

		$allcaps[ $obj->cap->edit_others_posts ] = true;

		return $allcaps;
	}

	/**
	 * Get the author term for a given co-author
	 */
	public function get_author_term( $coauthor ) {
		if ( ! is_object( $coauthor ) ) {
			return;
		}

		$cache_key = 'ssl-alp-author-term-' . $coauthor->user_nicename;

		if ( false !== ( $term = wp_cache_get( $cache_key, 'ssl-alp' ) ) ) {
			return $term;
		}

		// See if the prefixed term is available, otherwise default to just the nicename
		$term = get_term_by( 'slug', 'ssl-alp-coauthor-' . $coauthor->user_nicename, 'ssl_alp_coauthor' );

		if ( ! $term ) {
			$term = get_term_by( 'slug', $coauthor->user_nicename, 'ssl_alp_coauthor' );
		}

		wp_cache_set( $cache_key, $term, 'ssl-alp' );

		return $term;
	}

	/**
	 * Update the author term for a given co-author
	 */
	public function update_author_term( $coauthor ) {
		if ( is_int( $coauthor ) ) {
			// get user by their id
			$coauthor = get_user_by( 'id', $coauthor );
		}
		
		if ( ! is_object( $coauthor ) ) {
			return false;
		}

		// update the taxonomy term to include details about the user for searching
		$search_values = array();

		foreach ( $this->ajax_search_fields as $search_field ) {
			$search_values[] = $coauthor->$search_field;
		}

		$term_description = implode( ' ', $search_values );

		if ( $term = $this->get_author_term( $coauthor ) ) {
			if ( $term->description != $term_description ) {
				wp_update_term( $term->term_id, 'ssl_alp_coauthor', array( 'description' => $term_description ) );
			}
		} else {
			$coauthor_slug = 'ssl-alp-coauthor-' . $coauthor->user_nicename;

			$args = array(
				'slug'          => $coauthor_slug,
				'description'   => $term_description,
			);

			$new_term = wp_insert_term( $coauthor->user_login, 'ssl_alp_coauthor', $args );
		}

		wp_cache_delete( 'ssl-alp-author-term-' . $coauthor->user_nicename, 'ssl-alp' );

		return $this->get_author_term( $coauthor );
	}

	/**
	 * Retrieve a list of coauthor terms for a single post.
	 *
	 * Grabs a correctly ordered list of authors for a single post, appropriately
	 * cached because it requires `wp_get_object_terms()` to succeed.
	 */
	public function get_coauthor_terms_for_post( $post_id ) {
		if ( ! $post_id ) {
			return array();
		}

		$cache_key = 'coauthors_post_' . $post_id;
		$coauthor_terms = wp_cache_get( $cache_key, 'ssl-alp' );

		if ( false === $coauthor_terms ) {
			$coauthor_terms = wp_get_object_terms(
                $post_id,
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

			wp_cache_set( $cache_key, $coauthor_terms, 'ssl-alp' );
		}

		return $coauthor_terms;
	}

	/**
	 * Check whether the coauthors for this post have changed, to determine whether to trigger a save.
	 */
	public function check_coauthors_have_changed( $post_has_changed, WP_Post $last_revision, WP_Post $post ) {
		// skip when autosaving, as custom post data is annoyingly not included in $_POST during autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_has_changed;
		}

		if ( ! array_key_exists( 'coauthors', $_POST ) ) {
			// no coauthors submitted
			return $post_has_changed;
		}

		// coauthors on published post
		$parent_coauthors = get_coauthors( $post->ID );

		// coauthors on current revision
		$current_coauthors = array();

		foreach ( $_POST['coauthors'] as $author ) {
			$author = sanitize_text_field( $author );

			if ( $author ) {
				$current_coauthors[] = $this->get_coauthor_by( 'user_nicename', $author );
			}
		}

		// check if coauthors have changed
		if ( $parent_coauthors != $current_coauthors ) {
			$post_has_changed = true;
		}

		return $post_has_changed;
	}

	/**
	 * Callback to clear the cache on post save and post delete.
	 */
	public function clear_cache( $post_id ) {
		wp_cache_delete( 'coauthors_post_' . $post_id, 'ssl-alp' );
	}

	/**
	 * Callback to clear the cache when an object's terms are changed.
	 *
	 * @param $post_id The Post ID.
	 */
	public function clear_cache_on_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( 'ssl_alp_coauthor' !== $taxonomy ) {
            // we only care about the coauthors taxonomy
			return;
		}

		wp_cache_delete( 'coauthors_post_' . $object_id, 'ssl-alp' );
	}
}

if ( ! function_exists( 'get_coauthors' ) ) :
function get_coauthors( $post_id = 0 ) {
	global $post, $post_ID, $wpdb;

	$coauthors = array();
	$post_id = (int) $post_id;

	if ( ! $post_id && $post_ID ) {
		$post_id = $post_ID;
	}

	if ( ! $post_id && $post ) {
		$post_id = $post->ID;
	}

	if ( $post_id ) {
        global $ssl_alp;

		$coauthor_terms = $ssl_alp->coauthors->get_coauthor_terms_for_post( $post_id );

		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			foreach ( $coauthor_terms as $coauthor ) {
				$coauthor_slug = preg_replace( '#^cap\-#', '', $coauthor->slug );
				$post_author = $ssl_alp->coauthors->get_coauthor_by( 'user_nicename', $coauthor_slug );
				
				// In case the user has been deleted while plugin was deactivated
				if ( ! empty( $post_author ) ) {
					$coauthors[] = $post_author;
				}
			}
		} else {
			if ( $post && $post_id == $post->ID ) {
				$post_author = get_userdata( $post->post_author );
			} else {
				$post_author = get_userdata( $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM $wpdb->posts WHERE ID = %d", $post_id ) ) );
			}

			if ( ! empty( $post_author ) ) {
				$coauthors[] = $post_author;
			}
		}
	}

	return $coauthors;
}
endif;

if ( ! function_exists( 'is_coauthor_for_post' ) ) :
/**
 * Checks to see if the the specified user is author of the current global post or post (if specified)
 * @param object|int $user
 * @param int $post_id
 */
function is_coauthor_for_post( $user, $post_id = null ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	if ( ! $user ) {
		return false;
	}

	$coauthors = get_coauthors( $post->ID );

	if ( is_numeric( $user ) ) {
		$user = get_userdata( $user );
		$user = $user->user_login;
	} else if ( isset( $user->user_login ) ) {
		$user = $user->user_login;
	} else {
		return false;
	}

	foreach ( $coauthors as $coauthor ) {
		if ( $user == $coauthor->user_login || $user == $coauthor->linked_account ) {
			return true;
		}
	}

	return false;
}
endif;

class SSL_ALP_Users extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'ssl_alp_users_widget', // base ID
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

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
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
			$post_counts = $this->count_many_users_posts( $user_ids );

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
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Users' );
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

	/**
	 * Override default `count_many_users_posts` if coauthors are enabled
	 */
	protected function count_many_users_posts( $user_ids ) {
		global $ssl_alp;

		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
			/**
			 * Call coauthor class to get filtered counts. This tells the function that the
			 * user currently has 0 posts, which is not usually true. This is fine, unless
			 * for some reason the user's "count" metadata has not been updated.
			 */
			$counts[$user_id] = $ssl_alp->coauthors->filter_count_user_posts( 0, $user_id );
		}

		return $counts;
	}
}
