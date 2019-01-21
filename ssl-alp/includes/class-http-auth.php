<?php
/**
 * HTTP authentication tools.
 *
 * @package ssl-alp
 */

if ( ! class_exists( 'HTTP_Auth' ) ) :
	/**
	 * Implements Basic HTTP Authentication.
	 *
	 * Based on original version by David Naber <kontakt@dnaber.de>.
	 */
	class HTTP_Auth {
		/**
		 * Username and password.
		 *
		 * @var array
		 */
		protected $credentials = array();

		/**
		 * Name of the protected zone.
		 *
		 * @var string
		 */
		protected $realm = '';

		/**
		 * Constructor.
		 *
		 * @param string $realm Authentication realm.
		 */
		public function __construct( $realm ) {
			$this->realm = $realm;
			$this->parse_user_input();
		}

		/**
		 * Request user authenticates themselves.
		 */
		protected function parse_user_input() {
			if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
				// PHP has asked user for username and password.
				$this->credentials['name'] = wp_unslash( $_SERVER['PHP_AUTH_USER'] );
				$this->credentials['pass'] = wp_unslash( $_SERVER['PHP_AUTH_PW'] );
			} else {
				// No authorisation requested yet, so send it.
				$this->auth_required();
			}
		}

		/**
		 * Get the user's credentials.
		 */
		public function get_credentials() {
			return $this->credentials;
		}

		/**
		 * Print the auth form then exit.
		 */
		public function auth_required() {
			$protocol = wp_unslash( $_SERVER['SERVER_PROTOCOL'] );

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
