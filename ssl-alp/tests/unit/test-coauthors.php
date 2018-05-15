<?php

/**
 * Coauthors tests
 */
class CoauthorsTest extends WP_UnitTestCase {
	protected static $contributor_ids;
	protected static $author_ids;
	protected static $editor_ids;
    protected static $admin_ids;
    
	public static function wpSetUpBeforeClass( $factory ) {
        self::$contributor_ids = $factory->user->create_many( 2, array( 'role' => 'contributor' ) );
		self::$author_ids = $factory->user->create_many( 2, array( 'role' => 'author' ) );
		self::$editor_ids = $factory->user->create_many( 2, array( 'role' => 'editor' ) );
		self::$admin_ids = $factory->user->create_many( 2, array( 'role' => 'administrator' ) );
	}

    function setUp() {
        parent::setUp();

        $this->user_1 = $this->factory->user->create_and_get();
        $this->user_2 = $this->factory->user->create_and_get();
        $this->user_3 = $this->factory->user->create_and_get();

        $this->post_1 = $this->factory->post->create_and_get(
            array(
			    'post_author'     => $this->user_1->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );

        $this->post_2 = $this->factory->post->create_and_get(
            array(
			    'post_author'     => $this->user_2->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );

        $this->post_3 = $this->factory->post->create_and_get(
            array(
			    'post_author'     => $this->user_3->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );
    }

    /**
     * Checks the specified user id is deleted. For single sites,
     * the user is checked to be deleted. For multisite, the user
     * is checked to no longer be a member of the current blog.
     * 
     * This essentially checks the behaviour of `wp_delete_user` in
     * each case.
     */
    private function check_user_deleted( $user_id ) {
        if ( is_multisite() ) {
            $this->assertFalse( is_user_member_of_blog( $user_id ) );
        } else {
            $this->assertFalse( get_user_by( 'id', $user_id ) );
        }
    }

	function test_coauthors() {
        global $ssl_alp;

        // default posts should have no secondary author
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_1 ),
            array(
                $this->user_1
            )
        );
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_2 ),
            array(
                $this->user_2
            )
        );
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_3 ),
            array(
                $this->user_3
            )
        );

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_2 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
            )
        );

        // check coauthors are now present
		$this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthors( $this->post_2 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
            )
        );
    }

    function test_add_same_coauthor_to_post__author_id_arg() {
        global $ssl_alp;

        // set duplicate users to post
        $ssl_alp->coauthors->set_coauthors(
            $this->post_1,
            array(
                $this->user_1,
                $this->user_1
            )
        );

        $query = new WP_Query(
            array(
			    'author' => $this->user_1->ID
            )
        );
        
        // check user query
		$this->assertEquals( 1, count( $query->posts ) );
        $this->assertEquals( $this->post_1->ID, $query->posts[ 0 ]->ID );
        
        // check get_coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_1 ),
            array(
                $this->user_1
            )
        );
    }

    function test_add_same_coauthor_to_post__author_name_arg() {
        global $ssl_alp;

        // set duplicate users to post
        $ssl_alp->coauthors->set_coauthors(
            $this->post_1,
            array(
                $this->user_1,
                $this->user_1
            )
        );

        $query = new WP_Query(
            array(
			    'author_name' => $this->user_1->user_login
            )
        );
        
        // check user query
		$this->assertEquals( 1, count( $query->posts ) );
        $this->assertEquals( $this->post_1->ID, $query->posts[ 0 ]->ID );
        
        // check get_coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_1 ),
            array(
                $this->user_1
            )
        );
    }
    
	public function test__author_name_arg_plus_tax_query__user_is_post_author() {
        global $ssl_alp;
        
        $ssl_alp->coauthors->set_coauthors( $this->post_1->ID, array( $this->user_1->user_login ) );
        
        wp_set_post_terms( $this->post_1->ID, 'test', 'post_tag' );
        
		$query = new WP_Query(
            array(
			    'author_name' => $this->user_1->user_login,
			    'tag' => 'test',
            )
        );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $this->post_1->ID, $query->posts[ 0 ]->ID );
    }
    
	public function tests__author_name_arg_plus_tax_query__is_coauthor() {
        global $ssl_alp;

		$ssl_alp->coauthors->set_coauthors(
            $this->post_1->ID,
            array(
                $this->user_1,
                $this->user_2
            )
        );

        wp_set_post_terms( $this->post_1->ID, 'test', 'post_tag' );
        
		$query = new WP_Query(
            array(
			    'author_name' => $this->user_2->user_login,
			    'tag' => 'test',
            )
        );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $this->post_1->ID, $query->posts[ 0 ]->ID );
	}

	function test_add_coauthor_updates_post_author() {
        global $ssl_alp;
        
        // override post 1's author
		$ssl_alp->coauthors->set_coauthors(
            $this->post_1,
            array(
                $this->user_2,
                $this->user_3
            )
        );

        // refresh post
        $post = get_post( $this->post_1->ID );

        // WordPress core author should have changed to first in above list
		$this->assertEquals( $post->post_author, $this->user_2->ID );
	}

    function test_coauthor_order() {
        global $ssl_alp;

        // add coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_1 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
            )
        );

        // check order (use assertEquals to check order)
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_1 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
            )
        );

        // change order
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_1 ),
            array(
                $this->user_3,
                $this->user_2,
                $this->user_1
            )
        );

        // check order (use assertEquals to check order)
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_1 ),
            array(
                $this->user_3,
                $this->user_2,
                $this->user_1
            )
        );
    }

    function test_coauthor_terms() {
        global $ssl_alp;

        // create new users without terms
        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        
        // terms are created during user creation
        $this->assertInstanceOf( 'WP_Term', get_term_by( 'name', $user_1->user_login, 'ssl_alp_coauthor' ) );
        $this->assertInstanceOf( 'WP_Term', get_term_by( 'name', $user_2->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check lists of posts where a user is coauthor.
     */
    function test_get_coauthor_posts() {
        global $ssl_alp;

        /**
         * check users' own posts, i.e. post 1 -> user 1, post 2 -> user 2, etc.
         */

        // check they are primary author
        $this->assertEquals( $this->post_1->post_author, $this->user_1->ID );
        $this->assertEquals( $this->post_2->post_author, $this->user_2->ID );
        $this->assertEquals( $this->post_3->post_author, $this->user_3->ID );

        // check coauthor lists
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $this->user_1 ),
            array(
                $this->post_1
            )
        );
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $this->user_2 ),
            array(
                $this->post_2
            )
        );
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $this->user_3 ),
            array(
                $this->post_3
            )
        );

        /**
         * add coauthors
         */

        // add all authors to post 1
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_1 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
            )
        );

        // refresh post object
        $post = get_post( $this->post_1->ID );

        // primary author shouldn't have changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // users 2 and 3 should now be authors of post 1 as well
        $this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthor_posts( $this->user_2 ),
            array(
                $post,
                $this->post_2
            )
        );
        $this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthor_posts( $this->user_3 ),
            array(
                $post,
                $this->post_3
            )
        );
    }

    /**
     * Check that changing the only author of a post works.
     */
    function test_update_post_coauthors_remove_primary_author() {
        global $ssl_alp;

        // set post 2's coauthors to only user 1 (removes user 2)
        $ssl_alp->coauthors->set_coauthors(
            $this->post_2,
            array(
                $this->user_1
            )
        );

        // refresh post object
        $post = get_post( $this->post_2->ID );

        // post should now have user 1 as primary author
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // and user 1 should be sole coauthor
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1
            )
        );
    }

    /**
     * Setting the post author the "normal" way should not work; the only way to set
     * post authors should be via `set_coauthors`.
     */
    function test_update_post_author_programmatically_does_nothing() {
        global $ssl_alp;

        // set post 2's author to user 1
        wp_update_post(
            array(
                'ID'            =>  $this->post_2->ID,
                'post_author'   =>  $this->user_1->ID
            )
        );

        // refresh post object
        $post = get_post( $this->post_2->ID );

        // post should still be set to what it was before
        $this->assertEquals( $post->post_author, $this->user_2->ID );

        // the coauthor should be the same
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ), array( $this->user_2 )
        );
    }

    /**
     * Check that deleting a user that is the primary author of a post with no other
     * authors deletes that post.
     */
    function test_coauthor_delete_user_with_sole_author_post() {
        global $ssl_alp;

        // delete user
        wp_delete_user( $this->user_1->ID );

        // user should be deleted
        $this->check_user_deleted( $this->user_1->ID );

        // updated post should be null
        $this->assertNull( get_post( $this->post_1->ID ) );
    }

    /**
     * Check that deleting a user that is the primary author of a post with no other
     * authors, when reassigning the author's posts to another user, does not delete
     * that post.
     */
    function test_coauthor_delete_user_with_sole_author_post_with_reassign() {
        global $ssl_alp;

        // delete user, reassigning to user 2
        wp_delete_user( $this->user_1->ID, $this->user_2->ID );

        // user should be deleted
        $this->check_user_deleted( $this->user_1->ID );

        // post should have user 2 as author
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( get_post( $this->post_1->ID ) ),
            array(
                $this->user_2
            )
        );

        // post should retain its original publication status
        $this->assertEquals( get_post( $this->post_1->ID)->post_status, $this->post_1->post_status );
    }

    /**
     * Check that deleting a user that is a primary author of a post with multiple authors
     * doesn't also delete that post. It should just remove that author from the post, and
     * set a new primary author.
     */
    function test_coauthor_delete_user_that_is_primary_author_of_multi_author_post() {
        global $ssl_alp;

        // add coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_1 ),
            array(
                $this->user_1,
                $this->user_2
            )
        );

        // delete user
        wp_delete_user( $this->user_1->ID );

        // user should be deleted
        $this->check_user_deleted( $this->user_1->ID );

        // get updated post object
        $post = get_post( $this->post_1->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $this->user_2->ID );

        // post should have only user 2 as coauthor
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $this->user_1->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a primary author of a post with multiple authors
     * doesn't also delete that post, when the user's posts are reassigned.
     * 
     * In this case, the reassigned user is not a coauthor of the post so they should just
     * replace the deleted user's place in the coauthor list.
     */
    function test_coauthor_delete_user_that_is_primary_author_of_multi_author_post_with_reassign() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();
        $reassign_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $this->post_3,
            array(
                $new_user,
                $this->user_1,
                $this->user_2
            )
        );

        // delete user, reassigning their posts to other user
        wp_delete_user( $new_user->ID, $reassign_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_3->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $reassign_user->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $reassign_user,
                $this->user_1,
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a primary author of a post with multiple authors
     * doesn't also delete that post, when the user's posts are reassigned.
     * 
     * In this case, the reassigned user is a coauthor of the post, but with lower position
     * than the deleted user, so they should replace the deleted user's place in the coauthor
     * list.
     */
    function test_coauthor_delete_user_that_is_primary_author_of_multi_author_post_with_reassign_existing_user() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();
        $reassign_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $this->post_3,
            array(
                $new_user,
                $this->user_1,
                $reassign_user
            )
        );

        // delete user, reassigning their posts to other user
        wp_delete_user( $new_user->ID, $reassign_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_3->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $reassign_user->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $reassign_user,
                $this->user_1
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a secondary author of a post with multiple authors
     * doesn't also delete that post. It should just remove that author from the post, and
     * leave the primary author untouched.
     */
    function test_coauthor_delete_user_that_is_secondary_author_of_multi_author_post() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_1 ),
            array(
                $this->user_1,
                $this->user_2,
                $new_user
            )
        );

        // delete user
        wp_delete_user( $new_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_1->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1,
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a secondary author of a post with multiple authors
     * doesn't also delete that post, when the user's posts are reassigned.
     * 
     * In this case, the reassigned user is not a coauthor of the post so they should just
     * replace the deleted user's place in the coauthor list.
     */
    function test_coauthor_delete_user_that_is_secondary_author_of_multi_author_post_with_reassign() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();
        $reassign_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $this->post_3,
            array(
                $this->user_1,
                $new_user,
                $this->user_2
            )
        );

        // delete user, reassigning their posts to other user
        wp_delete_user( $new_user->ID, $reassign_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_3->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1,
                $reassign_user,
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a secondary author of a post with multiple authors
     * doesn't also delete that post, when the user's posts are reassigned.
     * 
     * In this case, the reassigned user is a coauthor of the post, but with lower position
     * than the deleted user, so they should replace the deleted user's place in the coauthor
     * list.
     */
    function test_coauthor_delete_user_that_is_secondary_author_of_multi_author_post_with_reassign_lower() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();
        $reassign_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $this->post_3,
            array(
                $this->user_1,
                $new_user,
                $this->user_2,
                $reassign_user
            )
        );

        // delete user, reassigning their posts to other user
        wp_delete_user( $new_user->ID, $reassign_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_3->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1,
                $reassign_user,
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Check that deleting a user that is a secondary author of a post with multiple authors
     * doesn't also delete that post, when the user's posts are reassigned.
     * 
     * In this case, the reassigned user is a coauthor of the post, but with higher position
     * than the deleted user, so they should stay in their current position and the deleted
     * user should just be removed from the coauthors list.
     */
    function test_coauthor_delete_user_that_is_secondary_author_of_multi_author_post_with_reassign_higher() {
        global $ssl_alp;

        $new_user = $this->factory->user->create_and_get();
        $reassign_user = $this->factory->user->create_and_get();

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $this->post_3,
            array(
                $this->user_1,
                $reassign_user,
                $this->user_2,
                $new_user
            )
        );

        // delete user, reassigning their posts to other user
        wp_delete_user( $new_user->ID, $reassign_user->ID );

        // user should be deleted
        $this->check_user_deleted( $new_user->ID );

        // refresh post object
        $post = get_post( $this->post_3->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1,
                $reassign_user,
                $this->user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( get_term_by( 'name', $new_user->user_login, 'ssl_alp_coauthor' ) );
    }

    /**
     * Test post counts including coauthored posts.
     */
    function test_post_counts() {
        global $ssl_alp;

        // users initially have 1 each
        $this->assertEquals( count_user_posts( $this->user_1->ID), 1 );
        $this->assertEquals( count_user_posts( $this->user_2->ID), 1 );
        $this->assertEquals( count_user_posts( $this->user_3->ID), 1 );

        // create a bunch of posts
        $count = 10;
        $post_ids = $this->factory->post->create_many( $count );

        // set coauthors
        foreach ( $post_ids as $post_id ) {
            $ssl_alp->coauthors->set_coauthors(
                $post_id,
                array(
                    $this->user_1,
                    $this->user_2
                )
            );
        }

        // user 1 and 2 should have $count more posts
        $this->assertEquals( count_user_posts( $this->user_1->ID), $count + 1 );
        $this->assertEquals( count_user_posts( $this->user_2->ID), $count + 1 );
        $this->assertEquals( count_user_posts( $this->user_3->ID), 1 );

        // test our implementation of count_many_users_posts
        $this->assertEquals(
            array_values(
                $ssl_alp->coauthors->count_many_users_posts(
                    array(
                        $this->user_1->ID,
                        $this->user_2->ID,
                        $this->user_3->ID
                    )
                )
            ),
            array(
                $count + 1,
                $count + 1,
                1
            )
        );
    }

    /**
     * Check the reported number of coauthored posts by an author agrees with the
     * actual amount
     */
    function test_reported_count_vs_actual_count() {
        global $ssl_alp;

        // create new user
        $user = $this->factory->user->create_and_get();

        // create posts as $user
        wp_set_current_user( $user->ID );
        $this->factory->post->create_many( 5 );

        // create posts as other user
        wp_set_current_user( $this->user_1->ID );
        $post_ids = $this->factory->post->create_many( 5 );

        // set $user to be coauthor
        foreach ( $post_ids as $post_id ) {
            $ssl_alp->coauthors->set_coauthors(
                get_post( $post_id ),
                array(
                    $this->user_1,
                    $user
                )
            );
        }

        // create posts as other user
        wp_set_current_user( $this->user_2->ID );
        $post_ids = $this->factory->post->create_many( 5 );

        // set $user to be coauthor
        foreach ( $post_ids as $post_id ) {
            $ssl_alp->coauthors->set_coauthors(
                get_post( $post_id ),
                array(
                    $this->user_1,
                    $this->user_2,
                    $user
                )
            );
        }

        // check filter reports correct count
        $this->assertEquals( 15, count_user_posts( $user->ID ) );

        // check manually get correct count
        $this->assertEquals( 15, count( $ssl_alp->coauthors->get_coauthor_posts( $user ) ) );
    }

    function test_author_page_posts() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();

        // create a bunch of posts
        $post_ids = $this->factory->post->create_many( 5 );

        // set coauthors
        foreach ( $post_ids as $post_id ) {
            $ssl_alp->coauthors->set_coauthors(
                $post_id,
                array(
                    $user_1,
                    $user_2
                )
            );
        }

        // extra post for user 1 with no coauthors
        $post_1 = $this->factory->post->create_and_get(
            array(
                'post_author'   =>  $user_1->ID
            )
        );

        $post_ids[] = $post_1->ID;

        // user 1 should have 6
        $this->go_to( get_author_posts_url( $user_1->ID ) );
        $this->assertEquals( $this->count_posts(), 6 );

        // user 2 should have 5
        $this->go_to( get_author_posts_url( $user_2->ID ) );
        $this->assertEquals( $this->count_posts(), 5 );

        // user 3 should have 0
        $this->go_to( get_author_posts_url( $user_3->ID ) );
        $this->assertEquals( $this->count_posts(), 0 );
    }

	/**
	 * On author pages, the queried object should only be set
	 * to a user that's not a member of the blog if they
	 * have at least one published post. This matches core behavior.
     * 
     * @group ms-required
	 */
	function test_author_archive_pages_for_network_users() {
        global $ssl_alp;
        
		// setup
		$author_1 = $this->factory->user->create_and_get( array( 'user_login' => 'msauthor1' ) );
        $author_2 = $this->factory->user->create_and_get( array( 'user_login' => 'msauthor2' ) );
        $blog_id_2 = $this->factory->blog->create( array( 'user_id' => $author_1->ID ) );
        
        switch_to_blog( $blog_id_2 );
        
		$blog_2_post_1 = $this->factory->post->create( array(
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_author'     => $author_1->ID,
        ) );
        
        // author 1 should have an author page
		$this->go_to( get_author_posts_url( $author_1->ID ) );
        $this->assertQueryTrue( 'is_author', 'is_archive' );
        
		// add the user to the blog
		add_user_to_blog( $blog_id_2, $author_2->ID, 'author' );
        
        // author 2 is now on the blog, but with no published posts
		$this->go_to( get_author_posts_url( $author_2->ID ) );
		$this->assertQueryTrue( 'is_author', 'is_archive' );
        
        // add the user as an author on the original post
		$ssl_alp->coauthors->set_coauthors( $blog_2_post_1, array( $author_1, $author_2 ) );
        
        // author 2 should now have an author page        
        $this->go_to( get_author_posts_url( $author_2->ID ) );
		$this->assertQueryTrue( 'is_author', 'is_archive' );
        
        // remove the user from the blog
        remove_user_from_blog( $author_2->ID, $blog_id_2 );
        
        // author 2 should still keep their archive page since they still have a published post
		$this->go_to( get_author_posts_url( $author_2->ID ) );
        $this->assertQueryTrue( 'is_author', 'is_archive' );
        
		// delete the user from the network
        wpmu_delete_user( $author_2->ID );
        
		// author 2 should now not have an author page
		$this->go_to( get_author_posts_url( $author_2->ID ) );
		$this->assertEquals( false, get_user_by( 'id', $author_2->ID ) );
        
        restore_current_blog();
	}

    /**
     * Counts posts in the loop
     */
    private function count_posts() {
        $count = 0;

        while( have_posts() ) {
            the_post();
            $count++;
        }

        return $count;
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
     * @expectedException WPDieException
     */
    function test_contributor_edit_own_post() {
        // contributors can't edit their own posts
        $this->_do_test_edit_post( self::$contributor_ids[0], self::$contributor_ids[0] );
    }

    function test_edit_own_post() {
        // authors, editors and admins can edit their own posts
        $this->assertTrue( $this->_do_test_edit_post( self::$author_ids[0], self::$author_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$editor_ids[0], self::$editor_ids[0] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[0], self::$admin_ids[0] ) );
    }

    /**
     * @expectedException WPDieException
     */
    function test_contributor_edit_other_post_1() {
        // contributors can't edit other contributors' posts
        $this->_do_test_edit_post( self::$contributor_ids[0], self::$contributor_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_contributor_edit_other_post_2() {
        // contributors can't edit authors' posts
        $this->_do_test_edit_post( self::$author_ids[0], self::$contributor_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_contributor_edit_other_post_3() {
        // contributors can't edit editors' posts
        $this->_do_test_edit_post( self::$editor_ids[0], self::$contributor_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_contributor_edit_other_post_4() {
        // contributors can't edit admins' posts
        $this->_do_test_edit_post( self::$admin_ids[0], self::$contributor_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_author_edit_other_post_1() {
        // authors can't edit contributors' posts
        $this->_do_test_edit_post( self::$contributor_ids[0], self::$author_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_author_edit_other_post_2() {
        // authors can't edit other authors' posts
        $this->_do_test_edit_post( self::$author_ids[0], self::$author_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_author_edit_other_post_3() {
        // authors can't edit editors' posts
        $this->_do_test_edit_post( self::$editor_ids[0], self::$author_ids[1] );
    }

    /**
     * @expectedException WPDieException
     */
    function test_author_edit_other_post_4() {
        // authors can't edit admins' posts
        $this->_do_test_edit_post( self::$admin_ids[0], self::$author_ids[1] );
    }

    function test_editor_edit_other_post() {
        // editors can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( self::$contributor_ids[0], self::$editor_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$author_ids[0], self::$editor_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$editor_ids[0], self::$editor_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[0], self::$editor_ids[1] ) );
    }

    function test_admin_edit_other_post() {
        // admins can edit others' posts
        $this->assertTrue( $this->_do_test_edit_post( self::$contributor_ids[0], self::$admin_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$author_ids[0], self::$admin_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$editor_ids[0], self::$admin_ids[1] ) );
        $this->assertTrue( $this->_do_test_edit_post( self::$admin_ids[0], self::$admin_ids[1] ) );
    }
}
