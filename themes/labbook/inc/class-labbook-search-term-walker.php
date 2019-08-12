<?php
/**
 * Term walker for advanced search page.
 *
 * @package labbook
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Advanced search term walker.
 */
class Labbook_Search_Term_Walker extends Walker {
	/**
	 * What the class handles.
	 *
	 * @var string
	 *
	 * @see Walker::$tree_type
	 */
	public $tree_type = 'term';

	/**
	 * Database fields to use.
	 *
	 * @var array
	 *
	 * @see Walker::$db_fields
	 * @todo Decouple this
	 */
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);

	/**
	 * Starts the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string $output   Used to append additional content (passed by reference).
	 * @param object $term     Term object.
	 * @param int    $depth    Depth of term. Used for padding.
	 * @param array  $args     Uses 'selected', 'show_count', 'name_field', 'count_field',
	 *                         'hide_empty' and 'value_field' keys. See wp_dropdown_categories().
	 * @param int    $id       Optional. ID of the current term. Default 0 (unused).
	 */
	public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat( '&nbsp;', $depth * 3 );

		if ( ! is_null( $args['value_callback'] ) ) {
			$value = call_user_func( $args['value_callback'], $term );
		} else {
			$value = $term->{$args['value_field']};
		}

		$item_selected = false;

		if ( ! is_null( $args['selected'] ) ) {
			if ( is_array( $args['selected'] ) ) {
				if ( in_array( (string) $value, $args['selected'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$item_selected = true;
				}
			} elseif ( (string) $value === (string) $args['selected'] ) {
				$item_selected = true;
			}
		}

		$name = $pad . $term->{$args['name_field']};

		if ( $args['show_count'] ) {
			if ( ! is_null( $args['count_callback'] ) ) {
				$count = call_user_func( $args['count_callback'], $term );
			} else {
				$count = $term->{$args['count_field']};
			}

			$name .= '&nbsp;&nbsp;(' . number_format_i18n( absint( $count ) ) . ')';
		}

		$output .= "\t";
		$output .= sprintf(
			'<option value="%1$s"%2$s%3$s>%4$s</option>',
			$value,
			( 0 !== $id ) ? ' id="' . esc_attr( $id ) . '"' : '',
			$item_selected ? ' selected="selected"' : '',
			$name
		);
		$output .= "\n";
	}
}
