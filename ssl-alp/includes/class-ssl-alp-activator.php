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
	 * This function fires when the plugin is activated, either on an individual
	 * blog or a network, but not when a blog is created on a network *after*
	 * this plugin has been network activated. That condition is handled by
	 * `activate_multisite_blog`. See
	 * https://wordpress.stackexchange.com/a/181150/138112.
	 *
	 * @param bool $network_wide Whether the plugin is being enabled on the
	 *                           network or just an individual site.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			// This is a network and the plugin is being activated on all blogs.
			self::activate_blogs_on_network();
		} else {
			// Activate single site.
			self::activate_single();
		}
	}

	/**
	 * Action to run when a new blog is created on a network, to activate it.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public static function activate_multisite_blog( $blog_id ) {
		// Activate blog using blog ID specified in call.
		self::activate_blog_on_network( $blog_id );
	}

	/**
	 * Activate each blog on a network.
	 *
	 * @global wpdb $wpdb
	 */
	private static function activate_blogs_on_network() {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		// Loop over all blogs on the network.
		foreach ( $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ) as $blog_id ) {
			// Activate this blog.
			self::activate_blog_on_network( $blog_id );
		}
	}

	/**
	 * Activate single blog on a network.
	 *
	 * @param int $blog_id Blog ID.
	 */
	private static function activate_blog_on_network( $blog_id ) {
		if ( ! is_multisite() ) {
			return;
		}

		// Switch to the blog.
		switch_to_blog( $blog_id );

		// Activate single site.
		self::activate_single();

		// Switch back to previous blog.
		restore_current_blog();
	}

	/**
	 * Activate single blog.
	 */
	private static function activate_single() {
		self::add_options();
		self::flush_rewrite_rules();
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
		add_option( 'ssl_alp_flag_unread_posts', true );
		add_option( 'ssl_alp_enable_tex', true );

		// Add network options.
		add_site_option( 'ssl_alp_additional_media_types', '' );
		add_site_option( 'ssl_alp_katex_use_custom_urls', false );
		add_site_option( 'ssl_alp_katex_js_url', '' );
		add_site_option( 'ssl_alp_katex_copy_js_url', '' );
		add_site_option( 'ssl_alp_katex_css_url', '' );
		add_site_option( 'ssl_alp_katex_copy_css_url', '' );
	}

	/**
	 * Flush rewrite rules.
	 */
	private static function flush_rewrite_rules() {
		global $ssl_alp;

		// Add rewrite rules.
		SSL_ALP_Revisions::add_unread_post_rewrite_rules();

		// Flush them.
		flush_rewrite_rules();
	}
}
