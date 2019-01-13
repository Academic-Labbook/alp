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
	 * Array for actions excluded from authentication using wp-ajax.
	 *
	 * @var array
	 */
	public static $exclude_ajax_actions = array();

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
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			// Extra authentication features disabled.
			return;
		}

		// Get authentication method.
		$authenticate_method = $this->get_authenticate_method();

		// Use authentication method to decide what needs done next.
		if ( 'redirect' === $authenticate_method ) {
			/**
			 * Hook just before the template is loaded, but after everything else is decided, in
			 * case we need to redirect the user to login.
			 */
			$loader->add_action( 'template_redirect', $this, $authenticate_method );
		} elseif ( 'authenticate_ajax' === $authenticate_method ) {
			$loader->add_action( 'admin_init', $this, $authenticate_method );
		}

		// Authenticate REST requests.
		$loader->add_action( 'rest_authentication_errors', $this, 'authenticate_rest_api' );

		// Disable XML-RPC interface.
		$loader->add_action( 'xmlrpc_enabled', $this, 'disable_xmlrpc', 10, 0 );

		// Remove "Back to [Blog]" from login page if login is required.
		$loader->add_action( 'login_enqueue_scripts', $this, 'remove_back_link' );
	}

	/**
	 * Remove "Back to [Blog]" link from login page if login is required.
	 */
	public function remove_back_link() {
		// Use CSS to hide it as there is no hook for this link.
		wp_enqueue_style( 'ssl-alp-login-hide-backlink-css', SSL_ALP_BASE_URL . 'css/login-hide-backlink.css', array(), $this->get_version(), 'all' );
	}

	/**
	 * Get the method to authenticate or null if no authentication is required.
	 */
	public function get_authenticate_method() {
		if ( ! isset( $GLOBALS['pagenow'] ) ) {
			// No page set, so assume normal authentication is needed.
			return 'redirect';
		}

		// Current page.
		$page = $GLOBALS['pagenow'];

		if ( in_array( $page, self::$excluded_pages, true ) ) {
			// No authentication required.
			return null;
		}

		if ( 'admin-ajax.php' === $page ) {
			// This is an AJAX request.
			$action = wp_unslash( $_REQUEST['action'] );

			// Check if the action is allowed through without authentication.
			if ( isset( $action ) && in_array( $action, self::$exclude_ajax_actions, false ) ) {
				return null;
			}

			// Otherwise authenticate the request.
			return 'authenticate_ajax';
		}

		// Default authentication procedure.
		return 'redirect';
	}

	/**
	 * Redirect to login page if user is not logged in.
	 */
	public function redirect() {
		if ( is_feed() ) {
			// Handle request for a syndication feed.
			$this->http_auth_feed();

			return;
		}

		// Check if the user is logged in or has rights on the current site or network.
		if ( ! $this->authenticate_user() ) {
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
	}

	/**
	 * Authenticate users requesting feeds via HTTP Basic auth.
	 */
	protected function http_auth_feed() {
		// Create HTTP authenticator.
		/* translators: 1: blog name */
		$auth = new HTTP_Auth( sprintf( __( '%s feed', 'ssl-alp' ), get_bloginfo( 'name' ) ) );

		// Get username from authentication form.
		$credentials = $auth->get_credentials();

		// Try to authenticate.
		$user = wp_authenticate( $credentials['name'], $credentials['pass'] );

		if ( ! is_a( $user, 'WP_User' ) || ! user_can( $user, 'read' ) ) {
			// User was not authenticated for this site.
			$auth->auth_required();
		}
	}

	/**
	 * Check if the current visitor is logged in and has read permission.
	 */
	public function authenticate_user() {
		// Also checks if multisite users can read this particular site.
		return is_user_logged_in() && current_user_can( 'read' );
	}

	/**
	 * Authenticate AJAX request.
	 */
	public function authenticate_ajax() {
		// Check if user is logged in already and has read permission.
		if ( ! $this->authenticate_user() ) {
			// "Forbidden" HTTP code.
			$this->exit_403();
		}
	}

	/**
	 * Exit showing "Forbidden".
	 */
	public static function exit_403() {
		$protocol = 'HTTP/1.1' === $_SERVER['SERVER_PROTOCOL'] ? 'HTTP/1.1' : 'HTTP/1.0';

		header( $protocol . ' 403 Forbidden' );

		// Show message.
		exit( '<h1>403 Forbidden</h1>' );
	}

	/**
	 * Authenticate REST access.
	 */
	public function authenticate_rest_api() {
		if ( ! $this->authenticate_user() ) {
			return new WP_Error(
				'rest_cannot_access',
				esc_attr__( 'Only authenticated users can access the REST API.', 'ssl-alp' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
	}

	/**
	 * Disable XML-PRC interface.
	 */
	public function disable_xmlrpc() {
		// Disables XML-RPC.
		return false;
	}
}
