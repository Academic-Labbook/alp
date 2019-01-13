<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Page wiki functionality
 */
class SSL_ALP_Pages extends SSL_ALP_Module {
	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// remove comment, author and thumbnail support
		$loader->add_action( 'init', $this, 'disable_post_type_support' );

		// remove month dropdown filter on admin page list
		$loader->add_action( 'months_dropdown_results', $this, 'disable_months_dropdown_results', 10, 2 );

		// remove date column from list of wiki pages in admin
		$loader->add_filter( 'manage_edit-page_columns', $this, 'manage_edit_columns' );

		// sort alphabetically by default
		$loader->add_filter( 'manage_edit-page_sortable_columns', $this, 'manage_edit_sortable_columns' );
	}

	/**
	 * Disable comments on pages
	 */
	public function disable_post_type_support() {
		remove_post_type_support( 'page', 'comments' );
		remove_post_type_support( 'page', 'author' );
		remove_post_type_support( 'page', 'thumbnail' );
	}

	/**
	 * Disable months dropdown box in admin page list
	 */
	public function disable_months_dropdown_results( $months, $post_type ) {
		if ( $post_type == 'page' ) {
			// return empty array to force it to hide (see months_dropdown() in class-wp-list-table.php)
			return array();
		}

		return $months;
	}

	/**
	 * Filter columns shown on list of wiki pages in admin panel
	 */
	public function manage_edit_columns( $columns ) {
		// remove date
		unset( $columns["date"] );

		return $columns;
	}

	/**
	 * Sort columns alphabetically by default on list of wiki pages in admin panel
	 */
	public function manage_edit_sortable_columns( $columns ) {
		// remove date
		unset( $columns["date"] );

		// make title default sort
		$columns["title"] = array( $columns["title"], true );

		return $columns;
	}
}
