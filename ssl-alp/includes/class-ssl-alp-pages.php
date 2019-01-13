<?php
/**
 * Page tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Page wiki functionality.
 */
class SSL_ALP_Pages extends SSL_ALP_Module {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Remove comment, author and thumbnail support from pages.
		$loader->add_action( 'init', $this, 'disable_post_type_support' );

		// Remove month dropdown filter on admin page list.
		$loader->add_action( 'months_dropdown_results', $this, 'disable_months_dropdown_results', 10, 2 );

		// Remove date column from admin page list.
		$loader->add_filter( 'manage_edit-page_columns', $this, 'manage_edit_columns' );

		// Sort pages alphabetically by default.
		$loader->add_filter( 'manage_edit-page_sortable_columns', $this, 'manage_edit_sortable_columns' );
	}

	/**
	 * Disable comments, author and thumbnail support on pages.
	 */
	public function disable_post_type_support() {
		remove_post_type_support( 'page', 'comments' );
		remove_post_type_support( 'page', 'author' );
		remove_post_type_support( 'page', 'thumbnail' );
	}

	/**
	 * Disable months dropdown box in admin page list.
	 *
	 * @param array  $months    Months.
	 * @param string $post_type Post type being shown.
	 * @return array Empty array if post type is page, otherwise $months.
	 */
	public function disable_months_dropdown_results( $months, $post_type ) {
		if ( 'page' === $post_type ) {
			// Return empty array to force it to hide (see months_dropdown() in class-wp-list-table.php).
			return array();
		}

		return $months;
	}

	/**
	 * Filter columns shown on list of wiki pages in admin panel.
	 *
	 * @param array $columns Columns to show by default.
	 * @return array Columns with date column removed.
	 */
	public function manage_edit_columns( $columns ) {
		if ( array_key_exists( 'date', $columns ) ) {
			// Remove date column.
			unset( $columns['date'] );
		}

		return $columns;
	}

	/**
	 * Remove date column and sort columns alphabetically by name on list of pages in admin panel.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Columns with title column set as default sort.
	 */
	public function manage_edit_sortable_columns( $columns ) {
		if ( array_key_exists( 'date', $columns ) ) {
			// Remove date column.
			unset( $columns['date'] );
		}

		// Make title the default sort.
		$columns['title'] = array( $columns['title'], true );

		return $columns;
	}
}
