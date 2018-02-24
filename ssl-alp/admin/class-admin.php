<?php

/**
 * The admin-specific functionality of the plugin.
 */
class SSL_ALP_Admin {
	/**
	 * The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		global $pagenow;

		// CSS only needed for options page
		if ( $pagenow == 'options-general.php' ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
		}
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';
	}

	/**
	 * Create settings sections.
	 */
	public function settings_api_init() {
		/**
		 * Settings sections
		 */

	 	add_settings_section(
			'ssl_alp_post_settings_section', // id
			__('Post Settings', 'ssl-alp'), // title
			array($this, 'post_settings_section_callback'), // callback
			'ssl-alp-admin-options' // page
		);

		/**
		 * Settings fields
		 */

	 	add_settings_field(
			'ssl_alp_post_categories', // id
			__('Post category settings', 'ssl-alp'), // title
			array($this, 'post_category_settings_callback'), // callback
			'ssl-alp-admin-options', // page
			'ssl_alp_post_settings_section' // section
		);

		add_settings_field(
			'ssl_alp_authors',
			__('Author settings', 'ssl-alp'),
			array($this, 'author_settings_callback'),
			'ssl-alp-admin-options',
			'ssl_alp_post_settings_section'
		);

		/**
		 * Settings
		 */

		register_setting(
			'ssl-alp-admin-options', // option group
			'ssl_alp_require_post_category', // option name
			array(
				'type'		=>	'boolean', // data type
				'default'	=>	true // default
			)
		);

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
			'ssl_alp_multiple_authors',
			array(
				'type'		=>	'boolean',
				'default'	=>	true
			)
		);
	}

	/**
	 * Callback functions for settings
	 */

	/*
	 * Basic settings
	 */

	// The basic section
	public function post_settings_section_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/basic/post-settings-section-display.php';
	}

	public function post_category_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/basic/post-category-settings-display.php';
	}

	public function author_settings_callback() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/basic/author-settings-display.php';
	}
}
