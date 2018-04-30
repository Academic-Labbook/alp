<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Page wiki functionality
 */
class SSL_ALP_Pages extends SSL_ALP_Module {
	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// remove comment, author and thumbnail support
		$loader->add_action( 'init', $this, 'disable_post_type_support' );

		// remove month dropdown filter on admin page list
		$loader->add_action( 'months_dropdown_results', $this, 'disable_months_dropdown_results', 10, 2 );

		// remove date column from list of wiki pages in admin
		$loader->add_filter( 'manage_edit-page_columns', $this, 'manage_edit_columns' );

		// sort alphabetically by default
		$loader->add_filter( 'manage_edit-page_sortable_columns', $this, 'manage_edit_sortable_columns' );

		// register page contents widget
		$loader->add_action( 'widgets_init', $this, 'register_contents_widget' );

		// modify post content to inject header ids
		$loader->add_filter( 'the_content', $this, 'prepare_page_content' );
	}

	/**
	 * Disable comments on pages
	 */
	public function disable_post_type_support() {
		remove_post_type_support( 'page', 'comments' );
		remove_post_type_support( 'page', 'author' );
		remove_post_type_support( 'page', 'thumbnail' );
	}

	/**
	 * Disable months dropdown box in admin page list
	 */
	public function disable_months_dropdown_results( $months, $post_type ) {
		if ( $post_type == 'page' ) {
			// return empty array to force it to hide (see months_dropdown() in class-wp-list-table.php)
			return array();
		}

		return $months;
	}

	/**
	 * Filter columns shown on list of wiki pages in admin panel
	 */
	public function manage_edit_columns( $columns ) {
		// remove date
		unset( $columns["date"] );

		return $columns;
	}

	/**
	 * Sort columns alphabetically by default on list of wiki pages in admin panel
	 */
	public function manage_edit_sortable_columns( $columns ) {
		// remove date
		unset( $columns["date"] );

		// make title default sort
		$columns["title"] = array( $columns["title"], true );

		return $columns;
	}

	/**
	 * Register contents widget
	 */
	public function register_contents_widget() {
		register_widget( 'SSL_ALP_Widget_Contents' );
	}

	/**
	 * Prepares page content by inserting id attributes into <h1>, <h2>, ... <h6> tags
	 * where necessary and building a table of contents
	 */
	public function prepare_page_content( $page_content ) {
		// variable to store contents for widget
		global $ssl_alp_page_toc;

		// reset contents
		$ssl_alp_page_toc = '';

		if ( ! is_page() ) {
			// don't need to generate contents
			return $page_content;
		}

		if ( empty( $page_content ) ) {
			// cannot generate table of contents
			return $page_content;
		}

		if ( ! extension_loaded( 'dom' ) ) {
			// cannot generate table of contents
			return $page_content;
		}

		// disable visible XML error reporting temporarily
		// (we will check for errors using libxml_get_errors)
		$prev_libxml_error_setting = libxml_use_internal_errors( true );
		
		$document = new DOMDocument();

		/**
		 * Nasty hack to remove doctype and html/body elements added automatically
		 * 
		 * Because we use DOMDocument, it implies a doctype and <html><body> ... </body></html>
		 * in the output unless the LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD flags are
		 * set. However, the LIBXML_HTML_NOIMPLIED flag messes up the document structure,
		 * leading to complications with $header->textContent below. The code below immediately
		 * removes the doctype and html tags. It's not nice, but it works.
		 * 
		 * https://stackoverflow.com/a/29499718/2251982
		 */

		// load HTML document contained within a div
		$document->loadHTML( '<div>' . $page_content . '</div>' );

		// get div (it should be the only one)
		$container = $document->getElementsByTagName( 'div' )->item( 0 );

		// remove the container element from the document, but keep a reference to it
		$container = $container->parentNode->removeChild($container);

		// get rid of all other children in the document (doctype, html, body...)
		while ( $document->firstChild ) {
			$document->removeChild( $document->firstChild );
		}

		// add children from the container back into document
		while ( $container->firstChild ) {
			$document->appendChild( $container->firstChild );
		}

		/**
		 * end of hack
		 */

		if ( count( libxml_get_errors() ) ) {
			// there were parser errors
			//error_log( sprintf( 'SSL_ALP parser errors: %s', print_r( libxml_get_errors(), true ) ) );

			// clear errors
			libxml_clear_errors();

			// return content without building a table of contents
			return $page_content;
		}
		
		// revert libxml error setting
		libxml_use_internal_errors($prev_libxml_error_setting);

		// create root list container
		$contents = new SSL_ALP_Menu_Level(); // level "0"
		$contents->is_root = true;

		// set parent container to root
		$head = &$contents; // level 0

		// create XPath query to get header elements
		$xpath = new DOMXPath( $document );
		$xpath_query = $xpath->query( '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' );

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
					//$head->add_child_menu( new SSL_ALP_Menu_Level() );

					if ( is_null( $head->last_child ) ) {
						$head->add_child_menu( new SSL_ALP_Menu_Level() );
					}

					$head = &$head->last_child;
				}
			}

			// update last level
			$last_level = $current_level;

			// unique id for this header
			$header_id = $this->_unique_id( $header, $document );

			// set tag's id
			$header->setAttribute( 'id', $header_id );

			// add new child to parent
			$child = new SSL_ALP_Menu_Level();
			$child->set_menu_data(
				array(
					'id'	=>	$header_id,
					'title'	=>	$header->textContent
				)
			);

			$head->add_child_menu( $child );
		}

		// reset global variable containing contents
		$ssl_alp_page_toc = $contents;

		// convert DOM with any changes made back into HTML
		return $document->saveHTML();
	}

	/**
	 * Get a unique id for the given tag
	 */
	protected function _unique_id( $tag, $dom ) {
		$id = $tag->getAttribute( 'id' );

		if ( empty( $id ) ) {
			// no id; convert text content into id
			$id = $this->_text_to_id( $tag->textContent );
		}

		if ( $this->_id_exists( $id, $dom ) && $dom->getElementById( $id ) !== $tag ) {
			// id already used elsewhere
			// generate new one

			// copy original id
			$original_id = $id;

			// number to add to end of id
			$num = 1;

			// add increasing natural number onto end of id until unique is found
			while ( $this->_id_exists( $id, $dom ) ) {
				// id with appended number
				$id = $original_id . $num;
					
				$num++;
			}
		}

		return $id;
	}

	/**
	 * Check if specified DOM element id already exists
	 */
	protected function _id_exists( $id, &$dom ) {
		return ! is_null( $dom->getElementById( $id ) );
	}

	/**
	 * Convert text within HTML tag to a valid id
	 */
	protected function _text_to_id( $text, $delimiter = '-' ) {
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
}

