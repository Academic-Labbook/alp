<?php
/**
 * Plugin tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Admin tools functionality
 */
class SSL_ALP_Tools extends SSL_ALP_Module {
	/**
	 * Overrideable settings and their overridden values
	 *
	 * Note: soome settings are set to null if switched off, others to boolean false.
	 *
	 * @var array
	 */
	protected $overrideable_settings = array(
		'default_pingback_flag' => null, // Attempt to notify other blogs.
		'default_ping_status'   => 'closed', // Allow link notifications from other blogs.
		'comment_registration'  => 1, // Require registration for comments.
		'comment_whitelist'     => null, // Comment author must have a previously approved comment.
		'comment_max_links'     => 0, // Hold comment in queue if it contains more than x links.
		'blog_public'           => 0, // Disencourage search engine indexing.
		'rss_use_excerpt'       => 0, // Show full text in RSS feeds.
	);

	/**
	 * Custom user roles, should the admin wish to set these.
	 *
	 * @var array
	 */
	protected $alp_user_roles = array(
		'administrator',
		'researcher',
		'intern',
		'subscriber',
		'excluded',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Admin tools page.
		$loader->add_action( 'admin_menu', $this, 'add_tools_page' );
	}

	/**
	 * Register the tools page.
	 */
	public function add_tools_page() {
		add_management_page(
			__( 'Academic Labbook Tools', 'ssl-alp' ),
			__( 'Academic Labbook', 'ssl-alp' ),
			'manage_options',
			SSL_ALP_SITE_TOOLS_MENU_SLUG,
			array( $this, 'output_admin_tools_page' )
		);
	}

	/**
	 * Callback function for the tools page.
	 */
	public function output_admin_tools_page() {
		// Check user has permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to use the ALP admin tools.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		// Default action confirmed state.
		$role_conversion_unconfirmed = false;

		// Default completed action states.
		$supported_theme_installed        = false;
		$supported_theme_active           = false;
		$pretty_permalinks_enabled        = false;
		$override_core_settings_completed = false;
		$role_conversion_completed        = false;
		$rebuild_references_completed     = false;
		$rebuild_coauthors_completed      = false;

		/**
		 * Check active theme.
		 */

		$themes      = wp_get_themes();
		$theme_names = wp_list_pluck( $themes, 'name' );

		if ( in_array( 'Labbook', $theme_names, true ) ) {
			$supported_theme_installed = true;

			// Get active theme.
			$active_theme = wp_get_theme();

			if ( 'Labbook' === $active_theme->name || 'Labbook' === $active_theme->parent_theme ) {
				$supported_theme_active = true;
			}
		}

		/**
		 * Check pretty permalinks.
		 */

		$pretty_permalinks_enabled = ! empty( get_option( 'permalink_structure' ) );

		/**
		 * Handle manage core settings form.
		 */

		// Check if core settings are all overridden.
		$core_settings_overridden = $this->core_settings_overridden();

		// Require login setting.
		$require_login = get_option( 'ssl_alp_require_login' );

		if ( ! $core_settings_overridden && $require_login ) {
			if ( array_key_exists( 'ssl_alp_manage_core_settings_submitted', $_POST ) && (bool) $_POST['ssl_alp_manage_core_settings_submitted'] ) {
				// User has submitted the form, verify the nonce.
				check_admin_referer( 'ssl-alp-manage-core-settings', 'ssl_alp_manage_core_settings_nonce' );

				// Do action.
				$this->override_core_settings();

				$override_core_settings_completed = true;

				// Update override flag.
				$core_settings_overridden = $this->core_settings_overridden();
			}
		}

		/**
		 * Handle convert roles form.
		 */

		// Check if user roles can be changed.
		$roles_convertable = $this->roles_are_default();

		// Check if user roles have been changed already.
		$roles_converted = $this->roles_converted();

		if ( $roles_convertable && ! $roles_converted ) {
			if ( array_key_exists( 'ssl_alp_convert_role_submitted', $_POST ) && (bool) $_POST['ssl_alp_convert_role_submitted'] ) {
				// User has submitted the form, verify the nonce.
				check_admin_referer( 'ssl-alp-convert-user-roles', 'ssl_alp_convert_user_roles_nonce' );

				// Check they checked the checkbox.
				if ( array_key_exists( 'ssl_alp_convert_role_confirm', $_POST ) && (bool) $_POST['ssl_alp_convert_role_confirm'] ) {
					// Do action.
					$this->convert_roles();

					$role_conversion_completed = true;

					// Update roles changed flag.
					$roles_converted = $this->roles_converted();
				} else {
					$role_conversion_unconfirmed = true;
				}
			}
		}

		/**
		 * Handle rebuild references form.
		 */

		// Check if cross-references are enabled.
		$references_enabled = get_option( 'ssl_alp_enable_crossreferences' );

		if ( $references_enabled ) {
			if ( array_key_exists( 'ssl_alp_rebuild_references_submitted', $_POST ) && (bool) $_POST['ssl_alp_rebuild_references_submitted'] ) {
				// User has submitted the form, verify the nonce.
				check_admin_referer( 'ssl-alp-rebuild-references', 'ssl_alp_rebuild_references_nonce' );

				// Do action.
				$this->rebuild_references();

				$rebuild_references_completed = true;
			}
		}

		/**
		 * Handle rebuild coauthors.
		 */

		// Check if coauthors are enabled.
		$coauthors_enabled = get_option( 'ssl_alp_allow_multiple_authors' );

		if ( $coauthors_enabled ) {
			if ( array_key_exists( 'ssl_alp_rebuild_coauthors_submitted', $_POST ) && (bool) $_POST['ssl_alp_rebuild_coauthors_submitted'] ) {
				// User has submitted the form.
				// Verify the nonce.
				check_admin_referer( 'ssl-alp-rebuild-coauthors', 'ssl_alp_rebuild_coauthors_nonce' );

				// Do action.
				$this->rebuild_coauthors();

				$rebuild_coauthors_completed = true;
			}
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/tools/display.php';
	}

	/**
	 * Check if the overrideable settings are already overriden.
	 */
	public function core_settings_overridden() {
		$current_settings = array_map( 'get_option', array_keys( $this->overrideable_settings ) );

		return array_values( $this->overrideable_settings ) == $current_settings; // Fuzzy comparison required.
	}

	/**
	 * Override core settings to ALP recommended defaults.
	 */
	public function override_core_settings() {
		foreach ( $this->overrideable_settings as $setting => $value ) {
			update_option( $setting, $value );
		}
	}

	/**
	 * Check if the user roles defined in this WordPress installation are set to their defaults,
	 * and therefore changeable by the convert_user_roles() function.
	 */
	public function roles_are_default() {
		$roles = wp_roles();

		// Default role names. See populate_roles() in wp-admin/includes/schema.php.
		$default_role_names = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
		);

		// Uf the WP_Roles settings are the same as above, the default roles are present.
		return array_keys( $roles->role_names ) === $default_role_names;
	}

