<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Render the site title for the selective refresh partial.
 *
 * @since 2.0
 *
 * @return void
 */
function ssl_alp_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @since 2.0
 *
 * @return void
 */
function ssl_alp_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Render the copyright text for the selective refresh partial.
 *
 * @since 2.0
 *
 * @return void
 */
function ssl_alp_customize_partial_copyright_text() {
	echo wp_kses_post( ssl_alp_get_option( 'copyright_text' ) );
}

/**
 * Render the read more text for the selective refresh partial.
 *
 * @since 2.0
 *
 * @return void
 */
function ssl_alp_customize_partial_read_more_text() {
	echo esc_html( ssl_alp_get_option( 'read_more_text' ) );
}
