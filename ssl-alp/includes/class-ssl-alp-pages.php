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
	 * Register admin scripts.
	 */
	public function register_admin_scripts() {
		wp_register_script(
			'ssl-alp-page-children-block-editor',
			esc_url( SSL_ALP_BASE_URL . 'blocks/children/block.js' ),
			array(
				'wp-blocks',
				'wp-components',
				'wp-i18n',
				'wp-element',
				'wp-data',
			),
			$this->get_version(),
			true
		);
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {
		register_block_type(
			'ssl-alp/page-children',
			array(
				'editor_script'   => 'ssl-alp-page-children-block-editor',
				'render_callback' => array( $this, 'render_page_children_block' ),
				'attributes'      => array(
					'className' => array(
						'type' => 'string',
					)
				)
			)
		);
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		/**
		 * Admin page list changes.
		 */

		// Remove comment, author and thumbnail support from pages.
		$loader->add_action( 'init', $this, 'disable_post_type_support' );

		// Remove month dropdown filter on admin page list.
		$loader->add_action( 'months_dropdown_results', $this, 'disable_months_dropdown_results', 10, 2 );

		// Remove date column from admin page list.
		$loader->add_filter( 'manage_edit-page_columns', $this, 'manage_edit_columns' );
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
	 * Render the page children block contents.
	 *
	 * @param array $attributes The block attributes.
	 * @return string The block contents.
	 */
	public function render_page_children_block( $attributes ) {
		$extra_classes = array( 'wp-block-ssl-alp-page-children' );

		if ( array_key_exists( 'className', $attributes ) ) {
			$classes = preg_split('/\s+/', $attributes['className'] );
			$extra_classes = array_merge( $extra_classes, $classes );
		}

		$post = get_post();

		// CSS class string.
		$ssl_alp_page_children_extra_classes = implode( ' ', $extra_classes );

		$args = array(
			'post_parent' => $post->ID,
			'post_type'   => $post->post_type,
			'orderby'     => 'menu_order',
			'order'       => 'asc',
		);

		$ssl_alp_page_children = get_children( $args );

		ob_start();
		require_once SSL_ALP_BASE_DIR . 'partials/blocks/children/display.php';
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}
