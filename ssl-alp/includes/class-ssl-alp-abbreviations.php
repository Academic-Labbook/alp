<?php
/**
 * Abbreviation tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Abbreviation functionality.
 */
class SSL_ALP_Abbreviations extends SSL_ALP_Module {
	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Enable edit summaries.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_abbreviations',
			array(
				'type' => 'boolean',
			)
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings_fields() {
		/**
		 * Post abbreviations settings.
		 */
		add_settings_field(
			'ssl_alp_abbreviation_settings',
			__( 'Abbreviations', 'ssl-alp' ),
			array( $this, 'abbreviations_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);
	}

	/**
	 * Abbreviations settings partial.
	 */
	public function abbreviations_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/abbreviations-settings-display.php';
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Register taxonomy for abbreviations.
		$loader->add_action( 'init', $this, 'register_abbreviation_taxonomy' );

		// Add text at the head of the new abbreviation form on the edit screen,
		$loader->add_action( 'ssl_alp_abbreviation_pre_add_form', $this, 'add_edit_screen_form_header_text' );

		// Change the column names in the abbreviation edit screen.
		$loader->add_filter( 'manage_edit-ssl_alp_abbreviation_columns', $this, 'filter_edit_screen_columns', 10, 1 );

		// Add abbreviation definitions in post content.
		$loader->add_filter( 'the_content', $this, 'add_abbreviation_definitions' );
	}

	/**
	 * Register taxonomy for abbreviations.
	 */
	public function register_abbreviation_taxonomy() {
		if ( ! get_option( 'ssl_alp_enable_abbreviations' ) ) {
			// Abbreviations disabled.
			return;
		}

		// Register new taxonomy so that we can store all of the relationships.
		$args = array(
			'labels'       => array(
				'name'          => __( 'Abbreviations', 'ssl-alp' ),
				'singular_name' => __( 'Abbreviation', 'ssl-alp' ),
				'search_items'  => __( 'Search Abbreviations', 'ssl-alp' ),
				'popular_items' => __( 'Popular Abbreviations', 'ssl-alp' ),
				'all_items'     => __( 'All Abbreviations', 'ssl-alp' ),
				'edit_item'     => __( 'Edit Abbreviation', 'ssl-alp' ),
				'view_item'     => __( 'View Abbreviation', 'ssl-alp' ),
				'update_item'   => __( 'Update Abbreviation', 'ssl-alp' ),
				'add_new_item'  => __( 'Add New Abbreviation', 'ssl-alp' ),
				'new_item_name' => __( 'New Abbreviation', 'ssl-alp' ),
				'not_found'     => __( 'No abbreviations found', 'ssl-alp' ),
			),
			'hierarchical' => false,
			'show_in_rest' => false, // Don't show in post editor.
		);

		// Create read flag taxonomy for abbreviations.
		register_taxonomy(
			'ssl_alp_abbreviation',
			array(
				'post',
				'page',
			),
			$args
		);
	}

	/**
	 * Add abbreviation edit screen form text.
	 */
	public function add_edit_screen_form_header_text() {
		if ( ! get_option( 'ssl_alp_enable_abbreviations' ) ) {
			// Abbreviations disabled.
			return;
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/abbreviations/header.php';
	}

	/**
	 * Filter edit screen columns.
	 *
	 * @param array $columns Columns to display.
	 */
	public function filter_edit_screen_columns( $columns ) {
		if ( ! get_option( 'ssl_alp_enable_abbreviations' ) ) {
			// Abbreviations disabled.
			return $columns;
		}

		if ( array_key_exists( 'description', $columns ) ) {
			// Replace description column.
			$columns['description'] = __( 'Definition', 'ssl-alp' );
		}

		if ( array_key_exists( 'slug', $columns ) ) {
			// Hide slug column.
			unset( $columns['slug'] );
		}

		return $columns;
	}

	/**
	 * Get abbreviations.
	 */
	private function get_abbreviations() {
		$abbreviations = wp_cache_get( 'ssl_alp_abbreviations' );

		if ( ! $abbreviations ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'ssl_alp_abbreviation',
					'hide_empty' => false,
				)
			);

			$abbreviations = array();

			foreach ( (array) $terms as $term ) {
				$abbreviations[ $term->name ] = $term->description;
			}

			wp_cache_set( 'ssl_alp_abbreviations', $abbreviations );
		}

		return $abbreviations;
	}

	/**
	 * Add abbreviation definitions to post content.
	 *
	 * @param string $content The post content.
	 */
	public function add_abbreviation_definitions( $content ) {
		if ( ! get_option( 'ssl_alp_enable_abbreviations' ) ) {
			// Abbreviations disabled.
			return $content;
		}

		$abbreviations = $this->get_abbreviations();

		// Add definitions.
		foreach ( $abbreviations as $abbreviation => $definition ) {
			$content = preg_replace(
				// Matches multilines. Match newlines with wildcards. Ungreedy matching.
				"/(?<![?.&])(?!<[^<>]*?)\b" . $abbreviation . "\b(?![^<>]*?>)/msU",
				'<abbr title="' . esc_attr( $definition ) . '">' . esc_html( $abbreviation ) . '</abbr>',
				$content
			);
		}

		return $content;
	}
}
