<?php

/*
Plugin Name:  Academic Labbook Plugin
Plugin URI:   https://alp.attackllama.com/
Description:  Turn WordPress into a collaborative academic labbook.
Version:      0.10.1
Author:       Sean Leavey
Author URI:   https://attackllama.com/
License:      GPL3
License URI:  https://www.gnu.org/licenses/gpl-3.0.en.html
*/

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Current plugin version.
 */

define( 'SSL_ALP_VERSION', '0.10.2' );

/**
 * Plugin name
 */

define( 'SSL_ALP_PLUGIN_NAME', 'Academic Labbook' );

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

/**
 * REST namespace
 */

define( 'SSL_ALP_REST_ROUTE', 'ssl-alp/v1' );

/**
 * Default settings
 */

define( 'SSL_ALP_KATEX_VERSION', '0.10.0' );
define( 'SSL_ALP_DEFAULT_KATEX_JS_URL', 'https://cdn.jsdelivr.net/npm/katex@0.10.0/dist/katex.min.js' );
define( 'SSL_ALP_DEFAULT_KATEX_CSS_URL', 'https://cdn.jsdelivr.net/npm/katex@0.10.0/dist/katex.min.css' );
define( 'SSL_ALP_DEFAULT_KATEX_COPY_JS_URL', 'https://cdn.jsdelivr.net/npm/katex@0.10.0/dist/contrib/copy-tex.min.js' );
define( 'SSL_ALP_DEFAULT_KATEX_COPY_CSS_URL', 'https://cdn.jsdelivr.net/npm/katex@0.10.0/dist/contrib/copy-tex.css' );

/**
 * Code to run on plugin activation and deactivation.
 */

// import classes
require_once SSL_ALP_BASE_DIR . 'includes/class-activator.php';
require_once SSL_ALP_BASE_DIR . 'includes/class-deactivator.php';
require_once SSL_ALP_BASE_DIR . 'includes/class-uninstaller.php';

// register hooks
register_activation_hook( __FILE__, array( 'SSL_ALP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SSL_ALP_Deactivator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'SSL_ALP_Uninstaller', 'uninstall' ) );

/**
 * Core plugin class used to load modules.
 */

require SSL_ALP_BASE_DIR . 'includes/class-alp.php';
require SSL_ALP_BASE_DIR . 'includes/class-alp-module.php';

/**
 * Execute plugin.
 */

function ssl_alp_run() {
    global $ssl_alp;

    $ssl_alp = new SSL_ALP();
	$ssl_alp->run();
}

ssl_alp_run();
