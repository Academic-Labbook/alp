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

        $this->assertEquals( strip_ws( $expected_1 ), strip_ws( $this->get_contents_widget_output( $this->post_1 ) ) );

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

        $this->assertEquals( $expected_2, strip_ws( $this->get_contents_widget_output( $this->post_2 ) ) );

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

        $this->assertEquals( $expected_3, strip_ws( $this->get_contents_widget_output( $this->post_3 ) ) );
    }
}