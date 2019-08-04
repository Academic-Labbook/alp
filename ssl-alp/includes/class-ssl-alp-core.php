<?php
/**
 * Core tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Plugin functionality that doesn't fit anywhere else.
 */
class SSL_ALP_Core extends SSL_ALP_Module {
	/**
	 * Match media type in string.
	 * https://regexr.com/4d6nq
	 *
	 * @var string
	 */
	protected $media_type_regex = '/^([a-z|]+)(\s+[\w]+\/[\w\-\.\+]+)\h*(\h+\/\/\h*.*)?$/';

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		// Public CSS.
		wp_enqueue_style(
			'ssl-alp-public-css',
			SSL_ALP_BASE_URL . 'css/public.css',
			array(),
			$this->get_version(),
			'all'
		);
	}

	/**
	 * Register the stylesheets for the admin panel.
	 */
	public function enqueue_admin_styles() {
		// Admin CSS.
		wp_enqueue_style(
			'ssl-alp-admin-css',
			SSL_ALP_BASE_URL . 'css/admin.css',
			array(),
			$this->get_version(),
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		// Public JavaScript.
		wp_enqueue_script(
			'ssl-alp-public-js',
			SSL_ALP_BASE_URL . 'js/public.js',
			array( 'jquery' ),
			$this->get_version(),
			false
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Disable post trackbacks.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_disable_post_trackbacks',
			array(
				'type' => 'boolean',
			)
		);

		// Additional upload media types allowed.
		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_additional_media_types',
			array(
				'type' => 'string',
			)
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings_fields() {
		/**
		 * Site access settings
		 */
		add_settings_field(
			'ssl_alp_access_settings',
			__( 'Access', 'ssl-alp' ),
			array( $this, 'access_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_site_settings_section'
		);

		/**
		 * Post meta settings field
		 */
		add_settings_field(
			'ssl_alp_category_settings',
			__( 'Meta', 'ssl-alp' ),
			array( $this, 'meta_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);

		/**
		 * Media types settings field
		 */
		add_settings_field(
			'ssl_alp_category_settings',
			__( 'Additional media types', 'ssl-alp' ),
			array( $this, 'media_types_settings_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_media_settings_section'
		);
	}

	/**
	 * Access settings partial.
	 */
	public function access_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/access-settings-display.php';
	}

	/**
	 * Meta settings partial.
	 */
	public function meta_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/meta-settings-display.php';
	}

	/**
	 * Media types settings partial.
	 */
	public function media_types_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/media/media-types-settings-display.php';
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Add additional media type support.
		$loader->add_filter( 'upload_mimes', $this, 'filter_media_types' );

		// Filter ssl_alp_additional_media_types option.
		$loader->add_filter( 'sanitize_option_ssl_alp_additional_media_types', $this, 'sanitize_additional_media_types', 10, 1 );

		// Remove WordPress link in meta widget.
		$loader->add_filter( 'widget_meta_poweredby', $this, 'filter_powered_by' );

		// Hide WordPress news and events.
		$loader->add_filter( 'wp_dashboard_setup', $this, 'remove_wp_dashboard_metaboxes' );

		// Disable post trackbacks.
		$loader->add_action( 'init', $this, 'disable_post_trackbacks' );
	}

	/**
	 * Sanitize term querystring, returning an array of integers.
	 *
	 * Used for e.g. coauthor__in, coauthor__not_in, ssl_alp_inventory_item__and, etc.
	 *
	 * @param WP_Query $query     Query object.
	 * @param string   $query_var Query var whose contents to sanitize.
	 */
	public function sanitize_querystring( $query, $query_var ) {
		$querystring = $query->get( $query_var, array() );
		$querystring = array_map( 'absint', array_unique( (array) $querystring ) );

		// Update querystring.
		$query->set( $query_var, $querystring );
	}

	/**
	 * Filters supplied media type string into an array.
	 *
	 * @param string|array $media_types Media types to filter.
	 * @return array Filtered media types.
	 */
	public function sanitize_additional_media_types( $media_types ) {
		if ( is_array( $media_types ) ) {
			// This is the second call to sanitize.
			return $media_types;
		} elseif ( empty( $media_types ) ) {
			// Nothing to sanitize.
			return $media_types;
		}

		// Break supplied types into lines.
		$media_types = preg_split( '/\r\n|\n|\r/', $media_types );

		// Running list of valid types.
		$valid_types = array();

		foreach ( $media_types as $media_type ) {
			if ( empty( $media_type ) ) {
				// Skip empty line.
				continue;
			}

			$matches = null;

			if ( preg_match( $this->media_type_regex, $media_type, $matches ) ) {
				// This is a valid media type. Remove empty elements in delimited list of types.
				$file_types = implode( '|', array_filter( explode( '|', $matches[1] ) ) );

				$new_media_type = array(
					'extension'  => sanitize_text_field( $file_types ),
					// Allow whitespace so the user can align fields in the textarea.
					'media_type' => $this->sanitize_text_field_preserving_whitespace( $matches[2] ),
				);

				if ( count( $matches ) > 3 ) {
					// A comment was found. Use special sanitize function to preserve outer whitespace.
					$new_media_type['comment'] = $this->sanitize_text_field_preserving_whitespace( $matches[3] );
				}

				// Add valid type.
				$valid_types[] = $new_media_type;
			} else {
				// Invalid type - add error message.
				// FIXME: this notice is not displayed anywhere, seemingly.
				add_settings_error(
					'ssl_alp_additional_media_types',
					'ssl-alp-invalid-media-types',
					sprintf(
						/* translators: 1: media type */
						__( 'Invalid media type entry "%1$s" specified', 'ssl-alp' ),
						esc_html( $media_type )
					)
				);

				// Return original value.
				// Ideally WordPress would pass the original value to this function call, but
				// it doesn't (https://github.com/WordPress/WordPress/blob/4848a09b3593b639bd9c3ccfcd6038e90adf5866/wp-includes/option.php#L2114).
				$valid_types = get_site_option( 'ssl_alp_additional_media_types', '' );
			}
		}

		return $valid_types;
	}

	/**
	 * Sanitize a string, but don't trim its whitespace.
	 * This is a copy of WordPress Core's `_sanitize_text_fields` function, but without
	 * trim() nor $keep_new.
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_text_field_preserving_whitespace( $str ) {
		$filtered = wp_check_invalid_utf8( $str );

		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, false );
			// Use html entities in a special case to make sure no newline stripping stage could
			// lead to a functional tag.
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}

		return $filtered;
	}

	/**
	 * Helper function to provide sanitised user-defined media types.
	 *
	 * The media types are stored in the database with untrimmed whitespace to allow the user
	 * to align their media types in the settings page textarea nicely. This function provides
	 * a stripped, deduplicated array of media types used by the media type checker.
	 */
	public function get_allowed_media_types() {
		// Get extra media types defined in the plugin settings.
		$extra_media_types = get_site_option( 'ssl_alp_additional_media_types' );

		if ( ! $extra_media_types || ! is_array( $extra_media_types ) ) {
			return array();
		}

		foreach ( $extra_media_types as $key => $extra_media_type ) {
			// Trim whitespace.
			$extra_media_type = array_map( 'trim', $extra_media_type );
			$extra_media_types[ $key ] = $extra_media_type;
		}

		return $extra_media_types;
	}

	/**
	 * Filter media types.
	 *
	 * @param array $media_types Media types to filter.
	 * @return array Filtered media types.
	 */
	public function filter_media_types( $media_types ) {
		$extra_media_types = $this->get_allowed_media_types();

		// Loop over user-defined media types.
		foreach ( $extra_media_types as $extra_media_type ) {
			if ( array_key_exists( $extra_media_type['extension'], $media_types ) ) {
				// Type already exists.
				continue;
			}

			// Add as valid media type.
			$media_types[ $extra_media_type['extension'] ] = $extra_media_type['media_type'];
		}

		return $media_types;
	}

	/**
	 * Remove WordPress URL from meta widget.
	 *
	 * @param string $list_item List item.
	 * @return string Empty string.
	 */
	public function filter_powered_by( $list_item ) {
		return '';
	}

	/**
	 * Remove admin panel dashboard metaboxes.
	 */
	public function remove_wp_dashboard_metaboxes() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
	}

	/**
	 * Disable post trackbacks.
	 */
	public function disable_post_trackbacks() {
		if ( ! get_option( 'ssl_alp_disable_post_trackbacks' ) ) {
			return;
		}

		remove_post_type_support( 'post', 'trackbacks' );
	}
}
