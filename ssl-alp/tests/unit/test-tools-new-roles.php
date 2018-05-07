<?php

/**
 * Tools tests using new roles defined by ALP
 */
class ToolsTestNewRoles extends WP_UnitTestCase {
    protected $admin;
    protected $editor;
    protected $author;
    protected $contributor;
    protected $subscriber;

    protected $role_key;
    protected $default_roles;

    public function setUp() {
        global $ssl_alp;

        parent::setUp();

        /**
         * create a user in each default role
         */

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

        $this->convert_roles();
    }

    public function tearDown() {
        $this->reset_roles();

        parent::tearDown();
    }

    /**
     * Convert user roles for the tests in this class.
     * 
     * This only works when the current blog is the one being tested.
     */
    private function convert_roles() {
        global $wpdb, $ssl_alp;

        // role settings name in options table
        $this->role_key = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'user_roles';

        // copy current roles
        $this->default_roles = get_option( $this->role_key );

        // convert roles
        $ssl_alp->tools->convert_roles();
    }

    /**
     * Reset changes made to roles
     */
    private function reset_roles() {
        update_option( $this->role_key, $this->default_roles );

        // refresh loaded roles
        self::flush_roles();
    }

    /**
     * From WordPress core's user/capabilities.php tests
     */
	private static function flush_roles() {
		// we want to make sure we're testing against the db, not just in-memory data
		// this will flush everything and reload it from the db
		unset( $GLOBALS['wp_user_roles'] );
		global $wp_roles;
		$wp_roles = new WP_Roles();
	}

	function test_new_roles() {
        global $ssl_alp;

        /**
         * check changed roles
         */

        // refresh users
        $admin = get_user_by( 'id', $this->admin->ID );
        $editor = get_user_by( 'id', $this->editor->ID );
        $author = get_user_by( 'id', $this->author->ID );
        $contributor = get_user_by( 'id', $this->contributor->ID );
        $subscriber = get_user_by( 'id', $this->subscriber->ID );

        // administrator unchanged
        $this->assertEquals( $admin->roles, array( 'administrator' ) );

        // editor changed to researcher
        $this->assertEquals( $editor->roles, array( 'researcher' ) );

        // author changed to intern
        $this->assertEquals( $author->roles, array( 'intern' ) );

        // contributor changed to subscriber
        $this->assertEquals( $contributor->roles, array( 'subscriber' ) );

        // subscriber unchanged
        $this->assertEquals( $subscriber->roles, array( 'subscriber' ) );

        // check roles changed
        $this->assertFalse( $ssl_alp->tools->roles_are_default() );
        $this->assertTrue( $ssl_alp->tools->roles_converted() );

        // check old groups are deleted
        $this->assertNull( get_role( 'editor' ) );
        $this->assertNull( get_role( 'author' ) );
        $this->assertNull( get_role( 'contributor' ) );
    }
}
