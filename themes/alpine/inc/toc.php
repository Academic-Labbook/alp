<?php
/**
 * Table of contents generator functions.
 *
 * @package Alpine
 */

class SSL_Alpine_Menu_Level {
	public $parent_menu = null;
	private $child_menus = array();
	public $last_child = null;
	private $menu_data = null;
	public $is_root = false;

	public function get_menu_data() {
		return $this->menu_data;
	}

	public function set_menu_data( $menu_data ) {
		$this->menu_data = $menu_data;
	}

	public function add_child_menu( $child ) {
		$child->parent_menu = &$this;
		$this->child_menus[] = $child;

		// update last child reference
		end($this->child_menus);
		$this->last_child = &$this->child_menus[key($this->child_menus)];
	}

	public function get_child_menus() {
		return $this->child_menus;
	}

	public function is_empty() {
		return empty( $this->child_menus ) && is_null( $this->menu_data );
	}
}

if ( ! function_exists( 'ssl_alpine_generate_post_contents' ) ) :
	/**
	 * Builds a table of contents for the specified page, and returns the page
     * with heading IDs injected where appropriate.
	 */
	function ssl_alpine_generate_post_contents( $post_content, &$toc ) {
		if ( ! extension_loaded( 'dom' ) ) {
			// cannot generate table of contents
			return $post_content;
        }

        if ( empty( $post_content ) ) {
            // nothing to do
            return $post_content;
        }

		// disable visible XML error reporting temporarily
		// (we ignore errors)
		$prev_libxml_error_setting = libxml_use_internal_errors( true );

        $document = new DOMDocument();

        // load HTML document
		$document->loadHTML( $post_content );

		// revert libxml error setting
		libxml_use_internal_errors( $prev_libxml_error_setting );

		// create root list container
		$toc = new SSL_Alpine_Menu_Level(); // level "0"
		$toc->is_root = true;

		// set parent container to root
		$head = &$toc; // level 0

		// create XPath query to get header elements
        $xpath = new DOMXPath( $document );
        // query headings (ignore h1, which shouldn't be used in posts)
		$xpath_query = $xpath->query( '//*[self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' );

		// default last level
		$last_level = 1;

		/**
		 * Build table of contents.
		 *
		 * This searches the document for <h1> .. <h6> elements, which are returned by
		 * $xpath_query in the order they are found in the document. Before starting,
		 * a root array is created to hold the whole tree, and this is defined as the
		 * first "parent". Then the document's header elements are iterated over:
		 *
		 *     First of all, the current header level (e.g. <h3> is level 3) is checked
		 * 	   against the previous level. If it's higher (for example, when an <h3>
		 *     follows an <h2>), a new array is created as a child of the current parent,
		 *     and this becomes the new parent (this array does not initially contain a
		 *     value, as there may not be a header for this level). If the current level
		 *     is lower than the previous, the parent is set to the current parent's
		 *     parent.
		 *
		 *     After determining the current level, a new child is added to the current
		 *     parent, and is given a value corresponding to the current header's id. The
		 *     previous level is set to the current level, and the loop continues.
		 *
		 * After the loop, the result is an tree structure containing each header from
		 * each level, ordered in a hierarchy where higher headers are children of its
		 * preceding lower one.
		 */
		foreach ( $xpath_query as $header ) {
			// get header's level
			sscanf( $header->tagName, 'h%u', $current_level );

			// update parent pointer
			if ( $current_level < $last_level ) {
				// move parent up
				for ( $i = $current_level; $i < $last_level; $i++ ) {
					$head = &$head->parent_menu;
				}
			} elseif ( $current_level > $last_level) {
				// move parent down
				for ( $i = $last_level; $i < $current_level; $i++ ) {
					// new parent should now be the last child of the current parent
					if ( is_null( $head->last_child ) ) {
						$head->add_child_menu( new SSL_Alpine_Menu_Level() );
					}

					$head = &$head->last_child;
				}
			}

			// update last level
			$last_level = $current_level;

			// unique id for this header
			$header_id = _unique_id( $header, $document );

			// set tag's id
			$header->setAttribute( 'id', $header_id );

			// add new child to parent
			$child = new SSL_Alpine_Menu_Level();
			$child->set_menu_data(
				array(
					'id'	=>	$header_id,
					'title'	=>	$header->textContent
				)
			);

			$head->add_child_menu( $child );
        }

		// convert DOM with any changes made back into HTML
        return $document->saveHTML();
	}
endif;

if ( ! function_exists( '_unique_id' ) ) :
	/**
	 * Get a unique id for the given tag
	 */
	function _unique_id( $tag, $dom ) {
		$id = $tag->getAttribute( 'id' );

		if ( empty( $id ) ) {
			// no id; convert text content into id
			$id = _text_to_id( $tag->textContent );
		}

		if ( _id_exists( $id, $dom ) && $dom->getElementById( $id ) !== $tag ) {
			// id already used elsewhere
			// generate new one

			// copy original id
			$original_id = $id;

			// number to add to end of id
			$num = 1;

			// add increasing natural number onto end of id until unique is found
			while ( _id_exists( $id, $dom ) ) {
				// id with appended number
				$id = $original_id . $num;

				$num++;
			}
		}

		return $id;
	}
endif;

if ( ! function_exists( '_id_exists' ) ) :
	/**
	 * Check if specified DOM element id already exists
	 */
	function _id_exists( $id, &$dom ) {
		return ! is_null( $dom->getElementById( $id ) );
	}
endif;

if ( ! function_exists( '_text_to_id' ) ) :
	/**
	 * Convert text within HTML tag to a valid id
	 */
	function _text_to_id( $text, $delimiter = '-' ) {
		// convert to lowercase
		$text = strtolower( $text );

		// strip whitespace before and after id
		$text = trim( $text, $delimiter );

		// replace inner whitespace with delimeter
		$text = preg_replace( '/\s/', $delimiter, $text );

		// remove anything that isn't a word or delimiter
		$text = preg_replace( '/[^\w\\' . $delimiter . ']/', '', $text );

		return $text;
	}
endif;
