<?php
/**
 * Table of contents generator functions.
 *
 * @package Labbook
 */

/**
 * Table of contents menu level.
 */
class Labbook_TOC_Menu_Level {
	/**
	 * Parent menu.
	 *
	 * @var $parent_menu
	 */
	public $parent_menu = null;

	/**
	 * Child menus.
	 *
	 * @var $child_menus
	 */
	private $child_menus = array();

	/**
	 * Last child.
	 *
	 * @var $last_child
	 */
	public $last_child = null;

	/**
	 * Menu data.
	 *
	 * @var $menu_data
	 */
	private $menu_data = null;

	/**
	 * Is root flag.
	 *
	 * @var $is_root
	 */
	public $is_root = false;

	/**
	 * Get menu data.
	 */
	public function get_menu_data() {
		return $this->menu_data;
	}

	/**
	 * Set menu data.
	 *
	 * @param array $menu_data Menu data.
	 */
	public function set_menu_data( $menu_data ) {
		$this->menu_data = $menu_data;
	}

	/**
	 * Add child menu.
	 *
	 * @param array $child Child.
	 */
	public function add_child_menu( $child ) {
		$child->parent_menu  = &$this;
		$this->child_menus[] = $child;

		// Update last child reference.
		end( $this->child_menus );
		$this->last_child = &$this->child_menus[ key( $this->child_menus ) ];
	}

	/**
	 * Number of children.
	 */
	public function count() {
		return count( $this->child_menus );
	}

	/**
	 * Get child menus.
	 */
	public function get_child_menus() {
		return $this->child_menus;
	}

	/**
	 * Check if empty.
	 */
	public function is_empty() {
		return empty( $this->child_menus ) && is_null( $this->menu_data );
	}
}

