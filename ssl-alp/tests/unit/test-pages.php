<?php

/**
 * Pages tests
 */
class PagesTest extends WP_UnitTestCase {
	public function setUp() {		
		parent::setUp();
		
		$this->post_1 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'<p>This contains no sections.</p>',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
		);

		$this->post_2 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1>First section header</h1>
                    <h2>Second section header</h2>
                    <h3>Third section header</h3>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );

		$this->post_3 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1 id="already-defined">First section header</h1>
                    <h2>Second section header</h2>
                    <h3>Third section header</h3>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );
        
		$this->post_4 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1>First section header</h1>
                    <h3>Third section header</h3>
                    <h5>Fifth section header</h5>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );

        // unbalanced header tags
		$this->post_5 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1>First section header</h2>
                    <h3>Third section header</h4>
                    <h5>Fifth section header</h6>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );

        // full list
		$this->post_6 = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1>First section header</h1>
                    <h2>Second section header</h2>
                    <h3>Third section header</h3>
                    <h4>Fourth section header</h4>
                    <h5>Fifth section header</h5>
                    <h6>Sixth section header</h6>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );
    }

    /**
     * Sets up the test as if the specified post was visited
     */
    private function go_to_post( $post ) {
        $this->go_to( get_permalink( $post ) );
        setup_postdata( $post );
    }

	public function test_id_injection() {
        // no changes expected
        $expected_1 = $this->post_1->post_content;

        $this->go_to_post( $this->post_1 );
        $this->assertEquals( strip_ws( $expected_1 ), strip_ws( get_echo( 'the_content' ) ) );

        // should inject ids based on titles
        $expected_2 = <<<EOF
<h1 id="first-section-header">First section header</h1>
<h2 id="second-section-header">Second section header</h2>
<h3 id="third-section-header">Third section header</h3>
EOF;

        $this->go_to_post( $this->post_2 );
        $this->assertEquals( strip_ws( $expected_2 ), strip_ws( get_echo( 'the_content' ) ) );

        // should inject ids based on titles, but leave already defined ids alone
        $expected_3 = <<<EOF
<h1 id="already-defined">First section header</h1>
<h2 id="second-section-header">Second section header</h2>
<h3 id="third-section-header">Third section header</h3>
EOF;

        $this->go_to_post( $this->post_3 );
        $this->assertEquals( strip_ws( $expected_3 ), strip_ws( get_echo( 'the_content' ) ) );

        // should handle skipped h tags
        $expected_4 = <<<EOF
<h1 id="first-section-header">First section header</h1>
<h3 id="third-section-header">Third section header</h3>
<h5 id="fifth-section-header">Fifth section header</h5>
EOF;

        $this->go_to_post( $this->post_4 );
        $this->assertEquals( strip_ws( $expected_4 ), strip_ws( get_echo( 'the_content' ) ) );

        // full list
        $expected_6 = <<<EOF
<h1 id="first-section-header">First section header</h1>
<h2 id="second-section-header">Second section header</h2>
<h3 id="third-section-header">Third section header</h3>
<h4 id="fourth-section-header">Fourth section header</h4>
<h5 id="fifth-section-header">Fifth section header</h5>
<h6 id="sixth-section-header">Sixth section header</h6>
EOF;

        $this->go_to_post( $this->post_6 );
        $this->assertEquals( strip_ws( $expected_6 ), strip_ws( get_echo( 'the_content' ) ) );
    }
    
    public function test_invalid_header_tags() {
        global $ssl_alp_page_toc;
        
        // invalid h tags should result in unchanged content
        $expected_5 = $this->post_5->post_content;

        $this->go_to_post( $this->post_5 );
        $this->assertEquals( strip_ws( $expected_5 ), strip_ws( get_echo( 'the_content' ) ) );

        // and empty contents
        $this->assertEmpty( $ssl_alp_page_toc );
    }

    private function get_contents_widget_output( $post, $max_levels = null ) {
        // create widget
        $widget = new SSL_ALP_Widget_Contents();

        // default instance
        $instance = array(
            'title'         =>  '',
            'max_levels'    =>  $max_levels
        );

        // empty args
        $args = array(
			'before_title'  => '',
			'after_title'   => '',
			'before_widget' => '',
			'after_widget'  => ''
		);

        $this->go_to_post( $post );

        // force rebuild of contents
        get_echo( 'the_content' );

        // return widget output
        return get_echo( array( $widget, 'widget' ), array( $args, $instance ) );
    }

    /**
     * Remove whitespace from expected test data
     */
    private function trim_expected( $expected ) {
        return implode('', array_map( 'trim', preg_split( '/$\R?^/m', $expected ) ) );
    }

	public function test_contents_tree() {
        global $ssl_alp_page_toc;

        // no output when no table of contents
        $expected_1 = '';

        $this->assertEquals( strip_ws( $expected_1 ), strip_ws( $this->get_contents_widget_output( $this->post_1, 6 ) ) );

        $expected_2_raw = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_2 = $this->trim_expected( $expected_2_raw );

        $this->assertEquals( $expected_2, strip_ws( $this->get_contents_widget_output( $this->post_2, 6 ) ) );

        $expected_3_raw = <<<EOF
Contents
<ul>
    <li>
        <a href="#already-defined">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_3 = $this->trim_expected( $expected_3_raw );

        $this->assertEquals( $expected_3, strip_ws( $this->get_contents_widget_output( $this->post_3, 6 ) ) );

        $expected_4_raw = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                        <ul>
                            <li>
                                <ul>
                                    <li>
                                        <a href="#fifth-section-header">Fifth section header</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_4 = $this->trim_expected( $expected_4_raw );
    
        $this->assertEquals( $expected_4, strip_ws( $this->get_contents_widget_output( $this->post_4, 6 ) ) );

        // no output when invalid
        $expected_5 = '';

        $this->assertEquals( strip_ws( $expected_5 ), strip_ws( $this->get_contents_widget_output( $this->post_5 ) ) );
    }

    public function test_contents_tree_max_level() {
        // 6 levels
        $expected_6_raw_1 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                        <ul>
                            <li>
                                <a href="#fourth-section-header">Fourth section header</a>
                                <ul>
                                    <li>
                                        <a href="#fifth-section-header">Fifth section header</a>
                                        <ul>
                                            <li>
                                                <a href="#sixth-section-header">Sixth section header</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_1 = $this->trim_expected( $expected_6_raw_1 );
    
        $this->assertEquals( $expected_6_1, strip_ws( $this->get_contents_widget_output( $this->post_6, 6 ) ) );

        // 5 levels
        $expected_6_raw_2 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                        <ul>
                            <li>
                                <a href="#fourth-section-header">Fourth section header</a>
                                <ul>
                                    <li>
                                        <a href="#fifth-section-header">Fifth section header</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_2 = $this->trim_expected( $expected_6_raw_2 );
    
        $this->assertEquals( $expected_6_2, strip_ws( $this->get_contents_widget_output( $this->post_6, 5 ) ) );

        // 4 levels
        $expected_6_raw_3 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                        <ul>
                            <li>
                                <a href="#fourth-section-header">Fourth section header</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_3 = $this->trim_expected( $expected_6_raw_3 );
    
        $this->assertEquals( $expected_6_3, strip_ws( $this->get_contents_widget_output( $this->post_6, 4 ) ) );

        // 0 levels should also default to 4 as per SSL_ALP_Widget_Contents::DEFAULT_MAX_LEVELS
        $this->assertEquals( $expected_6_3, strip_ws( $this->get_contents_widget_output( $this->post_6, 0 ) ) );

        // 3 levels
        $expected_6_raw_4 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
                <ul>
                    <li>
                        <a href="#third-section-header">Third section header</a>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_4 = $this->trim_expected( $expected_6_raw_4 );
    
        $this->assertEquals( $expected_6_4, strip_ws( $this->get_contents_widget_output( $this->post_6, 3 ) ) );

        // 2 levels
        $expected_6_raw_5 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#second-section-header">Second section header</a>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_5 = $this->trim_expected( $expected_6_raw_5 );
    
        $this->assertEquals( $expected_6_5, strip_ws( $this->get_contents_widget_output( $this->post_6, 2 ) ) );

        // 1 level
        $expected_6_raw_6 = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected_6_6 = $this->trim_expected( $expected_6_raw_6 );
    
        $this->assertEquals( $expected_6_6, strip_ws( $this->get_contents_widget_output( $this->post_6, 1 ) ) );
    }

    /**
     * Test header ID deduplication when header tag contents is the same
     */
    public function test_duplicate_headers() {
		$post = $this->factory->post->create_and_get(
			array(
                'post_content'	=>	'
                    <h1>First section header</h1>
                    <h2>First section header</h2>
                    <h3>First section header</h3>
                    <h4>First section header</h4>
                    <h5>First section header</h5>
                    <h6>First section header</h6>
                ',
                'post_type'     =>  'page',
                'post_status'   =>  'publish'
			)
        );

        // integers should be added to end of IDs until unique
        $expected_raw = <<<EOF
Contents
<ul>
    <li>
        <a href="#first-section-header">First section header</a>
        <ul>
            <li>
                <a href="#first-section-header1">First section header</a>
                <ul>
                    <li>
                        <a href="#first-section-header2">First section header</a>
                        <ul>
                            <li>
                                <a href="#first-section-header3">First section header</a>
                                <ul>
                                    <li>
                                        <a href="#first-section-header4">First section header</a>
                                        <ul>
                                            <li>
                                                <a href="#first-section-header5">First section header</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
EOF;

        // bunch up expected
        $expected = $this->trim_expected( $expected_raw );
    
        $this->assertEquals( $expected, strip_ws( $this->get_contents_widget_output( $post, 6 ) ) );
    }
}