<?php
/**
 * Alpine theme customizer
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function ssl_alp_customize_register( $wp_customize ) {
	global $ssl_alp_default_options;

	// enable core settings to live update
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	/**
	 * Post lists section
	 */

	$wp_customize->add_section(
		'ssl_alp_post_list_options',
		array(
			'title'      => __( 'Post Lists', 'ssl-alp' ),
			'priority'   => 80,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// pagination setting
	$wp_customize->add_setting(
		'ssl_alp_options[pagination_type]',
		array(
			'default'           => $ssl_alp_default_options['pagination_type'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[pagination_type]',
		array(
			'label'       => __( 'Pagination Type', 'ssl-alp' ),
			'description'	=>	__( 'Determines whether to display an "Older Posts" link or page numbers at the bottom of a list of posts.', 'ssl-alp' ),
			'section'     => 'ssl_alp_post_list_options',
			'type'        => 'select',
			'priority'    => 110,
			'choices'     => array(
				'default' => __( 'Default', 'ssl-alp' ),
				'numeric' => __( 'Numeric', 'ssl-alp' ),
			),
		)
	);

	// post display setting
	$wp_customize->add_setting(
		'ssl_alp_options[content_layout]',
		array(
			'default'           => $ssl_alp_default_options['content_layout'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[content_layout]',
		array(
			'label'    => __( 'Post Display', 'ssl-alp' ),
			'description'	=>	__( 'Determines how much post content is displayed in lists. Post pages always show the full content.', 'ssl-alp' ),
			'section'  => 'ssl_alp_post_list_options',
			'type'     => 'select',
			'priority' => 120,
			'choices'  => array(
				'full'          => __( 'Full post', 'ssl-alp' ),
				'excerpt'       => __( 'Excerpt', 'ssl-alp' )
			),
		)
	);

	// excerpt length setting
	$wp_customize->add_setting(
		'ssl_alp_options[excerpt_length]',
		array(
			'default'              => $ssl_alp_default_options['excerpt_length'],
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'ssl_alp_sanitize_number_absint',
			'sanitize_js_callback' => 'esc_attr',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[excerpt_length]',
		array(
			'label'    => __( 'Excerpt length', 'ssl-alp' ),
			'description'	=>	__( 'The number of words to display in the excerpt.', 'ssl-alp' ),
			'section'  => 'ssl_alp_post_list_options',
			'type'     => 'text',
			'priority' => 130,
		)
	);

	// read more text setting
	$wp_customize->add_setting(
		'ssl_alp_options[read_more_text]',
		array(
			'default'           => $ssl_alp_default_options['read_more_text'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[read_more_text]',
		array(
			'label'    => __( '"Read More" Text', 'ssl-alp' ),
			'description'	=>	__( 'The text used to link to the full post.', 'ssl-alp' ),
			'section'  => 'ssl_alp_post_list_options',
			'type'     => 'text',
			'priority' => 140,
		)
	);

	/**
	 * Sidebar section
	 */

	$wp_customize->add_section(
		'ssl_alpine_sidebar_options',
		array(
			'title'      => __( 'Sidebar', 'ssl-alp' ),
			'priority'   => 90,
			'capability' => 'edit_theme_options',
			'panel'      => ''
		)
	);

	// site_layout setting
	$wp_customize->add_setting(
		'ssl_alp_options[site_layout]',
		array(
			'default'           => $ssl_alp_default_options['site_layout'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[site_layout]',
		array(
			'label'    => __( 'Sidebar Placement', 'ssl-alp' ),
			'section'  => 'ssl_alpine_sidebar_options',
			'type'     => 'select',
			'priority' => 110,
			'choices'  => array(
				'content-sidebar' => __( 'Right of main content', 'ssl-alp' ),
				'sidebar-content' => __( 'Left of main content', 'ssl-alp' ),
				'full-width'      => __( 'Disabled', 'ssl-alp' ),
			),
		)
	);

	// search placeholder setting
	$wp_customize->add_setting(
		'ssl_alp_options[search_placeholder]',
		array(
			'default'           => $ssl_alp_default_options['search_placeholder'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[search_placeholder]',
		array(
			'label'    => __( 'Search Placeholder', 'ssl-alp' ),
			'description'	=>	__( 'The text to display in background of search box.', 'ssl-alp' ),
			'section'  => 'ssl_alpine_sidebar_options',
			'type'     => 'text',
			'priority' => 120,
		)
	);

	// page sidebar setting
	$wp_customize->add_setting(
		'ssl_alp_options[page_specific_sidebar]',
		array(
			'default'           => $ssl_alp_default_options['page_specific_sidebar'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[page_specific_sidebar]',
		array(
			'label'    => __( 'Show different sidebar on pages', 'ssl-alp' ),
			'section'  => 'ssl_alpine_sidebar_options',
			'type'     => 'checkbox',
			'priority' => 130,
		)
	);

	/**
	 * Footer section
	 */

	$wp_customize->add_section(
		'ssl_alp_footer_options',
		array(
			'title'      => __( 'Footer', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// copyright text setting
	$wp_customize->add_setting(
		'ssl_alp_options[copyright_text]',
		array(
			'default'           => $ssl_alp_default_options['copyright_text'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[copyright_text]',
		array(
			'label'    => __( 'Copyright Text', 'ssl-alp' ),
			'section'  => 'ssl_alp_footer_options',
			'type'     => 'text',
			'priority' => 110,
		)
	);

	// "powered by" setting
	$wp_customize->add_setting(
		'ssl_alp_options[powered_by]',
		array(
			'default'           => $ssl_alp_default_options['powered_by'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[powered_by]',
		array(
			'label'    => __( 'Show "Powered By" label', 'ssl-alp' ),
			'section'  => 'ssl_alp_footer_options',
			'type'     => 'checkbox',
			'priority' => 120,
		)
	);
}

add_action( 'customize_register', 'ssl_alp_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function ssl_alp_customize_preview_js() {
	wp_enqueue_script( 'ssl-alp-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '2.0.0', true );
}

add_action( 'customize_preview_init', 'ssl_alp_customize_preview_js' );

// Sanitization callback functions.
if ( ! function_exists( 'ssl_alp_sanitize_number_absint' ) ) {
	/**
	 * Sanitize positive integer.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $number Number to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return int Sanitized number; otherwise, the setting default.
	 */
	function ssl_alp_sanitize_number_absint( $number, $setting ) {
		$number = absint( $number );
		return ( $number ? $number : $setting->default );
	}
}

if ( ! function_exists( 'ssl_alp_sanitize_checkbox' ) ) {
	/**
	 * Sanitize checkbox.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 * @return bool Whether the checkbox is checked.
	 */
	function ssl_alp_sanitize_checkbox( $checked ) {
		return ( ( isset( $checked ) && true === $checked ) ? true : false );
	}
}

if ( ! function_exists( 'ssl_alp_sanitize_select' ) ) {
	/**
	 * Sanitize select.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $input The value to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed Sanitized value.
	 */
	function ssl_alp_sanitize_select( $input, $setting ) {
		$input = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;
		
		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}

/**
 * Customizer partials.
 *
 * @since 1.0.0
 */
function ssl_alp_customizer_partials( WP_Customize_Manager $wp_customize ) {

	if ( ! isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->get_setting( 'ssl_alp_options[copyright_text]' )->transport = 'refresh';
		$wp_customize->get_setting( 'ssl_alp_options[read_more_text]' )->transport = 'refresh';

		return;
	}

	$wp_customize->selective_refresh->add_partial(
		'blogname',
		array(
			'selector'            => '.site-title a',
			'container_inclusive' => false,
			'render_callback'     => 'ssl_alp_customize_partial_blogname',
		)
	);

	$wp_customize->selective_refresh->add_partial(
		'blogdescription',
		array(
			'selector'            => '.site-description',
			'container_inclusive' => false,
			'render_callback'     => 'ssl_alp_customize_partial_blogdescription',
		)
	);

	$wp_customize->selective_refresh->add_partial(
		'copyright-text',
		array(
			'selector'            => '.copyright-text',
			'settings'            => array( 'ssl_alp_options[copyright_text]' ),
			'container_inclusive' => false,
			'render_callback'     => 'ssl_alp_customize_partial_copyright_text',
		)
	);

	$wp_customize->selective_refresh->add_partial(
		'read-more-text',
		array(
			'selector'            => 'a.readmore',
			'settings'            => array( 'ssl_alp_options[read_more_text]' ),
			'container_inclusive' => false,
			'render_callback'     => 'ssl_alp_customize_partial_read_more_text',
		)
	);
}

add_action( 'customize_register', 'ssl_alp_customizer_partials', 99 );