class SSL_ALP_Menu_Level {
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

class SSL_ALP_Widget_Contents extends WP_Widget {
	/**
	 * Default maximum number of levels to show in contents
	 */
	const DEFAULT_MAX_LEVELS = 4;

	public function __construct() {
		parent::__construct(
			'ssl-alp-contents', // base ID
			esc_html__( 'Page Contents', 'ssl-alp' ), // name
			array(
				'description' => __( "Current page contents", 'ssl-alp' )
			)
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( ! $this->page_has_contents() ) {
			return;
		}

		echo $args['before_widget'];

		// default title
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Contents', 'ssl-alp' );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		$max_levels = ( ! empty( $instance['max_levels'] ) ) ? absint( $instance['max_levels'] ) : self::DEFAULT_MAX_LEVELS;
		
		if ( ! $max_levels ) {
			$max_levels = self::DEFAULT_MAX_LEVELS;
		}

		// output the contents
		$this->the_contents( $max_levels );

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$max_levels = isset( $instance['max_levels'] ) ? absint( $instance['max_levels'] ) : self::DEFAULT_MAX_LEVELS;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p><label for="<?php echo $this->get_field_id( 'max_levels' ); ?>"><?php _e( 'Maximum number of levels to show:', 'ssl-alp' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'max_levels' ); ?>" name="<?php echo $this->get_field_name( 'max_levels' ); ?>" type="number" step="1" min="1" max="6" value="<?php echo $max_levels; ?>" size="3" /></p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['max_levels'] = absint( $new_instance['max_levels'] );

		return $instance;
	}

	/**
	 * Checks if the widget is being displayed on a page with contents
	 */
	protected function page_has_contents() {
		global $ssl_alp_page_toc;

		if ( ! is_object( $ssl_alp_page_toc ) || ! ( $ssl_alp_page_toc instanceof SSL_ALP_Menu_Level ) ) {
			return false;
		} elseif ( $ssl_alp_page_toc->is_empty() ) {
			return false;
		}

		return true;
	}

	protected function the_contents( $max_levels = null ) {
		global $ssl_alp_page_toc;

		if ( is_null( $max_levels ) || ! is_int( $max_levels ) ) {
			// default max levels
			$max_levels = self::DEFAULT_MAX_LEVELS;
		}

		// call recursive menu printer
		$this->_content_list( $ssl_alp_page_toc, $max_levels );
	}

	protected function _content_list( $contents, $max_levels ) {
		if ( $max_levels < 0 ) {
			// beyond the maximum level setting
			return;
		}

		$menu_data = $contents->get_menu_data();

		if ( is_array( $menu_data ) ) {
			printf(
				'<a href="#%1$s">%2$s</a>',
				esc_html( $menu_data['id'] ),
				esc_html( $menu_data['title'] )
			);
		}

		$children = $contents->get_child_menus();

		if ( count( $children ) ) {
			echo '<ul>';

			foreach ( $children as $child ) {
				// show sublevel
				echo '<li>';
				$this->_content_list( $child, $max_levels - 1 );
				echo '</li>';
			}

			echo '</ul>';
		}
	}
}
