<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalisation, hooks, etc. It also maintains
 * the unique identifier of this plugin as well as the current version of the
 * plugin.
 */
class SSL_ALP {
	/**
	 * Loader responsible for maintaining and registering all hooks that power
	 * ALP.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		if ( defined( 'SSL_ALP_VERSION' ) ) {
			$this->version = SSL_ALP_VERSION;
		} else {
			$this->version = '0.1.0';
		}

		$this->plugin_name = 'Academic Labbook Plugin';
		$this->load_dependencies();
		$this->set_locale();
		$this->register_settings();
		$this->define_core_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';

		/**
		 * The class responsible for defining internationalisation functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-public.php';

		$this->loader = new SSL_ALP_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the SSL_ALP_i18n class in order to set the domain and to register
     * the hook with WordPress.
	 */
	private function set_locale() {
		$plugin_i18n = new SSL_ALP_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register plugin settings.
	 */
	private function register_settings() {
		/**
		 * Access settings
		 */

		 register_setting(
 			'ssl-alp-admin-options',
 			'ssl_alp_require_login',
 			array(
 				'type'		=>	'boolean',
 				'default'	=>	true
 			)
 		);

		/**
		 * Categories and tags settings
		 */

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

		/**
		 * Authors settings
		 */

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_multiple_authors',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		/**
		 * Edit summary settings
		 */

		register_setting(
 			'ssl-alp-admin-options',
 			'ssl_alp_post_edit_summaries',
 			array(
 				'type'		=>	'boolean',
 				'default'	=>	true
 			)
 		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_page_edit_summaries',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);

		register_setting(
			'ssl-alp-admin-options',
			'ssl_alp_edit_summary_max_length',
			array(
				'type'		=>	'integer',
				'default'	=>	100
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

		/**
		 * Mathematics settings
		 */

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
	 * Register global hooks related to core WordPress functionality
	 */
	 private function define_core_hooks() {
		 $this->loader->add_action( 'get_header', $this, 'check_logged_in');
		 $this->loader->add_action( 'init', $this, 'unregister_tags' );
		 $this->loader->add_action( 'init', $this, 'disable_post_formats' );
		 $this->loader->add_action( 'init', $this, 'disable_post_excerpts' );
		 $this->loader->add_action( 'init', $this, 'disable_post_trackbacks' );
	 }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {
		global $pagenow;

		$plugin_admin = new SSL_ALP_Admin( $this->get_plugin_name(), $this->get_version() );

		// styles and scripts
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// settings page
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_api_init' );

		/*
		 * revision comments
		 */

		 // register edit summary feature with posts and pages
 		$this->loader->add_action( 'init', $plugin_admin, 'add_edit_summary_support' );

		// add edit summary box to post and page edit screens
		$this->loader->add_action( 'post_submitbox_misc_actions', $plugin_admin, 'add_edit_summary_textbox' );

		// add edit summary to revision history list under posts/pages/etc.
		$this->loader->add_filter( 'wp_post_revision_title_expanded', $plugin_admin, 'add_revision_title_edit_summary', 10, 2 );
		// modify revision screen data
		$this->loader->add_filter( 'wp_prepare_revision_for_js', $plugin_admin, 'prepare_revision_for_js', 10, 2 );

		// When restoring a revision, also restore that revisions's revisioned meta.
		$this->loader->add_action( 'wp_restore_post_revision', $plugin_admin, 'restore_post_revision_meta', 10, 2 );
		// When creating a revision, also save any revisioned meta.
		$this->loader->add_action( '_wp_put_post_revision', $plugin_admin, 'save_revisioned_meta_fields' );

		// When revisioned post meta has changed, trigger a revision save.
		$this->loader->add_filter( 'wp_save_post_revision_post_has_changed', $plugin_admin, 'check_revisioned_meta_fields_have_changed', 10, 3 );

		// save edit summary as custom meta data when post is updated (needs to
		// have priority < 10 so the meta data is added before the revision
		// copy is made
		$this->loader->add_action( 'post_updated', $plugin_admin, 'save_post_edit_summary', 5, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = new SSL_ALP_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// MathJax shortcodes
		if ( get_option( 'ssl_alp_tex_enabled' ) ) {
			$this->loader->add_action( 'init', $plugin_public, 'add_mathjax_shortcodes' );
			$this->loader->add_action( 'wp_footer', $plugin_public, 'add_mathjax_script' );
		}

		// DOI shortcode
		if ( get_option( 'ssl_alp_doi_shortcode' ) ) {
			$this->loader->add_action( 'init', $plugin_public, 'add_doi_shortcodes' );
		}

		// arXiv shortcode
		if ( get_option( 'ssl_alp_arxiv_shortcode' ) ) {
			$this->loader->add_action( 'init', $plugin_public, 'add_arxiv_shortcodes' );
		}
	}

	/**
	 * Disable tags on posts.
	 */
	public function unregister_tags() {
		if ( !get_option( 'ssl_alp_disable_post_tags' ) ) {
			return;
		}

		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
	}

	/**
	 * Disable post formats
	 */
	public function disable_post_formats() {
		if ( !get_option( 'ssl_alp_disable_post_formats' ) ) {
			return;
		}

		remove_post_type_support( 'post', 'post-formats' );
	}

	/**
	 * Disable post excerpts
	 */
	public function disable_post_excerpts() {
		if ( !get_option( 'ssl_alp_disable_post_excerpts' ) ) {
			return;
		}

		remove_post_type_support( 'post', 'excerpt' );
	}

	/**
	 * Disable post trackbacks
	 */
	public function disable_post_trackbacks() {
		if ( !get_option( 'ssl_alp_disable_post_trackbacks' ) ) {
			return;
		}

		remove_post_type_support( 'post', 'trackbacks' );
	}

	/**
	 * Check user is logged in
	 */
	public function check_logged_in() {
		if ( !get_option( 'ssl_alp_require_login', true ) ) {
			return;
		}

    	if ( !is_user_logged_in() ) {
        	auth_redirect();
    	}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalisation functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

/**
 * Abstract class to define shared functions.
 */
abstract class SSL_ALP_Base {
	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Constructor
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = strval($plugin_name);
		$this->version = strval($version);
	}

	/**
	 * Enqueue styles in the page header
	 */
	abstract public function enqueue_styles();

	/**
	 * Enqueue scripts in the page header
	 */
	abstract public function enqueue_scripts();
}
