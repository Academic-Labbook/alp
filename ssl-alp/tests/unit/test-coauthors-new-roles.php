<?php

/**
 * Coauthors tests using new roles defined by ALP
 */
class CoauthorsNewRolesTest extends WP_UnitTestCase {
    protected $excluded_ids;
	protected $subscriber_ids;
	protected $intern_ids;
	protected $researcher_ids;
    protected $admin_ids;

    protected $role_key;
    protected $default_roles;

    public function setUp() {
        parent::setUp();

        $this->convert_roles();

        $this->excluded_ids = $this->factory->user->create_many( 2, array( 'role' => 'excluded' ) );
        $this->subscriber_ids = $this->factory->user->create_many( 2, array( 'role' => 'subscriber' ) );
		$this->intern_ids = $this->factory->user->create_many( 2, array( 'role' => 'intern' ) );
		$this->researcher_ids = $this->factory->user->create_many( 2, array( 'role' => 'researcher' ) );
        $this->admin_ids = $this->factory->user->create_many( 2, array( 'role' => 'administrator' ) );
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
        $this->_do_test_edit_post( $this->excluded_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_1() {
        // excluded can't edit other excludeds' posts
        $this->_do_test_edit_post( $this->excluded_ids[1], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_2() {
        // excluded can't edit subscribers' posts
        $this->_do_test_edit_post( $this->subscriber_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_3() {
        // excluded can't edit interns' posts
        $this->_do_test_edit_post( $this->intern_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_4() {
        // excluded can't edit researchers' posts
        $this->_do_test_edit_post( $this->researcher_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_5() {
        // excluded can't edit admins' posts
        $this->_do_test_edit_post( $this->admin_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_1() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->excluded_ids[1], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_2() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->subscriber_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_3() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->intern_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_4() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->researcher_ids[0], $this->excluded_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_excluded_coauthor_5() {
        // excluded cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->admin_ids[0], $this->excluded_ids[0] );
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
        $this->_do_test_edit_post( $this->excluded_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_1() {
        // subscribers can't edit their own posts
        $this->_do_test_edit_post( $this->subscriber_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_2() {
        // subscribers can't edit other subscribers' posts
        $this->_do_test_edit_post( $this->subscriber_ids[1], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_3() {
        // subscribers can't edit interns' posts
        $this->_do_test_edit_post( $this->intern_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_4() {
        // subscribers can't edit researchers' posts
        $this->_do_test_edit_post( $this->researcher_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_5() {
        // subscribers can't edit admins' posts
        $this->_do_test_edit_post( $this->admin_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_1() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->excluded_ids[1], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_2() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->subscriber_ids[1], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_3() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->intern_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_4() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->researcher_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_subscriber_coauthor_5() {
        // subscribers cannot edit posts they are coauthor of
        $this->_do_test_edit_coauthored_post( $this->admin_ids[0], $this->subscriber_ids[0] );
    }

    /**
     * Intern role tests
     * 
     * Interns can edit their own posts, including coauthored posts, but not posts
     * they are not authors or coauthors of.
     */

    function test_intern_0() {
        // interns can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( $this->intern_ids[0], $this->intern_ids[0] ) );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_1() {
        // interns can't edit excludeds' posts
        $this->_do_test_edit_post( $this->excluded_ids[1], $this->intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_2() {
        // interns can't edit subscribers' posts
        $this->_do_test_edit_post( $this->subscriber_ids[0], $this->intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_3() {
        // interns can't edit other interns' posts
        $this->_do_test_edit_post( $this->intern_ids[1], $this->intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_4() {
        // interns can't edit researchers' posts
        $this->_do_test_edit_post( $this->researcher_ids[0], $this->intern_ids[0] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_intern_5() {
        // interns can't edit admins' posts
        $this->_do_test_edit_post( $this->admin_ids[0], $this->intern_ids[0] );
    }

    function test_intern_coauthor_1() {
        // interns can edit posts they are coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->excluded_ids[0], $this->intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->subscriber_ids[0], $this->intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->intern_ids[1], $this->intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->researcher_ids[0], $this->intern_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->admin_ids[0], $this->intern_ids[0] ) );
    }

    /**
     * Researcher role tests
     * 
     * Researchers can edit everything.
     */

    function test_researcher() {
        // researchers can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( $this->researcher_ids[0], $this->researcher_ids[0] ) );

        // researchers can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( $this->excluded_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->subscriber_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->intern_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->researcher_ids[1], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->admin_ids[0], $this->researcher_ids[0] ) );

        // researchers can edit posts they're coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->excluded_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->subscriber_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->intern_ids[0], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->researcher_ids[1], $this->researcher_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->admin_ids[0], $this->researcher_ids[0] ) );
    }

    /**
     * Admin role tests
     * 
     * Admins can edit everything.
     */

    function test_admin() {
        // admins can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( $this->admin_ids[0], $this->admin_ids[0] ) );

        // admins can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( $this->excluded_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->subscriber_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->intern_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->researcher_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( $this->admin_ids[1], $this->admin_ids[0] ) );

        // admins can edit posts they're coauthor of
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->excluded_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->subscriber_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->intern_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->researcher_ids[0], $this->admin_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_coauthored_post( $this->admin_ids[1], $this->admin_ids[0] ) );
    }
}
