<?php

/**
 * Revisions tests
 */
class RevisionsTest extends WP_UnitTestCase {
    protected $user_1;
    protected $user_2;
    protected $user_3;

    public function setUp() {
        parent::setUp();

        $this->user_1 = $this->factory->user->create_and_get(
            array(
                'role' => 'editor'
            )
        );
        $this->user_2 = $this->factory->user->create_and_get();
        $this->user_3 = $this->factory->user->create_and_get();
    }

    private function get_read_status( $post, $user ) {
        global $ssl_alp;

        return $ssl_alp->revisions->get_post_read_status( $post, $user );
    }

    private function set_read_status( $read, $post, $user ) {
        global $ssl_alp;

        return $ssl_alp->revisions->set_post_read_status( $read, $post, $user );
    }

	public function test_unread_flags_not_set_for_unpublished_posts() {
        wp_set_current_user( $this->user_1->ID );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author'  => $this->user_1->ID,
                'post_status'  => 'draft',
                'post_content' => 'first line',
            )
        );

        // Post is 'read' if it is unpublished (i.e. not 'unread').
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_3 ) );

        // Edit post, remaining draft.
		edit_post(
			array(
				'post_ID'		=> $post->ID,
                'post_content'  => "first line\nsecond line",
			)
		);

        // Still read.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_3 ) );

        // Edit post, set pending.
		edit_post(
			array(
                'post_ID'		=> $post->ID,
                'post_status'   => 'pending',
			)
		);

        // Still read.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_3 ) );

        // Edit post, set private.
		edit_post(
			array(
                'post_ID'		=> $post->ID,
                'post_status'   => 'private',
			)
		);

        // Still read.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_3 ) );

        // Edit post, set auto-draft.
		edit_post(
			array(
                'post_ID'		=> $post->ID,
                'post_status'   => 'auto-draft',
			)
		);

        // Still read.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_3 ) );
    }

	public function test_unread_flags_set_for_other_users() {
        wp_set_current_user( $this->user_1->ID );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author'  => $this->user_1->ID,
                'post_status'  => 'draft',
                'post_content' => 'first line',
            )
        );

        // Update post to publish: this triggers the read flags.
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
                'post_status'   => 'publish',
			)
		);

        // Publishing user shouldn't have an unread flag, but everyone else should.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_3 ) );
    }

    /**
     * Check unread flags are not changed if a post undergoes only a minor edit.
     */
	public function test_unread_flags_unchanged_when_post_minor_edited() {
        wp_set_current_user( $this->user_1->ID );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author'  => $this->user_1->ID,
                'post_status'  => 'draft',
                'post_content' => 'first line',
            )
        );

        // Update post to publish: this triggers the read flags.
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
                'post_status'   => 'publish',
			)
		);

        // Publishing user shouldn't have an unread flag, but everyone else should.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_3 ) );

        // Set read flags.
        $this->set_read_status( true, $post, $this->user_2 );

        // Update post to publish: this triggers the read flags.
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
                'post_content'  => 'first line.',
			)
        );

        // User 2 should keep their read flag.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertTrue( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_3 ) );
    }

    /**
     * Check unread flags are changed if a post undergoes a major edit.
     */
	public function test_unread_flags_changed_when_post_major_edited() {
        wp_set_current_user( $this->user_1->ID );

        $post = $this->factory->post->create_and_get(
            array(
                'post_author'  => $this->user_1->ID,
                'post_status'  => 'draft',
                'post_content' => 'first line',
            )
        );

        // Update post to publish: this triggers the read flags.
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
                'post_status'   => 'publish',
			)
		);

        // Publishing user shouldn't have an unread flag, but everyone else should.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_3 ) );

        // Set read flags.
        $this->set_read_status( true, $post, $this->user_2 );

        // Update post to publish: this triggers the read flags.
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
                'post_content'  => "first line\nsecond line\nthird line", // Add two extra lines.
			)
        );

        // User 2's read flag should have been reset.
        $this->assertTrue( $this->get_read_status( $post, $this->user_1 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_2 ) );
        $this->assertFalse( $this->get_read_status( $post, $this->user_3 ) );
    }
}
