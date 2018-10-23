<?php

if ( is_active_sidebar( 'ssl-alp-sidebar-standard' ) ) {
	dynamic_sidebar( 'ssl-alp-sidebar-standard' );
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

	// recent posts
	the_widget(
		'WP_Widget_Recent_Posts',
		array(),
		array()
	);

	// recent comments
	the_widget(
		'WP_Widget_Recent_Comments',
		array(),
		array()
	);

	if ( is_plugin_active( SSL_ALP_BASE_NAME ) ) {
		// recent revisions
		the_widget(
			'SSL_ALP_Widget_Revisions',
			array(),
			array()
		);

		// users
		the_widget(
			'SSL_ALP_Widget_Users',
			array(),
			array()
		);
	}

	// categories
	the_widget(
		'WP_Widget_Categories',
		array(
			'count'			=>	true,
			'hierarchical'	=>	true,
			'dropdown'		=>	true
		),
		array()
	);

	// archives
	the_widget(
		'WP_Widget_Archives',
		array(
			'count'		=>	true,
			'dropdown'	=>	true
		),
		array()
	);

	// meta
	the_widget(
		'WP_Widget_Meta',
		array(),
		array()
	);
}