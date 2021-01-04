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
			'ssl_alp_allow_application_password_feed_access',
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

		// Allow feeds to be accessed using application passwords.
		$loader->add_filter( 'application_password_is_api_request', $this, 'allow_feed_access_with_application_password' );

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
	 * Check if the current visitor is logged in and has read permission,
	 * otherwise trigger a redirect to login or show a no permission error.
	 */
	public function maybe_redirect_or_print_no_permission() {
		if ( ! is_user_logged_in() ) {
			$this->redirect_to_login();
		}

		// Check if multisite users can read this particular site.
		if ( ! current_user_can( 'read' ) ) {
			$this->print_no_permission();
		}
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
			return 'admin-ajax';
		}

		// Default authentication procedure.
		return 'front-end';
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

		// Redirect to login or show an error to unauthenticated users.
		if ( ! is_user_logged_in() ) {
			$this->redirect_to_login();
		} elseif ( ! current_user_can( 'read' ) ) {
			$this->show_no_permission();
		}
	}

	/**
	 * Require authentication for feeds.
	 */
	public function prohibit_unauthenticated_feed_access() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		if ( 'feed' !== $this->get_request_type() ) {
			// Not a feed request.
			return;
		}

		if ( ! is_user_logged_in() ) {
			if ( get_option( 'ssl_alp_allow_application_password_feed_access' ) ) {
				// Explicitly check application passwords submitted via HTTP basic auth, since they
				// don't normally get checked on the front end. Ideally the filter
				// 'application_password_is_api_request' would be all that is needed but this is not
				// called by the time the current user is detected and cached on the front end.
				if ( wp_validate_application_password( false ) ) {
					// Valid application password; allow the request to go ahead.
					return;
				}
			}

			$this->show_no_permission();
		}
	}

	/**
	 * Require authentication for admin AJAX.
	 */
	public function prohibit_unauthenticated_ajax_access() {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			return;
		}

		if ( 'admin-ajax' !== $this->get_request_type() ) {
			// Not an AJAX request.
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			$this->show_no_permission();
		}
	}

	/**
	 * Require authentication for REST API.
	 *
	 * @param WP_Error|null|bool $error Current error.
	 * @return WP_Error|null|bool Updated error.
	 */
	public function prohibit_unauthenticated_rest_access( $error ) {
		if ( ! get_option( 'ssl_alp_require_login' ) ) {
			// Don't do anything.
			return $error;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			// User is not authenticated, so update the error.
			$error = new WP_Error(
				'rest_cannot_access',
				esc_html__( 'Only authenticated users can access the REST API.', 'ssl-alp' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $error;
	}

	/**
	 * Authenticate application passwords in feed request contexts.
	 *
	 * On its own this filter doesn't enable feed access using application passwords; the password
	 * check has to also be triggered when accessing the front-end of the site since it's not done
	 * by default in WordPress (only when accessing the REST API). This is done by
	 * 'prohibit_unauthenticated_feed_access'. This filter just allows the check made there to use
	 * the HTTP basic auth credentials specified in the front end request.
	 *
	 * @param bool $allow Current allowed status.
	 * @return bool Updated allowed status.
	 */
	public function allow_feed_access_with_application_password( $allow ) {
		if ( ! get_option( 'ssl_alp_require_login' ) || ! get_option( 'ssl_alp_allow_application_password_feed_access' ) ) {
			// Nothing to do.
			return $allow;
		}

		if ( is_feed() ) {
			return true;
		}
	}

	/**
	 * Redirect user to login screen.
	 */
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
	 * Show a no permission error.
	 */
	private function show_no_permission() {
		// Handles various cases depending on request type (HTML, XML, JSON, etc.).
		wp_die(
			esc_html__( 'You need a higher level of permission.', 'ssl-alp' ),
			403
		);
	}
}
