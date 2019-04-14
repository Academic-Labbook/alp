<?php

/**
 * Tests for revisions REST routes.
 *
 * See https://torquemag.io/2017/01/testing-api-endpoints/.
 */
class RevisionsRestTest extends WP_UnitTestCase {
    protected $admin;
    protected $editor;
    protected $author;
    protected $contributor;
    protected $subscriber;

	/**
	 * REST test server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

    /**
     * Routes.
     *
     * @var array
     */
    protected $routes = array(
        '/' . SSL_ALP_REST_ROUTE . '/update-revision-meta',
        '/' . SSL_ALP_REST_ROUTE . '/post-read-status',
    );

	public function setUp() {
        global $wp_rest_server;

        parent::setUp();

        $this->admin = $this->factory->user->create_and_get(
            array(
                'role'  =>  'administrator'
            )
        );

        $this->editor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'editor'
            )
        );

        $this->author = $this->factory->user->create_and_get(
            array(
                'role'  =>  'author'
            )
        );

        $this->contributor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'contributor'
            )
        );

        $this->subscriber = $this->factory->user->create_and_get(
            array(
                'role'  =>  'subscriber'
            )
        );

        $this->server = $wp_rest_server = new WP_REST_Server();

        // Initialise.
		do_action( 'rest_api_init' );
	}

	public function test_register_routes() {
        $routes = $this->server->get_routes();

        foreach ( $this->routes as $route ) {
            $this->assertArrayHasKey( $route, $routes );
        }
	}

	public function test_endpoints() {
        $registered_routes = $this->server->get_routes();

		foreach ( $registered_routes as $registered_route => $registered_route_config ) {
            if ( ! in_array( $registered_route, $this->routes, true ) ) {
                // Not a route we want to test.
                continue;
            }

            $this->assertTrue( is_array( $registered_route_config ) );

            foreach ( $registered_route_config as $i => $endpoint ) {
                $this->assertArrayHasKey( 'callback', $endpoint );
                $this->assertArrayHasKey( 0, $endpoint[ 'callback' ], get_class( $this ) );
                $this->assertArrayHasKey( 1, $endpoint[ 'callback' ], get_class( $this ) );
                $this->assertTrue( is_callable( array( $endpoint[ 'callback' ][0], $endpoint[ 'callback' ][1] ) ) );
            }
		}
    }

	public function test_update_revision_meta_has_no_get() {
		$request = new WP_REST_Request( 'GET', $this->routes[0] );
        $response = $this->server->dispatch( $request );

        // There should be no GET endpoint.
        $this->assertEquals( 404, $response->get_status() );
    }

    public function test_update_revision_meta_has_post() {
        $request = new WP_REST_Request( 'POST', $this->routes[0] );
        $response = $this->server->dispatch( $request );

        // There should be a POST endpoint. With no parameters, it should return 400 Bad Request.
        $this->assertEquals( 400, $response->get_status() );
    }

    public function test_update_revision_meta_unauthorised() {
        $post = $this->factory->post->create_and_get();

        $request = new WP_REST_Request( 'POST', $this->routes[0] );
        $request->set_param( 'post_id', $post->ID );
        $request->set_param( 'key', 'ssl_alp_edit_summary' );
        $request->set_param( 'value', 'here is my edit summary' );
        $response = $this->server->dispatch( $request );

        // Without setting a user, we should get 401 Unauthorized.
        $this->assertEquals( 401, $response->get_status() );
    }

    public function test_update_revision_meta_update() {
        $post_author = $this->factory->user->create_and_get(
            array(
                'role' => 'author',
            )
        );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author' => $post_author->ID,
                'post_status' => 'publish',
            )
        );

        $request = new WP_REST_Request( 'POST', $this->routes[0] );
        $request->set_param( 'post_id', $post->ID );
        $request->set_param( 'key', 'ssl_alp_edit_summary' );
        $request->set_param( 'value', 'here is my edit summary' );

        $cant_set = array(
            $this->subscriber,
            $this->contributor,
            $this->author,
        );

        foreach ( $cant_set as $user ) {
            // This role shouldn't be able to set the edit summary for someone else's post.
            wp_set_current_user( $user->ID );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 403, $response->get_status() );
        }

        $can_set = array(
            $post_author,
            $this->editor,
            $this->admin,
        );

        foreach ( $can_set as $user ) {
            // This role should be able to set the edit summary.
            wp_set_current_user( $user->ID );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );

            // Check the edit summary was set.
            //$this->assertEquals( 'here is my edit summary',  )
        }
    }

    public function test_post_read_status_has_get() {
		$request = new WP_REST_Request( 'GET', $this->routes[1] );
        $response = $this->server->dispatch( $request );

        // There should be a GET endpoint. With no parameters, it should return 400 Bad Request.
        $this->assertEquals( 400, $response->get_status() );
    }

    public function test_post_read_status_has_post() {
        $request = new WP_REST_Request( 'POST', $this->routes[1] );
        $response = $this->server->dispatch( $request );

        // There should be a POST endpoint. With no parameters, it should return 400 Bad Request.
        $this->assertEquals( 400, $response->get_status() );
    }

    public function test_post_read_status_unauthorized() {
        $post = $this->factory->post->create_and_get();

        $request = new WP_REST_Request( 'GET', $this->routes[1] );
        $request->set_param( 'post_id', $post->ID );
        $response = $this->server->dispatch( $request );

        // Without setting a user, we should get 401 Unauthorized.
        $this->assertEquals( 401, $response->get_status() );
    }

    public function test_post_read_status_get_authors_read_status() {
        $post_author = $this->factory->user->create_and_get(
            array(
                'role' => 'author',
            )
        );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author' => $post_author->ID,
                'post_status' => 'publish',
            )
        );

        $request = new WP_REST_Request( 'GET', $this->routes[1] );
        $request->set_param( 'post_id', $post->ID );
        $request->set_param( 'user_id', $post_author->ID );

        $cant_get = array(
            $this->subscriber,
            $this->contributor,
            $this->author,
            $this->editor,
        );

        foreach ( $cant_get as $user ) {
            // This role shouldn't be able to get the read status for someone else's post.
            wp_set_current_user( $user->ID );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 403, $response->get_status() );
        }

        $can_get = array(
            $post_author,
            $this->admin,
        );

        foreach ( $can_get as $user ) {
            // This role should be able to get the read status.
            wp_set_current_user( $user->ID );
            $response = $this->server->dispatch( $request );
            $this->assertEquals( 200, $response->get_status() );
        }
    }
}
