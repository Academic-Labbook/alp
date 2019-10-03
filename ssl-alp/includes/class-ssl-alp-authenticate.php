<?php
/**
 * Authentication tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Authentication.
 *
 * Provides the ability for users to set application passwords for REST and feeds, so that when the
 * site is private users may still access their accounts from external applications.
 *
 * Note: application passwords allow users to authenticate themselves when using the REST API or
 * viewing feeds *in addition* to the normal methods. That means that users who have already
 * authenticated themselves using some other method, such as WordPress's built-in cookie
 * authentication, will still be able to view the REST API and feeds.
 */
class SSL_ALP_Authenticate extends SSL_ALP_Module {
	/**
	 * Array for pages excluded from authentication check (admin-ajax.php is handled separately).
	 *
	 * @var array
	 */
	public static $excluded_pages = array(
		'wp-login.php',
		'wp-register.php',
	);

	/**
	 * Applications list table.
	 *
	 * @var SSL_ALP_Authenticate_Applications_List_Table
	 */
	protected $applications_list_table;

	/**
	 * The length of generated application passwords.
	 *
	 * @type integer
	 */
	const APPLICATION_PASSWORD_LENGTH = 30;

	/**
	 * Register styles.
	 */
	public function register_styles() {
		if ( get_option( 'ssl_alp_require_login' ) ) {
			wp_register_style(
				'ssl-alp-login-hide-backlink-css',
				esc_url( SSL_ALP_BASE_URL . 'css/login-hide-backlink.css' ),
				array(),
				$this->get_version()
			);
		}
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_require_login',
			array(
				'type' => 'boolean',
			)
		);

		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_applications',
			array(
				'type' => 'boolean',
			)
		);
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Prevent caching of unauthenticated status.
		$loader->add_filter( 'wp_rest_server_class', $this, 'wp_rest_server_class' );

		// Add support for HTTP authentication when requesting a REST endpoint.
		$loader->add_filter( 'determine_current_user', $this, 'determine_rest_user', 20 );

		// Authenticate application passwords.
		$loader->add_filter( 'authenticate', $this, 'authenticate_application', 10, 3 );

		// Prohibit unauthenticated front end requests.
		$loader->add_action( 'template_redirect', $this, 'prohibit_unauthenticated_front_end_access' );

		// Prohibit unauthenticated feed requests.
		$loader->add_action( 'template_redirect', $this, 'prohibit_unauthenticated_feed_access' );

		// Prohibit unauthenticated admin AJAX requests.
		$loader->add_action( 'admin_init', $this, 'prohibit_unauthenticated_ajax_access' );

		// Prohibit unauthenticated REST requests.
		$loader->add_action( 'rest_authentication_errors', $this, 'prohibit_unauthenticated_rest_access', 10, 1 );

		// Disable XML-RPC interface.
		$loader->add_action( 'xmlrpc_enabled', $this, '__return_false' );

		// Add admin applications page.
		$loader->add_action( 'admin_menu', $this, 'add_applications_page' );

		// Save admin applications table per page option.
		$loader->add_filter( 'set-screen-option', $this, 'save_applications_per_page_option', 10, 3 );

		// Handle submitted application form data.
		$loader->add_action( 'admin_post_ssl-alp-add-application', $this, 'handle_add_application_form' );
		$loader->add_action( 'admin_post_ssl-alp-revoke-application', $this, 'handle_revoke_application_form' );

		// Handle admin notices.
		$loader->add_action( 'admin_notices', $this, 'print_admin_notices' );
	}

	/**
	 * Enqueue styles in the login header.
	 */
	public function enqueue_login_styles() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		// Remove "Back to [Blog]" from login page if login is required.
		// Use CSS to hide it as there is no hook for this link.
		wp_enqueue_style( 'ssl-alp-login-hide-backlink-css' );
	}

	/**
	 * Prevent caching of unauthenticated status.
	 *
	 * We don't actually care about the `wp_rest_server_class` filter, it just
	 * happens right after the constant we do care about is defined.
	 */
	public function wp_rest_server_class( $class ) {
		global $current_user;

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && is_a( $current_user, 'WP_User' ) && 0 === $current_user->ID ) {
			/*
			 * For authentication to work, we need to remove the cached lack of a current user, so
			 * the next time it checks, we can detect that this is a rest api request and allow our
			 * override to happen.  This is because the constant is defined later than the first get
			 * current user call may run.
			 */
			$current_user = null;
		}

		return $class;
	}

	/**
	 * Add applications page to admin menu.
	 */
	public function add_applications_page() {
		if ( ! get_option( 'ssl_alp_enable_applications' ) ) {
			return;
		}

		$hook_suffix = add_users_page(
			__( 'Applications', 'ssl-alp' ),
			__( 'Applications', 'ssl-alp' ),
			'read',
			SSL_ALP_APPLICATIONS_MENU_SLUG,
			array( $this, 'output_admin_applications_page' )
		);

		if ( $hook_suffix ) {
			// Add callback for loading the page.
			add_action( "load-{$hook_suffix}", array( $this, 'load_applications_page_screen_options' ) );
		}
	}

	/**
	 * Save applications per page screen option when saved by the user.
	 *
	 * @param bool   $keep   Whether to save or skip saving the screen option value. Default false.
	 * @param string $option The option name.
	 * @param int    $value  The number of rows to use.
	 */
	public function save_applications_per_page_option( $keep, $option, $value ) {
		if ( 'ssl_alp_applications_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Load application passwords page screen options.
	 */
	public function load_applications_page_screen_options() {
		$arguments = array(
			'label'   => __( 'Applications Per Page', 'ssl-alp' ),
			'default' => 20,
			'option'  => 'ssl_alp_applications_per_page',
		);

		add_screen_option( 'per_page', $arguments );

		/*
		 * Instantiate the application passwords list table. Creating the instance here allow the
		 * core WP_List_Table class to automatically load the table columns in the screen options
		 * panel.
		 */
		$this->applications_list_table = new SSL_ALP_Authenticate_Applications_List_Table();
	}

	/**
	 * Callback function for the admin revisions page.
	 */
	public function output_admin_applications_page() {
		// Check user has permissions.
		if ( ! current_user_can( 'read' ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'ssl-alp' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to set application passwords.', 'ssl-alp' ) . '</p>',
				403
			);
		}

		$applications = array();

		// Create array with unique slugs as keys.
		foreach ( $this->get_user_applications() as $application ) {
			$slug                  = $this->generate_application_slug( $application );
			$application['slug']   = $slug;
			$applications[ $slug ] = $application;
		}

		// Prepare application passwords.
		$this->applications_list_table->items = $applications;
		$this->applications_list_table->prepare_items();

		// Render applications page.
		require_once SSL_ALP_BASE_DIR . 'partials/admin/users/applications/display.php';
	}

	/**
	 * Handle new application form submissions.
	 */
	public function handle_add_application_form() {
		if ( isset( $_REQUEST['action'] ) && 'ssl-alp-add-application' === $_REQUEST['action'] ) {
			// User has submitted the form, verify the nonce.
			check_admin_referer( 'ssl-alp-add-application', 'ssl_alp_add_application_nonce' );

			$application_name = sanitize_text_field( $_REQUEST['application_name'] );

			if ( $this->create_new_application( $application_name ) ) {
				$this->redirect( array( 'message' => 'ssl_alp_add_success' ) );
			} else {
				$this->redirect( array( 'message' => 'ssl_alp_add_failure' ) );
			}
		}
	}

	/**
	 * Handle revoke application form submissions.
	 */
	public function handle_revoke_application_form() {
		if ( isset( $_REQUEST['action'] ) && 'ssl-alp-revoke-application' === $_REQUEST['action'] ) {
			// User has submitted the form, verify the nonce.
			check_admin_referer( 'ssl-alp-manage-applications', 'ssl_alp_manage_applications_nonce' );

			$applications = (array) $_REQUEST['ssl_alp_applications'];

			foreach ( $applications as $application ) {
				$this->delete_user_application( $application );
			}

			$this->redirect( array( 'message' => 'ssl_alp_revoke_success' ) );
		}
	}

	/**
	 * Handle admin notices.
	 */
	public function print_admin_notices() {
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		switch ( $_GET['message'] ) {
			case 'ssl_alp_add_success':
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>' . esc_html__( 'Application added.', 'ssl-alp' ) . '</p>';
				echo '</div>';
				break;
			case 'ssl_alp_add_failure':
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . esc_html__( 'Application name invalid or already in use.', 'ssl-alp' ) . '</p>';
				echo '</div>';
				break;
			case 'ssl_alp_revoke_success':
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>' . esc_html__( 'Application(s) revoked.', 'ssl-alp' ) . '</p>';
				echo '</div>';
				break;
		}
	}

	private function redirect( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'page' => SSL_ALP_APPLICATIONS_MENU_SLUG,
			)
		);

		wp_redirect( admin_url( add_query_arg( $args, 'users.php' ) ) );

		exit;
	}

	/**
	 * Get URL which will allow an application to be revoked.
	 *
	 * @param string $application Application to get revoke URL for.
	 */
	public function get_revoke_url( $application ) {
		return sprintf(
			'<a href="%1$s" class="ssl-alp-application-revoke" aria-label="$2$s">%3$s</a>',
			wp_nonce_url( "admin-post.php?&amp;action=ssl-alp-revoke-application&amp;ssl_alp_applications[]={$application['slug']}", 'ssl-alp-manage-applications', 'ssl_alp_manage_applications_nonce' ),
			esc_attr(
				sprintf(
					/* translators: application name to revoke */
					__( 'Revoke &#8220;%s&#8221;', 'ssl-alp' ),
					$application['name']
				)
			),
			esc_html__( 'Revoke', 'ssl-alp' )
		);
	}

	/**
	 * Check if the current visitor is logged in and has read permission.
	 */
	public function user_authenticated() {
		// Also checks if multisite users can read this particular site.
		return is_user_logged_in() && current_user_can( 'read' );
	}

	/**
	 * Get the request type.
	 */
	public function get_request_type() {
		if ( is_feed() ) {
			return 'feed';
		}

		if ( ! isset( $GLOBALS['pagenow'] ) ) {
			// No page set, so assume normal authentication is needed.
			return 'front-end';
		}

		// Current page.
		$page = $GLOBALS['pagenow'];

		if ( 'admin-ajax.php' === $page ) {
			// This is an AJAX request.
			$action = wp_unslash( $_REQUEST['action'] );

			return 'admin-ajax';
		}

		// Default authentication procedure.
		return 'front-end';
	}

	/**
	 * Generate a unique repeateable slug from the application data.
	 *
	 * @param array $data Application data (as stored in user meta).
	 * @return string
	 */
	public function generate_application_slug( $data ) {
		$concat = $data['name'] . '|' . $data['password'] . '|' . $data['created'];
		$hash   = md5( $concat );
		return substr( $hash, 0, 12 );
	}

	/**
	 * Get user's application passwords.
	 *
	 * @param int $user_id|null User ID. If null, the current user is used.
	 * @return array|null
	 */
	public function get_user_applications( $user_id = null ) {
		if ( ! get_option( 'ssl_alp_enable_applications' ) ) {
			return;
		}

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$applications = get_user_meta( $user_id, 'ssl_alp_applications', true );

		if ( ! is_array( $applications ) ) {
			$applications = array();
		}

		return $applications;
	}

	/**
	 * Generate a new application password.
	 *
	 * @param string   $name    Password name.
	 * @param int|null $user_id User ID. If null, defaults to current user.
	 * @return bool True if the password was set, false if not.
	 */
	public function create_new_application( $application, $user_id = null ) {
		if ( 3 > strlen( $application ) || 30 < strlen( $application ) ) {
			// Application name too short or too long.
			return false;
		}

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$new_password = wp_generate_password( self::APPLICATION_PASSWORD_LENGTH, false );

		$new_item = array(
			'name'      => $application,
			'password'  => $new_password,
			'created'   => time(),
			'last_used' => null,
			'last_ip'   => null,
		);

		$applications = $this->get_user_applications( $user_id );

		if ( ! $applications ) {
			$applications = array();
		}

		if ( in_array( $application, wp_list_pluck( $applications, 'name' ), true ) ) {
			// Duplicate application.
			return false;
		}

		$applications[] = $new_item;

		return $this->set_user_applications( $applications, $user_id );
	}

	/**
	 * Delete a specified application.
	 *
	 * @param string   $slug The generated slug of the password to be deleted.
	 * @param int|null $user_id User ID. If null, defaults to current user.
	 * @return bool Whether the password was successfully found and deleted.
	 */
	public function delete_user_application( $slug, $user_id = null ) {
		$applications = $this->get_user_applications( $user_id );

		foreach ( $applications as $key => $item ) {
			if ( $this->generate_application_slug( $item ) === $slug ) {
				unset( $applications[ $key ] );
				$this->set_user_applications( $applications, $user_id );
				return true;
			}
		}

		// Specified application not found.
		return false;
	}

	/**
	 * Set a users applications.
	 *
	 * @param array    $applications Applications.
	 * @param int|null $user_id      User ID. If null, defaults to current user.
	 *
	 * @return bool
	 */
	public function set_user_applications( $applications, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return (bool) update_user_meta( $user_id, 'ssl_alp_applications', $applications );
	}

	/**
	 * Determine REST request user.
	 *
	 * @param $input_user
	 *
	 * @return WP_User|bool
	 */
	public function determine_rest_user( $input_user ) {
		if ( ! get_option( 'ssl_alp_enable_applications' ) ) {
			return $input_user;
		}

		if ( ! defined( 'REST_REQUEST' ) ) {
			// Only handle REST requests.
			return $input_user;
		}

		// Don't authenticate twice
		if ( ! empty( $input_user ) ) {
			return $input_user;
		}

		$user = $this->check_http_auth( $input_user );

		if ( is_a( $user, 'WP_User' ) ) {
			return $user->ID;
		}

		// If it wasn't a user what got returned, just pass on what we had received originally.
		return $input_user;
	}

	private function check_http_auth( $input_user ) {
		// Check that we're trying to authenticate
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $input_user;
		}

		return $this->authenticate_application( $input_user, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
	}

	/**
	 * Authenticate application passwords.
	 *
	 * @param WP_User $input_user User to authenticate.
	 * @param string  $username   User login.
	 * @param string  $password   User password.
	 *
	 * @return mixed
	 */
	public function authenticate_application( $input_user, $username, $password ) {
		if ( ! get_option( 'ssl_alp_enable_applications' ) ) {
			return $input_user;
		}

		$is_application = defined( 'REST_REQUEST' ) || is_feed();

		if ( ! $is_application ) {
			// Only attempt to authenticate applications.
			return $input_user;
		}

		$user = $this->authenticate_application_password( $username, $password );

		if ( is_a( $user, 'WP_User' ) ) {
			// User successfully authenticated.
			return $user;
		}

		// By default, return what we've been passed.
		return $input_user;
	}

	private function authenticate_application_password( $username, $password ) {
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {
			return false;
		}

		$applications = get_user_meta( $user->ID, 'ssl_alp_applications', true );

		if ( empty( $applications ) ) {
			return false;
		}

		/*
		 * Strip out anything non-alphanumeric. This is so passwords can be used with
		 * or without spaces to indicate the groupings for readability.
		 *
		 * Generated application passwords are exclusively alphanumeric.
		 */
		$password = preg_replace( '/[^a-z\d]/i', '', $password );

		foreach ( $applications as $key => $application ) {
			if ( $password === $application['password'] ) {
				// This application password matches.
				$application['last_used'] = time();
				$application['last_ip']   = $_SERVER['REMOTE_ADDR'];

				// Update meta.
				$applications[ $key ] = $application;
				update_user_meta( $user->ID, 'ssl_alp_applications', $applications );

				// Return the authenticated user.
				return $user;
			}
		}

		return false;
	}

	private function redirect_to_login() {
		// Tell the user's browser not to cache this page.
		nocache_headers();

		// Redirect to the login URL.
		wp_safe_redirect(
			// Don't force password entry for network users that aren't members here yet.
			wp_login_url( wp_unslash( $_SERVER['REQUEST_URI'] ), is_multisite() ),
			302 // "Found" redirect.
		);

		exit;
	}

	/**
	 * Redirect to login page if user is not logged in when they should be.
	 */
	public function prohibit_unauthenticated_front_end_access() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		if ( 'front-end' !== $this->get_request_type() ) {
			// Not a front end request.
			return;
		}

		// Current page.
		$page = $GLOBALS['pagenow'];

		if ( in_array( $page, self::$excluded_pages, true ) ) {
			// No authentication required.
			return;
		}

		// Check if the user is logged in or has rights on the current site or network.
		if ( ! $this->user_authenticated() ) {
			$this->redirect_to_login();
		}
	}

	/**
	 * Require application password for feeds.
	 */
	public function prohibit_unauthenticated_feed_access() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		if ( 'feed' !== $this->get_request_type() ) {
			// Not a front end request.
			return;
		}

		// Check if the user is logged in or has rights on the current site or network.
		if ( ! $this->user_authenticated() ) {
			// Check for submitted basic authentication credentials.
			$user = $this->check_http_auth( null );

			if ( is_a( $user, 'WP_User' ) ) {
				// Successfully authenticated.
				return;
			}

			$this->redirect_to_login();
		}
	}

	/**
	 * Prohibit access to admin AJAX without authentication.
	 */
	public function prohibit_unauthenticated_ajax_access() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		if ( 'admin-ajax' !== $this->get_request_type() ) {
			// Not an AJAX request.
			return;
		}

		// Check if user is logged in already and has read permission.
		if ( ! $this->user_authenticated() ) {
			// "Forbidden" HTTP code.
			$this->exit_403();
		}
	}

	/**
	 * Prohibit access to REST API without authentication.
	 *
	 * @param WP_Error|null|bool $error Current error.
	 * @return WP_Error|null|bool Updated error.
	 */
	public function prohibit_unauthenticated_rest_access( $error ) {
		if ( get_option( 'ssl_alp_require_login' ) && ! $this->user_authenticated() ) {
			// Login is required and the user is not authenticated, so update the error.
			$error = new WP_Error(
				'rest_cannot_access',
				esc_html__( 'Only authenticated users can access the REST API.', 'ssl-alp' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $error;
	}

	/**
	 * Exit showing "Forbidden".
	 */
	public static function exit_403() {
		$protocol = 'HTTP/1.1' === $_SERVER['SERVER_PROTOCOL'] ? 'HTTP/1.1' : 'HTTP/1.0';

		header( $protocol . ' 403 Forbidden' );

		// Show message.
		exit( '{"error": {"code": 403, "message": "Forbidden"}' );
	}
}
