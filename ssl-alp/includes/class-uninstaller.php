<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Fired during plugin uninstallation.
 */
class SSL_ALP_Uninstaller {
	/**
	 * Uninstall plugin.
	 */
	public static function uninstall() {
        self::delete_options();
    }

    private static function delete_options() {
        // delete site options
        delete_option( 'ssl_alp_require_login' );
        delete_option( 'ssl_alp_allow_multiple_authors' );
        delete_option( 'ssl_alp_disable_post_trackbacks' );
        delete_option( 'ssl_alp_enable_crossreferences' );
        delete_option( 'ssl_alp_enable_edit_summaries' );
        delete_option( 'ssl_alp_enable_tex' );

        // delete network options
        delete_site_option( 'ssl_alp_additional_media_types' );
        delete_site_option( 'ssl_alp_tex_use_custom_urls' );
        delete_site_option( 'ssl_alp_katex_js_url' );
        delete_site_option( 'ssl_alp_katex_copy_js_url' );
        delete_site_option( 'ssl_alp_katex_css_url' );
        delete_site_option( 'ssl_alp_katex_copy_css_url' );
    }
}
