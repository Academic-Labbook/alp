<?php
/**
 * Cross-reference tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Literature reference functionality.
 */
class SSL_ALP_References extends SSL_ALP_Module {
	/**
	 * Supported post types for reference extraction/display, and whether to display their date
	 * on the revisions list.
	 *
	 * @var array
	 */
	protected $supported_reference_post_types = array(
		'post'              => true,
		'page'              => false,
		'ssl-alp-inventory' => false,
	);

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Enable cross-references on supported post types.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_crossreferences',
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
		 * Post references settings
		 */
		add_settings_field(
			'ssl_alp_reference_settings',
			__( 'References', 'ssl-alp' ),
			array( $this, 'reference_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
	}

	/**
	 * Reference settings partial.
	 */
	public function reference_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/reference-settings-display.php';
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Create cross-reference taxonomy. Priority makes it get called after settings are registered.
		$loader->add_action( 'init', $this, 'register_crossreference_taxonomy', 20 );

		// Extract references from saved posts.
		$loader->add_action( 'save_post', $this, 'extract_crossreferences', 10, 2 );
	}

	/**
	 * Register cross-reference taxonomy.
	 */
	public function register_crossreference_taxonomy() {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// Cross-references are disabled.
			return;
		}

		register_taxonomy(
			'ssl_alp_crossreference',
			array_keys( $this->supported_reference_post_types ),
			array(
				'hierarchical' => false,
				'rewrite'      => false,
				'meta_box_cb'  => false,
				'public'       => false,
				'labels'       => array(
					'name'          => _x( 'Cross-references', 'cross-reference taxonomy general name', 'ssl-alp' ),
					'singular_name' => _x( 'Cross-reference', 'cross-reference taxonomy singular name', 'ssl-alp' ),
				),
			)
		);
	}

	/**
	 * Extract references from updated/created posts and insert them into the term database for
	 * display under the post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function extract_crossreferences( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// Cross-references are disabled.
			return;
		} elseif ( ! $this->is_supported( $post ) ) {
			// Post type not supported.
			return;
		}

		// Find URLs in post content.
		$urls = wp_extract_urls( $post->post_content );

		// Terms to set.
		$terms = array();

		foreach ( $urls as $url ) {
			// Attempt to find the post ID for the URL.
			$reference_id = url_to_postid( $url );

			$referenced_post = get_post( $reference_id );

			if ( is_null( $referenced_post ) ) {
				// Invalid post - skip.
				continue;
			} elseif ( ! $this->is_supported( $referenced_post ) ) {
				// Target not supported for referencing.
				continue;
			} elseif ( $referenced_post->ID === $post_id ) {
				// Self-reference.
				continue;
			}

			/*
			 * Create referenced-to relationship.
			 */

			// Create "reference to" term.
			$ref_to_post_term_name = sprintf( 'reference-to-post-id-%d', $referenced_post->ID );

			// Add term name to list that will be associated with the post.
			$terms[ $ref_to_post_term_name ] = $referenced_post->ID;
		}

		// Update post's reference taxonomy terms (replaces any existing terms).
		wp_set_post_terms( $post->ID, array_keys( $terms ), 'ssl_alp_crossreference' );

		// Set internal term metadata.
		foreach ( $terms as $term_name => $referenced_post_id ) {
			// Get term.
			$term = get_term_by( 'name', $term_name, 'ssl_alp_crossreference' );

			// Add term metadata.
			update_term_meta( $term->term_id, 'reference-to-post-id', $referenced_post_id );
		}
	}

	/**
	 * Check if specified post is supported with references.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public function is_supported( $post ) {
		$post = get_post( $post );

		return array_key_exists( $post->post_type, $this->supported_reference_post_types );
	}

	/**
	 * Check if the specified post should have its publication date shown in cross-references.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public function show_date( $post ) {
		$post = get_post( $post );

		if ( is_null( $post ) || ! $this->is_supported( $post ) ) {
			// Post is invalid or post type is not supported.
			return;
		}

		// Values of supported_reference_post_types specifies whether to show date.
		return (bool) $this->supported_reference_post_types[ $post->post_type ];
	}

	/**
	 * Re-scan supported post types to update references.
	 */
	public function rebuild_references() {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// Cross-references are disabled.
			return;
		}

		// Allow unlimited execution time.
		ini_set( 'max_execution_time', 0 );

		foreach ( array_keys( $this->supported_reference_post_types ) as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'published',
					'nopaging'    => true,
				)
			);

			foreach ( $posts as $post ) {
				$this->extract_crossreferences( $post->ID, $post );
			}
		}
	}

	/**
	 * Get posts that are referenced by the specified post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @return array|null Referenced posts, or null if invalid post specified.
	 *
	 * @global $ssl_alp
	 */
	public function get_reference_to_posts( $post = null ) {
		global $ssl_alp;

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		$terms = get_the_terms( $post, 'ssl_alp_crossreference' );

		$posts = array();

		if ( ! is_array( $terms ) ) {
			// No terms to get posts from.
			return $posts;
		}

		// Get the posts associated with the terms.
		foreach ( $terms as $term ) {
			// Get referenced post ID.
			$referenced_post_id = get_term_meta(
				$term->term_id,
				'reference-to-post-id',
				'ssl_alp_crossreference'
			);

			$referenced_post = get_post( $referenced_post_id );

			if ( is_null( $referenced_post ) ) {
				continue;
			}

			if ( 'publish' !== $referenced_post->post_status ) {
				// Ignore unpublished posts.
				continue;
			}

			if ( ! post_type_exists( $referenced_post->post_type ) ) {
				// The referenced post is some type that doesn't exist any more.
				continue;
			}

			// Check user permission to view.
			if ( ! $ssl_alp->core->current_user_can_read_post( $referenced_post ) ) {
				continue;
			}

			$posts[] = $referenced_post;
		}

		return $posts;
	}

	/**
	 * Get posts that reference the specified post.
	 *
	 * @param int|WP_Post|null $post Post ID or post object. Defaults to global $post.
	 * @return array|null Referencing posts, or null if invalid post specified.
	 * @global $wpdb
	 * @global $ssl_alp;
	 */
	public function get_reference_from_posts( $post = null ) {
		global $wpdb, $ssl_alp;

		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		// Reference posts cache key.
		$cache_key = 'ssl-alp-reference-from_posts-' . $post->ID;

		$posts = wp_cache_get( $cache_key );

		if ( false === $posts ) {
			// Query for terms that reference this post.
			$object_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT posts.ID
					FROM {$wpdb->termmeta} AS termmeta
					INNER JOIN {$wpdb->term_relationships} AS term_relationships
						ON termmeta.term_id = term_relationships.term_taxonomy_id
					INNER JOIN {$wpdb->posts} AS posts
						ON term_relationships.object_id = posts.ID
					INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy
						ON termmeta.term_id = term_taxonomy.term_id
					WHERE
						termmeta.meta_key = %s
						AND termmeta.meta_value = %d
						AND term_taxonomy.taxonomy = %s
					ORDER BY
						posts.post_date DESC
					",
					'reference-to-post-id',
					$post->ID,
					'ssl_alp_crossreference'
				)
			);

			$posts = array();

			// Get the posts associated with the term IDs.
			foreach ( $object_ids as $post_id ) {
				$referenced_post = get_post( $post_id );

				if ( is_null( $referenced_post ) ) {
					continue;
				}

				if ( 'publish' !== $referenced_post->post_status ) {
					// Ignore unpublished posts.
					continue;
				}

				if ( ! post_type_exists( $referenced_post->post_type ) ) {
					// The referenced post is some type that doesn't exist any more.
					continue;
				}

				// Check user permission to view.
				if ( ! $ssl_alp->core->current_user_can_read_post( $referenced_post ) ) {
					continue;
				}

				$posts[] = $referenced_post;
			}

			wp_cache_set( $cache_key, $posts );
		}

		return $posts;
	}
}
