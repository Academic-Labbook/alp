<?php

/**
 * TeX markup functionality
 */
class SSL_ALP_Tex extends SSL_ALP_Module {
    /**
	 * Whether to add the MathJax script to the page
	 */
	public $add_mathjax_script = false;

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
			'ssl_alp_tex_enabled',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

        register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_mathjax_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_MATHJAX_URL
			)
		);
	}

    /**
     * Register settings fields
     */
    public function register_settings_fields() {
        /**
         * Post mathematics settings
         */

        add_settings_field(
			'ssl_alp_enable_mathematics_settings',
			__( 'Mathematics display', 'ssl-alp' ),
			array( $this, 'enable_tex_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

        /**
         * Post MathJax settings
         */

		add_settings_field(
			'ssl_alp_mathjax_url_settings',
			__( 'MathJax JavaScript URL', 'ssl-alp' ),
			array( $this, 'mathjax_javascript_url_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function enable_tex_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/enable-tex-settings-display.php';
	}

	public function mathjax_javascript_url_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/mathjax-javascript-url-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

        // MathJax shortcodes
		$loader->add_action( 'init', $this, 'add_mathjax_shortcodes' );
		$loader->add_action( 'wp_footer', $this, 'add_mathjax_script' );
	}

    /**
	 * Add MathJax shortcodes to editor
	 */
	public function add_mathjax_shortcodes() {
        if ( ! get_option( 'ssl_alp_tex_enabled' ) ) {
            return;
        }

		add_shortcode( 'tex', array( $this, 'tex_shortcode_hook' ) );
	}

	public function tex_shortcode_hook( $atts, $content ) {
		$this->add_mathjax_script = true;

		// add optional "syntax" attribute, which defaults to "inline", but can also be "block"
		$shortcode_atts = shortcode_atts(
			array(
				'display' => 'inline',
			),
			$atts
		);

		if ( $shortcode_atts['display'] === 'inline' ) {
			return '\(' . $content . '\)';
		} elseif ( $shortcode_atts['display'] === 'block' ) {
			return '\[' . $content . '\]';
		}
	}

	public function add_mathjax_script() {
		if ( ! $this->add_mathjax_script ) {
			// don't load script
			return;
		}

		// MathJax URL and SRI settings
		$mathjax_url = esc_url( get_option( 'ssl_alp_mathjax_url' ) );

		// enqueue script in footer
		wp_enqueue_script( 'ssl-alp-mathjax-script', $mathjax_url, array(), SSL_ALP_MATHJAX_VERSION, true );
	}
}
