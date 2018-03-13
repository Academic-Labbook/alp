<?php

/**
 * Literature reference functionality
 */
class SSL_ALP_References extends SSL_ALP_Module {
	/**
	 * Supported post types for reference extraction/display
	 */
	protected $supported_reference_post_types = array(
		'post',
		'page'
	);

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
			'ssl_alp_journal_reference_settings',
			__( 'Journal references', 'ssl-alp' ),
			array( $this, 'journal_reference_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function journal_reference_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/journal-reference-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// extract references from saved posts
		$loader->add_action( 'init', $this, 'create_reference_taxonomy' );
		$loader->add_action( 'save_post', $this, 'extract_references', 10, 2 );

        // DOI shortcode
		$loader->add_action( 'init', $this, 'add_doi_shortcodes' );
		$loader->add_filter( 'strip_shortcodes_tagnames', $this, 'prevent_doi_excerpt_strip' );

		// arXiv shortcode
		$loader->add_action( 'init', $this, 'add_arxiv_shortcodes' );
		$loader->add_filter( 'strip_shortcodes_tagnames', $this, 'prevent_arxiv_excerpt_strip' );
	}

	public function create_reference_taxonomy() {
		// create internal reference taxonomy
		register_taxonomy(
			'ssl_alp_post_internal_reference',
			$this->supported_reference_post_types,
			array(
				'hierarchical'	=> false,
				'rewrite' 		=> false,
				'meta_box_cb'	=> false,
				'public'		=> false,
				'labels' 		=> array(
					'name'                       => _x( 'Internal References', 'internal reference taxonomy general name', 'ssl-alp' ),
					'singular_name'              => _x( 'Internal Reference', 'internal reference taxonomy singular name', 'ssl-alp' )
				)
			)
		);

		// create external reference taxonomy
		register_taxonomy(
			'ssl_alp_post_external_reference',
			$this->supported_reference_post_types,
			array(
				'hierarchical'	=> false,
				'rewrite' 		=> false,
				'meta_box_cb'	=> false,
				'public'		=> false,
				'labels' 		=> array(
					'name'                       => _x( 'External References', 'external reference taxonomy general name', 'ssl-alp' ),
					'singular_name'              => _x( 'External Reference', 'external reference taxonomy singular name', 'ssl-alp' )
				)
			)
		);

		// add post type support
		foreach ( $this->supported_reference_post_types as $post_type ) {
			add_post_type_support( $post_type, 'ssl-alp-references' );
		}
	}

	/**
	 * Extract references from updated/created posts and insert them into the
	 * term database for display under the post
	 */
	public function extract_references( $post_id, $post ) {
		if ( ! post_type_supports( $post->post_type, 'ssl-alp-references' ) ) {
			// post type not supported
			return;
		}

		// parse post content
		// this is required in order for shortcodes to be processed into URLs
		$content = do_shortcode( $post->post_content );

		// find URLs in post content
		$urls = wp_extract_urls( $content );

		// terms to set
		$internal_terms = array();
		$external_terms = array();

		foreach ( $urls as $url ) {
			// attempt to find the post ID for the URL
			$reference_id = url_to_postid( $url );

			if ( $reference_id === 0) {
				// not an internal URL
				// escape
				$url = esc_url( $url );

				// don't use wp_hash here because it salts the data
				$term_name = "reference-to-url-" . md5( $url );
				// add term name to list that will be associated with the post
				$external_terms[$term_name] = $url;
			} else {
				$referenced_post = get_post( $reference_id );

				if ( is_null( $referenced_post ) ) {
					// invalid post - skip
					continue;
				}

				/*
				 * create referenced-to relationship
				 */

				// create "reference to" term
				$ref_to_post_term_name = sprintf("reference-to-post-id-%d", $reference_id);

				// add term name to list that will be associated with the post
				$internal_terms[$ref_to_post_term_name] = $reference_id;
			}
		}

		// update post's reference taxonomy terms (replaces any existing terms)
		wp_set_post_terms( $post->ID, array_keys( $external_terms ), 'ssl_alp_post_external_reference' );
		wp_set_post_terms( $post->ID, array_keys( $internal_terms ), 'ssl_alp_post_internal_reference' );

		// set external term metadata
		foreach ( $external_terms as $term_name => $url ) {
			// get term
			$term = get_term_by( 'name', $term_name, 'ssl_alp_post_external_reference' );
			// add term metadata
			update_term_meta( $term->term_id, "reference-to-url", $url );
		}

		// set internal term metadata
		foreach ( $internal_terms as $term_name => $post_id ) {
			// get term
			$term = get_term_by( 'name', $term_name, 'ssl_alp_post_internal_reference' );
			// add term metadata
			update_term_meta( $term->term_id, "reference-to-post-id", $post_id );
		}
	}

    public function add_doi_shortcodes() {
        if ( ! get_option( 'ssl_alp_doi_shortcode' ) ) {
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

	public function prevent_doi_excerpt_strip( $tags_to_remove ) {
		return $this->parent->core->prevent_excerpt_strip( 'doi', $tags_to_remove );
	}

	public function add_arxiv_shortcodes() {
        if ( ! get_option( 'ssl_alp_arxiv_shortcode' ) ) {
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

	public function prevent_arxiv_excerpt_strip( $tags_to_remove ) {
		return $this->parent->core->prevent_excerpt_strip( 'arxiv', $tags_to_remove );
	}
}
