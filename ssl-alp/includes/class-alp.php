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
	 * Overrideable settings and their overridden values
	 * 
	 * (Note: soome settings are set to null if switched off, others to boolean false.)
	 */
	protected $overrideable_settings = array(
		'default_pingback_flag'	=>	null, // attempt to notify other blogs
		'default_ping_status'	=>	'closed', // allow link notifications from other blogs
		'comment_registration'	=>	1, // require registration for comments
		'comment_whitelist'		=>	null, // comment author must have a previously approved comment
		'comment_max_links'		=>	0, // hold comment in queue if it contains more than x links
		'blog_public'			=>	0, // disencourage search engine indexing
	);

	/**
	 * Custom user roles, should the admin wish to set these
	 */
	protected $alp_user_roles = array(
		'administrator',
		'researcher',
		'intern',
		'subscriber',
		'excluded'
	);

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
		 * authentication
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-authenticate.php';

		$this->auth = new SSL_ALP_Authenticate( $this );

		/**
		 * Wiki functionality
		 */

		require_once SSL_ALP_BASE_DIR . 'includes/class-wiki.php';

		$this->wiki = new SSL_ALP_Wiki( $this );

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
		$this->auth->register_hooks();
		$this->wiki->register_hooks();
		$this->coauthors->register_hooks();
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
		$this->auth->register_settings();
		$this->wiki->register_settings();
		$this->coauthors->register_hooks();
		$this->revisions->register_settings();
		$this->references->register_settings();
		$this->tex->register_settings();
	}

	/**
     * Register the settings page.
     */
	public function add_admin_menu() {
		add_options_page(
			__( 'Academic Labbook Settings', 'ssl-alp' ),
			__( 'Academic Labbook', 'ssl-alp' ),
			'manage_options',
			'ssl-alp-admin-options',
			array( $this, 'output_admin_settings_page' )
		);

		add_management_page(
			__( 'Academic Labbook Tools', 'ssl-alp' ),
			__( 'Academic Labbook', 'ssl-alp' ),
			'manage_options',
			'ssl-alp-admin-tools',
			array( $this, 'output_admin_tools_page' )
		);
	}

	/**
	 * Callback function for the settings page.
	 */
	public function output_admin_settings_page() {
		// check user has permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP admin settings.' ) . '</p>',
				403
			);
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/display.php';
	}

	/**
	 * Callback function for the tools page.
	 */
	public function output_admin_tools_page() {
		// check user has permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP admin tools.' ) . '</p>',
				403
			);
		}

		// default action confirmed state
		$role_conversion_unconfirmed = false;

		// default completed action states
		$override_core_settings_completed = false;
		$role_conversion_completed = false;
		$rebuild_references_completed = false;

		/**
		 * Handle manage core settings form
		 */

		// check if core settings are all overridden
		$core_settings_overridden = $this->_core_settings_overriden();

		// require login setting
		$require_login = get_option( 'ssl_alp_require_login' );

		if ( ! $core_settings_overridden && $require_login ) {
			if ( array_key_exists( 'ssl_alp_manage_core_settings_submitted', $_POST ) && (bool) $_POST['ssl_alp_manage_core_settings_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-manage-core-settings', 'ssl_alp_manage_core_settings_nonce' );

				// do action
				$this->_override_core_settings();

				$override_core_settings_completed = true;

				// update override flag
				$core_settings_overridden = $this->_core_settings_overriden();
			}
		}

		/**
		 * Handle convert roles form
		 */

		// check if user roles can be changed
		$roles_convertable = $this->_roles_are_default();

		// check if user roles have been changed already
		$roles_converted = $this->_roles_converted();

		if ( $roles_convertable && ! $roles_converted ) {
			if ( array_key_exists( 'ssl_alp_convert_role_submitted', $_POST ) && (bool) $_POST['ssl_alp_convert_role_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-convert-user-roles', 'ssl_alp_convert_user_roles_nonce' );

				// check they checked the checkbox
				if ( array_key_exists( 'ssl_alp_convert_role_confirm', $_POST ) && (bool) $_POST['ssl_alp_convert_role_confirm'] ) {
					// do action
					$this->_convert_roles();

					$role_conversion_completed = true;

					// update roles changed flag
					$roles_converted = $this->_roles_converted();
				} else {
					$role_conversion_unconfirmed = true;
				}
			}
		}

		/**
		 * Handle rebuild references form
		 */

		// check if cross-references are enabled
		$references_enabled = get_option( 'ssl_alp_enable_crossreferences' );

		if ( $references_enabled ) {
			if ( array_key_exists( 'ssl_alp_rebuild_references_submitted', $_POST ) && (bool) $_POST['ssl_alp_rebuild_references_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-rebuild-references', 'ssl_alp_rebuild_references_nonce' );

				// do action
				$this->_rebuild_references();

				$rebuild_references_completed = true;
			}
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/tools/display.php';
	}

	/**
	 * Checks if the overrideable settings are already overriden
	 */
	private function _core_settings_overriden() {
		$current_settings = array_map( 'get_option', array_keys( $this->overrideable_settings ) );

		return $current_settings == array_values( $this->overrideable_settings );
	}

	/**
	 * Overrides core settings to ALP recommended defaults
	 */
	private function _override_core_settings() {
		foreach ( $this->overrideable_settings as $setting => $value ) {
			update_option( $setting, $value );
		}
	}

	/**
	 * Checks if the user roles defined in this WordPress installation are set to their
	 * defaults, and therefore changeable by the _change_user_roles() tool.
	 */
	private function _roles_are_default() {
		global $wp_roles;

		// default role names
		// see populate_roles() in wp-admin/includes/schema.php
		$default_role_names = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber'
		);

		// if the WP_Roles settings are the same as above, the default roles are present
		return array_keys( $wp_roles->role_names ) == $default_role_names;
	}

	/**
	 * Checks if the user roles have been converted already to the ALP varieties
	 */
	private function _roles_converted() {
		global $wp_roles;

		// if there is no difference between the WP_Roles settings and the custom ALP roles,
		// they must have been converted already
		return empty( array_diff( array_keys( $wp_roles->role_names ), array_values( $this->alp_user_roles ) ) );
	}

	/**
	 * Changes the default user roles.
	 */
	private function _convert_roles() {
		// first of all, create the new roles
		$this->_create_role_copy( 'researcher', 'Researcher', 'editor' );
		$this->_create_role_copy( 'intern', 'Intern', 'author' );
		$this->_create_role( 'excluded', 'Excluded', array() );

		// move user roles
		$this->_swap_user_roles( 'editor', 'researcher' );
		$this->_swap_user_roles( 'author', 'intern' );
		$this->_swap_user_roles( 'contributor', 'subscriber' );

		// delete roles
		$this->_delete_role( 'editor' );
		$this->_delete_role( 'author' );
		$this->_delete_role( 'contributor' );

		// set default_role option to 'Researcher'
		update_option( 'default_role', 'researcher' );
	}

	/**
	 * Create a new role, copying capabilities from another
	 */
	private function _create_role_copy( $new_role, $display_name, $source_role ) {
		// get source role
		$source_role = get_role( $source_role );

		$this->_create_role( $new_role, $display_name, $source_role->capabilities );
	}

	/**
 	 * Create a new role with the specified permissions
 	 */
	private function _create_role( $new_role, $display_name, $permissions ) {
		if( ! get_role( $new_role ) ) {
			add_role( $new_role, $display_name, $permissions );
		}
	}

	private function _swap_user_roles( $old_role, $new_role ) {
		// get users with old role
		$users = get_users( array( 'role' => $old_role ) );

		// swap to new role
		foreach ( $users as $user ) {
			$user->remove_role( $old_role );
			$user->add_role( $new_role );
		}
	}

	private function _delete_role( $role ) {
		if( get_role( $role ) ) {
			remove_role( $role );
		}
	}

	/**
	 * Rebuild post/page references
	 */
	private function _rebuild_references() {
		global $ssl_alp;

		// pass call to reference object
		return $ssl_alp->references->rebuild_references();
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
		$this->auth->register_settings_fields();
		$this->wiki->register_settings_fields();
		$this->coauthors->register_hooks();
		$this->revisions->register_settings_fields();
		$this->references->register_settings_fields();
		$this->tex->register_settings_fields();
    }

    public function site_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/section-display.php';
	}

    public function post_settings_section_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/section-display.php';
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
