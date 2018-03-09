<?php
/**
 * Theme helper functions.
 *
 * @package ssl-alp
 */

if ( ! function_exists( 'ssl_alp_get_image_alignment_options' ) ) :

	/**
	 * Returns image alignment options.
	 */
	function ssl_alp_get_image_alignment_options() {

		$choices = array(
			'none'   => _x( 'None', 'Alignment', 'ssl-alp' ),
			'left'   => _x( 'Left', 'Alignment', 'ssl-alp' ),
			'center' => _x( 'Center', 'Alignment', 'ssl-alp' ),
			'right'  => _x( 'Right', 'Alignment', 'ssl-alp' ),
		);
		return $choices;

	}

endif;

if ( ! function_exists( 'ssl_alp_get_image_sizes_options' ) ) :

	/**
	 * Returns image sizes options.
	 *
	 * @since 1.2
	 *
	 * @param bool $add_disable Add disable option or not.
	 * @param array $allowed Allowed array.
	 * @param bool $show_dimension Show or hide dimension.
	 */
	function ssl_alp_get_image_sizes_options( $add_disable = true, $allowed = array(), $show_dimension = true ) {

		global $_wp_additional_image_sizes;
		$get_intermediate_image_sizes = get_intermediate_image_sizes();
		$choices = array();
		if ( true === $add_disable ) {
			$choices['disable'] = esc_html__( 'No Image', 'ssl-alp' );
		}
		$choices['thumbnail'] = esc_html__( 'Thumbnail', 'ssl-alp' );
		$choices['medium']    = esc_html__( 'Medium', 'ssl-alp' );
		$choices['large']     = esc_html__( 'Large', 'ssl-alp' );
		$choices['full']      = esc_html__( 'Full (original)', 'ssl-alp' );

		if ( true === $show_dimension ) {
			foreach ( array( 'thumbnail', 'medium', 'large' ) as $key => $_size ) {
				$choices[ $_size ] = $choices[ $_size ] . ' (' . get_option( $_size . '_size_w' ) . 'x' . get_option( $_size . '_size_h' ) . ')';
			}
		}

		if ( ! empty( $_wp_additional_image_sizes ) && is_array( $_wp_additional_image_sizes ) ) {
			foreach ( $_wp_additional_image_sizes as $key => $size ) {
				$choices[ $key ] = $key;
				if ( true === $show_dimension ){
					$choices[ $key ] .= ' ('. $size['width'] . 'x' . $size['height'] . ')';
				}
			}
		}

		if ( ! empty( $allowed ) ) {
			foreach ( $choices as $key => $value ) {
				if ( ! in_array( $key, $allowed ) ) {
					unset( $choices[ $key ] );
				}
			}
		}

		return $choices;

	}

endif;

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
