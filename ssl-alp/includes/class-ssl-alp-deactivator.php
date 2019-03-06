<?php
/**
 * Deactivation tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Fired during plugin deactivation.
 */
class SSL_ALP_Deactivator {
	/**
	 * Deactivate plugin.
	 *
	 * This function fires when the plugin is deactivated, either on an
	 * individual blog or a network.
	 *
	 * @param bool $network_wide Whether the plugin is being deactivated on the
	 *                           network or just an individual site.
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			// This is a network and the plugin is being deactivated on all
			// blogs.
			self::deactivate_blogs_on_network();
		} else {
			// Deactivate single site.
			self::deactivate_single();
		}
	}

	/**
	 * Deactivate each blog on a network.
	 *
	 * @global wpdb $wpdb
	 */
	private static function deactivate_blogs_on_network() {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		// Loop over all blogs on the network.
		foreach ( $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ) as $blog_id ) {
			// Deactivate this blog.
			self::deactivate_blog_on_network( $blog_id );
		}
	}

	/**
	 * Deactivate single blog on a network.
	 *
	 * @param int $blog_id Blog ID.
	 */
	private static function deactivate_blog_on_network( $blog_id ) {
		if ( ! is_multisite() ) {
			return;
		}

		// Switch to the blog.
		switch_to_blog( $blog_id );

		// Deactivate single site.
		self::deactivate_single();

		// Switch back to previous blog.
		restore_current_blog();
	}

	/**
	 * Deactivate single blog.
	 */
	private static function deactivate_single() {
		self::flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules.
	 */
	private static function flush_rewrite_rules() {
		// Flush them.
		flush_rewrite_rules();
	}
}
