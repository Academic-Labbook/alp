<?php
/**
 * Alpine Theme Customizer
 *
 * @package Alpine
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function alpine_customize_register( $wp_customize ) {
	global $alpine_default_options;

	/**
	 * Post lists section
	 */

	$wp_customize->add_section(
		'alpine_post_list_options',
		array(
			'title'      => __( 'Post Lists', 'alpine' ),
			'priority'   => 80,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// post display setting
	$wp_customize->add_setting(
		'alpine_options[content_layout]',
		array(
			'default'           => $alpine_default_options['content_layout'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'alpine_options[content_layout]',
		array(
			'label'    => __( 'Post Display', 'alpine' ),
			'description'	=>	__( 'Determines how much post content is displayed in lists. Post pages always show the full content.', 'alpine' ),
			'section'  => 'alpine_post_list_options',
			'type'     => 'select',
			'priority' => 120,
			'choices'  => array(
				'full'          => __( 'Full post', 'alpine' ),
				'excerpt'       => __( 'Excerpt', 'alpine' )
			),
		)
	);

	// excerpt length setting
	$wp_customize->add_setting(
		'alpine_options[excerpt_length]',
		array(
			'default'              => $alpine_default_options['excerpt_length'],
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'alpine_sanitize_number_absint',
			'sanitize_js_callback' => 'esc_attr',
		)
	);

	$wp_customize->add_control(
		'alpine_options[excerpt_length]',
		array(
			'label'    => __( 'Excerpt length', 'alpine' ),
			'description'	=>	__( 'The number of words to display in the excerpt.', 'alpine' ),
			'section'  => 'alpine_post_list_options',
			'type'     => 'text',
			'priority' => 130,
		)
	);

	/**
	 * Revisions section
	 */

	$wp_customize->add_section(
		'alpine_revision_options',
		array(
			'title'      => __( 'Revisions', 'alpine' ),
			'priority'   => 82,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	$wp_customize->add_setting(
		'alpine_options[show_edit_summaries]',
		array(
			'default'           => $alpine_default_options['show_edit_summaries'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_edit_summaries]',
		array(
			'label'    => __( 'Show edit summaries', 'alpine' ),
			'description'	=>	__( 'Display a list of edit summaries under each post/page.', 'alpine' ),
			'section'  => 'alpine_revision_options',
			'type'     => 'checkbox',
			'priority' => 100
		)
	);

	// edit summaries per page
	$wp_customize->add_setting(
		'alpine_options[edit_summaries_per_page]',
		array(
			'default'           	=> $alpine_default_options['edit_summaries_per_page'],
			'capability'        	=> 'edit_theme_options',
			'sanitize_callback'    	=> 'alpine_sanitize_number_absint',
			'sanitize_js_callback' 	=> 'esc_attr',
		)
	);

	$wp_customize->add_control(
		'alpine_options[edit_summaries_per_page]',
		array(
			'label'    => __( 'Edit summary page size', 'alpine' ),
			'description'	=>	__( 'Maximum number of edit summaries to display per page.', 'alpine' ),
			'section'  => 'alpine_revision_options',
			'type'     => 'text',
			'priority'	=>	110
		)
	);

	/**
	 * Cross-references section
	 */

	$wp_customize->add_section(
		'alpine_reference_options',
		array(
			'title'      => __( 'References', 'alpine' ),
			'priority'   => 84,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	$wp_customize->add_setting(
		'alpine_options[show_crossreferences]',
		array(
			'default'           => $alpine_default_options['show_crossreferences'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_crossreferences]',
		array(
			'label'    => __( 'Show cross-references', 'alpine' ),
			'description'	=>	__( 'Display a list of posts/pages that link to/from the current post/page.', 'alpine' ),
			'section'  => 'alpine_reference_options',
			'type'     => 'checkbox',
			'priority' => 100
		)
	);

	/**
	 * Pages section
	 */

	$wp_customize->add_section(
		'alpine_page_options',
		array(
			'title'      => __( 'Pages', 'alpine' ),
			'priority'   => 86,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// breadcrumbs display setting
	$wp_customize->add_setting(
		'alpine_options[show_page_breadcrumbs]',
		array(
			'default'           => $alpine_default_options['show_page_breadcrumbs'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_page_breadcrumbs]',
		array(
			'label'    => __( 'Show breadcrumbs', 'alpine' ),
			'description'	=>	__( 'Display a trail of links to parent pages at the top of each page.', 'alpine' ),
			'section'  => 'alpine_page_options',
			'type'     => 'checkbox',
			'priority' => 100
		)
	);

	// table of contents display setting
	$wp_customize->add_setting(
		'alpine_options[show_page_table_of_contents]',
		array(
			'default'           => $alpine_default_options['show_page_table_of_contents'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_page_table_of_contents]',
		array(
			'label'    => __( 'Show table of contents', 'alpine' ),
			'description'	=>	__( 'Generate and display a table of contents panel shown at the top right of the page.', 'alpine' ),
			'section'  => 'alpine_page_options',
			'type'     => 'checkbox',
			'priority' => 110
		)
	);

	// table of contents display setting
	$wp_customize->add_setting(
		'alpine_options[table_of_contents_max_depth]',
		array(
			'default'           => $alpine_default_options['table_of_contents_max_depth'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'alpine_options[table_of_contents_max_depth]',
		array(
			'label'    => __( 'Table of contents maximum depth', 'alpine' ),
			'description'	=>	__( 'Maximum heading level displayed in the table of contents.', 'alpine' ),
			'section'  => 'alpine_page_options',
			'type'     => 'select',
			'choices'  => array(
				2          => __( 'h2', 'alpine' ),
				3    	   => __( 'h3', 'alpine' ),
				4    	   => __( 'h4', 'alpine' ),
				5    	   => __( 'h5', 'alpine' ),
				6    	   => __( 'h6', 'alpine' )
			),
			'priority'	=>	120
		)
	);

	/**
	 * Sidebar section
	 */

	$wp_customize->add_section(
		'alpine_sidebar_options',
		array(
			'title'      => __( 'Sidebar', 'alpine' ),
			'priority'   => 90,
			'capability' => 'edit_theme_options',
			'panel'      => ''
		)
	);

	// search placeholder setting
	$wp_customize->add_setting(
		'alpine_options[search_placeholder]',
		array(
			'default'           => $alpine_default_options['search_placeholder'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'alpine_options[search_placeholder]',
		array(
			'label'    => __( 'Search Placeholder', 'alpine' ),
			'description'	=>	__( 'The text to display in background of search box.', 'alpine' ),
			'section'  => 'alpine_sidebar_options',
			'type'     => 'text',
			'priority' => 120,
		)
	);

	/**
	 * Footer section
	 */

	$wp_customize->add_section(
		'alpine_footer_options',
		array(
			'title'      => __( 'Footer', 'alpine' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// copyright text setting
	$wp_customize->add_setting(
		'alpine_options[copyright_text]',
		array(
			'default'           => $alpine_default_options['copyright_text'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'alpine_options[copyright_text]',
		array(
			'label'    => __( 'Copyright Text', 'alpine' ),
			'section'  => 'alpine_footer_options',
			'type'     => 'text',
			'priority' => 110,
		)
	);

	// "powered by" setting
	$wp_customize->add_setting(
		'alpine_options[show_powered_by]',
		array(
			'default'           => $alpine_default_options['show_powered_by'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_powered_by]',
		array(
			'label'    => __( 'Show "Powered By" label', 'alpine' ),
			'section'  => 'alpine_footer_options',
			'type'     => 'checkbox',
			'priority' => 120,
		)
	);

	// privacy policy link setting
	$wp_customize->add_setting(
		'alpine_options[show_privacy_policy]',
		array(
			'default'           => $alpine_default_options['show_privacy_policy'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'alpine_options[show_privacy_policy]',
		array(
			'label'    		=> __( 'Show Privacy Policy link', 'alpine' ),
			'description'	=>	__( 'Only displayed if a privacy policy has been configured.', 'alpine' ),
			'section'  		=> 'alpine_footer_options',
			'type'     		=> 'checkbox',
			'priority' 		=> 130,
		)
	);

	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial( 'blogname', array(
			'selector'        => '.site-title a',
			'render_callback' => 'alpine_customize_partial_blogname',
		) );
		$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
			'selector'        => '.site-description',
			'render_callback' => 'alpine_customize_partial_blogdescription',
		) );
		$wp_customize->selective_refresh->add_partial( 'copyright-text', array(
			'selector'            => '.copyright-text',
			'settings'            => array( 'alpine_options[copyright_text]' ),
			'container_inclusive' => false,
			'render_callback'     => 'alpine_customize_partial_copyright_text',
		) );
	}
}
add_action( 'customize_register', 'alpine_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function alpine_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function alpine_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function alpine_customize_preview_js() {
	wp_enqueue_script( 'alpine-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'alpine_customize_preview_js' );

// Sanitization callback functions.
if ( ! function_exists( 'alpine_sanitize_number_absint' ) ) {
	/**
	 * Sanitize positive integer.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $number Number to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return int Sanitized number; otherwise, the setting default.
	 */
	function alpine_sanitize_number_absint( $number, $setting ) {
		$number = absint( $number );
		return ( $number ? $number : $setting->default );
	}
}

if ( ! function_exists( 'alpine_sanitize_checkbox' ) ) {
	/**
	 * Sanitize checkbox.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 * @return bool Whether the checkbox is checked.
	 */
	function alpine_sanitize_checkbox( $checked ) {
		return ( ( isset( $checked ) && true === $checked ) ? true : false );
	}
}

if ( ! function_exists( 'alpine_sanitize_select' ) ) {
	/**
	 * Sanitize select.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $input The value to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed Sanitized value.
	 */
	function alpine_sanitize_select( $input, $setting ) {
		$input = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;

		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}
