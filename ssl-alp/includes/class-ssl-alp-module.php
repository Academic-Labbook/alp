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

		// Enqueue module styles and scripts.
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );

		// Register module hooks.
		$this->register_hooks();
	}

	/**
	 * Enqueue styles in the page header.
	 */
	public function enqueue_styles() {}

	/**
	 * Enqueue styles in the admin header.
	 */
	public function enqueue_admin_styles() {}

	/**
	 * Enqueue scripts in the page header.
	 */
	public function enqueue_scripts() {}

	/**
	 * Enqueue scripts in the admin header.
	 */
	public function enqueue_admin_scripts() {}

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
}
