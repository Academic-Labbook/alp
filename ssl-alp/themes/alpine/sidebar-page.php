<?php

if ( is_active_sidebar( 'ssl-alp-sidebar-page' ) ) {
	dynamic_sidebar( 'ssl-alp-sidebar-page' );
} else {
	/**
	 * fall-back sidebar widgets for when the custom sidebar is not found
	 * (shouldn't be needed, as the dynamic sidebar is created at runtime)
	 */

	// search
	the_widget(
		'WP_Widget_Search',
		array(),
		array()
	);

	if ( is_plugin_active( SSL_ALP_BASE_NAME ) ) {
		// page contents
		the_widget(
			'SSL_ALP_Widget_Contents',
			array(),
			array()
		);
	}
}