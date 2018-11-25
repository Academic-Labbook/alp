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
	 * Whether to add the TeX rendering scripts to the page
	 */
	public $add_tex_scripts = false;

	/**
	 * Register the stylesheets.
	 */
	public function enqueue_styles() {
		$css_url = esc_url( $this->get_css_url() );

		wp_enqueue_style( 'ssl-alp-katex', $css_url, array(), SSL_ALP_KATEX_VERSION );
	}

	public function enqueue_admin_scripts() {
		$screen = get_current_screen();
		
		$setting_menu_slug = 'settings_page_' . SSL_ALP_NETWORK_SETTINGS_MENU_SLUG . '-network';
		
		if ( $setting_menu_slug === $screen->id ) {
			wp_enqueue_script( 'ssl-alp-tex-settings-js', SSL_ALP_BASE_URL . 'js/admin-tex.js', array( 'jquery' ), $this->get_version(), true );
		}
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
			'ssl_alp_katex_css_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_CSS_URL
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

		// enqueue block script
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'register_block' );
	}

	/**
	 * Register TeX block.
	 */
	public function register_block() {
		global $ssl_alp;

		if ( ! get_option( "ssl_alp_enable_tex" ) ) {
			return;
		}

		// enqueue block editor plugin script
		wp_enqueue_script(
			'ssl-alp-tex-block-js',
			SSL_ALP_BASE_URL . 'js/blocks/tex/index.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
			$ssl_alp->get_version()
		);
	}

    /**
	 * Add TeX shortcodes to editor
	 */
	public function add_tex_shortcode() {
        if ( ! get_option( 'ssl_alp_enable_tex' ) ) {
            return;
        }

		add_shortcode( 'tex', array( $this, 'tex_shortcode_hook' ) );
	}

	public function tex_shortcode_hook( $atts, $content ) {
		$this->add_tex_scripts = true;

		// add optional "display" attribute, to allow display in block form instead of inline
		$shortcode_atts = shortcode_atts(
			array(
				'display' => 'inline',
			),
			$atts
		);

		// default span classes
		$classes = array( 'ssl-alp-katex-equation' );

		$display_block = false;

		if ( $shortcode_atts['display'] === 'block' ) {
			// render as block
			$classes[] = "katex-display";
			$display_block = true;
		}

		return sprintf(
			'<span class="%1$s" data-display="%2$s">%3$s</span>',
			implode( ' ', $classes ),
			$display_block ? "true" : "false",
			htmlspecialchars( html_entity_decode( $content ) )
		);
	}

	public function exempt_texturize( $shortcodes ) {
		// add tex shortcode to exemption list
		$shortcodes[] = 'tex';

		return $shortcodes;
	}

	public function enqueue_tex_scripts() {
		if ( ! $this->add_tex_scripts ) {
			// don't load script
			return;
		}

		// JavaScript and CSS URLs
		$js_url = esc_url( $this->get_js_url() );

		// enqueue scripts
		wp_enqueue_script( 'ssl-alp-katex', $js_url, array(), SSL_ALP_KATEX_VERSION );
		wp_enqueue_script( 'ssl-alp-katex-render', SSL_ALP_BASE_URL . 'js/katex.js', array(), SSL_ALP_KATEX_VERSION );
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
