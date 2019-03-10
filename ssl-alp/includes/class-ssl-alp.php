<?php
/**
 * Base plugin.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
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
	 *
	 * @var object
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version     = SSL_ALP_VERSION;
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

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-loader.php';

		$this->loader = new SSL_ALP_Loader();

		/**
		 * Core functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-core.php';

		$this->core = new SSL_ALP_Core( $this );

		/**
		 * Admin tools.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-tools.php';

		$this->tools = new SSL_ALP_Tools( $this );

		/**
		 * Authentication.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-authenticate.php';
		require_once SSL_ALP_BASE_DIR . 'includes/class-http-auth.php';

		$this->auth = new SSL_ALP_Authenticate( $this );

		/**
		 * Search.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-search.php';

		$this->search = new SSL_ALP_Search( $this );

		/**
		 * Page functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-pages.php';

		$this->pages = new SSL_ALP_Pages( $this );

		/**
		 * Coauthor functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-coauthors.php';
		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-coauthors-widget.php';

		$this->coauthors = new SSL_ALP_Coauthors( $this );

		/**
		 * Revision summary functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-revisions.php';
		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-revisions-widget.php';

		$this->revisions = new SSL_ALP_Revisions( $this );

		/**
		 * Cross-reference functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-references.php';

		$this->references = new SSL_ALP_References( $this );

		/**
		 * TeX markup functionality.
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-tex.php';

		$this->tex = new SSL_ALP_Tex( $this );
	}

	/**
	 * Register plugin hooks.
	 */
	private function register() {
		// Internationalisation.
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );

		// Network admin settings page.
		$this->loader->add_action( 'network_admin_menu', $this, 'add_network_admin_menu' );
		// Network admin settings handler.
		$this->loader->add_action( 'network_admin_edit_' . SSL_ALP_NETWORK_SETTINGS_PAGE, $this, 'update_network_options' );

		// Site admin settings page.
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );

		// Plugin settings.
		$this->loader->add_action( 'admin_init', $this, 'admin_settings_init' );
		$this->loader->add_filter( 'plugin_action_links_' . SSL_ALP_BASE_NAME, $this, 'admin_plugin_settings_link' );

		// Register submodules.
		$this->core->register();
		$this->tools->register();
		$this->auth->register();
		$this->search->register();
		$this->pages->register();
		$this->coauthors->register();
		$this->revisions->register();
		$this->references->register();
		$this->tex->register();
	}

	/**
	 * Load translations.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'ssl-alp',
			false,
			SSL_ALP_BASE_DIR . 'languages/'
		);
	}

	/**
	 * Register the network settings page, if network is enabled.
	 */
	public function add_network_admin_menu() {
		add_submenu_page(
			'settings.php', // Network settings page.
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
		// Check user has permissions.
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to use the ALP network admin settings.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/display-network.php';
	}

	/**
	 * Callback function for the site settings page.
	 */
	public function output_site_settings_page() {
		// Check user has permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to use the ALP site admin settings.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/display-site.php';
	}

	/**
	 * Handle settings data posted from the network settings page.
	 */
	public function update_network_options() {
		// Check nonce.
		check_admin_referer( 'ssl-alp-network-admin-options' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to use the ALP network admin settings.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		// Get current options.
		global $new_whitelist_options;

		$options = $new_whitelist_options[ SSL_ALP_NETWORK_SETTINGS_PAGE ];

		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				// Save option.
				update_site_option( $option, wp_unslash( $_POST[ $option ] ) );
			} else {
				// Option doesn't exist any more - delete.
				delete_site_option( $option );
			}
		}

		// Redirect back to settings page.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => SSL_ALP_NETWORK_SETTINGS_MENU_SLUG,
					'updated' => 'true',
				),
				network_admin_url( 'settings.php' )
			)
		);

		exit;
	}

	/**
	 * Show options page link on plugin list.
	 *
	 * @param array $links Network page links.
	 * @return array Network page links with admin settings page added.
	 */
	public function admin_plugin_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( menu_page_url( SSL_ALP_SITE_SETTINGS_MENU_SLUG, false ) ),
			esc_html__( 'Settings', 'ssl-alp' )
		);

		$tools_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( menu_page_url( SSL_ALP_SITE_TOOLS_MENU_SLUG, false ) ),
			esc_html__( 'Tools', 'ssl-alp' )
		);

		// Add to start of link list.
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
			'ssl_alp_site_settings_section',
			__( 'Site Settings', 'ssl-alp' ),
			array( $this, 'site_settings_section_callback' ),
			'ssl-alp-admin-options'
		);

		add_settings_section(
			'ssl_alp_post_settings_section',
			__( 'Post Settings', 'ssl-alp' ),
			array( $this, 'post_settings_section_callback' ),
			'ssl-alp-admin-options'
		);

		add_settings_section(
			'ssl_alp_script_settings_section',
			__( 'Script Settings', 'ssl-alp' ),
			array( $this, 'scripts_settings_section_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE
		);

		add_settings_section(
			'ssl_alp_media_settings_section',
			__( 'Media Settings', 'ssl-alp' ),
			array( $this, 'media_settings_section_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE
		);
	}

	/**
	 * Site settings section partial.
	 */
	public function site_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/section-display.php';
	}

	/**
	 * Post settings section partial.
	 */
	public function post_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/section-display.php';
	}

	/**
	 * Scripts settings section partial.
	 */
	public function scripts_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/scripts/section-display.php';
	}

	/**
	 * Media settings section partial.
	 */
	public function media_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/media/section-display.php';
	}
}
