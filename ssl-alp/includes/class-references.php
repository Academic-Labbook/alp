<?php

/**
 * Literature reference functionality
 */
class SSL_ALP_References extends SSL_ALP_Module {
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

        // DOI shortcode
		$loader->add_action( 'init', $this, 'add_doi_shortcodes' );

		// arXiv shortcode
		$loader->add_action( 'init', $this, 'add_arxiv_shortcodes' );
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
}
