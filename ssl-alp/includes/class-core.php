<?php

/**
 * Plugin functionality that doesn't fit anywhere else.
 */
class SSL_ALP_Core extends SSL_ALP_Module {
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
        wp_enqueue_style( 'ssl-alp-public-css', SSL_ALP_BASE_URL . 'css/public.css', array(), $this->get_version(), 'all' );
	}

	public function enqueue_admin_styles() {
        wp_enqueue_style( 'ssl-alp-admin-css', SSL_ALP_BASE_URL . 'css/admin.css', array(), $this->get_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
        wp_enqueue_script( 'ssl-alp-public-js', SSL_ALP_BASE_URL . 'js/public.js', array( 'jquery' ), $this->get_version(), false );
	}

	public function enqueue_admin_scripts() {
        
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
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
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/access-settings-display.php';
	}

    public function meta_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/meta-settings-display.php';
	}

    public function author_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/author-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );
		
		// remove WordPress link in meta widget
		$loader->add_filter( 'widget_meta_poweredby', $this, 'filter_powered_by' );

		// hide WordPress news and events
		$loader->add_filter( 'wp_dashboard_setup', $this, 'remove_wp_dashboard_metaboxes' );

        // post meta stuff
        $loader->add_action( 'init', $this, 'unregister_tags' );
        $loader->add_action( 'init', $this, 'disable_post_excerpts' );
        $loader->add_action( 'init', $this, 'disable_post_trackbacks' );
	}

	/**
	 * Remove WordPress URL from meta widget
	 */
	public function filter_powered_by( $list_item ) {
		return '';
	}

	public function remove_wp_dashboard_metaboxes() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
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
	 * Prevent WordPress's wp_trim_excerpt() function, which generates a default
	 * excerpt when a user-specified one is not present, from removing shortcodes
	 */
	public function prevent_excerpt_strip( $tag, $tags_to_remove ) {
		if ( ( $key = array_search( $tag, $tags_to_remove ) ) !== false ) {
			unset( $tags_to_remove[ $key ] );
		}

		return $tags_to_remove;
	}
}
