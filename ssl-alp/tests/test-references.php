<?php

/**
 * Cross-references tests
 */
class CrossReferencesTest extends WP_UnitTestCase {
	protected static $_post_1;
	protected static $_post_2;
	protected static $_post_3;
	protected $post_1;
	protected $post_2;
	protected $post_3;

	public static function wpSetUpBeforeClass( $factory ) {
		/**
		 * create posts
		 */
		
		self::$_post_1 = $factory->post->create_and_get(
			array(
				'post_content'	=>	'This contains no cross-references.'
			)
		);

		self::$_post_2 = $factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a>.',
					get_permalink( self::$_post_1 )
				)
			)
		);

		self::$_post_3 = $factory->post->create_and_get(
			array(
				'post_content'	=>	sprintf(
					'Cross reference to <a href="%s">post 1</a> and <a href="%s">post 2</a>.',
					get_permalink( self::$_post_1 ),
					get_permalink( self::$_post_2 )
				)
			)
		);
	}

	public function setUp() {		
		parent::setUp();

		$this->post_1 = clone self::$_post_1;
		$this->post_2 = clone self::$_post_2;
		$this->post_3 = clone self::$_post_3;
	}

	public function test_get_references() {
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
	}

	function test_references_from() {
		global $ssl_alp;

		// rebuild references
		$ssl_alp->references->rebuild_references();

		// post 1 referenced by 2 and 3
		$this->assertEqualSets(
			$ssl_alp->references->get_reference_from_posts( $this->post_1 ),
			array( $this->post_2, $this->post_3 )
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
	}
}