if ( ! function_exists( 'labbook_generate_post_contents' ) ) :
	/**
	 * Build a table of contents for the specified page, and return the page
	 * with heading IDs injected where appropriate.
	 *
	 * @param string                 $post_content Post content.
	 * @param Labbook_TOC_Menu_Level $toc          Variable to store generated menus.
	 */
	function labbook_generate_post_contents( $post_content, &$toc ) {
		if ( ! labbook_php_dom_extension_loaded() ) {
			// Cannot generate table of contents.
			return $post_content;
		}

		if ( empty( $post_content ) ) {
			// Nothing to do.
			return $post_content;
		}

		// Disable visible XML error reporting temporarily (we ignore errors).
		$prev_libxml_error_setting = libxml_use_internal_errors( true );

		$document = new DOMDocument();

		/**
		 * Append a charset to the document to avoid encoding errors (by default, loadHTML assumes
		 * iso-8859-1, whereas WordPress uses UTF-8.
		 *
		 * The head is extracted again later.
		 *
		 * https://php.net/manual/en/domdocument.loadhtml.php#95251
		 */
		$post_content = '<?xml encoding="' . esc_attr( get_bloginfo( 'charset' ) ) . '">' . $post_content . '</body>';

		// Load HTML document.
		$document->loadHTML( $post_content );

		/**
		 * Extract XML encoding element.
		 *
		 * See https://php.net/manual/en/domdocument.loadhtml.php#95251.
		 */

		foreach ( $document->childNodes as $item ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( XML_PI_NODE === $item->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				// Remove XML encoding element.
				$document->removeChild( $item );
			}
		}

		// Set encoding directly.
		$document->encoding = 'UTF-8';

		// Revert libxml error setting.
		libxml_use_internal_errors( $prev_libxml_error_setting );

		// Create root list container.
		$toc          = new Labbook_TOC_Menu_Level();
		$toc->is_root = true;

		// Set parent container to root.
		$head = &$toc; // Level 0.

		// Create XPath query to get header elements.
		$xpath = new DOMXPath( $document );
		// Query headings (ignore h1, which shouldn't be used in posts).
		$xpath_query = $xpath->query( '//*[self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' );

		// Default last level.
		$last_level = 1;

		/**
		 * Build table of contents.
		 *
		 * This searches the document for <h1> .. <h6> elements, which are returned by
		 * $xpath_query in the order they are found in the document. Before starting,
		 * a root array is created to hold the whole tree, and this is defined as the
		 * first "parent". Then the document's header elements are iterated over:
		 *
		 * First of all, the current header level (e.g. <h3> is level 3) is checked
		 * against the previous level. If it's higher (for example, when an <h3>
		 * follows an <h2>), a new array is created as a child of the current parent,
		 * and this becomes the new parent (this array does not initially contain a
		 * value, as there may not be a header for this level). If the current level
		 * is lower than the previous, the parent is set to the current parent's
		 * parent.
		 *
		 * After determining the current level, a new child is added to the current
		 * parent, and is given a value corresponding to the current header's id. The
		 * previous level is set to the current level, and the loop continues.
		 *
		 * After the loop, the result is an tree structure containing each header from
		 * each level, ordered in a hierarchy where higher headers are children of its
		 * preceding lower one.
		 */
		foreach ( $xpath_query as $header ) {
			// Get header's level.
			sscanf( $header->tagName, 'h%u', $current_level ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Update parent pointer.
			if ( $current_level < $last_level ) {
				// Move parent up.
				for ( $i = $current_level; $i < $last_level; $i++ ) {
					$head = &$head->parent_menu;
				}
			} elseif ( $current_level > $last_level ) {
				// Move parent down.
				for ( $i = $last_level; $i < $current_level; $i++ ) {
					// New parent should now be the last child of the current parent.
					if ( is_null( $head->last_child ) ) {
						$head->add_child_menu( new Labbook_TOC_Menu_Level() );
					}

					$head = &$head->last_child;
				}
			}

			// Update last level.
			$last_level = $current_level;

			// Unique id for this header.
			$header_id = labbook_tag_unique_id( $header, $document );

			// Set tag's ID.
			$header->setAttribute( 'id', $header_id );

			// Add new child to parent.
			$child = new Labbook_TOC_Menu_Level();
			$child->set_menu_data(
				array(
					'id'    => $header_id,
					'title' => $header->textContent, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				)
			);

			$head->add_child_menu( $child );
		}

		// Convert DOM with any changes made back into HTML.
		return $document->saveHTML();
	}
endif;

if ( ! function_exists( 'labbook_tag_unique_id' ) ) :
	/**
	 * Get a unique id for the given tag.
	 *
	 * @param DOMNode     $tag HTML tag.
	 * @param DOMDocument $dom HTML document.
	 * @return int
	 */
	function labbook_tag_unique_id( $tag, $dom ) {
		$id = $tag->getAttribute( 'id' );

		if ( empty( $id ) ) {
			// No ID; convert text content into ID.
			$id = labbook_text_to_id( $tag->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		if ( labbook_tag_id_exists( $id, $dom ) && $dom->getElementById( $id ) !== $tag ) {
			/**
			 * ID already used elsewhere - generate new one.
			 */

			// Copy original ID.
			$original_id = $id;

			// Number to add to end of ID.
			$num = 1;

			// Add increasing natural number onto end of ID until unique is found.
			while ( labbook_tag_id_exists( $id, $dom ) ) {
				// ID with appended number.
				$id = $original_id . $num;

				$num++;
			}
		}

		return $id;
	}
endif;

if ( ! function_exists( 'labbook_tag_id_exists' ) ) :
	/**
	 * Check if specified DOM element ID already exists.
	 *
	 * @param int         $id  Element ID.
	 * @param DOMDocument $dom HTML document.
	 */
	function labbook_tag_id_exists( $id, &$dom ) {
		return ! is_null( $dom->getElementById( $id ) );
	}
endif;

if ( ! function_exists( 'labbook_text_to_id' ) ) :
	/**
	 * Convert text within HTML tag to a valid ID.
	 *
	 * @param string $text      Text to convert.
	 * @param string $delimiter Delimiter to use. Defaults to hyphen.
	 */
	function labbook_text_to_id( $text, $delimiter = null ) {
		// Convert to lowercase.
		$text = strtolower( $text );

		if ( is_null( $delimiter ) ) {
			$delimiter = '-';
		}

		// Strip whitespace before and after ID.
		$text = trim( $text, $delimiter );

		// Replace inner whitespace with delimeter.
		$text = preg_replace( '/\s/', $delimiter, $text );

		// Remove anything that isn't a word or delimiter.
		$text = preg_replace( '/[^\w\\' . $delimiter . ']/', '', $text );

		return $text;
	}
endif;
