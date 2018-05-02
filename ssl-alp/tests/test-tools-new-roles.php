<?php

/**
 * Tools tests using new roles defined by ALP
 * 
 * Must be run independently, as it changes the roles in the database, which affect
 * other tests.
 * 
 * @group alp-role-convert
 */
class ToolsTestNewRoles extends WP_UnitTestCase {
    protected static $admin;
    protected static $editor;
    protected static $author;
    protected static $contributor;
    protected static $subscriber;

	public static function wpSetUpBeforeClass( $factory ) {
        global $ssl_alp;

        /**
         * create a user in each default role
         */

        self::$admin = $factory->user->create_and_get(
            array(
                'role'  =>  'administrator'
            )
        );

        self::$editor = $factory->user->create_and_get(
            array(
                'role'  =>  'editor'
            )
        );

        self::$author = $factory->user->create_and_get(
            array(
                'role'  =>  'author'
            )
        );

        self::$contributor = $factory->user->create_and_get(
            array(
                'role'  =>  'contributor'
            )
        );

        self::$subscriber = $factory->user->create_and_get(
            array(
                'role'  =>  'subscriber'
            )
        );

        // convert roles
        $ssl_alp->tools->convert_roles();
	}

    function setUp() {
        parent::setUp();
    }

	function test_new_roles() {
        global $ssl_alp;

        /**
         * check changed roles
         */

        // refresh users
        $admin = get_user_by( 'id', self::$admin->ID );
        $editor = get_user_by( 'id', self::$editor->ID );
        $author = get_user_by( 'id', self::$author->ID );
        $contributor = get_user_by( 'id', self::$contributor->ID );
        $subscriber = get_user_by( 'id', self::$subscriber->ID );

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
