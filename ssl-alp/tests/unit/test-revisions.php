<?php

/**
 * Revisions tests
 */
class RevisionsTest extends WP_UnitTestCase {
	function test_revision_edit_messages_are_set() {
        $user_id = $this->factory->user->create(
            array(
                // user must at least be able to edit their own posts
                'role' => 'author'
            )
        );

        $previous_user = wp_get_current_user();
        wp_set_current_user( $user_id );

		// create post
        $post_id = $this->factory->post->create(
            array(
                'post_author'  =>  $user_id
            )
        );

        /*
         * First edit
         */

        // set edit message
        $_POST['ssl_alp_revision_post_edit_summary'] = 'First edit message.';
        $_POST['ssl_alp_edit_summary_nonce'] = wp_create_nonce( 'ssl-alp-edit-summary' );
        
		// update post in order for initial revision to be stored
		wp_update_post(
			array(
				'post_content'  => 'changed content',
                'ID'            => $post_id
			)
        );
        
		// get revisions
        $revisions = wp_get_post_revisions(
			$post_id,
			array(
				'orderby'	=>	'date',
				'order'		=>	'DESC'
			)
        );
        
        $this->assertCount( 1, $revisions );

        $revision = reset( $revisions );

        // check message
        $revision_meta = get_post_meta( $revision->ID, 'ssl_alp_edit_summary' );
        $this->assertCount( 1, $revision_meta );
        $this->assertEquals( 'First edit message.', $revision_meta[0]['message'] );

        /*
         * Second edit
         */

        // set edit message
        $_POST['ssl_alp_revision_post_edit_summary'] = 'Second edit message.';
        $_POST['ssl_alp_edit_summary_nonce'] = wp_create_nonce( 'ssl-alp-edit-summary' );
        
		// update post in order for initial revision to be stored
		wp_update_post(
			array(
				'post_content'  => 'changed content again',
                'ID'            => $post_id
			)
        );

		// get revisions
        $revisions = wp_get_post_revisions(
			$post_id,
			array(
				'orderby'	=>	'date',
				'order'		=>	'DESC'
			)
        );
        
        $this->assertCount( 2, $revisions );

        $revision = reset( $revisions );

        // check message
        $revision_meta = get_post_meta( $revision->ID, 'ssl_alp_edit_summary' );
        $this->assertCount( 1, $revision_meta );
        $this->assertEquals( 'Second edit message.', $revision_meta[0]['message'] );

        // restore previous user
        wp_set_current_user( $previous_user->ID );
	}
}
