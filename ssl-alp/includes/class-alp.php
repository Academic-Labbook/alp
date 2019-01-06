<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

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
		$this->version = SSL_ALP_VERSION;
		$this->plugin_name = SSL_ALP_PLUGIN_NAME;
		$this->load_modules();
		$this->register();
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
		 * admin tools
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-tools.php';

		$this->tools = new SSL_ALP_Tools( $this );

		/**
		 * authentication
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-authenticate.php';

		$this->auth = new SSL_ALP_Authenticate( $this );

		/**
		 * Page wiki functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-pages.php';

		$this->pages = new SSL_ALP_Pages( $this );

		/**
		 * coauthor functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-coauthors.php';

		$this->coauthors = new SSL_ALP_Coauthors( $this );

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
	private function register() {
		// internationalisation
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );

		// network admin settings page
		$this->loader->add_action( 'network_admin_menu', $this, 'add_network_admin_menu' );
		// network admin settings handler
		$this->loader->add_action( 'network_admin_edit_' . SSL_ALP_NETWORK_SETTINGS_PAGE, $this, 'update_network_options' );

		// site admin settings page
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );

		// plugin settings
		$this->loader->add_action( 'admin_init', $this, 'admin_settings_init' );
		$this->loader->add_filter( 'plugin_action_links_' . SSL_ALP_BASE_NAME, $this, 'admin_plugin_settings_link' );

		// register submodules
		$this->core->register();
		$this->tools->register();
		$this->auth->register();
		$this->pages->register();
		$this->coauthors->register();
		$this->revisions->register();
		$this->references->register();
		$this->tex->register();
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
	 * Register the network settings page, if network is enabled
	 */
	public function add_network_admin_menu() {
		add_submenu_page(
			'settings.php', // network settings page
			__( 'Academic Labbook Settings', 'ssl-alp' ),
			__( 'Academic Labbook', 'ssl-alp' ),
			'manage_network_options',
			SSL_ALP_NETWORK_SETTINGS_MENU_SLUG,
			array( $this, 'output_network_settings_page' )
		);
	}

	/**
     * Register the site settings page.
     */
	public function add_admin_menu() {
		add_options_page(
			__( 'Academic Labbook Settings', 'ssl-alp' ),
			__( 'Academic Labbook', 'ssl-alp' ),
			'manage_options',
			SSL_ALP_SITE_SETTINGS_MENU_SLUG,
			array( $this, 'output_site_settings_page' )
		);
	}

	/**
	 * Callback function for the network settings page.
	 */
	public function output_network_settings_page() {
		// check user has permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP network admin settings.' ) . '</p>',
				403
			);
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/display-network.php';
	}

	/**
	 * Callback function for the site settings page.
	 */
	public function output_site_settings_page() {
		// check user has permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP site admin settings.' ) . '</p>',
				403
			);
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/display-site.php';
	}

	/**
	 * Handle settings data posted from the network settings page
	 */
	public function update_network_options() {
		// check nonce
		check_admin_referer( 'ssl-alp-network-admin-options' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP network admin settings.' ) . '</p>',
				403
			);
		}

		// get current options
		global $new_whitelist_options;
		$options = $new_whitelist_options[ SSL_ALP_NETWORK_SETTINGS_PAGE ];

		foreach ( $options as $option ) {
			if ( isset( $_POST[$option] ) ) {
				// save option
				update_site_option( $option, $_POST[ $option ] );
			} else {
				// option doesn't exist any more; delete
				delete_site_option( $option );
			}
		}

		// redirect back to settings page
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => SSL_ALP_NETWORK_SETTINGS_MENU_SLUG,
					'updated' => 'true'
				),
				network_admin_url( 'settings.php' )
			)
		);

		// wp_safe_redirect doesn't exit on its own
		exit;
	}

	/**
	 * Show options page link on plugin list
	 */
	public function admin_plugin_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			menu_page_url( SSL_ALP_SITE_SETTINGS_MENU_SLUG, false ),
			__( 'Settings' )
		);

		$tools_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			menu_page_url( SSL_ALP_SITE_TOOLS_MENU_SLUG, false ),
			__( 'Tools' )
		);

		// add to start of link list
		array_unshift( $links, $settings_link, $tools_link );

		return $links;
	}

    /**
	 * Create settings sections.
	 */
	public function admin_settings_init() {
		/**
		 * Create plugin settings page sections. These are used on both the site
		 * and network settings pages, but the controls displayed are different.
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

		add_settings_section(
			'ssl_alp_script_settings_section',
			__( 'Script Settings', 'ssl-alp' ),
			array( $this, 'scripts_settings_section_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE
		);

		add_settings_section(
			'ssl_alp_media_settings_section', // id
			__( 'Media Settings', 'ssl-alp' ), // title
			array( $this, 'media_settings_section_callback' ), // callback
			SSL_ALP_NETWORK_SETTINGS_PAGE
		);
    }

    public function site_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/section-display.php';
	}

    public function post_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/section-display.php';
	}

	public function scripts_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/scripts/section-display.php';
	}

	public function media_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/media/section-display.php';
	}
}
