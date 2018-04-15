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
		if ( ! $this->post_supports_coauthors() || ! $this->current_user_can_set_authors() ) {
			return;
		}

		wp_enqueue_script( 'ssl-alp-coauthors-js', SSL_ALP_BASE_URL . 'js/coauthors.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-autocomplete' ), $this->get_version(), true );

		$js_strings = array(
			'edit_label' => __( 'Edit', 'ssl-alp' ),
			'delete_label' => __( 'Remove', 'ssl-alp' ),
			'confirm_delete' => __( 'Are you sure you want to remove this author?', 'ssl-alp' ),
			'input_box_title' => __( 'Click to change this author, or drag to change their position', 'ssl-alp' ),
			'search_box_text' => __( 'Search for an author', 'ssl-alp' ),
			'help_text' => __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.', 'ssl-alp' ),
		);

		wp_localize_script( 'ssl-alp-coauthors-js', 'ssl_alp_coauthors_strings', $js_strings );

		if ( $this->is_post_type_enabled( get_post_type() ) ) {
			$data = add_query_arg(
				array(
					'action' => 'ssl_alp_coauthors_ajax_suggest',
					'post_type' => rawurlencode( get_post_type() ),
				),
				wp_nonce_url( 'admin-ajax.php', 'ssl-alp-coauthors-search' )
			);

			wp_localize_script( 'ssl-alp-coauthors-js', 'ssl_alp_coauthors_ajax_suggest_link', $data );
		}
	}

    /**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// register the authors widget
		$loader->add_action( 'widgets_init', $this, 'register_users_widget' );

		// register authors taxonomy
		$loader->add_action( 'init', $this, 'register_taxonomy' );

		// remove author support
		$loader->add_action( 'init', $this, 'remove_author_support' );

		// hooks to modify the published post number count on the Users WP List Table
		// these are required because the count_many_users_posts() function has no hooks
		$loader->add_filter( 'manage_users_columns', $this, 'filter_manage_users_columns' );
		$loader->add_filter( 'manage_users_custom_column', $this, 'filter_manage_users_custom_column', 10, 3 );

		// Modify SQL queries to include coauthors
		$loader->add_filter( 'posts_where', $this, 'posts_where_filter', 10, 2 );
		$loader->add_filter( 'posts_join', $this, 'posts_join_filter', 10, 2 );
		$loader->add_filter( 'posts_groupby', $this, 'posts_groupby_filter', 10, 2 );

		// Action to set users when a post is saved
		$loader->add_action( 'save_post', $this, 'coauthors_update_post', 10, 2 );

		// auto-suggest via AJAX
		$loader->add_action( 'wp_ajax_ssl_alp_coauthors_ajax_suggest', $this, 'ajax_suggest' );

		// delete user terms from posts when a user is deleted
		$loader->add_action( 'delete_user', $this, 'delete_user_action', 10, 2 );

		// Include coauthored posts in post counts.
		// Unfortunately, this doesn't filter results retrieved with `count_many_users_posts`, which
		// also doesn't have hooks to allow filtering; therefore know that this filter doesn't catch
		// every count event.
		$loader->add_filter( 'get_usernumposts', $this, 'filter_count_user_posts', 10, 2 );

		// Filter to allow coauthors to edit posts
		$loader->add_filter( 'user_has_cap', $this, 'filter_user_has_cap', 10, 3 );

		// Handle the custom author meta box
		$loader->add_action( 'add_meta_boxes', $this, 'remove_authors_box' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_coauthors_box' );

		// Make sure we've correctly set author data on author pages
		$loader->add_filter( 'posts_selection', $this, 'fix_author_page' ); // use posts_selection since it's after WP_Query has built the request and before it's queried any posts
		$loader->add_action( 'the_post', $this, 'fix_author_page' );

		// save revisions if coauthors have changed
		$loader->add_filter( 'wp_save_post_revision_post_has_changed', $this, 'check_coauthors_have_changed', 10, 3 );

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
	public function register_taxonomy() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// Register new taxonomy so that we can store all of the relationships
		$args = array(
			'hierarchical'   	=> false,
			'label'          	=> __( 'Authors', 'ssl-alp' ),
			'query_var'      	=> false,
			'rewrite'        	=> false,
			'public'         	=> false,
			'sort'           	=> true, // remember order terms are added to posts
			'show_ui'        	=> false,
			'show_admin_column'	=> true // show associated terms in admin edit screen
		);

		// callback to update user post counts
		$args['update_count_callback'] = array( $this, 'update_users_posts_count' );

        // create coauthor taxonomy
		register_taxonomy( 'ssl_alp_coauthor', $this->supported_post_types, $args );
	}

	/**
	 * Remove author support (replaced by coauthor support)
	 */
	public function remove_author_support() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		foreach ( $this->supported_post_types as $post_type ) {
			remove_post_type_support( $post_type, 'author' );
		}
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

		return get_term_by( 'name', $user->user_login, 'ssl_alp_coauthor' );
	}

	public function delete_coauthor_term( $user ) {
		if ( is_null( $user ) ) {
			return false;
		}

		// get the user's term
		$term = $this->get_coauthor_term( $user );
		
		// delete user's term
		wp_delete_term( $term->term_id, 'ssl_alp_coauthor' );
	}

	/**
	 * Remove the standard post edit authors metabox (is replaced with coauthors box by add_coauthors_box)
	 */
	public function remove_authors_box() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( $this->post_supports_coauthors() ) {
			remove_meta_box( 'authordiv', get_post_type(), 'normal' );	
        }
	}

	/**
	 * Add the coauthors metabox
	 */
	public function add_coauthors_box() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( ! $this->post_supports_coauthors() || ! $this->current_user_can_set_authors() ) {
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
	 * Get coauthor avatar HTML
	 */
	public function get_coauthor_avatar( $coauthor, $only_url = false ) {
		if ( $only_url ) {
			return get_avatar_url(
				$coauthor->ID,
				array(
					'size'	=>	25
				)
			);
		}

		return get_avatar( $coauthor->ID, 25 );
	}

	/**
	 * Callback for adding the custom author box
	 */
	public function coauthors_meta_box( $post ) {
        $current_screen = get_current_screen();
		$coauthors = $this->get_coauthors( $post );

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
						<span id="<?php echo esc_attr( 'coauthor-readonly-' . $count ); ?>" class="coauthor-tag">
							<input type="text" name="coauthorsinput[]" readonly="readonly" value="<?php echo esc_attr( $coauthor->display_name ); ?>" />
							<input type="text" name="coauthors[]" value="<?php echo esc_attr( $coauthor->user_login ); ?>" />
							<input type="text" name="coauthorsavatar[]" value="<?php echo $this->get_coauthor_avatar( $coauthor, true); ?>" />
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

		<?php wp_nonce_field( 'ssl-alp-coauthors-edit', 'ssl-alp-coauthors-nonce' ); ?>

		<?php
	}

	/**
	 * Unset the post count column because it's going to be inaccurate; instead
     * provide our own
	 */
	function filter_manage_users_columns( $columns ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return $columns;
		}

		$new_columns = array();

		// unset and add our column while retaining the order of the columns
		foreach ( $columns as $column_name => $column_title ) {
			if ( 'posts' == $column_name ) {
				$new_columns['ssl-alp-coauthors-post-count'] = __( 'Posts', 'ssl-alp' );
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
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
				'<a href="edit.php?author_name=%1$s" title="%2$s" class="edit">%3$d</a>',
				$user->user_nicename,
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

		$coauthor = get_user_by( 'login', $term->name );

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
	 * Modify the author query posts SQL to include posts co-authored
	 */
	function posts_join_filter( $join, $query ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
			$author_name = sanitize_title( $query->get( 'author_name' ) );
		} else {
			$author_data = get_userdata( $query->get( 'author' ) );

			if ( is_object( $author_data ) ) {
				$author_name = $author_data->user_login;
			} else {
				// no author defined
				return $where;
			}
		}

		$terms = array();
		$coauthor = get_user_by( 'login', $author_name );

		if ( $author_term = $this->get_coauthor_term( $coauthor ) ) {
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
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
	 * Update a post's co-authors on the 'save_post' hook
	 *
	 * @param $post_ID
	 */
	function coauthors_update_post( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && ! DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $this->post_supports_coauthors( $post ) ) {
			return;
		}

		$coauthors = array();

		if ( $this->current_user_can_set_authors( $post ) && isset( $_POST['ssl-alp-coauthors-nonce'] ) && isset( $_POST['coauthors'] ) ) {
			// User is allowed to set authors, and the post has been updated using the post edit page.
			// We will allow coauthors to be completely replaced by whatever is specified.

			// check nonce
			check_admin_referer( 'ssl-alp-coauthors-edit', 'ssl-alp-coauthors-nonce' );

			$posted_coauthor_names = (array) $_POST['coauthors'];

			foreach ( $posted_coauthor_names as $posted_coauthor_name ) {
				$posted_coauthor_name = sanitize_text_field( $posted_coauthor_name );

				// get user object
				$user = get_user_by( 'login', $posted_coauthor_name );

				if ( is_object( $user ) ) {
					$coauthors[] = $user;
				}
			}
		} else {
			// The post has been updated using quick edit or programmatically, or the user doesn't
			// have capabilities to set authors. We will only allow the main author to be changed.

			// get existing coauthors
			$coauthors = $this->get_coauthors( $post );

			// get post's primary author
			$primary_author = get_userdata( $post->post_author );

			if ( reset( $coauthors ) !== $primary_author ) {
				// primary author is no longer first coauthor
				
				if ( in_array( $primary_author, $coauthors ) ) {
					// delete author from current place in list
					unset( $coauthors[ array_search( $primary_author, $coauthors ) ] );
				}
				
				// add author to front
				array_unshift( $coauthors, $primary_author );
			}				
		}
		
		$this->set_coauthors( $post, $coauthors );
	}

	function has_author_terms( $post_id ) {
		$terms = wp_get_object_terms( $post_id, 'ssl_alp_coauthor', array( 'fields' => 'ids' ) );

		return ! empty( $terms ) && ! is_wp_error( $terms );
	}

	/**
	 * Set one or more coauthors for a post
	 */
	public function set_coauthors( $post, $coauthors ) {
		global $wpdb;

		$post = get_post( $post );

		// get post's primary author
		$primary_author = get_userdata( $post->post_author );

		if ( ! is_array( $coauthors ) ) {
			// invalid input
			return;
		}

		if ( empty( $coauthors ) ) {
			// no coauthors specified; this might be because the post has been created
			// programmatically.
			
			// use post's primary author 
			if ( is_object( $primary_author ) ) {
				$coauthors[] = $primary_author;
			}
		}

		// get coauthor objects and create their terms
		foreach ( $coauthors as $coauthor ) {
			if ( ! is_object( $coauthor ) ) {
				// invalid user specified
				// remove
				unset( $coauthors[ array_search( $coauthor, $coauthors ) ] );
			}
			
			// create author term if it doesn't exist
			$this->add_coauthor_term( $coauthor );
		}

		$coauthor_terms = array_map( array( $this, 'get_coauthor_term' ), $coauthors );

		// create list of term ids
		$coauthor_term_ids = wp_list_pluck( $coauthor_terms, 'term_id' );

		wp_set_post_terms( $post->ID, $coauthor_term_ids, 'ssl_alp_coauthor', false );

		if ( ! count( $coauthors ) ) {
			// empty terms
			// no point continuing
			return;
		}

		// update primary author if no longer first
		if ( reset( $coauthors ) !== $primary_author ) {
			$new_author = reset( $coauthors );

			$wpdb->update( $wpdb->posts, array( 'post_author' => $new_author->ID ), array( 'ID' => $post->ID ) );
			clean_post_cache( $post->ID );
		}
	}

	/**
	 * Action taken when user is deleted.
	 */
	function delete_user_action( $delete_id, $reassign_id ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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

		if ( ! is_null( $reassign_id ) ) {
			// user's posts are to be reassigned

			// get user to reassign posts to
			$reassign_user = get_user_by( 'id', $reassign_id );

			if ( is_object( $reassign_user ) ) {
				// WordPress Core handles reassigning primary author posts
				// we reassign seconary (coauthored) posts here
				
				// get all posts user is author of
				$coauthored_posts = $this->get_coauthor_posts( $delete_user );

				// get primary posts
				$primary_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type IN ('$post_type_sql')", $delete_user->ID ) );
				$primary_posts = array_filter( $primary_post_ids, 'get_post' );

				// remove primary authored posts from list
				$secondary_posts = array_diff( $coauthored_posts, $primary_posts );

				if ( count( $secondary_posts ) ) {
					foreach ( $secondary_posts as $secondary_post ) {
						// get existing coauthors of this post
						$coauthors = $this->get_coauthors( $secondary_post );

						// reassign coauthor, preserving order
						$coauthors[ array_search( $delete_user, $coauthors ) ] = $reassign_user;

						// update
						$this->set_coauthors( $secondary_post, $coauthors );
					}
				}
			}
		} else {
			// users posts will not be reassigned, but we must make sure that their posts
			// are not deleted where there are other authors

			// get user's primary posts
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type IN ('$post_type_sql')", $delete_user->ID ) );

			foreach ( $post_ids as $post_id ) {
				$post = get_post( $post_id );

				// check if post has multiple coauthors
				$coauthors = $this->get_coauthors( $post );

				if ( count( $coauthors ) > 1 ) {
					// remove the deleted user
					unset( $coauthors[ array_search( $delete_user, $coauthors ) ] );

					// set coauthors (this changes the primary author)
					$this->set_coauthors( $post, $coauthors );
				}
			}
		}

		// delete user's term
		$this->delete_coauthor_term( $delete_user );
	}

	public function get_coauthor_posts( $user ) {
		$posts = array();

		// find user term
		$user_term = $this->get_coauthor_term( $user );

		if ( ! $user_term ) {
			// no term, so assume no posts
			return $posts;
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

	/**
	 * Filter the count_users_posts() core function to include our correct count
	 */
	function filter_count_user_posts( $count, $user_id ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return $count;
		}

		$user = get_userdata( $user_id );
		$user = get_user_by( 'login', $user->user_login );
		$term = $this->get_coauthor_term( $user );

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
	 * Alternatively, on an author archive, if the first story has coauthors and
	 * the first author is NOT the same as the author for the archive,
	 * the query_var is changed.
	 */
	public function fix_author_page() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
			$author = get_user_by( 'login', $author_name );
		} else {
			// no query variable was specified; not much we can do
			return;
		}

		global $wp_query, $authordata;

		if ( is_object( $author ) ) {
			// override the authordata global with the requested author, in case the
			// first post's primary author is not the requested author
			$authordata = $author;
			$term = $this->get_coauthor_term( $authordata );
		}

		if ( ( is_object( $authordata ) ) || ( ! empty( $term ) && $term->count ) ) {
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

    /**
     * Filter array of comment notification email addresses
     */
    public function filter_comment_notification_email_recipients( $recipients, $comment_id ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
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
	 * Handles search-as-you-type for adding authors
	 */
	public function ajax_suggest() {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ssl-alp-coauthors-search' ) ) {
			// invalid/no nonce provided
			exit();
		}

		if ( empty( $_REQUEST['term'] ) ) {
			// no JSON search query made
			exit();
		}

		$search = sanitize_text_field( strtolower( $_REQUEST['term'] ) );
		$ignore = array_map( 'sanitize_text_field', explode( ',', $_REQUEST['existing_authors'] ) );

		$authors = $this->search_authors( $search, $ignore );

		// JSON response
		$response = array();

		foreach ( $authors as $author ) {
			// add author to JSON response
			$response[] = array(
				'id'			=>	$author->ID,
				'login'			=>	$author->user_login,
				'display_name'	=>	$author->display_name,
				'avatar'		=>	$this->get_coauthor_avatar( $author, true )
			);
		}

		// send client JSON, then exit
		wp_send_json( $response );
	}

	/**
	 * Get matching authors based on a search value
	 */
	public function search_authors( $search = '', $ignored_authors = array() ) {
        // search for author
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

		// get all authors
		$authors = get_users( $args );

		// filter out ignored ones
		foreach ( $authors as $key => $author ) {
			if ( in_array( $author->user_login, $ignored_authors ) ) {
				// remove ignored author
				unset( $authors[ $key ] );
			}
		}

		return $authors;
	}

	/**
	 * Allows coauthors to edit the post they're coauthors of
	 */
	function filter_user_has_cap( $allcaps, $caps, $args ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return $allcaps;
		}

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
		if ( ! is_user_logged_in() || ! $this->is_coauthor_for_post( $user_id, $post_id ) ) {
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
	 * Add author term
	 */
	public function add_coauthor_term( $coauthor ) {
		if ( is_int( $coauthor ) ) {
			// get user by their id
			$coauthor = get_user_by( 'id', $coauthor );
		}
		
		if ( ! is_object( $coauthor ) ) {
			return;
		}

		if ( ! $this->get_coauthor_term( $coauthor ) ) {
			// term doesn't yet exist
			wp_insert_term( $coauthor->user_login, 'ssl_alp_coauthor' );
		}
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
	 * Check whether the coauthors for this post have changed, to determine whether to trigger a save.
	 */
	public function check_coauthors_have_changed( $post_has_changed, WP_Post $last_revision, WP_Post $post ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// coauthors disabled
			return;
		}

		// skip when autosaving, as custom post data is annoyingly not included in $_POST during autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_has_changed;
		}

		if ( ! array_key_exists( 'coauthors', $_POST ) ) {
			// no coauthors submitted
			return $post_has_changed;
		}

		// coauthors on published post
		$parent_coauthors = $this->get_coauthors( $post );

		// coauthors on current revision
		$current_coauthor_ids = array();

		foreach ( $_POST['coauthors'] as $author_nicename ) {
			$author_nicename = sanitize_text_field( $author_nicename );

			// get author (returns false if author doesn't exist)
			$author = get_user_by( 'login', $author_nicename );

			if ( $author ) {
				$current_coauthor_ids[] = $author->ID;
			}
		}

		// get parent coauthor ids
		$parent_coauthor_ids = wp_list_pluck( $parent_coauthors, 'id' );

		// check if coauthors have changed
		// a change in order of existing authors will trigger post change due to !==
		if ( $parent_coauthor_ids !== $current_coauthor_ids ) {
			$post_has_changed = true;
		}

		return $post_has_changed;
	}

	public function get_coauthors( $post = null ) {	
		$post = get_post( $post );
	
		if ( is_null( $post ) ) {
			// no post
			return;
		}
	
		// empty coauthors list
		$coauthors = array();
	
		// get terms this post's terms
		$coauthor_terms = $this->get_coauthor_terms_for_post( $post );
	
		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			// this post has coauthors
			foreach ( $coauthor_terms as $coauthor_term ) {
				$post_author = get_user_by( 'login', $coauthor_term->name );
				
				// in case the user has been deleted while plugin was deactivated
				if ( ! empty( $post_author ) ) {
					$coauthors[] = $post_author;
				}
			}
		} else {
			// there aren't coauthors, so get the post's only author
			$post_author = get_userdata( $post->post_author );
	
			if ( ! empty( $post_author ) ) {
				$coauthors[] = $post_author;
			}
		}
	
		return $coauthors;
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
			if ( $user == $coauthor->user_login || $user == $coauthor->linked_account ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Override default `count_many_users_posts` if coauthors are enabled
	 */
	public function count_many_users_posts( $user_ids ) {
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
			$counts[$user_id] = $this->filter_count_user_posts( 0, $user_id );
		}

		return $counts;
	}

	/**
	 * Filter display of coauthor terms on e.g. admin post list
	 */
	public function filter_coauthor_term_display( $value, $term_id, $context ) {
		if ( ! get_option( 'ssl_alp_multiple_authors' ) ) {
			// don't modify
			return $value;
		} elseif ( 'display' !== $context ) {
			// don't change non-display contexts
			return $value;
		}

		$term = get_term_by( 'id', $term_id, 'ssl_alp_coauthor' );

		// the term name is the user's login
		$user = get_user_by( 'login', $term->name );

		if ( is_null( $user ) ) {
			// fall back to default
			return $value;
		}

		return $user->display_name;
	}
}

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
}
