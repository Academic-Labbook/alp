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
		$css_url = esc_url( get_option( 'ssl_alp_katex_css_url' ) );

		wp_enqueue_style( 'ssl-alp-katex', $css_url, array(), SSL_ALP_KATEX_VERSION );
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
			'ssl_alp_katex_js_url',
			array(
				'type'				=>	'string',
				'sanitize_callback'	=>	'esc_url_raw',
				'default'			=>	SSL_ALP_DEFAULT_KATEX_JS_URL
			)
		);
	
        register_setting(
			'ssl-alp-admin-options',
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
			'ssl_alp_enable_mathematics_settings',
			__( 'Mathematics display', 'ssl-alp' ),
			array( $this, 'enable_tex_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function enable_tex_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/enable-tex-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// tex shortcode
		$loader->add_action( 'init', $this, 'add_tex_shortcode' );

		// add JavaScript
		$loader->add_action( 'wp_footer', $this, 'enqueue_tex_scripts' );

		// prevent processing of contents inside [tex][/tex] tags
		$loader->add_filter( 'no_texturize_shortcodes', $this, 'exempt_texturize' );
	}

    /**
	 * Add TeX shortcodes to editor
	 */
	public function add_tex_shortcode() {
        if ( ! get_option( 'ssl_alp_tex_enabled' ) ) {
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
			($display_block) ? "true" : "false",
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
		$js_url = esc_url( get_option( 'ssl_alp_katex_js_url' ) );

		// enqueue scripts
		wp_enqueue_script( 'ssl-alp-katex', $js_url, array(), SSL_ALP_KATEX_VERSION );
		wp_enqueue_script( 'ssl-alp-katex-render', SSL_ALP_BASE_URL . 'js/katex.js', array(), SSL_ALP_KATEX_VERSION );
	}
}
