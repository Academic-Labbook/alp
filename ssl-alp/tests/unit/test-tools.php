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
        $this->assertNotEquals( wp_get_theme()->name, 'Labbook' );

        // backup current theme
        $previous_theme = get_stylesheet();

        // change theme
        switch_theme( 'Labbook' );

        // check theme now active
        $this->assertEquals( wp_get_theme()->name, 'Labbook' );

        // change back to default theme
        switch_theme( $previous_theme );
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
}
