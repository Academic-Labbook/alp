<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Authentication
 */
class SSL_ALP_Authenticate extends SSL_ALP_Module {
    /**
	 * Array for pages excluded from authentication check
	 * (admin-ajax.php is handled separately)
	 */
    public static $excluded_pages = array(
        'wp-login.php',
        'wp-register.php'
    );

	/**
	 * Array for actions excluded from authentication using
     * wp-ajax
	 */
	public static $exclude_ajax_actions = array();

	/**
	 * Register settings
	 */
	public function register_settings() {
        register_setting(
            'ssl-alp-admin-options',
            'ssl_alp_require_login',
            array(
                'type'		=>	'boolean',
                'default'	=>	true
            )
        );
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {
        $loader = $this->get_loader();
        
        if ( ! get_option( 'ssl_alp_require_login' ) ) {
            // extra authentication features disabled
            return;
        }

        // get authentication method
        $authenticate_method = $this->get_authenticate_method();

        // use authentication method to decide what needs done next
        if ( 'redirect' === $authenticate_method ) {
            // hook just before the template is loaded, but after everything else is
            // decided, in case we need to redirect the user to login
			$loader->add_action( 'template_redirect', $this, $authenticate_method );
		} elseif ( 'authenticate_ajax' === $authenticate_method ) {
			$loader->add_action( 'admin_init', $this, $authenticate_method );
        }
        
        // authenticate REST requests
        $loader->add_action( 'rest_authentication_errors', $this, 'authenticate_rest_api' );

        // remove "Back to [Blog]" from login page if login is required
		$loader->add_action( 'login_enqueue_scripts', $this, 'remove_back_link' );
    }

	/**
	 * Remove "Back to [Blog]" link from login page if login is required
	 */
	public function remove_back_link() {
        // use CSS to hide it as there is no hook for this link
        wp_enqueue_style( 'ssl-alp-login-hide-backlink-css', SSL_ALP_BASE_URL . 'css/login-hide-backlink.css', array(), $this->get_version(), 'all' );
	}

	/**
	 * get the method to authenticate or null
	 * if no authentication is required
	 */
	public function get_authenticate_method() {
		if ( ! isset( $GLOBALS[ 'pagenow' ] ) ) {
            // no page set, so assume normal authentication is needed
			return 'redirect';
        }
        
		// current page
        $page = $GLOBALS[ 'pagenow' ];
        
		if ( in_array( $page, self::$excluded_pages, true ) ) {
            // no authentication required
			return null;
        }
        
		if ( 'admin-ajax.php' === $page ) {
            // this is an AJAX request
            // check if the action is allowed through without authentication
            if ( isset( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], $this->exclude_ajax_actions, false ) ) {
				return null;
            }
            
            // otherwise authenticate the request
			return 'authenticate_ajax';
        }
        
        // default authenticate
		return 'redirect';
    }

    /**
	 * Redirect to login page if user is not logged in
	 */
	public function redirect() {        
		if ( is_feed() ) {
            // handle request for a syndication feed
            $this->http_auth_feed();
            
			return;
        }
        
		// check if the user is logged in or has rights on the current site or network
		if ( ! $this->authenticate_user() ) {            
            // tell the user's browser not to cache this page
            nocache_headers();
            
            // redirect to the login URL
			wp_redirect(
                // don't force password entry for network users that aren't members here yet
				wp_login_url( $_SERVER[ 'REQUEST_URI' ], is_multisite() ),
				$status = 302 // "Found" redirect
            );
            
            // wp_redirect doesn't exit on its own
			exit();
		}
    }

	/**
	 * authenticate users requesting feeds via HTTP Basic auth
	 */
	protected function http_auth_feed() {
        // create HTTP authenticator
        /* translators: 1: blog name */
        $auth = new HTTP_Auth( sprintf( __( '%s feed', 'ssl-alp' ), get_bloginfo( 'name' ) ) );
        
        // get username from authentication form
        $credentials = $auth->get_credentials();
        
        // try to authenticate
        $user = wp_authenticate( $credentials['name'], $credentials['pass'] );
        
		if ( ! is_a( $user, 'WP_User' ) || ! user_can( $user, 'read' ) ) {
            // user was not authenticated for this site
			$auth->auth_required();
		}
    }

	/**
	 * checks if the current visitor is logged in and has read permission
	 */
	public function authenticate_user() {
        // also checks if multisite users can read this particular site
        return is_user_logged_in() && current_user_can( 'read' );
    }

	public function authenticate_ajax() {
        // check if user is logged in already and has read permission
		if ( ! $this->authenticate_user() ) {
            // "Forbidden" HTTP code
			$this->_exit_403();
		}
    }

	/**
	 * Exit showing "Forbidden"
	 */
	public static function _exit_403() {
        $protocol = 'HTTP/1.1' === $_SERVER[ 'SERVER_PROTOCOL' ] ? 'HTTP/1.1' : 'HTTP/1.0';
        
        header( $protocol . ' 403 Forbidden' );
        
        // show message
		exit( '<h1>403 Forbidden</h1>' );
    }

    /**
	 * Authenticate REST access
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
}

/**
 * Implements Basic HTTP Authentication
 *
 * @author David Naber <kontakt@dnaber.de>
 */
if ( ! class_exists( 'HTTP_Auth' ) ) :
class HTTP_Auth {
    /**
     * Username and password
     */
    protected $credentials = array();

    /**
     * Name of the protected zone
     *
     * @var string
     */
    protected $realm = '';

    /**
     * Constructor
     */
    public function __construct( $realm ) {
        $this->realm = $realm;
        $this->parse_user_input();
    }

    /**
     * Request user authenticates themselves
     */
    protected function parse_user_input() {
        if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
            // PHP has asked user for username and password
            $this->credentials['name'] = $_SERVER['PHP_AUTH_USER'];
            $this->credentials['pass'] = $_SERVER['PHP_AUTH_PW'];
        } else {
            // no authorisation requested yet, so send it
            $this->auth_required();
        }
    }

    /**
     * Get the user's credentials
     */
    public function get_credentials() {
        return $this->credentials;
    }

    /**
     * prints the auth form then exits
     */
    public function auth_required() {
        $protocol = $_SERVER['SERVER_PROTOCOL'];

        if ( 'HTTP/1.1' !== $protocol && 'HTTP/1.0' !== $protocol ) {
            $protocol = 'HTTP/1.0';
        }

        header( 'WWW-Authenticate: Basic realm="' . $this->realm . '"' );
        header( $protocol . ' 401 Unauthorized' );
        echo '<h1>Authentication failed</h1>';

        exit();
    }
}
endif;