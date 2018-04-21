<?php

/**
 * Coauthors tests
 */
class CoauthorsTest extends WP_UnitTestCase {
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
        
        // add term by user id
        $this->assertFalse( get_term_by( 'name', $user_1->user_login, 'ssl_alp_coauthor' ) );
        $ssl_alp->coauthors->add_coauthor_term( $user_1->ID );
        $this->assertInstanceOf( 'WP_Term', get_term_by( 'name', $user_1->user_login, 'ssl_alp_coauthor' ) );
        
        // add term by user object
        $this->assertFalse( get_term_by( 'name', $user_2->user_login, 'ssl_alp_coauthor' ) );
        $ssl_alp->coauthors->add_coauthor_term( $user_2 );
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
     * Check that programmatically changing post primary authors works. The coauthors
     * should not be removed, just the order changed. Only if post data is detected
     * should coauthors be completely replaced.
     */
    function test_update_post_author_programmatic() {
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

        // post should now have user 1 as primary author
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // the coauthors should be flipped
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $post ),
            array(
                $this->user_1,
                $this->user_2
            )
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

        // get updated post object
        $post = get_post( $this->post_1->ID );

        // post should have no coauthors
        $this->assertEquals( $ssl_alp->coauthors->get_coauthors( $post ), array() );

        // user's post should be trashed
        $this->assertEquals( $post->post_status, 'trash' );
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
     * doesn't also delete that post, when the user's posts are reassigned. It should just
     * change the post to remove the author and add the reassigned author, and leave the
     * primary author untouched.
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

        // refresh post object
        $post = get_post( $this->post_1->ID );

        // check the primary author wasn't changed
        $this->assertEquals( $post->post_author, $this->user_1->ID );

        // check user is deleted from coauthors
        $this->assertEquals(
            $ssl_alp->coauthors->get_coauthors( $this->post_3 ),
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
}
