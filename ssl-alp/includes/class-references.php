<?php

/**
 * Literature reference functionality
 */
class SSL_ALP_References extends SSL_ALP_Module {
	/**
	 * Supported post types for reference extraction/display, and whether to display their date
	 * on the revisions list
	 */
	protected $supported_reference_post_types = array(
		'post'	=>	true,
		'page'	=>	false
	);

	/**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_enable_crossreferences',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

        register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_doi_shortcode',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_arxiv_shortcode',
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
         * Post references settings
         */

        add_settings_field(
			'ssl_alp_reference_settings',
			__( 'References', 'ssl-alp' ),
			array( $this, 'reference_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function reference_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/reference-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// extract references from saved posts
		$loader->add_action( 'init', $this, 'create_crossreference_taxonomy' );
		$loader->add_action( 'save_post', $this, 'extract_crossreferences', 10, 2 );

        // DOI shortcode
		$loader->add_action( 'init', $this, 'add_doi_shortcode' );

		// arXiv shortcode
		$loader->add_action( 'init', $this, 'add_arxiv_shortcode' );
	}

	public function create_crossreference_taxonomy() {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// cross-references are disabled
			return;
		}

		// create internal reference taxonomy
		register_taxonomy(
			'ssl_alp_crossreference',
			array_keys( $this->supported_reference_post_types ),
			array(
				'hierarchical'	=> false,
				'rewrite' 		=> false,
				'meta_box_cb'	=> false,
				'public'		=> false,
				'labels' 		=> array(
					'name'                       => _x( 'Cross-references', 'cross-reference taxonomy general name', 'ssl-alp' ),
					'singular_name'              => _x( 'Cross-reference', 'cross-reference taxonomy singular name', 'ssl-alp' )
				)
			)
		);

		// add post type support
		foreach ( array_keys( $this->supported_reference_post_types ) as $post_type ) {
			add_post_type_support( $post_type, 'ssl-alp-crossreferences' );
		}
	}

	/**
	 * Extract references from updated/created posts and insert them into the
	 * term database for display under the post
	 */
	public function extract_crossreferences( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// cross-references are disabled
			return;
		} elseif ( ! post_type_supports( $post->post_type, 'ssl-alp-crossreferences' ) ) {
			// post type not supported
			return;
		}

		// parse post content
		// this is required in order for shortcodes to be processed into URLs
		$content = do_shortcode( $post->post_content );

		// find URLs in post content
		$urls = wp_extract_urls( $content );

		// terms to set
		$terms = array();

		foreach ( $urls as $url ) {
			// attempt to find the post ID for the URL
			$reference_id = url_to_postid( $url );

			if ( $reference_id === 0 ) {
				// invalid URL
				continue;
			}

			$referenced_post = get_post( $reference_id );

			if ( is_null( $referenced_post ) ) {
				// invalid post - skip
				continue;
			} elseif ( ! $this->is_supported( $referenced_post ) ) {
				// not supported for referencing
				continue;
			}

			/*
			 * create referenced-to relationship
			 */

			// create "reference to" term
			$ref_to_post_term_name = sprintf("reference-to-post-id-%d", $reference_id);

			// add term name to list that will be associated with the post
			$terms[$ref_to_post_term_name] = $reference_id;
		}

		// update post's reference taxonomy terms (replaces any existing terms)
		wp_set_post_terms( $post->ID, array_keys( $terms ), 'ssl_alp_crossreference' );

		// set internal term metadata
		foreach ( $terms as $term_name => $post_id ) {
			// get term
			$term = get_term_by( 'name', $term_name, 'ssl_alp_crossreference' );
			// add term metadata
			update_term_meta( $term->term_id, "reference-to-post-id", $post_id );
		}
	}

	/**
	 * Check if specified post is supported with references
	 */
	public function is_supported( $post ) {
		$post = get_post( $post );

		return array_key_exists( $post->post_type, $this->supported_reference_post_types );
	}

	/**
	 * Check whether the specified post should have its publication date shown in cross-references
	 */
	public function show_date( $post ) {
		$post = get_post( $post );

		if ( ! $this->is_supported( $post ) ) {
			// post type is not supported
			return null;
		}

		// values of supported_reference_post_types specifies whether to show date
		return (bool) $this->supported_reference_post_types[$post->post_type];
	}

    public function add_doi_shortcode() {
        if ( ! get_option( 'ssl_alp_doi_shortcode' ) ) {
			// DOI shortcodes disabled
            return;
        }

		add_shortcode( 'doi', array( $this, 'doi_shortcode_hook' ) );
	}

	public function doi_shortcode_hook( $atts, $content ) {
		$content = sanitize_text_field( $content );

		// DOI URL
		$url = SSL_ALP_DOI_BASE_URL . $content;

		return '<a href="' . $url . '">doi:' . $content . '</a>';
	}

	public function add_arxiv_shortcode() {
        if ( ! get_option( 'ssl_alp_arxiv_shortcode' ) ) {
			// arXiv shortcodes disabled
            return;
        }

		add_shortcode( 'arxiv', array( $this, 'arxiv_shortcode_hook' ) );
	}

	public function arxiv_shortcode_hook( $atts, $content ) {
		$content = sanitize_text_field( $content );

		// arXiv URL
		$url = SSL_ALP_ARXIV_BASE_URL . $content;

		return '<a href="' . $url . '">arXiv:' . $content . '</a>';
	}

	/**
	 * Re-scan supported post types to update references
	 */
	public function rebuild_references() {
		if ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
			// cross-references are disabled
			return;
		}

		foreach ( array_keys( $this->supported_reference_post_types ) as $post_type ) {
			$posts = get_posts(
				array( 
					'post_type' 		=> $post_type,
					'post_status'		=> 'published',
					'posts_per_page'	=> -1 // needed to get all
				)
			);

			foreach ( $posts as $post ) {
				$this->extract_crossreferences( $post->ID, $post );
			}
		}
	}
}
