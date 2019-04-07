<?php
/**
 * Search tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Advanced search functionality.
 */
class SSL_ALP_Search extends SSL_ALP_Module {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Allow extra public query vars.
		$loader->add_filter( 'query_vars', $this, 'whitelist_advanced_search_query_vars' );

		// Support date querystrings in WP_Query.
		$loader->add_action( 'pre_get_posts', $this, 'parse_date_query_vars' );

		// Fix inconsistent behaviour applied to tag and category AND queries.
		$loader->add_action( 'parse_tax_query', $this, 'fix_category_tag_and_query_var_inconsistency' );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_disallow_public_advanced_search',
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
		 * Post multiple author settings field.
		 */
		add_settings_field(
			'ssl_alp_search_settings',
			__( 'Search', 'ssl-alp' ),
			array( $this, 'search_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_site_settings_section'
		);
	}

	/**
	 * Search settings partial.
	 */
	public function search_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/search-settings-display.php';
	}

	/**
	 * Enqueue scripts in the admin header.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		$setting_menu_slug = 'settings_page_' . SSL_ALP_SITE_SETTINGS_MENU_SLUG;

		if ( $setting_menu_slug === $screen->id ) {
			wp_enqueue_script(
				'ssl-alp-search-settings-js',
				SSL_ALP_BASE_URL . 'js/admin-settings-search.js',
				array( 'jquery' ),
				$this->get_version(),
				true
			);
		}
	}

	/**
	 * Check if the current user can make advanced searches.
	 */
	public function current_user_can_advanced_search() {
		if ( get_option( 'ssl_alp_disallow_public_advanced_search' ) ) {
			return is_user_logged_in();
		}

		return true;
	}

	/**
	 * Whitelist advanced search query vars.
	 *
	 * This allows posts to be filtered by lists of authors, categories and tags. Note that
	 * coauthor post filtering is provided by the coauthor module.
	 *
	 * @param string[] $public_query_vars Array of public query vars.
	 */
	public function whitelist_advanced_search_query_vars( $public_query_vars ) {
		if ( ! $this->current_user_can_advanced_search() ) {
			// Advanced search disabled.
			return $public_query_vars;
		}

		// Private query vars to make public. These are sanitised by WordPress core.
		$extra_query_vars = array(
			'author__in',     // Author query vars only used when coauthor support is disabled.
			'author__not_in',
			'category__and',
			'category__in',
			'category__not_in',
			'tag__and',
			'tag__in',
			'tag__not_in',
		);

		// Merge new query vars into existing ones.
		$public_query_vars = wp_parse_args( $extra_query_vars, $public_query_vars );

		// Custom query vars to make public. These are sanitised and handled by
		// `parse_date_query_vars`.
		$custom_query_vars = array(
			'ssl_alp_after_year',
			'ssl_alp_after_month',
			'ssl_alp_after_day',
			'ssl_alp_before_year',
			'ssl_alp_before_month',
			'ssl_alp_before_day',
		);

		// Merge new query vars into existing ones.
		$public_query_vars = wp_parse_args( $custom_query_vars, $public_query_vars );

		return $public_query_vars;
	}

	/**
	 * Sanitise date querystrings and inject them as filters into WP_Query.
	 *
	 * This detects values submitted through the custom search function and turns them into the
	 * filters expected by WP_Query.
	 *
	 * @param WP_Query $query The query (passed by reference).
	 */
	public function parse_date_query_vars( $query ) {
		if ( ! $query->is_search() ) {
			return;
		}

		if ( ! $this->current_user_can_advanced_search() ) {
			// Advanced search disabled.
			return;
		}

		// Get querystrings.
		$after_year   = $query->get( 'ssl_alp_after_year' );
		$after_month  = $query->get( 'ssl_alp_after_month' );
		$after_day    = $query->get( 'ssl_alp_after_day' );
		$before_year  = $query->get( 'ssl_alp_before_year' );
		$before_month = $query->get( 'ssl_alp_before_month' );
		$before_day   = $query->get( 'ssl_alp_before_day' );

		$date_query = array(
			'after'     => array(),
			'before'    => array(),
			'inclusive' => true,
		);

		if ( ! empty( $after_year ) ) {
			$date_query['after']['year'] = $after_year;
		}

		if ( ! empty( $after_month ) ) {
			$date_query['after']['month'] = $after_month;
		}

		if ( ! empty( $after_day ) ) {
			$date_query['after']['day'] = $after_day;
		}

		if ( ! empty( $before_year ) ) {
			$date_query['before']['year'] = $before_year;
		}

		if ( ! empty( $before_month ) ) {
			$date_query['before']['month'] = $before_month;
		}

		if ( ! empty( $before_day ) ) {
			$date_query['before']['day'] = $before_day;
		}

		$query->set( 'date_query', $date_query );
	}

	/**
	 * Fix inconsistency in WordPress when handling tag__and queries.
	 *
	 * If a category__and query contains only a single item, it is merged into category__in. This is
	 * not applied to singular tag__and queries. This may lead to user confusion with the search
	 * page, since items in category__and can jump to category__in, but not items in tag__and and
	 * tag__in.
	 *
	 * See https://core.trac.wordpress.org/ticket/46459.
	 *
	 * @param WP_Query $query The query.
	 */
	public function fix_category_tag_and_query_var_inconsistency( $query ) {
		$tag_and = $query->get( 'tag__and', array() );

		if ( ! empty( $tag_and ) && 1 === count( $tag_and ) ) {
			// There is only one tag AND term specified, so merge it into IN, consistent with core
			// behaviour for categories.
			$tag_in   = $query->get( 'tag__in', array() );
			$tag_in[] = absint( reset( $tag_and ) );

			// Reset AND.
			$tag_and = array();

			$query->set( 'tag__and', $tag_and );
			$query->set( 'tag__in', $tag_in );
		}
	}
}
