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

    private function create_coauthor_post( $author, $coauthors = array() ) {
        global $ssl_alp;

        $terms = array();

        foreach ( $coauthors as $coauthor ) {
            $terms[] = intval( $ssl_alp->coauthors->get_coauthor_term( $coauthor )->term_id );
        }

        // cannot set taxonomy before post creation
        $post = $this->factory->post->create_and_get(
            array(
                'post_author'     => $author->ID,
                'post_status'     => 'publish',
                'post_type'       => 'post'
            )
        );

        if ( ! empty( $terms ) ) {
            wp_set_post_terms( $post->ID, $terms, "ssl_alp_coauthor" );
        }

        return $post;
    }

    /**
     * Counts posts in the loop
     */
    private function count_posts() {
        $count = 0;

        while ( have_posts() ) {
            the_post();
            $count++;
        }

        return $count;
    }

    /**
     * Delete user(s) from blog on a multisite installation.
     * 
     * This replicates the behaviour of wp-admin/network/users.php?action=dodelete.
     * 
     * $user_ids should be an array of user ids to be deleted
     * $blog_users should be an array of user id => array( blog id => reassign user id ).
     * The reassign user id can be null, in which case no reassignment is made.
     */
    private function delete_users_multisite( $user_ids, $blog_users ) {
        // go to network admin user delete page
        $this->go_to( network_admin_url( 'network/users.php' ) );
        
        /*
         * set expected postdata (required as `remove_user_from_blog` action inspects it)
         */

        // users to delete
        $_POST['user'] = $user_ids;

        // users to reassign deleted user's content to, per blog
        $_POST['blog'] = $blog_users;

        // remove user from blogs
        foreach ( $blog_users as $user_id => $blog_users ) {
            foreach ( $blog_users as $blog_id => $reassign_user_id ) {
                if ( ! is_null( $reassign_user_id ) ) {
                    remove_user_from_blog( $user_id, $blog_id, $reassign_user_id );
                } else {
                    remove_user_from_blog( $user_id, $blog_id );
                }
            }
        }

        // delete user
        foreach ( $user_ids as $user_id ) {
            wpmu_delete_user( $user_id );
        }
    }

    /**
     * Deletes user in different way depending on whether this is a single site or network
     * 
     * Note: `delete_user` is already defined as a static method.
     */
    private function ssl_delete_user( $user_id, $reassign_id = null ) {
        if ( ! is_multisite() ) {
            return wp_delete_user( $user_id, $reassign_id );
        } else {
            return $this->delete_users_multisite(
                array( $user_id ),
                array(
                    $user_id => array(
                        get_current_blog_id() => $reassign_id
                    )
                )
            );
        }
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

	function test_get_coauthors() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user, array( $user ) );

        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user
            )
        );
    }

    /**
     * Coauthor list should still include primary author even if no coauthors were explicitly set.
     */
	function test_get_coauthors_no_term_set() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user );

        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user
            )
        );
    }

    function test_set_coauthors() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();

        $post = $post = $this->create_coauthor_post($user_1 );

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );

        // check coauthors
		$this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );
    }

    /**
     * Setting coauthors overwrites existing coauthors
     */
    function test_set_coauthors_overwriting_existing_coauthors() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();

        $post = $this->create_coauthor_post($user_1, array( $user_1 ) );

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );

        // check coauthors
		$this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );
    }

    function test_add_same_coauthor_to_post__author_id_arg() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user, array( $user ) );

        // set duplicate users to post
        $ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                $user,
                $user
            )
        );

        $query = new WP_Query(
            array(
			    'author' => $user->ID
            )
        );
        
        // check user query
		$this->assertEquals( 1, count( $query->posts ) );
        $this->assertEquals( $post->ID, $query->posts[ 0 ]->ID );
        
        // check get_coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user
            )
        );
    }

    function test_add_same_coauthor_to_post__author_name_arg() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user, array( $user ) );

        // set duplicate users to post
        $ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                $user,
                $user
            )
        );

        $query = new WP_Query(
            array(
			    'author_name' => $user->user_login
            )
        );
        
        // check user query
		$this->assertEquals( 1, count( $query->posts ) );
        $this->assertEquals( $post->ID, $query->posts[ 0 ]->ID );
        
        // check get_coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user
            )
        );
    }
    
	public function test__author_name_arg_plus_tax_query__user_is_post_author() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user, array( $user ) );
        
        $ssl_alp->coauthors->set_coauthors( $post, array( $user ) );
        
        wp_set_post_terms( $post->ID, 'test', 'post_tag' );
        
		$query = new WP_Query(
            array(
			    'author_name' => $user->user_login,
			    'tag' => 'test',
            )
        );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post->ID, $query->posts[ 0 ]->ID );
    }
    
	public function tests__author_name_arg_plus_tax_query__is_coauthor() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user_1, array( $user_1 ) );

		$ssl_alp->coauthors->set_coauthors(
            $post->ID,
            array(
                $user_1,
                $user_2
            )
        );

        wp_set_post_terms( $post->ID, 'test', 'post_tag' );
        
		$query = new WP_Query(
            array(
			    'author_name' => $user_2->user_login,
			    'tag' => 'test',
            )
        );

		$this->assertEquals( 1, count( $query->posts ) );
		$this->assertEquals( $post->ID, $query->posts[ 0 ]->ID );
	}

	function test_add_coauthor_updates_post_author() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user_1, array( $user_1 ) );

        // override post 1's author
		$ssl_alp->coauthors->set_coauthors(
            $post,
            array(
                $user_2,
                $user_3
            )
        );

        // refresh post
        $post = get_post( $post->ID );

        // WordPress core author should have changed to first in above list
		$this->assertEquals( $post->post_author, $user_2->ID );
	}

    function test_coauthor_order() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post($user_1, array( $user_1 ) );

        // set coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $post ),
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );

        // check order (use assertEquals to check order)
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );

        // change order
        $ssl_alp->coauthors->set_coauthors(
            get_post( $post ),
            array(
                $user_3,
                $user_2,
                $user_1
            )
        );

        // check order (use assertEquals to check order)
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_3,
                $user_2,
                $user_1
            )
        );
    }

    function test_coauthor_terms_created_when_user_created() {
        global $ssl_alp;

        // create new users
        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        
        // check terms were created
        $this->assertInstanceOf( 'WP_Term', $ssl_alp->coauthors->get_coauthor_term( $user_1 ) );
        $this->assertInstanceOf( 'WP_Term', $ssl_alp->coauthors->get_coauthor_term( $user_2 ) );
    }

    /**
     * Check lists of posts where a user is coauthor.
     */
    function test_get_coauthor_posts() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get(array('display_name' => 'ssl user 1'));
        $user_2 = $this->factory->user->create_and_get(array('display_name' => 'ssl user 2'));
        $user_3 = $this->factory->user->create_and_get(array('display_name' => 'ssl user 3'));
        $post_1 = $this->create_coauthor_post( $user_1, array( $user_1 ) );
        $post_2 = $this->create_coauthor_post( $user_2, array( $user_2 ) );
        $post_3 = $this->create_coauthor_post( $user_3, array( $user_3 ) );
        $post_4 = $this->create_coauthor_post( $user_1, array( $user_1, $user_2 ) );
        $post_5 = $this->create_coauthor_post( $user_1, array( $user_1, $user_3 ) );
        $post_6 = $this->create_coauthor_post( $user_1, array( $user_1, $user_2, $user_3 ) );

        // user 1
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $user_1 ),
            array(
                $post_1, $post_4, $post_5, $post_6
            )
        );

        // user 2
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $user_2 ),
            array(
                $post_2, $post_4, $post_6
            )
        );

        // user 3
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthor_posts( $user_3 ),
            array(
                $post_3, $post_5, $post_6
            )
        );
    }

    /**
     * Check that changing the only author of a post works.
     */
    function test_update_post_remove_primary_coauthor() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $post_1 = $this->create_coauthor_post( $user_1, array( $user_1 ) );
        $post_2 = $this->create_coauthor_post( $user_2, array( $user_2 ) );

        // set post 2's coauthors to only user 1 (removes user 2)
        $ssl_alp->coauthors->set_coauthors(
            $post_2,
            array(
                $user_1
            )
        );

        // refresh post object
        $post_2 = get_post( $post_2->ID );

        // post should now have user 1 as primary author
        $this->assertEquals( $post_2->post_author, $user_1->ID );

        // and user 1 should be sole coauthor
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post_1 ),
            array(
                $user_1
            )
        );
    }

    /**
     * Check that deleting a user that is the primary author of a post with no other
     * authors deletes that post.
     */
    function test_coauthor_delete_user_with_sole_author_post() {
        global $ssl_alp;

        $user = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user, array( $user ) );

        // copy post id
        $post_id = $post->ID;

        // delete user
        $this->ssl_delete_user( $user->ID );

        // user should be deleted
        $this->check_user_deleted( $user->ID );

        // updated post should be trash
        $this->assertEquals( get_post( $post_id )->post_status, 'trash' );
    }

    /**
     * Check that deleting a user that is the primary author of a post with no other
     * authors, when reassigning the author's posts to another user, does not delete
     * that post.
     */
    function test_coauthor_delete_user_with_sole_author_post_with_reassign() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1 ) );

        // delete user, reassigning to user 2
        $this->ssl_delete_user( $user_1->ID, $user_2->ID );

        // user should be deleted
        $this->check_user_deleted( $user_1->ID );

        // post should have user 2 as author
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( get_post( $post->ID ) ),
            array(
                $user_2
            )
        );

        // post should retain its original publication status
        $this->assertEquals( get_post( $post->ID)->post_status, $post->post_status );
    }

    /**
     * Check that deleting a user that is a primary author of a post with multiple authors
     * doesn't also delete that post. It should just remove that author from the post, and
     * set a new primary author.
     */
    function test_coauthor_delete_user_that_is_primary_author_of_multi_author_post() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2 ) );

        // delete user
        $this->ssl_delete_user( $user_1->ID );

        // user should be deleted
        $this->check_user_deleted( $user_1->ID );

        // get updated post object
        $post = get_post( $post->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $user_2->ID );

        // post should have only user 2 as coauthor
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_1 ) );
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

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2 ) );

        // delete user, reassigning their posts to other user
        $this->ssl_delete_user( $user_1->ID, $user_3->ID );

        // user should be deleted
        $this->check_user_deleted( $user_1->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $user_3->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_3,
                $user_2
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_1 ) );
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

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2, $user_3 ) );

        // delete user, reassigning their posts to other user
        $this->ssl_delete_user( $user_1->ID, $user_2->ID );

        // user should be deleted
        $this->check_user_deleted( $user_1->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author was changed
        $this->assertEquals( $post->post_author, $user_2->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_2,
                $user_3
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_1 ) );
    }

    /**
     * Check that deleting a user that is a secondary author of a post with multiple authors
     * doesn't also delete that post. It should just remove that author from the post, and
     * leave the primary author untouched.
     */
    function test_coauthor_delete_user_that_is_secondary_author_of_multi_author_post() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2, $user_3 ) );

        // delete user
        $this->ssl_delete_user( $user_2->ID );

        // user should be deleted
        $this->check_user_deleted( $user_2->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_3
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_2 ) );
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

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2 ) );

        // delete user, reassigning their posts to other user
        $this->ssl_delete_user( $user_2->ID, $user_3->ID );

        // user should be deleted
        $this->check_user_deleted( $user_2->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_3
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_2 ) );
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

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $user_4 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2, $user_3, $user_4 ) );

        // delete user, reassigning their posts to other user
        $this->ssl_delete_user( $user_2->ID, $user_4->ID );

        // user should be deleted
        $this->check_user_deleted( $user_2->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_4,
                $user_3
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_2 ) );
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

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();
        $user_3 = $this->factory->user->create_and_get();
        $user_4 = $this->factory->user->create_and_get();
        $post = $this->create_coauthor_post( $user_1, array( $user_1, $user_2, $user_3, $user_4 ) );

        // delete user, reassigning their posts to other user
        $this->ssl_delete_user( $user_4->ID, $user_2->ID );

        // user should be deleted
        $this->check_user_deleted( $user_4->ID );

        // refresh post object
        $post = get_post( $post->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $user_1,
                $user_2,
                $user_3
            )
        );

        // check post wasn't trashed
        $this->assertEquals( $post->post_status, 'publish' );

        // check term is also deleted
        $this->assertFalse( $ssl_alp->coauthors->get_coauthor_term( $user_4 ) );
    }

    // /**
    //  * Test post counts including coauthored posts.
    //  */
    // function test_post_counts() {
    //     global $ssl_alp;

    //     // users initially have 1 each
    //     $this->assertEquals( count_user_posts( $this->user_1->ID), 1 );
    //     $this->assertEquals( count_user_posts( $this->user_2->ID), 1 );
    //     $this->assertEquals( count_user_posts( $this->user_3->ID), 1 );

    //     // create a bunch of posts
    //     $count = 10;
    //     $post_ids = $this->factory->post->create_many( $count );

    //     // set coauthors
    //     foreach ( $post_ids as $post_id ) {
    //         $ssl_alp->coauthors->set_coauthors(
    //             $post_id,
    //             array(
    //                 $this->user_1,
    //                 $this->user_2
    //             )
    //         );
    //     }

    //     // user 1 and 2 should have $count more posts
    //     $this->assertEquals( count_user_posts( $this->user_1->ID), $count + 1 );
    //     $this->assertEquals( count_user_posts( $this->user_2->ID), $count + 1 );
    //     $this->assertEquals( count_user_posts( $this->user_3->ID), 1 );

    //     // test our implementation of count_many_users_posts
    //     $this->assertEquals(
    //         array_values(
    //             $ssl_alp->coauthors->count_many_users_posts(
    //                 array(
    //                     $this->user_1->ID,
    //                     $this->user_2->ID,
    //                     $this->user_3->ID
    //                 )
    //             )
    //         ),
    //         array(
    //             $count + 1,
    //             $count + 1,
    //             1
    //         )
    //     );
    // }

    // /**
    //  * Check the reported number of coauthored posts by an author agrees with the
    //  * actual amount
    //  */
    // function test_reported_count_vs_actual_count() {
    //     global $ssl_alp;

    //     // create new user
    //     $user = $this->factory->user->create_and_get();

    //     // create posts as $user
    //     wp_set_current_user( $user->ID );
    //     $this->factory->post->create_many( 5 );

    //     // create posts as other user
    //     wp_set_current_user( $this->user_1->ID );
    //     $post_ids = $this->factory->post->create_many( 5 );

    //     // set $user to be coauthor
    //     foreach ( $post_ids as $post_id ) {
    //         $ssl_alp->coauthors->set_coauthors(
    //             get_post( $post_id ),
    //             array(
    //                 $this->user_1,
    //                 $user
    //             )
    //         );
    //     }

    //     // create posts as other user
    //     wp_set_current_user( $this->user_2->ID );
    //     $post_ids = $this->factory->post->create_many( 5 );

    //     // set $user to be coauthor
    //     foreach ( $post_ids as $post_id ) {
    //         $ssl_alp->coauthors->set_coauthors(
    //             get_post( $post_id ),
    //             array(
    //                 $this->user_1,
    //                 $this->user_2,
    //                 $user
    //             )
    //         );
    //     }

    //     // check filter reports correct count
    //     $this->assertEquals( 15, count_user_posts( $user->ID ) );

    //     // check manually get correct count
    //     $this->assertEquals( 15, count( $ssl_alp->coauthors->get_coauthor_posts( $user ) ) );
    // }

    // /* doesn't work on network sites - WP_Query object isn't a user post query for some reason (it works
    //    for single sites...)
    // function test_author_page_posts() {
    //     global $ssl_alp;

    //     $user_1 = $this->factory->user->create_and_get();
    //     $user_2 = $this->factory->user->create_and_get();
    //     $user_3 = $this->factory->user->create_and_get();

    //     // create a bunch of posts
    //     $post_ids = $this->factory->post->create_many( 5 );

    //     // set coauthors
    //     foreach ( $post_ids as $post_id ) {
    //         $ssl_alp->coauthors->set_coauthors(
    //             $post_id,
    //             array(
    //                 $user_1,
    //                 $user_2
    //             )
    //         );
    //     }

    //     // extra post for user 1 with no coauthors
    //     $post_1 = $this->factory->post->create_and_get(
    //         array(
    //             'post_author'   =>  $user_1->ID
    //         )
    //     );

    //     $post_ids[] = $post_1->ID;

    //     // user 1 should have 6
    //     $this->go_to( get_author_posts_url( $user_1->ID ) );

    //     log_message( have_posts() ? "has posts" : "no posts" );
    //     log_message( $wp_query->posts );

    //     $this->assertEquals( $this->count_posts(), 6 );

    //     // user 2 should have 5
    //     $this->go_to( get_author_posts_url( $user_2->ID ) );
    //     $this->assertEquals( $this->count_posts(), 5 );

    //     // user 3 should have 0
    //     $this->go_to( get_author_posts_url( $user_3->ID ) );
    //     $this->assertEquals( $this->count_posts(), 0 );
    // }*/

    /**
     * Test that renaming a user also renames their corresponding term.
     */
    public function test_rename_user_renames_coauthor_terms() {
        global $ssl_alp;

        $user_1 = $this->factory->user->create_and_get();
        $user_2 = $this->factory->user->create_and_get();

        // NEEDS TO USE create_coauthor_post
        // add a bunch of coauthored posts for user 1
        $this->factory->post->create_many(
            5,
            array(
			    'post_author'     => $user_1->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );

        // get existing user term
        $user_1_old_term = $ssl_alp->coauthors->get_coauthor_term( $user_1 );

        // term name should be the display name
        $this->assertEquals( $user_1_old_term->name, $user_1->display_name );

        // user 1 should have 5 posts, user 2 should have none
        $this->assertEquals( count_user_posts( $user_1->ID), 5 );
        $this->assertEquals( count_user_posts( $user_2->ID), 0 );

        // rename user 1
        wp_update_user(
            array(
                'ID'    =>  $user_1->ID,
                // note: user_login cannot be altered, even if it is specified here
                'user_nicename' =>  'updated_user_1',
                'display_name'  =>  'display',
                'first_name'    =>  'first',
                'last_name'     =>  'last'
            )
        );

        // refresh user object
        $user_1 = get_user_by( 'id', $user_1->ID );

        // get new user term
        $user_1_new_term = $ssl_alp->coauthors->get_coauthor_term( $user_1 );

        // term name should be the new display name
        $this->assertEquals( $user_1_new_term->name, 'display' );

        // users should still have same number of posts
        $this->assertEquals( count_user_posts( $user_1->ID), 5 );
        $this->assertEquals( count_user_posts( $user_2->ID), 0 );
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
