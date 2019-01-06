<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * TeX markup functionality
 */
class SSL_ALP_Tex extends SSL_ALP_Module {
	/**
	 * Register the stylesheets.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'ssl-alp-katex' );
		wp_enqueue_style( 'ssl-alp-katex-contrib-copy' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'ssl-alp-katex' );
		wp_enqueue_script( 'ssl-alp-katex-contrib-copy' );
		wp_enqueue_script( 'ssl-alp-katex-render' );
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style( 'ssl-alp-katex' );
	}

	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		$setting_menu_slug = 'settings_page_' . SSL_ALP_NETWORK_SETTINGS_MENU_SLUG . '-network';

		if ( $setting_menu_slug === $screen->id ) {
			wp_enqueue_script( 'ssl-alp-tex-settings-js', SSL_ALP_BASE_URL . 'js/admin-tex.js', array( 'jquery' ), $this->get_version(), true );
		}

		wp_enqueue_script( 'ssl-alp-katex' );
		// disabled until it can be polished
		//wp_enqueue_script( 'ssl-alp-katex-inline' );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_tex',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

        register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_tex_use_custom_urls',
			array(
				'type'		=>	'boolean',
				'default'	=>	false
			)
		);

        register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_js_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_JS_URL
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_copy_js_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_COPY_JS_URL
			)
		);

        register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_css_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_CSS_URL
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_copy_css_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_COPY_CSS_URL
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
			'ssl_alp_mathematics_display_settings',
			__( 'Mathematics display', 'ssl-alp' ),
			array( $this, 'tex_display_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);

		// add mathematics settings section to network admin if available
        add_settings_field(
			'ssl_alp_mathematics_settings',
			__( 'Mathematics', 'ssl-alp' ),
			array( $this, 'tex_scripts_settings_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_script_settings_section'
		);
    }

    public function tex_scripts_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/tex-scripts-settings-display.php';
	}

    public function tex_display_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/tex-display-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// add JavaScript
		$loader->add_action( 'init', $this, 'register_tex_scripts' );
	}

	public function register_tex_scripts() {
		// JavaScript and CSS URLs
		$js_url = $this->get_js_url();
		$css_url = $this->get_css_url();

		wp_register_style(
			'tex-block',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/style.css' ),
			array(),
			$this->get_version()
		);

		wp_register_style(
			'tex-block-editor',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/editor.css' ),
			array(),
			$this->get_version()
		);

		wp_register_script(
			'ssl-alp-katex',
			esc_url( $js_url ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		wp_register_style(
			'ssl-alp-katex',
			esc_url( $css_url ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		// KaTeX copy support
		wp_register_script(
			'ssl-alp-katex-contrib-copy',
			esc_url( SSL_ALP_DEFAULT_KATEX_COPY_JS_URL ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		wp_register_style(
			'ssl-alp-katex-contrib-copy',
			esc_url( SSL_ALP_DEFAULT_KATEX_COPY_CSS_URL ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		wp_register_script(
			'ssl-alp-katex-render',
			esc_url( SSL_ALP_BASE_URL . 'js/katex.js' ),
			array(),
			$this->get_version()
		);

		wp_register_script(
			'ssl-alp-katex-inline',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/inline.js' ),
			array(
				'wp-element',
				'wp-i18n',
				'wp-editor'
			),
			$this->get_version()
		);

		wp_register_script(
			'tex-block-editor',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/block.js' ),
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element'
			),
			$this->get_version()
		);

		register_block_type( 'ssl-alp/tex', array(
			'editor_script' 	=> 'tex-block-editor',
			'editor_style'  	=> 'tex-block-editor',
			'style'         	=> 'tex-block'
		) );
	}

	/**
	 * Get KaTeX JavaScript library URL
	 */
	protected function get_js_url() {
		if ( get_site_option( 'ssl_alp_tex_use_custom_urls' ) ) {
			// use custom URL
			$url = get_site_option( 'ssl_alp_katex_js_url' );
		} else {
			// use default URL
			$url = SSL_ALP_DEFAULT_KATEX_JS_URL;
		}

		return $url;
	}

	/**
	 * Get KaTeX CSS URL
	 */
	protected function get_css_url() {
		if ( get_site_option( 'ssl_alp_tex_use_custom_urls' ) ) {
			// use custom URL
			$url = get_site_option( 'ssl_alp_katex_css_url' );
		} else {
			// use default URL
			$url = SSL_ALP_DEFAULT_KATEX_CSS_URL;
		}

		return $url;
	}
}
