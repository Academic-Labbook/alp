<?php

/**
 * The main ALP class. From here, all of the plugin's functionality is
 * coordinated via calls to methods within this class and other "module"
 * classes (subclasses of `SSL_ALP_Module`).
 *
 * This class directly defines the internationalisation settings, and the admin
 * settings page. It also calls out to each module to register their hooks.
 *
 * The unique identifier for this plugin, and its version, is also kept here.
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
		$this->load_modules();
		$this->register_hooks();
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

	/**
	 * Load the required dependencies and classes for this plugin.
	 */
	private function load_modules() {
		/**
		 * Settings that don't fit into their own discrete classification.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-loader.php';

		$this->loader = new SSL_ALP_Loader();

		/**
		 * core functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-core.php';

		$this->core = new SSL_ALP_Core( $this );

		/**
		 * revision summary functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-revisions.php';

		$this->revisions = new SSL_ALP_Revisions( $this );

		/**
		 * literature reference functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-references.php';

		$this->references = new SSL_ALP_References( $this );

		/**
		 * TeX markup functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-tex.php';

		$this->tex = new SSL_ALP_Tex( $this );
	}

	/**
	 * Register plugin hooks.
	 */
	private function register_hooks() {
		// register plugin settings (high priority)
		$this->loader->add_action( 'init', $this, 'register_settings', 3 );

		// internationalisation
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );

		// admin settings page
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );

		// plugin settings (high priority)
		$this->loader->add_action( 'admin_init', $this, 'admin_settings_init' );

		// register module hooks
		$this->core->register_hooks();
		$this->revisions->register_hooks();
		$this->references->register_hooks();
		$this->tex->register_hooks();
	}

	/**
	 * Load translations
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'ssl-alp',
			false,
			SSL_ALP_BASE_DIR . 'languages/'
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// call modules to create their settings
		$this->core->register_settings();
		$this->revisions->register_settings();
		$this->references->register_settings();
		$this->tex->register_settings();
	}

	/**
     * Register the settings page.
     */
	public function add_admin_menu() {
		add_options_page(
			'Academic Labbook',
			__('Academic Labbook', 'ssl-alp'),
			'manage_options',
			'ssl-alp-admin-options',
			array($this, 'create_admin_interface')
		);
	}

	/**
	 * Callback function for the settings page.
	 */
	public function create_admin_interface() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin-display.php';
	}

    /**
	 * Create settings sections.
	 */
	public function admin_settings_init() {
		/**
		 * Create plugin settings page section
		 */

		 add_settings_section(
 			'ssl_alp_site_settings_section', // id
 			__( 'Site Settings', 'ssl-alp' ), // title
 			array( $this, 'site_settings_section_callback' ), // callback
 			'ssl-alp-admin-options' // page
		);

	 	add_settings_section(
			'ssl_alp_post_settings_section', // id
			__( 'Post Settings', 'ssl-alp' ), // title
			array( $this, 'post_settings_section_callback' ), // callback
			'ssl-alp-admin-options' // page
		);

		// call modules to create their settings fields
		$this->core->register_settings_fields();
		$this->revisions->register_settings_fields();
		$this->references->register_settings_fields();
		$this->tex->register_settings_fields();
    }

    public function site_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/site-settings-section-display.php';
	}

    public function post_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/post-settings-section-display.php';
	}
}

/**
 * Abstract class to define shared functions.
 */
abstract class SSL_ALP_Module {
	protected $parent;

	public function __construct( $parent ) {
		$this->parent = $parent;
	}

	public function get_plugin_name() {
		return $this->parent->get_plugin_name();
	}

	public function get_loader() {
		return $this->parent->get_loader();
	}

	public function get_version() {
		return $this->parent->get_version();
	}

	/**
	 * Enqueue styles in the page header
	 */
	abstract public function enqueue_styles();

	/**
	 * Enqueue scripts in the page header
	 */
	abstract public function enqueue_scripts();

	/**
	 * Register settings
	 */
	abstract public function register_settings();

	/**
	 * Register settings fields
	 */
	abstract public function register_settings_fields();

	/**
	 * Register hooks
	 */
	abstract public function register_hooks();
}
