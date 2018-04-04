<?php

/**
 * Cross-references tests
 */
class CrossReferencesTest extends WP_UnitTestCase {
	public function setUp() {
		global $ssl_alp;

		// call parent setup
		parent::setUp();

		/**
		 * create posts
		 */
		
		$this->post_1 = $this->factory->post->create(
			array(
				'post_content'	=>	'This contains no cross-references.'
			)
		);

		$this->post_2 = $this->factory->post->create(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a>.',
					get_permalink( $this->post_1 )
				)
			)
		);

		$this->post_3 = $this->factory->post->create(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a> and <a href="%s">post 2</a>.',
					get_permalink( $this->post_1 ),
					get_permalink( $this->post_2 )
				)
			)
		);

		// rebuild references
		$ssl_alp->references->rebuild_references();
	}

	public function test_get_references() {
		global $ssl_alp;
		
		/**
		 * test get_reference_to_posts can handle post IDs or objects
		 */

		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_1 ),
			$ssl_alp->references->get_reference_to_posts( get_post( $this->post_1 ) )
		);

		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_2 ),
			$ssl_alp->references->get_reference_to_posts( get_post( $this->post_2 ) )
		);

		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_3 ),
			$ssl_alp->references->get_reference_to_posts( get_post( $this->post_3 ) )
		);

		/**
		 * test get_reference_from_posts can handle post IDs or objects
		 */

		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_1 ),
			$ssl_alp->references->get_reference_from_posts( get_post( $this->post_1 ) )
		);

		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_2 ),
			$ssl_alp->references->get_reference_from_posts( get_post( $this->post_2 ) )
		);

		$this->assertEquals(
			$ssl_alp->references->get_reference_from_posts( $this->post_3 ),
			$ssl_alp->references->get_reference_from_posts( get_post( $this->post_3 ) )
		);
	}

	function test_cross_references() {
		global $ssl_alp;

		// post 1 references nothing
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_1 ),
			array()
		);

		// post 2 references post 1
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_2 ),
			array( get_post( $this->post_1 ) )
		);

		// post 3 references posts 1 and 2
		$this->assertEquals(
			$ssl_alp->references->get_reference_to_posts( $this->post_3 ),
			array( get_post( $this->post_1 ), get_post( $this->post_2 ) )
		);
	}
}
