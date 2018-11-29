<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Fired during plugin activation.
 */
class SSL_ALP_Activator {
	/**
	 * Activate plugin.
	 */
	public static function activate() {
		// Add options with default values
		// (if they already exist, nothing happens).
        add_option( 'ssl_alp_require_login', true );
        add_option( 'ssl_alp_allow_multiple_authors', true );
        add_option( 'ssl_alp_disable_post_trackbacks', true );
        add_option( 'ssl_alp_enable_crossreferences', true );
        add_option( 'ssl_alp_enable_post_edit_summaries', true );
        add_option( 'ssl_alp_enable_page_edit_summaries', true );

        // add network options
        add_site_option( 'ssl_alp_additional_media_types', '' );
	}
}
