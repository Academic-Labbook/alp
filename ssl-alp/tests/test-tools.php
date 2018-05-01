<?php

/**
 * Tools tests
 */
class ToolsTest extends WP_UnitTestCase {
    function setUp() {
        parent::setUp();
    }

    function test_activate_theme() {
        global $ssl_alp;

        // by default, theme not active
        $this->assertNotEquals( wp_get_theme()->name, 'Alpine' );

        // change theme
        switch_theme( 'Alpine' );

        // check theme now active
        $this->assertEquals( wp_get_theme()->name, 'Alpine' );
    }

    function test_override_core_settings() {
        global $ssl_alp;

        // by default, settings not overridden
        $this->assertFalse( $ssl_alp->tools->core_settings_overridden() );

        // override settings
        $ssl_alp->tools->override_core_settings();

        // check settings are overridden
        $this->assertTrue( $ssl_alp->tools->core_settings_overridden() );
    }

    function test_default_roles() {
        global $ssl_alp;

        // by default roles are not converted
        $this->assertTrue( $ssl_alp->tools->roles_are_default() );
        $this->assertFalse( $ssl_alp->tools->roles_converted() );
    }

	function test_change_roles() {
        global $ssl_alp;

        /**
         * create a user in each default role
         */

        $user_admin = $this->factory->user->create_and_get(
            array(
                'role'  =>  'administrator'
            )
        );

        $user_editor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'editor'
            )
        );

        $user_author = $this->factory->user->create_and_get(
            array(
                'role'  =>  'author'
            )
        );

        $user_contributor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'contributor'
            )
        );

        $user_subscriber = $this->factory->user->create_and_get(
            array(
                'role'  =>  'subscriber'
            )
        );

        // change roles
        $ssl_alp->tools->convert_roles();

        // refresh user objects
        $user_admin = get_user_by('id', $user_admin->ID);
        $user_editor = get_user_by('id', $user_editor->ID);
        $user_author = get_user_by('id', $user_author->ID);
        $user_contributor = get_user_by('id', $user_contributor->ID);
        $user_subscriber = get_user_by('id', $user_subscriber->ID);

        /**
         * check changed roles
         */

        // administrator unchanged
        $this->assertEquals( $user_admin->roles, array( 'administrator' ) );

        // editor changed to researcher
        $this->assertEquals( $user_editor->roles, array( 'researcher' ) );

        // author changed to intern
        $this->assertEquals( $user_author->roles, array( 'intern' ) );

        // contributor changed to subscriber
        $this->assertEquals( $user_contributor->roles, array( 'subscriber' ) );

        // subscriber unchanged
        $this->assertEquals( $user_subscriber->roles, array( 'subscriber' ) );

        // check roles changed
        $this->assertFalse( $ssl_alp->tools->roles_are_default() );
        $this->assertTrue( $ssl_alp->tools->roles_converted() );

        // check old groups are deleted
        $this->assertNull( get_role( 'editor' ) );
        $this->assertNull( get_role( 'author' ) );
        $this->assertNull( get_role( 'contributor' ) );
    }
}
