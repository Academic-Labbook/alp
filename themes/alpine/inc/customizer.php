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
	global $ssl_alpine_default_options;

	/**
	 * Post lists section
	 */

	$wp_customize->add_section(
		'ssl_alpine_post_list_options',
		array(
			'title'      => __( 'Post Lists', 'ssl-alpine' ),
			'priority'   => 80,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// post display setting
	$wp_customize->add_setting(
		'ssl_alpine_options[content_layout]',
		array(
			'default'           => $ssl_alpine_default_options['content_layout'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alpine_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[content_layout]',
		array(
			'label'    => __( 'Post Display', 'ssl-alpine' ),
			'description'	=>	__( 'Determines how much post content is displayed in lists. Post pages always show the full content.', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_post_list_options',
			'type'     => 'select',
			'priority' => 120,
			'choices'  => array(
				'full'          => __( 'Full post', 'ssl-alpine' ),
				'excerpt'       => __( 'Excerpt', 'ssl-alpine' )
			),
		)
	);

	// excerpt length setting
	$wp_customize->add_setting(
		'ssl_alpine_options[excerpt_length]',
		array(
			'default'              => $ssl_alpine_default_options['excerpt_length'],
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'ssl_alpine_sanitize_number_absint',
			'sanitize_js_callback' => 'esc_attr',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[excerpt_length]',
		array(
			'label'    => __( 'Excerpt length', 'ssl-alpine' ),
			'description'	=>	__( 'The number of words to display in the excerpt.', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_post_list_options',
			'type'     => 'text',
			'priority' => 130,
		)
	);

	/**
	 * Pages section
	 */

	/**
	 * Post lists section
	 */

	$wp_customize->add_section(
		'ssl_alpine_page_options',
		array(
			'title'      => __( 'Pages', 'ssl-alpine' ),
			'priority'   => 85,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// table of contents display setting
	$wp_customize->add_setting(
		'ssl_alpine_options[display_page_table_of_contents]',
		array(
			'default'           => $ssl_alpine_default_options['display_page_table_of_contents'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[display_page_table_of_contents]',
		array(
			'label'    => __( 'Display table of contents', 'ssl-alpine' ),
			'description'	=>	__( 'Generate and display a table of contents panel shown at the top right of the page.', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_page_options',
			'type'     => 'checkbox',
			'priority' => 120
		)
	);

	// table of contents display setting
	$wp_customize->add_setting(
		'ssl_alpine_options[table_of_contents_max_depth]',
		array(
			'default'           => $ssl_alpine_default_options['table_of_contents_max_depth'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alpine_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[table_of_contents_max_depth]',
		array(
			'label'    => __( 'Table of contents maximum depth', 'ssl-alpine' ),
			'description'	=>	__( 'Maximum heading level displayed in the table of contents.', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_page_options',
			'type'     => 'select',
			'choices'  => array(
				2          => __( 'h2', 'ssl-alpine' ),
				3    	   => __( 'h3', 'ssl-alpine' ),
				4    	   => __( 'h4', 'ssl-alpine' ),
				5    	   => __( 'h5', 'ssl-alpine' ),
				6    	   => __( 'h6', 'ssl-alpine' )
			),
			'priority'	=>	130
		)
	);

	/**
	 * Sidebar section
	 */

	$wp_customize->add_section(
		'ssl_alpine_sidebar_options',
		array(
			'title'      => __( 'Sidebar', 'ssl-alpine' ),
			'priority'   => 90,
			'capability' => 'edit_theme_options',
			'panel'      => ''
		)
	);

	// search placeholder setting
	$wp_customize->add_setting(
		'ssl_alpine_options[search_placeholder]',
		array(
			'default'           => $ssl_alpine_default_options['search_placeholder'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[search_placeholder]',
		array(
			'label'    => __( 'Search Placeholder', 'ssl-alpine' ),
			'description'	=>	__( 'The text to display in background of search box.', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_sidebar_options',
			'type'     => 'text',
			'priority' => 120,
		)
	);

	/**
	 * Footer section
	 */

	$wp_customize->add_section(
		'ssl_alpine_footer_options',
		array(
			'title'      => __( 'Footer', 'ssl-alpine' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// copyright text setting
	$wp_customize->add_setting(
		'ssl_alpine_options[copyright_text]',
		array(
			'default'           => $ssl_alpine_default_options['copyright_text'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[copyright_text]',
		array(
			'label'    => __( 'Copyright Text', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_footer_options',
			'type'     => 'text',
			'priority' => 110,
		)
	);

	// "powered by" setting
	$wp_customize->add_setting(
		'ssl_alpine_options[powered_by]',
		array(
			'default'           => $ssl_alpine_default_options['powered_by'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alpine_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'ssl_alpine_options[powered_by]',
		array(
			'label'    => __( 'Show "Powered By" label', 'ssl-alpine' ),
			'section'  => 'ssl_alpine_footer_options',
			'type'     => 'checkbox',
			'priority' => 120,
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
			'settings'            => array( 'ssl_alpine_options[copyright_text]' ),
			'container_inclusive' => false,
			'render_callback'     => 'ssl_alpine_customize_partial_copyright_text',
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
if ( ! function_exists( 'ssl_alpine_sanitize_number_absint' ) ) {
	/**
	 * Sanitize positive integer.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $number Number to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return int Sanitized number; otherwise, the setting default.
	 */
	function ssl_alpine_sanitize_number_absint( $number, $setting ) {
		$number = absint( $number );
		return ( $number ? $number : $setting->default );
	}
}

if ( ! function_exists( 'ssl_alpine_sanitize_checkbox' ) ) {
	/**
	 * Sanitize checkbox.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 * @return bool Whether the checkbox is checked.
	 */
	function ssl_alpine_sanitize_checkbox( $checked ) {
		return ( ( isset( $checked ) && true === $checked ) ? true : false );
	}
}

if ( ! function_exists( 'ssl_alpine_sanitize_select' ) ) {
	/**
	 * Sanitize select.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $input The value to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed Sanitized value.
	 */
	function ssl_alpine_sanitize_select( $input, $setting ) {
		$input = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;

		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}
