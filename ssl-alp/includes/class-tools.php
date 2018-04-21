<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Admin tools functionality
 */
class SSL_ALP_Tools extends SSL_ALP_Module {
	/**
	 * Overrideable settings and their overridden values
	 * 
	 * (Note: soome settings are set to null if switched off, others to boolean false.)
	 */
	protected $overrideable_settings = array(
		'default_pingback_flag'	=>	null, // attempt to notify other blogs
		'default_ping_status'	=>	'closed', // allow link notifications from other blogs
		'comment_registration'	=>	1, // require registration for comments
		'comment_whitelist'		=>	null, // comment author must have a previously approved comment
		'comment_max_links'		=>	0, // hold comment in queue if it contains more than x links
		'blog_public'			=>	0, // disencourage search engine indexing
		'rss_use_excerpt'		=>	0, // show full text in RSS feeds
	);

	/**
	 * Custom user roles, should the admin wish to set these
	 */
	protected $alp_user_roles = array(
		'administrator',
		'researcher',
		'intern',
		'subscriber',
		'excluded'
	);

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// admin tools page
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
			SSL_ALP_TOOLS_MENU_SLUG,
			array( $this, 'output_admin_tools_page' )
		);
    }

	/**
	 * Callback function for the tools page.
	 */
	public function output_admin_tools_page() {
		// check user has permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to use the ALP admin tools.' ) . '</p>',
				403
			);
		}

		// default action confirmed state
		$role_conversion_unconfirmed = false;

		// default completed action states
		$alpine_active = false;
		$override_core_settings_completed = false;
		$role_conversion_completed = false;
		$rebuild_references_completed = false;

		/**
		 * Check active theme
		 */

		$theme = wp_get_theme();
		
		if ( 'Alpine' == $theme->name || 'Alpine' == $theme->parent_theme ) {
    		$alpine_active = true;
		}

		/**
		 * Handle manage core settings form
		 */

		// check if core settings are all overridden
		$core_settings_overridden = $this->_core_settings_overriden();

		// require login setting
		$require_login = get_option( 'ssl_alp_require_login' );

		if ( ! $core_settings_overridden && $require_login ) {
			if ( array_key_exists( 'ssl_alp_manage_core_settings_submitted', $_POST ) && (bool) $_POST['ssl_alp_manage_core_settings_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-manage-core-settings', 'ssl_alp_manage_core_settings_nonce' );

				// do action
				$this->_override_core_settings();

				$override_core_settings_completed = true;

				// update override flag
				$core_settings_overridden = $this->_core_settings_overriden();
			}
		}

		/**
		 * Handle convert roles form
		 */

		// check if user roles can be changed
		$roles_convertable = $this->_roles_are_default();

		// check if user roles have been changed already
		$roles_converted = $this->_roles_converted();

		if ( $roles_convertable && ! $roles_converted ) {
			if ( array_key_exists( 'ssl_alp_convert_role_submitted', $_POST ) && (bool) $_POST['ssl_alp_convert_role_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-convert-user-roles', 'ssl_alp_convert_user_roles_nonce' );

				// check they checked the checkbox
				if ( array_key_exists( 'ssl_alp_convert_role_confirm', $_POST ) && (bool) $_POST['ssl_alp_convert_role_confirm'] ) {
					// do action
					$this->_convert_roles();

					$role_conversion_completed = true;

					// update roles changed flag
					$roles_converted = $this->_roles_converted();
				} else {
					$role_conversion_unconfirmed = true;
				}
			}
		}

		/**
		 * Handle rebuild references form
		 */

		// check if cross-references are enabled
		$references_enabled = get_option( 'ssl_alp_enable_crossreferences' );

		if ( $references_enabled ) {
			if ( array_key_exists( 'ssl_alp_rebuild_references_submitted', $_POST ) && (bool) $_POST['ssl_alp_rebuild_references_submitted'] ) {
				// user has submitted the form

				// verify the nonce
				check_admin_referer( 'ssl-alp-rebuild-references', 'ssl_alp_rebuild_references_nonce' );

				// do action
				$this->_rebuild_references();

				$rebuild_references_completed = true;
			}
		}

		require_once SSL_ALP_BASE_DIR . 'partials/admin/tools/display.php';
	}

	/**
	 * Checks if the overrideable settings are already overriden
	 */
	private function _core_settings_overriden() {
		$current_settings = array_map( 'get_option', array_keys( $this->overrideable_settings ) );

		return $current_settings == array_values( $this->overrideable_settings );
	}

	/**
	 * Overrides core settings to ALP recommended defaults
	 */
	private function _override_core_settings() {
		foreach ( $this->overrideable_settings as $setting => $value ) {
			update_option( $setting, $value );
		}
	}

	/**
	 * Checks if the user roles defined in this WordPress installation are set to their
	 * defaults, and therefore changeable by the _change_user_roles() tool.
	 */
	private function _roles_are_default() {
		global $wp_roles;

		// default role names
		// see populate_roles() in wp-admin/includes/schema.php
		$default_role_names = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber'
		);

		// if the WP_Roles settings are the same as above, the default roles are present
		return array_keys( $wp_roles->role_names ) == $default_role_names;
	}

	/**
	 * Checks if the user roles have been converted already to the ALP varieties
	 */
	private function _roles_converted() {
		global $wp_roles;

		// if there is no difference between the WP_Roles settings and the custom ALP roles,
		// they must have been converted already
		return empty( array_diff( array_keys( $wp_roles->role_names ), array_values( $this->alp_user_roles ) ) );
	}

	/**
	 * Changes the default user roles.
	 */
	private function _convert_roles() {
		// first of all, create the new roles
		$this->_create_role_copy( 'researcher', 'Researcher', 'editor' );
		$this->_create_role_copy( 'intern', 'Intern', 'author' );
		$this->_create_role( 'excluded', 'Excluded', array() );

		// move user roles
		$this->_swap_user_roles( 'editor', 'researcher' );
		$this->_swap_user_roles( 'author', 'intern' );
		$this->_swap_user_roles( 'contributor', 'subscriber' );

		// delete roles
		$this->_delete_role( 'editor' );
		$this->_delete_role( 'author' );
		$this->_delete_role( 'contributor' );

		// set default_role option to 'Researcher'
		update_option( 'default_role', 'researcher' );
	}

	/**
	 * Create a new role, copying capabilities from another
	 */
	private function _create_role_copy( $new_role, $display_name, $source_role ) {
		// get source role
		$source_role = get_role( $source_role );

		$this->_create_role( $new_role, $display_name, $source_role->capabilities );
	}

	/**
 	 * Create a new role with the specified permissions
 	 */
	private function _create_role( $new_role, $display_name, $permissions ) {
		if( ! get_role( $new_role ) ) {
			add_role( $new_role, $display_name, $permissions );
		}
	}

	private function _swap_user_roles( $old_role, $new_role ) {
		// get users with old role
		$users = get_users( array( 'role' => $old_role ) );

		// swap to new role
		foreach ( $users as $user ) {
			$user->remove_role( $old_role );
			$user->add_role( $new_role );
		}
	}

	private function _delete_role( $role ) {
		if( get_role( $role ) ) {
			remove_role( $role );
		}
	}

	/**
	 * Rebuild post/page references
	 */
	private function _rebuild_references() {
		global $ssl_alp;

		// pass call to reference object
		return $ssl_alp->references->rebuild_references();
	}
}