	/**
	 * Check if the user roles have been converted already to the ALP varieties.
	 */
	public function roles_converted() {
		$roles = wp_roles();

		// If there is no difference between the WP_Roles settings and the custom ALP roles, they
		// must have been converted already.
		return empty( array_diff( array_keys( $roles->role_names ), array_values( $this->alp_user_roles ) ) );
	}

	/**
	 * Changes the default user roles.
	 */
	public function convert_roles() {
		// First of all, create the new roles.
		$this->create_role_copy( 'researcher', 'Researcher', 'editor' );
		$this->create_role_copy( 'intern', 'Intern', 'author' );
		$this->create_role( 'excluded', 'Excluded', array() );

		// Move user roles.
		$this->swap_user_roles( 'editor', 'researcher' );
		$this->swap_user_roles( 'author', 'intern' );
		$this->swap_user_roles( 'contributor', 'subscriber' );

		// Delete roles.
		$this->delete_role( 'editor' );
		$this->delete_role( 'author' );
		$this->delete_role( 'contributor' );

		// Set default_role option to 'Researcher'.
		update_option( 'default_role', 'researcher' );
	}

	/**
	 * Create a new role, copying capabilities from another.
	 *
	 * @param string $new_role     Role slug.
	 * @param string $display_name Role display name.
	 * @param string $source_role  Role to copy capabilities from.
	 */
	private function create_role_copy( $new_role, $display_name, $source_role ) {
		// Get source role.
		$source_role = get_role( $source_role );

		$this->create_role( $new_role, $display_name, $source_role->capabilities );
	}

	/**
	 * Create a new role with the specified permissions.
	 *
	 * @param string $new_role     Role slug.
	 * @param string $display_name Role display name.
	 * @param array  $permissions  Role permissions.
	 */
	private function create_role( $new_role, $display_name, $permissions ) {
		if ( ! get_role( $new_role ) ) {
			add_role( $new_role, $display_name, $permissions );
		}
	}

	/**
	 * Swap user roles. Users with $old_role are moved to $new_role.
	 *
	 * @param string $old_role Old user role.
	 * @param string $new_role New user role.
	 */
	private function swap_user_roles( $old_role, $new_role ) {
		// Get users with old role.
		$users = get_users( array( 'role' => $old_role ) );

		// Swap to new role.
		foreach ( $users as $user ) {
			$user->remove_role( $old_role );
			$user->add_role( $new_role );
		}
	}

	/**
	 * Delete role.
	 *
	 * @param string $role Role to delete.
	 */
	private function delete_role( $role ) {
		if ( get_role( $role ) ) {
			remove_role( $role );
		}
	}

	/**
	 * Rebuild post/page references.
	 *
	 * @global $ssl_alp
	 */
	private function rebuild_references() {
		global $ssl_alp;

		// Pass call to reference object.
		return $ssl_alp->references->rebuild_references();
	}

	/**
	 * Rebuild coauthor terms.
	 *
	 * @global $ssl_alp
	 */
	private function rebuild_coauthors() {
		global $ssl_alp;

		// Pass call to reference object.
		return $ssl_alp->coauthors->rebuild_coauthors();
	}
}
