<?php
/**
 * Standard module definitions.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Abstract class to define shared functions.
 */
abstract class SSL_ALP_Module {
	/**
	 * Parent.
	 *
	 * @var object
	 */
	protected $parent;

	/**
	 * Constructor.
	 *
	 * @param object $parent Parent class.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * Get plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->parent->get_plugin_name();
	}

	/**
	 * Get loader.
	 */
	public function get_loader() {
		return $this->parent->get_loader();
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->parent->get_version();
	}

	/**
	 * Register module.
	 */
	public function register() {
		$loader = $this->get_loader();

		// Register module settings.
		$loader->add_action( 'admin_init', $this, 'register_settings', 5 ); // High priority.
		$loader->add_action( 'admin_init', $this, 'register_settings_fields' );

		// Register styles and scripts.
		$loader->add_action( 'init', $this, 'register_styles' );
		$loader->add_action( 'init', $this, 'register_scripts' );
		$loader->add_action( 'admin_init', $this, 'register_admin_styles' );
		$loader->add_action( 'admin_init', $this, 'register_admin_scripts' );

		// Enqueue module styles and scripts.
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );
		$loader->add_action( 'login_enqueue_scripts', $this, 'enqueue_login_styles' );
		$loader->add_action( 'login_enqueue_scripts', $this, 'enqueue_login_scripts' );
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_block_editor_styles' );
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_block_editor_scripts' );

		// Register blocks.
		$loader->add_action( 'init', $this, 'register_blocks' );

		// Register module hooks.
		$this->register_hooks();
	}

	/**
	 * Register styles.
	 */
	public function register_styles() {}

	/**
	 * Register admin styles.
	 */
	public function register_admin_styles() {}

	/**
	 * Register scripts.
	 */
	public function register_scripts() {}

	/**
	 * Register admin scripts.
	 */
	public function register_admin_scripts() {}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {}

	/**
	 * Register settings.
	 */
	public function register_settings() {}

	/**
	 * Register settings fields.
	 */
	public function register_settings_fields() {}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {}

	/**
	 * Enqueue styles in the page header.
	 */
	public function enqueue_styles() {}

	/**
	 * Enqueue styles in the admin header.
	 */
	public function enqueue_admin_styles() {}

	/**
	 * Enqueue styles in the login header.
	 */
	public function enqueue_login_styles() {}

	/**
	 * Enqueue block editor styles.
	 */
	public function enqueue_block_editor_styles() {}

	/**
	 * Enqueue scripts in the page header.
	 */
	public function enqueue_scripts() {}

	/**
	 * Enqueue scripts in the admin header.
	 */
	public function enqueue_admin_scripts() {}

	/**
	 * Enqueue scripts in the login header.
	 */
	public function enqueue_login_scripts() {}

	/**
	 * Enqueue block editor scripts.
	 */
	public function enqueue_block_editor_scripts() {}
}
