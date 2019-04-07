<?php

/**
 * Coauthors search tests.
 *
 * Based on WordPress search tests at /tests/phpunit/tests/query/search.php.
 */
class CoauthorsSearchTest extends WP_UnitTestCase {
    protected $q;
    protected $editor;
    protected $user1;
    protected $user2;
    protected $user3;
    protected $user4;
    protected $post1;
    protected $post2;
    protected $post3;

	public function setUp() {
        parent::setUp();

        $this->q = new WP_Query();

        $this->editor = $this->factory->user->create_and_get(
            array(
                'role' => 'editor'
            )
        );

        $this->user1 = $this->factory->user->create_and_get();
        $this->user2 = $this->factory->user->create_and_get();
        $this->user3 = $this->factory->user->create_and_get();
        $this->user4 = $this->factory->user->create_and_get();

        $this->post1 = $this->create_coauthor_post( $this->user1, array( $this->user1, $this->user2, $this->user3 ) );
        $this->post2 = $this->create_coauthor_post( $this->user1, array( $this->user1, $this->user3 ) );
        $this->post3 = $this->create_coauthor_post( $this->user2, array( $this->user2 ) );
	}

	public function tearDown() {
        unset( $this->q );

		parent::tearDown();
	}

    /**
     * Create a post with coauthors.
     *
     * Note: the $coauthors array must contain the $author to emulate front-end
     * behaviour.
     */
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
                'post_type'       => 'post',
            )
        );

        if ( ! empty( $terms ) ) {
            wp_set_post_terms( $post->ID, $terms, 'ssl_alp_coauthor' );
        }

        return $post;
    }

    protected function get_search_results( $search_string, $extra_args ) {
        $query_data = array(
            's'              => $search_string,
            'post_type'      => 'post',
            'posts_per_page' => -1, // Required to get all results.
        );

        $query_data = wp_parse_args( $extra_args, $query_data );

		return $this->q->query( $query_data );
    }

    public function test_coauthor_in() {
        global $ssl_alp;

        // Set user to one that can do advanced searches.
        wp_set_current_user( $this->editor->ID );

        // User 1 has two posts.
		$this->assertEqualSets(
            array(
                $this->post1,
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                    ),
                )
            )
        );

        // User 2 has two posts.
		$this->assertEqualSets(
            array(
                $this->post1,
                $this->post3,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );

        // User 3 has two posts.
		$this->assertEqualSets(
            array(
                $this->post1,
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                )
            )
        );

        // User 4 has no posts.
		$this->assertEmpty(
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user4 )->term_id,
                    ),
                )
            )
        );
    }

    public function test_coauthor_not_in() {
        global $ssl_alp;

        // Set user to one that can do advanced searches.
        wp_set_current_user( $this->editor->ID );

		$this->assertEqualSets(
            array(
                $this->post3,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                    ),
                )
            )
        );

		$this->assertEqualSets(
            array(
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );

        $this->assertEqualSets(
            array(
                $this->post3,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                )
            )
        );

        $this->assertEqualSets(
            array(
                $this->post1,
                $this->post2,
                $this->post3,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user4 )->term_id,
                    ),
                )
            )
        );
    }

    public function test_coauthor_and() {
        global $ssl_alp;

        // Set user to one that can do advanced searches.
        wp_set_current_user( $this->editor->ID );

		$this->assertEqualSets(
            array(
                $this->post1,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );

        $this->assertEqualSets(
            array(
                $this->post1,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                )
            )
        );


        $this->assertEqualSets(
            array(
                $this->post1,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );

        $this->assertEqualSets(
            array(
                $this->post1,
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                )
            )
        );

        $this->assertEmpty(
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user4 )->term_id,
                    ),
                )
            )
        );
    }

    public function test_coauthor_in_not_in() {
        global $ssl_alp;

        // Set user to one that can do advanced searches.
        wp_set_current_user( $this->editor->ID );

		$this->assertEqualSets(
            array(
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );
    }

    public function test_coauthor_and_not_in() {
        global $ssl_alp;

        // Set user to one that can do advanced searches.
        wp_set_current_user( $this->editor->ID );

		$this->assertEqualSets(
            array(
                $this->post2,
            ),
            $this->get_search_results(
                '',
                array(
                    'ssl_alp_coauthor__and' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user1 )->term_id,
                        $ssl_alp->coauthors->get_coauthor_term( $this->user3 )->term_id,
                    ),
                    'ssl_alp_coauthor__not_in' => array(
                        $ssl_alp->coauthors->get_coauthor_term( $this->user2 )->term_id,
                    ),
                )
            )
        );
    }
}
