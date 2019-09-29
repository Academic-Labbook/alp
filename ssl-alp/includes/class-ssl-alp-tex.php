<?php
/**
 * Mathematical rendering tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
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
		if ( get_option( 'ssl_alp_enable_tex' ) ) {
			wp_enqueue_style( 'ssl-alp-katex' );
			wp_enqueue_style( 'ssl-alp-katex-contrib-copy' );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		if ( get_option( 'ssl_alp_enable_tex' ) ) {
			wp_enqueue_script( 'ssl-alp-katex' );
			wp_enqueue_script( 'ssl-alp-katex-contrib-copy' );
			wp_enqueue_script( 'ssl-alp-katex-render' );
		}
	}

	/**
	 * Enqueue styles in the admin header.
	 */
	public function enqueue_admin_styles() {
		if ( get_option( 'ssl_alp_enable_tex' ) ) {
			wp_enqueue_style( 'ssl-alp-katex' );
		}
	}

	/**
	 * Enqueue scripts in the admin header.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		$setting_menu_slug = 'settings_page_' . SSL_ALP_NETWORK_SETTINGS_MENU_SLUG . '-network';

		if ( $setting_menu_slug === $screen->id ) {
			wp_enqueue_script(
				'ssl-alp-tex-settings-js',
				SSL_ALP_BASE_URL . 'js/admin-network-settings-tex.js',
				array( 'jquery' ),
				$this->get_version(),
				true
			);
		}

		if ( get_option( 'ssl_alp_enable_tex' ) ) {
			wp_enqueue_script( 'ssl-alp-katex' );
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
				'type'    => 'boolean',
				'default' => true,
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_use_custom_urls',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_js_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_copy_js_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_css_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_katex_copy_css_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
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

		// Add mathematics settings section to network admin if available.
		add_settings_field(
			'ssl_alp_mathematics_settings',
			__( 'Mathematics', 'ssl-alp' ),
			array( $this, 'tex_scripts_settings_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_script_settings_section'
		);
	}

	/**
	 * TeX scripts settings partial.
	 */
	public function tex_scripts_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/tex-scripts-settings-display.php';
	}

	/**
	 * TeX display settings partial.
	 */
	public function tex_display_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/tex-display-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Add JavaScript.
		$loader->add_action( 'init', $this, 'register_tex_scripts' );
	}

	/**
	 * Register TeX scripts.
	 */
	public function register_tex_scripts() {
		if ( ! get_option( 'ssl_alp_enable_tex' ) ) {
			return;
		}

		// JavaScript and CSS URLs.
		$katex_js_url       = $this->get_katex_js_url();
		$katex_copy_js_url  = $this->get_katex_copy_js_url();
		$katex_css_url      = $this->get_katex_css_url();
		$katex_copy_css_url = $this->get_katex_copy_css_url();

		wp_register_style(
			'ssl-alp-tex-block',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/style.css' ),
			array(),
			$this->get_version()
		);

		wp_register_style(
			'ssl-alp-tex-block-editor',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/editor.css' ),
			array(),
			$this->get_version()
		);

		wp_register_script(
			'ssl-alp-katex',
			esc_url( $katex_js_url ),
			array(),
			SSL_ALP_KATEX_VERSION,
			true
		);

		wp_register_style(
			'ssl-alp-katex',
			esc_url( $katex_css_url ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		// KaTeX copy support.
		wp_register_script(
			'ssl-alp-katex-contrib-copy',
			esc_url( $katex_copy_js_url ),
			array(),
			SSL_ALP_KATEX_VERSION,
			true
		);

		wp_register_style(
			'ssl-alp-katex-contrib-copy',
			esc_url( $katex_copy_css_url ),
			array(),
			SSL_ALP_KATEX_VERSION
		);

		wp_register_script(
			'ssl-alp-katex-render',
			esc_url( SSL_ALP_BASE_URL . 'js/katex.js' ),
			array(),
			$this->get_version(),
			true
		);

		wp_register_script(
			'ssl-alp-katex-inline',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/inline.js' ),
			array(
				'wp-element',
				'wp-i18n',
				'wp-editor',
			),
			$this->get_version(),
			true
		);

		wp_register_script(
			'ssl-alp-tex-block-editor',
			esc_url( SSL_ALP_BASE_URL . 'blocks/tex/block.js' ),
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
			),
			$this->get_version(),
			true
		);

		register_block_type(
			'ssl-alp/tex',
			array(
				'editor_script' => 'ssl-alp-tex-block-editor',
				'editor_style'  => 'ssl-alp-tex-block-editor',
				'style'         => 'ssl-alp-tex-block',
			)
		);
	}

	/**
	 * Get KaTeX JavaScript library URL.
	 */
	protected function get_katex_js_url() {
		if ( get_site_option( 'ssl_alp_katex_use_custom_urls' ) ) {
			// Use custom URL.
			$url = get_site_option( 'ssl_alp_katex_js_url' );
		} else {
			// Use default URL.
			$url = SSL_ALP_BASE_URL . 'vendor/katex/katex.min.js';
		}

		return $url;
	}

	/**
	 * Get KaTeX Copy JavaScript library URL.
	 */
	protected function get_katex_copy_js_url() {
		if ( get_site_option( 'ssl_alp_katex_use_custom_urls' ) ) {
			// Use custom URL.
			$url = get_site_option( 'ssl_alp_katex_copy_js_url' );
		} else {
			// Use default URL.
			$url = SSL_ALP_BASE_URL . 'vendor/katex/contrib/copy-tex.min.js';
		}

		return $url;
	}

	/**
	 * Get KaTeX CSS URL.
	 */
	protected function get_katex_css_url() {
		if ( get_site_option( 'ssl_alp_katex_use_custom_urls' ) ) {
			// Use custom URL.
			$url = get_site_option( 'ssl_alp_katex_css_url' );
		} else {
			// Use default URL.
			$url = SSL_ALP_BASE_URL . 'vendor/katex/katex.min.css';
		}

		return $url;
	}

	/**
	 * Get KaTeX Copy CSS URL.
	 */
	protected function get_katex_copy_css_url() {
		if ( get_site_option( 'ssl_alp_katex_use_custom_urls' ) ) {
			// Use custom URL.
			$url = get_site_option( 'ssl_alp_katex_copy_css_url' );
		} else {
			// Use default URL.
			$url = SSL_ALP_BASE_URL . 'vendor/katex/contrib/copy-tex.min.css';
		}

		return $url;
	}
}
