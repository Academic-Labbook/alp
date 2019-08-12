<?php

/**
 * Default category tests
 */
class DefaultCategoryTest extends WP_UnitTestCase {
	public function test_lone_default_category_not_removed() {
        $post = $this->factory->post->create_and_get();

        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );

        $this->assertEquals(
            wp_list_pluck( $categories, 'term_id' ),
            array( get_option( 'default_category' ) )
        );
    }

    public function test_creating_post_with_additional_category_removes_uncategorised() {
        $category = $this->factory->category->create_and_get();

        $post = $this->factory->post->create_and_get(
            array(
                'post_category' => array(
                    get_option( 'default_category' ),
                    $category->term_id,
                )
            )
        );

        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );

        $this->assertEquals(
            wp_list_pluck( $categories, 'term_id' ),
            array( $category->term_id )
        );
    }

    public function test_updating_post_with_additional_category_removes_uncategorised() {
        $post = $this->factory->post->create_and_get();

        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );

        // Only the default category.
        $this->assertEquals(
            wp_list_pluck( $categories, 'term_id' ),
            array( get_option( 'default_category' ) )
        );

        // Now add category.
        $category = $this->factory->category->create_and_get();
        $postarr = $post->to_array();
        $postarr['post_category'][] = $category->term_id;
        wp_update_post( $postarr );

        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );

        // Only the updated category.
        $this->assertEquals(
            wp_list_pluck( $categories, 'term_id' ),
            array( $category->term_id )
        );
    }
}
