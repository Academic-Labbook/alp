<?php

/**
 * Multisite coauthors tests
 */
class MultisiteCoauthorsTest extends WP_UnitTestCase {
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
        
        // remove the user from the blog (this removes their authorship)
        remove_user_from_blog( $author_2->ID, $blog_id_2 );
        
        // author 2 should no longer have an author page on this blog
		$this->go_to( get_author_posts_url( $author_2->ID ) );
        $this->assertQueryTrue( 'is_404' );
        
		// delete the user from the network
        wpmu_delete_user( $author_2->ID );
        
		// author 2 should now not have an author page
        $this->go_to( get_author_posts_url( $author_2->ID ) );
        $this->assertQueryTrue( 'is_404' );
		$this->assertEquals( false, get_user_by( 'id', $author_2->ID ) );
        
        restore_current_blog();
	}
}
