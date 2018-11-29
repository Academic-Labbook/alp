<?php

/**
 * Log a message to STDOUT
 *
 * @param string $message
 */
function log_message( $message ) {
	fwrite( STDOUT, $message . PHP_EOL );
}
/**
 * Log an error message to STDERR
 *
 * @param string $message
 */
function error_message( $message ) {
	fwrite( STDERR, 'Error: ' . $message . PHP_EOL );
	exit( 1 );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Main plugin file.
	require dirname( dirname( __FILE__ ) ) . '/alp.php';

	// Run activation routine.
	$activator = new SSL_ALP_Activator();
	$activator::activate();
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';