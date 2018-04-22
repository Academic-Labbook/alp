<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Fired during plugin deactivation.
 */
class SSL_ALP_Deactivator {
	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		self::change_theme();
	}

	/**
	 * Change theme back to default. The bundled Alpine theme does not work without
	 * the plugin.
	 */
	public static function change_theme() {
		// first unregister the template directory
		self::unregister_theme_directory();

		// implicitly changes theme to default when it can't find the Alpine template
		// directory
		validate_current_theme();
	}

	/**
	 * Unregister theme directory.
	 * 
	 * This is a bit clunky and ideally should use a function unregister_theme_directory,
	 * but this doesn't exist in WordPress as of the time of writing.
	 * 
	 * Based on code in `register_theme_directory` function.
	 */
	public static function unregister_theme_directory() {
		global $wp_theme_directories;

		if ( ! is_array( $wp_theme_directories ) ) {
			// nothing to do
			return;
		}

		$untrailed = untrailingslashit( SSL_ALP_THEME_DIR );
		$key = array_search( $untrailed, $wp_theme_directories );

		if ( $key !== false ) {
			unset( $wp_theme_directories[ $key ] );
		}
	}
}
