<?php

/**
 * Inventory tests
 */
class InventoryTests extends WP_UnitTestCase {
    protected $admin;
    protected $editor;
    protected $author;
    protected $contributor;
    protected $subscriber;

	public function setUp() {
        parent::setUp();

        $this->admin = $this->factory->user->create_and_get(
            array(
                'role'  =>  'administrator'
            )
        );

        $this->editor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'editor'
            )
        );

        $this->author = $this->factory->user->create_and_get(
            array(
                'role'  =>  'author'
            )
        );

        $this->contributor = $this->factory->user->create_and_get(
            array(
                'role'  =>  'contributor'
            )
        );

        $this->subscriber = $this->factory->user->create_and_get(
            array(
                'role'  =>  'subscriber'
            )
        );
	}

	public function test_creating_inventory_post_creates_corresponding_term() {
        global $ssl_alp;

		$post = $this->factory->post->create_and_get(
			array(
				'post_type' => 'ssl-alp-inventory',
			)
        );

        $this->assertEquals( $post->post_type, 'ssl-alp-inventory' );
        // A corresponding term should have been created.
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertNotFalse( $term );
        $this->assertEquals( $term->slug, $post->ID );
    }

	public function test_deleting_inventory_post_deletes_corresponding_term() {
        global $ssl_alp;

		$post = $this->factory->post->create_and_get(
			array(
				'post_type' => 'ssl-alp-inventory',
			)
        );

        $this->assertEquals( $post->post_type, 'ssl-alp-inventory' );
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertNotFalse( $term );
        $this->assertEquals( $term->slug, $post->ID );

        // Force delete post.
        wp_delete_post( $post->ID, true );

        // The term should be deleted too.
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertFalse( $term );
    }

	public function test_trashing_inventory_post_does_not_delete_corresponding_term() {
        global $ssl_alp;

		$post = $this->factory->post->create_and_get(
			array(
				'post_type' => 'ssl-alp-inventory',
			)
        );

        $this->assertEquals( $post->post_type, 'ssl-alp-inventory' );
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertNotFalse( $term );
        $this->assertEquals( $term->slug, $post->ID );

        // Trash the post.
        wp_trash_post( $post->ID );

        // The term should still be present.
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertNotFalse( $term );
        $this->assertEquals( $term->slug, $post->ID );

        // Now delete the post.
        wp_delete_post( $post->ID );

        // The term should be deleted too.
        $term = get_term_by( 'slug', $post->ID, 'ssl_alp_inventory_item' );
        $this->assertFalse( $term );
    }

    public function test_user_cannot_create_inventory_item_terms_manually() {
        $users = array(
            $this->admin,
            $this->editor,
            $this->author,
            $this->contributor,
            $this->subscriber,
        );

        foreach ( $users as $user ) {
            wp_set_current_user( $user->ID );
            $term = wp_insert_term( 'Test', 'ssl_alp_inventory_item' );
            $this->assertTrue( is_wp_error( $term ) );
        }
    }
}
