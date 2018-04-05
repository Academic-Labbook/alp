<?php

/**
 * Coauthors tests
 */
class CoauthorsTest extends WP_UnitTestCase {
    protected static $_user_1;
    protected static $_user_2;
    protected static $_user_3;
    protected static $_post_1;
    protected static $_post_2;
    protected static $_post_3;
    protected $user_1;
    protected $user_2;
    protected $user_3;
    protected $post_1;
    protected $post_2;
    protected $post_3;

	public static function wpSetUpBeforeClass( $factory ) {
        self::$_user_1 = $factory->user->create_and_get();
        self::$_user_2 = $factory->user->create_and_get();
        self::$_user_3 = $factory->user->create_and_get();

		self::$_post_1 = $factory->post->create_and_get(
            array(
			    'post_author'     => self::$_user_1->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );

        self::$_post_2 = $factory->post->create_and_get(
            array(
			    'post_author'     => self::$_user_2->ID,
			    'post_status'     => 'publish',
			    'post_type'       => 'post',
            )
        );

        self::$_post_3 = $factory->post->create_and_get(
            array(
			    'post_author'     => self::$_user_3->ID,
			    'post_status'     => 'draft',
			    'post_type'       => 'post',
            )
        );
    }

    function setUp() {
        $this->user_1 = clone self::$_user_1;
        $this->user_2 = clone self::$_user_2;
        $this->user_3 = clone self::$_user_3;
        $this->post_1 = clone self::$_post_1;
        $this->post_2 = clone self::$_post_2;
        $this->post_3 = clone self::$_post_3;
    }

	function test_coauthors() {
        global $ssl_alp;

        // posts should have no coauthors, just the author, by default
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

        // add coauthors
        $ssl_alp->coauthors->set_coauthors(
            get_post( $this->post_2 ),
            array(
                $this->user_1->user_login,
                $this->user_2->user_login,
                $this->user_3->user_login
            )
        );

        // check coauthors are present
		$this->assertEqualSets(
            $ssl_alp->coauthors->get_coauthors( $this->post_2 ),
            array(
                $this->user_1,
                $this->user_2,
                $this->user_3
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
        $ssl_alp->coauthors->add_author_term( $user_1->ID );
        $this->assertInstanceOf( 'WP_Term', get_term_by( 'name', $user_1->user_login, 'ssl_alp_coauthor' ) );
        
        // add term by user object
        $this->assertFalse( get_term_by( 'name', $user_2->user_login, 'ssl_alp_coauthor' ) );
        $ssl_alp->coauthors->add_author_term( $user_2 );
        $this->assertInstanceOf( 'WP_Term', get_term_by( 'name', $user_2->user_login, 'ssl_alp_coauthor' ) );
    }
}
