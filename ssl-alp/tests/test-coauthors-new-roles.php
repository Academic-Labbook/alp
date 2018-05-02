<?php

/**
 * Coauthors tests using new roles defined by ALP
 * 
 * Must be run independently, as it changes the roles in the database, which affect
 * other tests.
 * 
 * @group alp-role-permissions
 */
class CoauthorsNewRolesTest extends WP_UnitTestCase {
    protected static $excluded_ids;
	protected static $subscriber_ids;
	protected static $intern_ids;
	protected static $researcher_ids;
    protected static $admin_ids;

    protected static $user_ids = array();
    
	public static function wpSetUpBeforeClass( $factory ) {
        global $ssl_alp;

        // convert roles
        $ssl_alp->tools->convert_roles();

        // create users with each role
        self::$user_ids = self::$excluded_ids = $factory->user->create_many( 2, array( 'role' => 'excluded' ) );
        self::$user_ids[] = self::$subscriber_ids = $factory->user->create_many( 2, array( 'role' => 'subscriber' ) );
		self::$user_ids[] = self::$intern_ids = $factory->user->create_many( 2, array( 'role' => 'intern' ) );
		self::$user_ids[] = self::$researcher_ids = $factory->user->create_many( 2, array( 'role' => 'researcher' ) );
		self::$user_ids[] = self::$admin_ids = $factory->user->create_many( 2, array( 'role' => 'administrator' ) );
	}

    function setUp() {
        parent::setUp();
    }

    /**
     * Test $user_id_editor editing a post created by $user_id_author
     * 
     * `edit_post` raises a WPDieException if the user can't edit the post,
     * otherwise this function returns true.
     */
    private function _do_test_edit_post( $user_id_author, $user_id_editor ) {
        // create post as author user
        wp_set_current_user( $user_id_author );
        $post = $this->factory->post->create_and_get();

        // edit post as editor user
        wp_set_current_user( $user_id_editor );
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
				'content'		=>	'New content.'
			)
        );
        
        return true;
    }

    /**
     * Test $user_id_editor editing a post created by $user_id_author, that they
     * are a coauthor of
     * 
     * `edit_post` raises a WPDieException if the user can't edit the post,
     * otherwise this function returns true.
     */
    private function _do_test_edit_coauthored_post( $user_id_author, $user_id_editor ) {        
        global $ssl_alp;

        // create post as author user
        wp_set_current_user( $user_id_author );
        $post = $this->factory->post->create_and_get();

        // set coauthor
        $ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                get_user_by( 'id', $user_id_author ),
                get_user_by( 'id', $user_id_editor )
            )
        );

        // edit post as editor user
        wp_set_current_user( $user_id_editor );
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
				'content'		=>	'New content.'
			)
        );
        
        return true;
    }

    /**
     * Excluded role tests
     * 
     * Excluded users cannot edit anything.
     */

    /**
     * @expectedException WPDieException
     */
    function test_excluded_0() {
        // excluded can't edit their own posts
        $this->_do_test_edit_post( self::$excluded_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_1() {
        // excluded can't edit other excludeds' posts
        $this->_do_test_edit_post( self::$excluded_ids[1], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_2() {
        // excluded can't edit subscribers' posts
        $this->_do_test_edit_post( self::$subscriber_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_3() {
        // excluded can't edit interns' posts
        $this->_do_test_edit_post( self::$intern_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_4() {
        // excluded can't edit researchers' posts
        $this->_do_test_edit_post( self::$researcher_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_5() {
        // excluded can't edit admins' posts
        $this->_do_test_edit_post( self::$admin_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_1() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$excluded_ids[1], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_2() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$subscriber_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_3() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$intern_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_4() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$researcher_ids[0], self::$excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_5() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$admin_ids[0], self::$excluded_ids[0] );
    }

    /**
     * Subscriber role tests
     * 
     * Subscribers cannot edit anything.
     */

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_0() {
        // subscribers can't edit excludeds' posts
        $this->_do_test_edit_post( self::$excluded_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_1() {
        // subscribers can't edit their own posts
        $this->_do_test_edit_post( self::$subscriber_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_2() {
        // subscribers can't edit other subscribers' posts
        $this->_do_test_edit_post( self::$subscriber_ids[1], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_3() {
        // subscribers can't edit interns' posts
        $this->_do_test_edit_post( self::$intern_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_4() {
        // subscribers can't edit researchers' posts
        $this->_do_test_edit_post( self::$researcher_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_5() {
        // subscribers can't edit admins' posts
        $this->_do_test_edit_post( self::$admin_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_1() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$excluded_ids[1], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_2() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$subscriber_ids[1], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_3() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$intern_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_4() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$researcher_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_5() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( self::$admin_ids[0], self::$subscriber_ids[0] );
    }

    /**
     * Intern role tests
     * 
     * Interns can edit their own posts, including coauthored posts, but not posts
     * they are not authors or coauthors of.
     */

    function test_intern_0() {
        // interns can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( self::$intern_ids[0], self::$intern_ids[0] ) );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_1() {
        // interns can't edit excludeds' posts
        $this->_do_test_edit_post( self::$excluded_ids[1], self::$intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_2() {
        // interns can't edit subscribers' posts
        $this->_do_test_edit_post( self::$subscriber_ids[0], self::$intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_3() {
        // interns can't edit other interns' posts
        $this->_do_test_edit_post( self::$intern_ids[1], self::$intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_4() {
        // interns can't edit researchers' posts
        $this->_do_test_edit_post( self::$researcher_ids[0], self::$intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_5() {
        // interns can't edit admins' posts
        $this->_do_test_edit_post( self::$admin_ids[0], self::$intern_ids[0] );
    }

    function test_intern_coauthor_1() {
        // interns can edit posts they are coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$excluded_ids[0], self::$intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$subscriber_ids[0], self::$intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$intern_ids[1], self::$intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$researcher_ids[0], self::$intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$admin_ids[0], self::$intern_ids[0] ) );
    }

    /**
     * Researcher role tests
     * 
     * Researchers can edit everything.
     */

    function test_researcher() {
        // researchers can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( self::$researcher_ids[0], self::$researcher_ids[0] ) );

        // researchers can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( self::$excluded_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$subscriber_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$intern_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$researcher_ids[1], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[0], self::$researcher_ids[0] ) );

        // researchers can edit posts they're coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$excluded_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$subscriber_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$intern_ids[0], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$researcher_ids[1], self::$researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$admin_ids[0], self::$researcher_ids[0] ) );
    }

    /**
     * Admin role tests
     * 
     * Admins can edit everything.
     */

    function test_admin() {
        // admins can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[0], self::$admin_ids[0] ) );

        // admins can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( self::$excluded_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$subscriber_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$intern_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$researcher_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[1], self::$admin_ids[0] ) );

        // admins can edit posts they're coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$excluded_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$subscriber_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$intern_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$researcher_ids[0], self::$admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( self::$admin_ids[1], self::$admin_ids[0] ) );
    }
}
