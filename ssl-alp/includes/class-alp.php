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
		$this->define_global_hooks();
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
	 * Register global hooks related to core WordPress functionality
	 */
	 private function define_global_hooks() {
		 $this->loader->add_action( 'get_header', $this, 'check_logged_in');
		 $this->loader->add_action( 'init', $this, 'unregister_tags' );
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

		// add edit summary box to post and page edit screens
		$this->loader->add_action( 'post_submitbox_misc_actions', $plugin_admin, 'add_edit_summary_textbox' );

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
		//$this->loader->add_action( 'wp_restore_post_revision', $plugin_admin, 'restore_post_revision', 10, 2 );
		//$this->loader->add_filter( 'wp_save_post_revision_post_has_changed', $plugin_admin, 'check_revisioned_meta_fields_have_changed', 10, 3 );

		//if ($pagenow == 'revision.php') {
		//	$this->loader->add_filter( '_wp_post_revision_fields', $this, 'post_revision_fields', 10, 1 );
		//	$this->loader->add_filter( '_wp_post_revision_field_postmeta', $this, 'post_revision_field', 1, 2 );
		//}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {
		$plugin_public = new SSL_ALP_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Disable tags on posts.
	 */
	public function unregister_tags() {
		$disable_tags = (get_option('ssl_alp_disable_post_tags') ? get_option('ssl_alp_disable_post_tags') : false);

		if ( !$disable_tags) {
			return;
		}

		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
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
