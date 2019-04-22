<?php
/**
 * Labbook Theme Customizer
 *
 * @package Labbook
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function labbook_customize_register( $wp_customize ) {
	global $labbook_default_options;

	/**
	 * Post lists section
	 */

	$wp_customize->add_section(
		'labbook_post_list_options',
		array(
			'title'      => __( 'Post Lists', 'labbook' ),
			'priority'   => 80,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// Post display setting.
	$wp_customize->add_setting(
		'labbook_options[content_layout]',
		array(
			'default'           => $labbook_default_options['content_layout'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'labbook_sanitize_select',
		)
	);

	$wp_customize->add_control(
		'labbook_options[content_layout]',
		array(
			'label'       => __( 'Post Display', 'labbook' ),
			'description' => __( 'Determines how much post content is displayed in lists. Post pages always show the full content.', 'labbook' ),
			'section'     => 'labbook_post_list_options',
			'type'        => 'select',
			'priority'    => 120,
			'choices'     => array(
				'full'    => __( 'Full post', 'labbook' ),
				'excerpt' => __( 'Excerpt', 'labbook' ),
			),
		)
	);

	// Excerpt length setting.
	$wp_customize->add_setting(
		'labbook_options[excerpt_length]',
		array(
			'default'              => $labbook_default_options['excerpt_length'],
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'labbook_sanitize_number_absint',
			'sanitize_js_callback' => 'esc_attr',
		)
	);

	$wp_customize->add_control(
		'labbook_options[excerpt_length]',
		array(
			'label'       => __( 'Excerpt length', 'labbook' ),
			'description' => __( 'The number of words to display in the excerpt.', 'labbook' ),
			'section'     => 'labbook_post_list_options',
			'type'        => 'text',
			'priority'    => 130,
		)
	);

	/**
	 * Revisions section
	 */

	if ( labbook_ssl_alp_edit_summaries_enabled() ) {
		$wp_customize->add_section(
			'labbook_revision_options',
			array(
				'title'      => __( 'Revisions', 'labbook' ),
				'priority'   => 82,
				'capability' => 'edit_theme_options',
				'panel'      => '',
			)
		);

		$wp_customize->add_setting(
			'labbook_options[show_edit_summaries]',
			array(
				'default'           => $labbook_default_options['show_edit_summaries'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'labbook_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			'labbook_options[show_edit_summaries]',
			array(
				'label'       => __( 'Show edit summaries', 'labbook' ),
				'description' => __( 'Display a list of edit summaries under each post/page.', 'labbook' ),
				'section'     => 'labbook_revision_options',
				'type'        => 'checkbox',
				'priority'    => 100,
			)
		);

		// Edit summaries per page.
		$wp_customize->add_setting(
			'labbook_options[edit_summaries_per_page]',
			array(
				'default'              => $labbook_default_options['edit_summaries_per_page'],
				'capability'           => 'edit_theme_options',
				'sanitize_callback'    => 'labbook_sanitize_number_absint',
				'sanitize_js_callback' => 'esc_attr',
			)
		);

		$wp_customize->add_control(
			'labbook_options[edit_summaries_per_page]',
			array(
				'label'       => __( 'Edit summary page size', 'labbook' ),
				'description' => __( 'Maximum number of edit summaries to display per page.', 'labbook' ),
				'section'     => 'labbook_revision_options',
				'type'        => 'text',
				'priority'    => 110,
			)
		);
	} // End if().

	/**
	 * Cross-references section.
	 */

	if ( labbook_ssl_alp_crossreferences_enabled() ) {
		$wp_customize->add_section(
			'labbook_reference_options',
			array(
				'title'      => __( 'References', 'labbook' ),
				'priority'   => 84,
				'capability' => 'edit_theme_options',
				'panel'      => '',
			)
		);

		$wp_customize->add_setting(
			'labbook_options[show_crossreferences]',
			array(
				'default'           => $labbook_default_options['show_crossreferences'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'labbook_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			'labbook_options[show_crossreferences]',
			array(
				'label'       => __( 'Show cross-references', 'labbook' ),
				'description' => __( 'Display a list of posts/pages that link to/from the current post/page.', 'labbook' ),
				'section'     => 'labbook_reference_options',
				'type'        => 'checkbox',
				'priority'    => 100,
			)
		);
	}

	/**
	 * Posts section.
	 */

	if ( labbook_ssl_alp_unread_flags_enabled() ) {
		$wp_customize->add_section(
			'labbook_post_options',
			array(
				'title'      => __( 'Posts', 'labbook' ),
				'priority'   => 86,
				'capability' => 'edit_theme_options',
				'panel'      => '',
			)
		);

		// Unread flag setting.
		$wp_customize->add_setting(
			'labbook_options[show_unread_flags]',
			array(
				'default'           => $labbook_default_options['show_unread_flags'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'labbook_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			'labbook_options[show_unread_flags]',
			array(
				'label'       => __( 'Show unread posts', 'labbook' ),
				'description' => __( 'Display an icon next to each post title designating whether or not it has been read. Users can also use this icon to toggle the post\'s read status.', 'labbook' ),
				'section'     => 'labbook_post_options',
				'type'        => 'checkbox',
				'priority'    => 100,
			)
		);
	}

	/**
	 * Pages section.
	 */

	$wp_customize->add_section(
		'labbook_page_options',
		array(
			'title'      => __( 'Pages', 'labbook' ),
			'priority'   => 88,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// Breadcrumbs display setting.
	$wp_customize->add_setting(
		'labbook_options[show_page_breadcrumbs]',
		array(
			'default'           => $labbook_default_options['show_page_breadcrumbs'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'labbook_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'labbook_options[show_page_breadcrumbs]',
		array(
			'label'       => __( 'Show breadcrumbs', 'labbook' ),
			'description' => __( 'Display a trail of links to parent pages at the top of each page.', 'labbook' ),
			'section'     => 'labbook_page_options',
			'type'        => 'checkbox',
			'priority'    => 100,
		)
	);

	// Table of contents display setting.
	if ( labbook_php_dom_extension_loaded() ) {
		$wp_customize->add_setting(
			'labbook_options[show_page_table_of_contents]',
			array(
				'default'           => $labbook_default_options['show_page_table_of_contents'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'labbook_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			'labbook_options[show_page_table_of_contents]',
			array(
				'label'       => __( 'Show table of contents', 'labbook' ),
				'description' => __( 'Generate and display a table of contents panel shown at the top right of the page.', 'labbook' ),
				'section'     => 'labbook_page_options',
				'type'        => 'checkbox',
				'priority'    => 110,
			)
		);

		// Table of contents maximum header depth setting.
		$wp_customize->add_setting(
			'labbook_options[table_of_contents_max_depth]',
			array(
				'default'           => $labbook_default_options['table_of_contents_max_depth'],
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'labbook_sanitize_select',
			)
		);

		$wp_customize->add_control(
			'labbook_options[table_of_contents_max_depth]',
			array(
				'label'       => __( 'Table of contents maximum depth', 'labbook' ),
				'description' => __( 'Maximum heading level displayed in the table of contents.', 'labbook' ),
				'section'     => 'labbook_page_options',
				'type'        => 'select',
				'choices'     => array(
					2 => __( 'h2', 'labbook' ),
					3 => __( 'h3', 'labbook' ),
					4 => __( 'h4', 'labbook' ),
					5 => __( 'h5', 'labbook' ),
					6 => __( 'h6', 'labbook' ),
				),
				'priority'    => 120,
			)
		);
	}

	/**
	 * Sidebar section.
	 */

	$wp_customize->add_section(
		'labbook_sidebar_options',
		array(
			'title'      => __( 'Sidebar', 'labbook' ),
			'priority'   => 90,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// Search placeholder setting.
	$wp_customize->add_setting(
		'labbook_options[search_placeholder]',
		array(
			'default'           => $labbook_default_options['search_placeholder'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'labbook_options[search_placeholder]',
		array(
			'label'       => __( 'Search Placeholder', 'labbook' ),
			'description' => __( 'The text to display in background of search box.', 'labbook' ),
			'section'     => 'labbook_sidebar_options',
			'type'        => 'text',
			'priority'    => 120,
		)
	);

	/**
	 * Footer section.
	 */

	$wp_customize->add_section(
		'labbook_footer_options',
		array(
			'title'      => __( 'Footer', 'labbook' ),
			'priority'   => 100,
			'capability' => 'edit_theme_options',
			'panel'      => '',
		)
	);

	// Copyright text setting.
	$wp_customize->add_setting(
		'labbook_options[copyright_text]',
		array(
			'default'           => $labbook_default_options['copyright_text'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);

	$wp_customize->add_control(
		'labbook_options[copyright_text]',
		array(
			'label'    => __( 'Copyright Text', 'labbook' ),
			'section'  => 'labbook_footer_options',
			'type'     => 'text',
			'priority' => 110,
		)
	);

	// Privacy policy link setting.
	$wp_customize->add_setting(
		'labbook_options[show_privacy_policy]',
		array(
			'default'           => $labbook_default_options['show_privacy_policy'],
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'labbook_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'labbook_options[show_privacy_policy]',
		array(
			'label'       => __( 'Show Privacy Policy link', 'labbook' ),
			'description' => __( 'Only displayed if a privacy policy has been configured.', 'labbook' ),
			'section'     => 'labbook_footer_options',
			'type'        => 'checkbox',
			'priority'    => 130,
		)
	);

	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.site-title a',
				'render_callback' => 'labbook_customize_partial_blogname',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			'blogdescription',
			array(
				'selector'        => '.site-description',
				'render_callback' => 'labbook_customize_partial_blogdescription',
			)
		);

		$wp_customize->selective_refresh->add_partial(
			'copyright-text',
			array(
				'selector'            => '.copyright-text',
				'settings'            => array( 'labbook_options[copyright_text]' ),
				'container_inclusive' => false,
				'render_callback'     => 'labbook_customize_partial_copyright_text',
			)
		);
	}
}
add_action( 'customize_register', 'labbook_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function labbook_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function labbook_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function labbook_customize_preview_js() {
	wp_enqueue_script(
		'labbook-customizer',
		get_template_directory_uri() . '/js/customizer.js',
		array( 'customize-preview' ),
		LABBOOK_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'labbook_customize_preview_js' );

// Sanitization callback functions.
if ( ! function_exists( 'labbook_sanitize_number_absint' ) ) {
	/**
	 * Sanitize positive integer.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $number Number to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return int Sanitized number; otherwise, the setting default.
	 */
	function labbook_sanitize_number_absint( $number, $setting ) {
		$number = absint( $number );
		return ( $number ? $number : $setting->default );
	}
}

if ( ! function_exists( 'labbook_sanitize_checkbox' ) ) {
	/**
	 * Sanitize checkbox.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $checked Whether the checkbox is checked.
	 * @return bool Whether the checkbox is checked.
	 */
	function labbook_sanitize_checkbox( $checked ) {
		return ( ( isset( $checked ) && true === $checked ) ? true : false );
	}
}

if ( ! function_exists( 'labbook_sanitize_select' ) ) {
	/**
	 * Sanitize select.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $input The value to sanitize.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed Sanitized value.
	 */
	function labbook_sanitize_select( $input, $setting ) {
		$input   = sanitize_key( $input );
		$choices = $setting->manager->get_control( $setting->id )->choices;

		return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
	}
}
