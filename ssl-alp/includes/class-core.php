<?php

/**
 * Plugin functionality that doesn't fit anywhere else.
 */
class SSL_ALP_Core extends SSL_ALP_Module {
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
        wp_enqueue_style( 'ssl-alp-admin-css', SSL_ALP_BASE_URL . 'css/admin.css', array(), $this->get_version(), 'all' );
        wp_enqueue_style( 'ssl-alp-public-css', SSL_ALP_BASE_URL . 'css/public.css', array(), $this->get_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
        wp_enqueue_script( 'ssl-alp-public-js', SSL_ALP_BASE_URL . 'js/public.js', array( 'jquery' ), $this->get_version(), false );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
           'ssl-alp-admin-options',
           'ssl_alp_require_login',
           array(
               'type'		=>	'boolean',
               'default'	=>	true
           )
       );

        register_setting(
           'ssl-alp-admin-options',
           'ssl_alp_copyright_text',
           array(
               'type'				=>	'text',
               'sanitize_callback'	=>	'sanitize_text_field',
               'default'			=>	'Institute'
           )
       );

        register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_disable_post_tags',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_disable_post_formats',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_disable_post_excerpts',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_disable_post_trackbacks',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

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
		 * Site access settings
		 */

	 	add_settings_field(
			'ssl_alp_access_settings', // id
			__( 'Access', 'ssl-alp' ), // title
			array( $this, 'access_settings_callback' ), // callback
			'ssl-alp-admin-options', // page
			'ssl_alp_site_settings_section' // section
		);

        /**
         * Site display settings
         */

		add_settings_field(
			'ssl_alp_display_settings',
			__( 'Display', 'ssl-alp' ),
			array( $this, 'display_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_site_settings_section'
		);

        /**
         * Post meta settings field
         */

        add_settings_field(
			'ssl_alp_category_settings', // id
			__( 'Meta', 'ssl-alp' ), // title
			array( $this, 'meta_settings_callback' ), // callback
			'ssl-alp-admin-options', // page
			'ssl_alp_post_settings_section' // section
		);

        /**
         * Post multiple author settings field (TODO: move to module)
         */

        add_settings_field(
			'ssl_alp_author_settings',
			__( 'Authors', 'ssl-alp' ),
			array( $this, 'author_settings_callback' ),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);
    }

    public function access_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/access-settings-display.php';
	}

	public function display_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/display-settings-display.php';
	}

    public function meta_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/meta-settings-display.php';
	}

    public function author_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/author-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );

        // private site
        $loader->add_action( 'get_header', $this, 'check_logged_in');

        // post meta stuff
        $loader->add_action( 'init', $this, 'unregister_tags' );
        $loader->add_action( 'init', $this, 'disable_post_formats' );
        $loader->add_action( 'init', $this, 'disable_post_excerpts' );
        $loader->add_action( 'init', $this, 'disable_post_trackbacks' );
	}

    /**
     * Disable tags on posts.
     */
    public function unregister_tags() {
        if ( ! get_option( 'ssl_alp_disable_post_tags' ) ) {
            return;
        }

        unregister_taxonomy_for_object_type( 'post_tag', 'post' );
    }

    /**
     * Disable post formats
     */
    public function disable_post_formats() {
        if ( ! get_option( 'ssl_alp_disable_post_formats' ) ) {
            return;
        }

        remove_post_type_support( 'post', 'post-formats' );
    }

    /**
     * Disable post excerpts
     */
    public function disable_post_excerpts() {
        if ( ! get_option( 'ssl_alp_disable_post_excerpts' ) ) {
            return;
        }

        remove_post_type_support( 'post', 'excerpt' );
    }

    /**
     * Disable post trackbacks
     */
    public function disable_post_trackbacks() {
        if ( ! get_option( 'ssl_alp_disable_post_trackbacks' ) ) {
            return;
        }

        remove_post_type_support( 'post', 'trackbacks' );
    }

    /**
     * Check user is logged in
     */
    public function check_logged_in() {
        if ( ! get_option( 'ssl_alp_require_login', true ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            auth_redirect();
        }
    }
}
