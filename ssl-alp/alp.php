<?php
/**
 * Plugin Name:  Academic Labbook
 * Plugin URI:   https://alp.attackllama.com/
 * Description:  Turn WordPress into a collaborative academic labbook.
 * Version:      0.21.0
 * Author:       Sean Leavey
 * Author URI:   https://attackllama.com/
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Current plugin version.
 */

define( 'SSL_ALP_VERSION', '0.21.0' );

/**
 * Plugin name and path
 */

define( 'SSL_ALP_PLUGIN_NAME', 'Academic Labbook' );
define( 'SSL_ALP_PLUGIN_PATH', 'ssl-alp/alp.php' );

/**
 * Base plugin directory
 */

define( 'SSL_ALP_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSL_ALP_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'SSL_ALP_BASE_NAME', plugin_basename( __FILE__ ) );

/**
 * Admin slugs
 */

define( 'SSL_ALP_SITE_SETTINGS_PAGE', 'ssl-alp-admin-options' );
define( 'SSL_ALP_NETWORK_SETTINGS_PAGE', 'ssl-alp-network-admin-options' );
define( 'SSL_ALP_SITE_SETTINGS_MENU_SLUG', 'ssl-alp-site-options' );
define( 'SSL_ALP_NETWORK_SETTINGS_MENU_SLUG', 'ssl-alp-network-options' );
define( 'SSL_ALP_SITE_TOOLS_MENU_SLUG', 'ssl-alp-admin-tools' );
define( 'SSL_ALP_POST_REVISIONS_MENU_SLUG', 'ssl-alp-admin-post-revisions' );
define( 'SSL_ALP_PAGE_REVISIONS_MENU_SLUG', 'ssl-alp-admin-page-revisions' );
define( 'SSL_ALP_INVENTORY_REVISIONS_MENU_SLUG', 'ssl-alp-admin-inventory-revisions' );

/**
 * REST namespace
 */

define( 'SSL_ALP_REST_ROUTE', 'ssl-alp/v1' );

/**
 * Default settings
 */

// KaTeX version.
define( 'SSL_ALP_KATEX_VERSION', '0.10.2' );

// Recent revisions widget cache timeout.
define( 'SSL_ALP_RECENT_REVISIONS_CACHE_TIMEOUT', 5 * 60 );

/**
 * Code to run on plugin activation and deactivation.
 */

// Import classes.
require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-activator.php';
require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-deactivator.php';
require_once SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-uninstaller.php';

// Register special hooks.
register_activation_hook( __FILE__, array( 'SSL_ALP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SSL_ALP_Deactivator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'SSL_ALP_Uninstaller', 'uninstall' ) );

// Run setup on new blogs created on network installations.
add_action( 'wp_initialize_site', array( 'SSL_ALP_Activator', 'activate_multisite_blog' ), 10, 1 );

// Run uninstall on deleted blogs on network installations.
add_action( 'wp_uninitialize_site', array( 'SSL_ALP_Uninstaller', 'uninstall_multisite_blog' ), 10, 1 );

/**
 * Core plugin class used to load modules.
 */

require SSL_ALP_BASE_DIR . 'includes/class-ssl-alp.php';
require SSL_ALP_BASE_DIR . 'includes/class-ssl-alp-module.php';

/**
 * Execute plugin.
 */
function ssl_alp_run() {
	global $ssl_alp;

	$ssl_alp = new SSL_ALP();
	$ssl_alp->run();
}

ssl_alp_run();
