<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
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

	public function register() {
		$loader = $this->get_loader();

		// register settings
		$loader->add_action( 'admin_init', $this, 'register_settings', 5 ); // high priority
		$loader->add_action( 'admin_init', $this, 'register_settings_fields' );

		// enqueue styles and scripts
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles' );
        $loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
        $loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );

		// register hooks
		$this->register_hooks();
	}

	/**
	 * Enqueue styles in the page header
	 */
	public function enqueue_styles() {}
	public function enqueue_admin_styles() {}

	/**
	 * Enqueue scripts in the page header
	 */
	public function enqueue_scripts() {}
	public function enqueue_admin_scripts() {}

	/**
	 * Register settings and fields
	 */
	public function register_settings() {}
	public function register_settings_fields() {}

	/**
	 * Register hooks
	 */
	public function register_hooks() {}
}
