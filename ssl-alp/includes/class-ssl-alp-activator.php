<?php
/**
 * Activation tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Fired during plugin activation.
 */
class SSL_ALP_Activator {
	/**
	 * Activate plugin.
	 *
	 * This function fires when the plugin is activated, either on an individual blog or a
	 * network, but not when a blog is created on a network *after* this plugin has been network
	 * activated. That condition is handled by `activate_multisite_blog`.
	 *
	 * @param bool $network_wide Whether the plugin is being enabled on the
	 *                           network or just an individual site.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			// This is a network and the plugin is being activated on all blogs. Add options to each
			// blog.
			self::add_options_to_blogs();
		} else {
			// Add options for single site.
			self::add_options();
		}
	}

	/**
	 * Action to run when a new blog is created on a network, to add plugin options and their
	 * default values to the specified blog.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public static function activate_multisite_blog( $blog_id ) {
		// Add blog options using blog ID specified in call.
		self::add_options_to_blog( $blog_id );
	}

	/**
	 * Add plugin options and their default values to each blog on the network.
	 *
	 * @global wpdb $wpdb
	 */
	private static function add_options_to_blogs() {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		// Loop over all blogs on the network.
		foreach ( $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ) as $blog_id ) {
			// Add options to this blog.
			self::add_options_to_blog( $blog_id );
		}
	}

	/**
	 * Add plugin options and their default values to the specified blog.
	 *
	 * @param int $blog_id Blog ID.
	 */
	private static function add_options_to_blog( $blog_id ) {
		if ( ! is_multisite() ) {
			return;
		}

		// Switch to the blog.
		switch_to_blog( $blog_id );

		// Add options to the blog.
		self::add_options();

		// Switch back to previous blog.
		restore_current_blog();
	}

	/**
	 * Add plugin options and their default values.
	 */
	private static function add_options() {
		// Add options with default values (if they already exist, nothing happens).
		add_option( 'ssl_alp_require_login', true );
		add_option( 'ssl_alp_allow_multiple_authors', true );
		add_option( 'ssl_alp_disable_post_trackbacks', true );
		add_option( 'ssl_alp_enable_crossreferences', true );
		add_option( 'ssl_alp_enable_edit_summaries', true );
		add_option( 'ssl_alp_flag_read_posts', true );
		add_option( 'ssl_alp_enable_tex', true );

		// Add network options.
		add_site_option( 'ssl_alp_additional_media_types', '' );
		add_site_option( 'ssl_alp_katex_use_custom_urls', false );
		add_site_option( 'ssl_alp_katex_js_url', '' );
		add_site_option( 'ssl_alp_katex_copy_js_url', '' );
		add_site_option( 'ssl_alp_katex_css_url', '' );
		add_site_option( 'ssl_alp_katex_copy_css_url', '' );
	}
}
