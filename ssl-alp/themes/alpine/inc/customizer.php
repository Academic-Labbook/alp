<?php
/**
 * Alpine theme customizer
 *
 * @package ssl-alp
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function ssl_alp_customize_register( $wp_customize ) {
	global $ssl_alp_default_options;

	// Panels, sections and fields.
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	// Add Panel.
	$wp_customize->add_panel(
		'ssl_alp_options_panel',
		array(
			'title'      => __( 'Alpine Theme', 'ssl-alp' ),
			'priority'   => 0,
			'capability' => 'edit_theme_options',
		)
	);

	// General Section.
	$wp_customize->add_section(
		'ssl_alp_options_general',
		array(
			'title'      => __( 'General Options', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => 'ssl_alp_options_panel',
		)
	);

	// Setting - site_layout.
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
			'label'    => __( 'Site Layout', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_general',
			'type'     => 'select',
			'priority' => 105,
			'choices'  => array(
				'content-sidebar' => __( 'Sidebar on right', 'ssl-alp' ),
				'sidebar-content' => __( 'Sidebar on left', 'ssl-alp' ),
				'full-width'      => __( 'No sidebar', 'ssl-alp' ),
			),
		)
	);

	// Setting - content_layout.
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
			'label'    => __( 'Content Layout', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_general',
			'type'     => 'select',
			'priority' => 115,
			'choices'  => array(
				'full'          => __( 'Full Post (with image)', 'ssl-alp' ),
				'excerpt'       => __( 'Excerpt Only', 'ssl-alp' ),
				'excerpt-thumb' => __( 'Excerpt with thumbnail', 'ssl-alp' ),
			),
		)
	);

	// Setting - archive_image_thumbnail_size.
	$wp_customize->add_setting(
		'ssl_alp_options[archive_image_thumbnail_size]',
		array(
			'default'           => $ssl_alp_default_options['archive_image_thumbnail_size'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_select',
		)
	);
	$wp_customize->add_control(
		'ssl_alp_options[archive_image_thumbnail_size]',
		array(
			'label'           => __( 'Archive Image Size', 'ssl-alp' ),
			'section'         => 'ssl_alp_options_general',
			'type'            => 'select',
			'priority'        => 120,
			'choices'         => ssl_alp_get_image_sizes_options( false ),
			'active_callback' => 'ssl_alp_is_non_excerpt_content_layout_active',
		)
	);

	// Setting - archive_image_alignment.
	$wp_customize->add_setting(
		'ssl_alp_options[archive_image_alignment]',
		array(
			'default'           => $ssl_alp_default_options['archive_image_alignment'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_select',
		)
	);
	$wp_customize->add_control(
		'ssl_alp_options[archive_image_alignment]',
		array(
			'label'           => __( 'Archive Image Alignment', 'ssl-alp' ),
			'section'         => 'ssl_alp_options_general',
			'type'            => 'select',
			'priority'        => 125,
			'choices'         => ssl_alp_get_image_alignment_options(),
			'active_callback' => 'ssl_alp_is_non_excerpt_content_layout_active',
		)
	);

	// Blog Section.
	$wp_customize->add_section(
		'ssl_alp_options_blog',
		array(
			'title'      => __( 'Blog Options', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => 'ssl_alp_options_panel',
		)
	);

	// Setting - read_more_text.
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
			'label'    => __( 'Read more text', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_blog',
			'type'     => 'text',
			'priority' => 210,
		)
	);

	// Setting - excerpt_length.
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
			'label'    => __( 'Excerpt Length', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_blog',
			'type'     => 'text',
			'priority' => 220,
		)
	);

	// Search Section.
	$wp_customize->add_section(
		'ssl_alp_options_search',
		array(
			'title'      => __( 'Search Options', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => 'ssl_alp_options_panel',
		)
	);

	// Setting - search_placeholder.
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
			'section'  => 'ssl_alp_options_search',
			'type'     => 'text',
			'priority' => 220,
		)
	);

	// Pagination Section.
	$wp_customize->add_section(
		'ssl_alp_options_pagination',
		array(
			'title'      => __( 'Pagination Options', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => 'ssl_alp_options_panel',
		)
	);

	// Setting - pagination_type.
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
			'section'     => 'ssl_alp_options_pagination',
			'type'        => 'select',
			'priority'    => 220,
			'choices'     => array(
				'default' => __( 'Default', 'ssl-alp' ),
				'numeric' => __( 'Numeric', 'ssl-alp' ),
			),
		)
	);

	// Footer Section.
	$wp_customize->add_section(
		'ssl_alp_options_footer',
		array(
			'title'      => __( 'Footer Options', 'ssl-alp' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => 'ssl_alp_options_panel',
		)
	);

	// Setting - copyright_text.
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
			'label'    => __( 'Copyright text', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_footer',
			'type'     => 'text',
			'priority' => 910,
		)
	);

	// Setting - powered_by.
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
			'label'    => __( 'Show Powered By', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_footer',
			'type'     => 'checkbox',
			'priority' => 920,
		)
	);

	// Setting - go_to_top.
	$wp_customize->add_setting(
		'ssl_alp_options[go_to_top]',
		array(
			'default'           => $ssl_alp_default_options['go_to_top'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'ssl_alp_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'ssl_alp_options[go_to_top]',
		array(
			'label'    => __( 'Enable Go To Top', 'ssl-alp' ),
			'section'  => 'ssl_alp_options_footer',
			'type'     => 'checkbox',
			'priority' => 920,
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

if ( ! function_exists( 'ssl_alp_is_non_excerpt_content_layout_active' ) ) :
	/**
	 * Check if non excerpt content layout is active.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Customize_Control $control WP_Customize_Control instance.
	 *
	 * @return bool Whether the control is active to the current preview.
	 */
	function ssl_alp_is_non_excerpt_content_layout_active( $control ) {
		if ( 'excerpt' !== $control->manager->get_setting( 'ssl_alp_options[content_layout]' )->value() ) {
			return true;
		}

		return false;
	}
endif;

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
