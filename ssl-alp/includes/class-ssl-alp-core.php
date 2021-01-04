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
	 * Register admin styles.
	 */
	public function register_admin_styles() {
		wp_register_style(
			'ssl-alp-admin-css',
			esc_url( SSL_ALP_BASE_URL . 'css/admin.css' ),
			array(),
			$this->get_version()
		);
	}

	/**
	 * Register scripts.
	 */
	public function register_scripts() {
		if ( get_option( 'ssl_alp_disable_social_media_blocks' ) ) {
			wp_register_script(
				'ssl-alp-disallow-blocks',
				esc_url( SSL_ALP_BASE_URL . 'js/disallow-social-media-blocks.js' ),
				array(
					'wp-blocks',
					'wp-dom-ready',
					// Note: this dependency is required to prevent a race condition to ensure that
					// all blocks are registered by the time the script runs.
					'wp-edit-post',
				),
				$this->get_version(),
				false
			);
		}
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

		// Disable social media blocks.
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_disable_social_media_blocks',
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

		// Override upload media types.
		register_setting(
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_override_media_types',
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
			'ssl_alp_post_meta_settings',
			__( 'Meta', 'ssl-alp' ),
			array( $this, 'meta_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);

		/**
		 * Default image link settings field
		 */
		add_settings_field(
			'ssl_alp_default_image_link_settings',
			__( 'Default image link', 'ssl-alp' ),
			array( $this, 'default_image_link_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_post_settings_section'
		);

		/**
		 * Media types settings field
		 */
		add_settings_field(
			'ssl_alp_media_types_settings',
			__( 'Additional media types', 'ssl-alp' ),
			array( $this, 'media_types_settings_callback' ),
			SSL_ALP_NETWORK_SETTINGS_PAGE,
			'ssl_alp_media_settings_section'
		);
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Add additional media type support.
		$loader->add_filter( 'upload_mimes', $this, 'filter_media_types' );

		// Handle when media type reported by PHP's finfo_open is different from the type inferred
		// from the filename.
		$loader->add_filter( 'wp_check_filetype_and_ext', $this, 'check_file_type', 10, 3 );

		// Filter ssl_alp_additional_media_types option.
		$loader->add_filter( 'sanitize_option_ssl_alp_additional_media_types', $this, 'sanitize_additional_media_types', 10, 1 );

		// Remove "Uncategorised" category on posts where other categories have been set.
		$loader->add_action( 'set_object_terms', $this, 'remove_superfluous_uncategorised', 10, 4 );

		// Remove WordPress link in meta widget.
		$loader->add_filter( 'widget_meta_poweredby', $this, 'filter_powered_by' );

		// Hide WordPress news and events.
		$loader->add_filter( 'wp_dashboard_setup', $this, 'remove_wp_dashboard_metaboxes' );

		// Disable post trackbacks.
		$loader->add_action( 'init', $this, 'disable_post_trackbacks' );

		// Allow core `image_default_link_type` setting to be updated from plugin settings page.
		$loader->add_filter( 'allowed_options', $this, 'allow_image_default_link_type_update' );
	}

	/**
	 * Enqueue styles in the admin header.
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style( 'ssl-alp-admin-css' );
	}

	/**
	 * Enqueue block editor scripts.
	 */
	public function enqueue_block_editor_scripts() {
		if ( get_option( 'ssl_alp_disable_social_media_blocks' ) ) {
			wp_enqueue_script( 'ssl-alp-disallow-blocks' );
		}
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
	 * Default image link settings partial.
	 */
	public function default_image_link_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/post/default-image-link-settings-display.php';
	}

	/**
	 * Media types settings partial.
	 */
	public function media_types_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/media/media-types-settings-display.php';
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
	 * Checks if the current user (including not logged in users), can read the specified post.
	 *
	 * @param WP_Post $post The post.
	 *
	 * @return bool|null The permission, or null if the post is invalid.
	 */
	public function current_user_can_read_post( $post ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$post_type_obj = get_post_type_object( $post->post_type );

			if ( is_null( $post_type_obj ) ) {
				return;
			}

			return current_user_can( $post_type_obj->cap->read_post, $post );
		} else {
			// Anonymous user on a public site.
			return 'publish' === get_post_status( $post );
		}
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
			$extra_media_type          = array_map( 'trim', $extra_media_type );
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
	 * Handle when media type reported by PHP's finfo is different from the type inferred from the
	 * filename. This hook changes the media type reported by PHP to match the one defined by the
	 * admin if the file extension is in the list of media types to allow uploads for.
	 *
	 * @param array  $fileinfo File data array (may be empty).
	 * @param string $filepath Full path to the file.
	 * @param string $filename The name of the file (may differ from $filepath).
	 */
	public function check_file_type( $fileinfo, $filepath, $filename ) {
		if ( ! get_site_option( 'ssl_alp_override_media_types' ) ) {
			// Media type overriding disabled.
			return $fileinfo;
		}

		$pieces = explode( '.', $filename );
		$ext    = strtolower( array_pop( $pieces ) );

		$extra_media_types = $this->get_allowed_media_types();

		foreach ( $extra_media_types as $extra_media_type ) {
			if ( $extra_media_type['extension'] === $ext ) {
				// The extension is in the admin-defined override list.
				$fileinfo['ext']             = $ext;
				$fileinfo['type']            = $extra_media_type['media_type'];
				$fileinfo['proper_filename'] = $filename;

				break;
			}
		}

		return $fileinfo;
	}

	/**
	 * Remove unnecessary "Uncategorised" category on posts saved with another, non-default
	 * category.
	 *
	 * This is performed on the `set_object_terms` action as part of `wp_set_object_terms` function
	 * because the `save_post` action, where this would logically be run, is run *before* terms are
	 * set by the block editor (in contrast to the classic editor).
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     An array of object terms.
	 * @param array  $tt_ids    An array of term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function remove_superfluous_uncategorised( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( 'category' !== $taxonomy ) {
			return;
		}

		$post = get_post( $object_id );

		if ( is_null( $post ) || 'post' !== $post->post_type ) {
			return;
		}

		if ( count( $terms ) <= 1 ) {
			return;
		}

		// Get default category.
		$default_category = get_term_by( 'id', get_option( 'default_category' ), $taxonomy );

		// Rebuild list of terms using $tt_ids and not the provided $terms, since
		// $terms can be mixed type and is unsanitised by `wp_set_object_terms`.
		$terms = array();
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );

			if ( $term ) {
				$terms[] = $term;
			}
		}

		if ( ! in_array( $default_category->term_id, wp_list_pluck( $terms, 'term_id' ), true ) ) {
			return;
		}

		// Remove the default category from the post.
		wp_remove_object_terms( $post->ID, $default_category->term_id, 'category' );
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

	/**
	 * Allow core `image_default_link_type` setting to be updated from the
	 * plugin settings page.
	 *
	 * @param array $allowed_options The allowed options for this option page.
	 * @return array The updated allowed options.
	 * @global $option_page
	 */
	public function allow_image_default_link_type_update( $allowed_options ) {
		global $option_page;

		if ( SSL_ALP_SITE_SETTINGS_PAGE === $option_page ) {
			$allowed_options[ SSL_ALP_SITE_SETTINGS_PAGE ][] = 'image_default_link_type';
		}

		return $allowed_options;
	}
}
