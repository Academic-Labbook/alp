<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Plugin functionality that doesn't fit anywhere else.
 */
class SSL_ALP_Core extends SSL_ALP_Module {
	/**
	 * Match media type in string
	 * https://regexr.com/3mqrh
	 */
	protected $media_type_regex = '/^([a-z|]+)(\s+[\w]+\/[\w\-\.\+]+)(\h+\/\/\h*.*)?$/';

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
        wp_enqueue_style( 'ssl-alp-public-css', SSL_ALP_BASE_URL . 'css/public.css', array(), $this->get_version(), 'all' );
	}

	public function enqueue_admin_styles() {
        wp_enqueue_style( 'ssl-alp-admin-css', SSL_ALP_BASE_URL . 'css/admin.css', array(), $this->get_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
        wp_enqueue_script( 'ssl-alp-public-js', SSL_ALP_BASE_URL . 'js/public.js', array( 'jquery' ), $this->get_version(), false );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_disable_post_trackbacks',
			array(
				'type'		=>	'boolean'
			)
		);

		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_additional_media_types',
			array(
				'type'		=>	'string'
			)
		);
	}

    /**
     * Register settings fields
     */
    public function register_settings_fields() {
        /**
		 * Site access settings
		 */

	 	add_settings_field(
			'ssl_alp_access_settings', // id
			__( 'Access', 'ssl-alp' ), // title
			array( $this, 'access_settings_callback' ), // callback
			SSL_ALP_SITE_SETTINGS_PAGE, // page
			'ssl_alp_site_settings_section' // section
		);

        /**
         * Post meta settings field
         */

        add_settings_field(
			'ssl_alp_category_settings', // id
			__( 'Meta', 'ssl-alp' ), // title
			array( $this, 'meta_settings_callback' ), // callback
			SSL_ALP_SITE_SETTINGS_PAGE, // page
			'ssl_alp_post_settings_section' // section
		);

		/**
         * Media types settings field
         */

        add_settings_field(
			'ssl_alp_category_settings', // id
			__( 'Additional media types', 'ssl-alp' ), // title
			array( $this, 'media_types_settings_callback' ), // callback
			SSL_ALP_NETWORK_SETTINGS_PAGE, // page
			'ssl_alp_media_settings_section' // section
		);
    }

    public function access_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/access-settings-display.php';
	}

    public function meta_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/meta-settings-display.php';
	}

	public function media_types_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/media/media-types-settings-display.php';
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// add additional media type support
		$loader->add_filter( 'upload_mimes', $this, 'filter_mime_types' );
		
		// filter ssl_alp_additional_media_types option
		$loader->add_filter( 'sanitize_option_ssl_alp_additional_media_types', $this, 'sanitize_additional_media_types', 10, 1 );

		// remove WordPress link in meta widget
		$loader->add_filter( 'widget_meta_poweredby', $this, 'filter_powered_by' );

		// hide WordPress news and events
		$loader->add_filter( 'wp_dashboard_setup', $this, 'remove_wp_dashboard_metaboxes' );

        // post meta stuff
		$loader->add_action( 'init', $this, 'disable_post_trackbacks' );
	}

	/**
	 * Filters supplied media type string into an array
	 */
	public function sanitize_additional_media_types( $media_types ) {
		if ( is_array( $media_types ) ) {
			// second call to sanitize
			return $media_types;
		} elseif ( empty( $media_types ) ) {
			// nothing to sanitize
			return;
		}

		// break into lines
		$media_types = preg_split( '/\r\n|\n|\r/', $media_types );

		// valid types
		$valid_types = array();

		foreach ( $media_types as $media_type ) {
			if ( empty( $media_type ) ) {
				// skip empty line
				continue;
			}

			$matches = null;

			if ( preg_match( $this->media_type_regex, $media_type, $matches ) ) {
				// valid media type

				// clean file types (split using | as delimiter, remove empty elements, then recombine)
				$file_types = implode( '|', array_filter( explode( '|', $matches[1] ) ) );

				$new_media_type = array(
					'extension'		=> sanitize_text_field( $file_types ),
					// allow whitespace so the user can align fields in the textarea
					'media_type'	=> $this->sanitize_text_field_preserving_whitespace( $matches[2] )
				);

				if ( count( $matches ) > 3 ) {
					// a comment was found
					// use special sanitize function to preserve outer whitespace
					$new_media_type['comment'] = $this->sanitize_text_field_preserving_whitespace( $matches[3] );
				}

				$valid_types[] = $new_media_type;
			} else {
				// invalid
				// FIXME: this notice is not displayed anywhere, seemingly.
				add_settings_error(
					'ssl_alp_additional_media_types',
					'ssl-alp-invalid-media-types',
					__(
						sprintf(
							'Invalid media type entry "%s" specified',
							esc_html( $media_type )
						),
						'ssl-alp'
					)
				);

				// return original value
				// ideally WordPress would pass the original value to this function call, but
				// it doesn't (https://github.com/WordPress/WordPress/blob/4848a09b3593b639bd9c3ccfcd6038e90adf5866/wp-includes/option.php#L2114)
				$valid_types = get_site_option( 'ssl_alp_additional_media_types', '' );
			}
		}

		return $valid_types;
	}

	/**
	 * Sanitize a string, but don't trim its whitespace.
	 * This is a copy of WordPress Core's `_sanitize_text_fields` function, but without
	 * trim() nor $keep_new.
	 */
	protected function sanitize_text_field_preserving_whitespace( $str ) {
		$filtered = wp_check_invalid_utf8( $str );

		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, false );
			// Use html entities in a special case to make sure no later
			// newline stripping stage could lead to a functional tag
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}

		return $filtered;
	}

	public function filter_mime_types( $media_types ) {
		// get extra media types defined in the plugin settings
		$extra_media_types = get_site_option( 'ssl_alp_additional_media_types' );

		if ( ! $extra_media_types || ! is_array( $extra_media_types ) ) {
			// nothing to add
			return $media_types;
		}

		foreach ($extra_media_types as $extra_media_type ) {
			if ( array_key_exists( $extra_media_type['extension'], $media_types ) ) {
				// type already exists
				continue;
			}

			// strip any whitespace that the user might have added to the media type
			$media_type = trim( $extra_media_type['media_type'] );

			// add
			$media_types[$extra_media_type['extension']] = $media_type;
		}

		return $media_types;
	}

	/**
	 * Remove WordPress URL from meta widget
	 */
	public function filter_powered_by( $list_item ) {
		return '';
	}

	public function remove_wp_dashboard_metaboxes() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
	}

    /**
     * Disable post trackbacks
     */
    public function disable_post_trackbacks() {
        if ( ! get_option( 'ssl_alp_disable_post_trackbacks' ) ) {
            return;
        }

        remove_post_type_support( 'post', 'trackbacks' );
    }
}
