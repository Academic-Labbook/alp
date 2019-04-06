<?php

/**
 * Cross-references tests
 */
class CrossReferencesTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->post_1 = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	'This contains no cross-references.'
			)
		);

		$this->post_2 = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a>.',
					get_permalink( $this->post_1 )
				)
			)
		);

		$this->post_3 = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a> and <a href="%s">post 2</a>.',
					get_permalink( $this->post_1 ),
					get_permalink( $this->post_2 )
				)
			)
		);

		$this->page_1 = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	'This contains no cross-references.',
				'post_type'		=>	'page'
			)
		);

		$this->page_2 = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a>.',
					get_permalink( $this->post_1 )
				),
				'post_type'		=>	'page'
			)
		);
	}

	public function test_references() {
		global $ssl_alp;

		// rebuild references
		$ssl_alp->references->rebuild_references();

		/**
		 * test get_reference_to_posts can handle post IDs or objects
		 */

		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_1 ),
			$ssl_alp->references->get_reference_to_posts( $this->post_1->ID )
		);
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_2 ),
			$ssl_alp->references->get_reference_to_posts( $this->post_2->ID )
		);
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_3 ),
			$ssl_alp->references->get_reference_to_posts( $this->post_3->ID )
		);

		/**
		 * test get_reference_from_posts can handle post IDs or objects
		 */

		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_1 ),
			$ssl_alp->references->get_reference_from_posts( $this->post_1->ID )
		);
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_2 ),
			$ssl_alp->references->get_reference_from_posts( $this->post_2->ID )
		);
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_3 ),
			$ssl_alp->references->get_reference_from_posts( $this->post_3->ID )
		);
	}

	function test_references_to() {
		global $ssl_alp;

		// rebuild references
		$ssl_alp->references->rebuild_references();

		// post 1 references nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_1 ),
			array()
		);

		// post 2 references post 1
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_2 ),
			array( $this->post_1 )
		);

		// post 3 references posts 1 and 2
		$this->assertEqualSets(
			$ssl_alp->references->get_reference_to_posts( $this->post_3 ),
			array( $this->post_1, $this->post_2 )
		);

		// page 1 references nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->page_1 ),
			array()
		);

		// page 2 references post 1
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->page_2 ),
			array( $this->post_1 )
		);
	}

	function test_references_from() {
		global $ssl_alp;

		// rebuild references
		$ssl_alp->references->rebuild_references();

		// post 1 referenced by posts 2 and 3 and page 2
		$this->assertEqualSets(
			$ssl_alp->references->get_reference_from_posts( $this->post_1 ),
			array( $this->post_2, $this->post_3, $this->page_2 )
		);

		// post 2 referenced by post 3
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_2 ),
			array( $this->post_3 )
		);

		// post 3 referenced by nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_3 ),
			array()
		);

		// page 1 referenced by nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->page_1 ),
			array()
		);

		// page 2 referenced by nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->page_2 ),
			array()
		);
	}

	function test_invalid_references() {
		global $ssl_alp;

		$post = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	'This contains an <a href="https://google.com/">external reference</a>.'
			)
		);

		// external reference shouldn't show up
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $post ),
			array()
		);
	}

	function test_multi_reference() {
		global $ssl_alp;

		$post = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Reference to <a href="%1$s">post 1</a>, and <a href="%1$s">again</a>; reference to <a href="%2$s">post 2</a>, and <a href="%2$s">again</a>',
					get_permalink( $this->post_1 ),
					get_permalink( $this->post_2 )
				)
			)
		);

		// reference should only show up once for each post
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $post ),
			array( $this->post_1, $this->post_2 )
		);
	}

	function test_references_after_edit_and_revert() {
		global $ssl_alp;

		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );

		// needed to allow editing
		wp_set_current_user( $editor );

		// create post
		$post = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	'Empty.'
			)
		);

		// no references
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $post ),
			array()
		);

		// edit post to add reference
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
				'content'		=>	sprintf(
					'This is a <a href="%1$s">link</a>.',
					get_permalink( $this->post_1 )
				)
			)
		);

		// one reference now
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $post ),
			array( $this->post_1 )
		);

		// edit post to add another reference
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
				'content'		=>	sprintf(
					'This is a <a href="%1$s">link</a>, and <a href="%2$s">another</a>.',
					get_permalink( $this->post_1 ),
					get_permalink( $this->post_2 )
				)
			)
		);

		// two references now
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $post ),
			array( $this->post_1, $this->post_2 )
		);

		// there should be two revisions now
		$revisions = wp_get_post_revisions( $post->ID );
		$this->assertCount( 2, $revisions );

		// earlier revision
		$first_revision = end( $revisions );

		// restore to earlier revision
		wp_restore_post_revision( $first_revision->ID );

		// only one reference like the first time
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $post ),
			array( $this->post_1 )
		);
	}

	function test_self_reference() {
		global $ssl_alp;

		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );

		// needed to allow editing
		wp_set_current_user( $editor );

		// create post
		$post = $this->factory->post->create_and_get(
			array(
				'post_content'	=>	'Empty.'
			)
		);

		// edit post to add self-reference
		edit_post(
			array(
				'post_ID'		=>	$post->ID,
				'content'		=>	sprintf(
					'This is a <a href="%1$s">link</a> to myself. This is a <a href="%2$s">link</a> to another.',
					get_permalink( $post ),
					get_permalink( $this->post_1 )
				)
			)
		);

		// self reference shouldn't show up, but other reference should
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $post ),
			array( $this->post_1 )
		);
	}

	function test_draft_reference() {
		global $ssl_alp;

		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );

		// needed to allow editing
		wp_set_current_user( $editor );

		// Create published post.
		$published = $this->factory->post->create_and_get(
			array(
				'post_content' => 'Empty.',
			)
		);

		// Create draft that links to published post.
		$draft = $this->factory->post->create_and_get(
			array(
				'post_content' => sprintf(
					'This is a <a href="%1$s">link</a> to published post.',
					get_permalink( $published )
				),
				'post_status'  => 'draft',
			)
		);

		// Draft post should show link to published post.
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $draft ),
			array( $published )
		);

		// Published post should not show link from draft.
		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $published ),
			array()
		);
	}
}
