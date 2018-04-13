<?php

/*
Plugin Name:  Academic Labbook Plugin
Plugin URI:   https://alp.attackllama.com/
Description:  Turn WordPress into a collaborative academic notebook.
Version:      0.7.1
Author:       Sean Leavey
Author URI:   https://attackllama.com/
License:      GPL3
License URI:  https://www.gnu.org/licenses/gpl-3.0.en.html
*/

if ( ! defined( 'WPINC' ) ) {
    exit;
}

/**
 * Current plugin version.
 */

define( 'SSL_ALP_VERSION', '0.7.1' );

/**
 * Plugin name
 */

define( 'SSL_ALP_PLUGIN_NAME', 'Academic Labbook' );

/**
 * Base plugin directory
 */

define( 'SSL_ALP_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSL_ALP_BASE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Default settings
 */

define( 'SSL_ALP_KATEX_VERSION', '0.9.0' );
define( 'SSL_ALP_DEFAULT_KATEX_JS_URL', 'https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.9.0/katex.min.js' );
define( 'SSL_ALP_DEFAULT_KATEX_CSS_URL', 'https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.9.0/katex.min.css' );
define( 'SSL_ALP_DOI_BASE_URL', 'https://doi.org/' );
define( 'SSL_ALP_ARXIV_BASE_URL', 'https://arxiv.org/abs/' );

/**
 * Code to run on plugin activation and deactivation.
 */

// import classes
require_once SSL_ALP_BASE_DIR . 'includes/class-activator.php';
require_once SSL_ALP_BASE_DIR . 'includes/class-deactivator.php';

// register hooks
register_activation_hook( __FILE__, array( 'SSL_ALP_Activator', 'activate' ) );
register_activation_hook( __FILE__, array( 'SSL_ALP_Deactivator', 'deactivate' ) );

/**
 * Add theme directory provided by this plugin
 */

register_theme_directory( SSL_ALP_BASE_DIR . 'themes' );

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
