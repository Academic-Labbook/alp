<?php

/**
 * Wiki functionality
 */
class SSL_ALP_Wiki extends SSL_ALP_Module {
	/**
	 * Register the stylesheets.
	 */
	public function enqueue_styles() {

	}

	/**
	 * Register JavaScript.
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Register settings
	 */
	public function register_settings() {

	}

    /**
     * Register settings fields
     */
    public function register_settings_fields() {
        /**
         * Post references settings
         */
    }

	/**
	 * Register hooks
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// remove comment, author and thumbnail support
		$loader->add_action( 'init', $this, 'disable_post_type_support' );

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
		register_widget( 'SSL_ALP_Page_Contents' );
	}

	/**
	 * Prepares page content by inserting id attributes into <h1>, <h2>, ... <h6> tags
	 * where necessary and building a table of contents
	 */
	public function prepare_page_content( $content ) {
		// variable to store contents for widget
		global $ssl_alp_page_toc;

		// disable visible XML error reporting temporarily
		// (we will check for errors using libxml_get_errors)
		$prev_libxml_error_setting = libxml_use_internal_errors( true );

		// load content into DOM parser
		$document = new DOMDocument();
		$document->loadHTML($content);

		if ( count( libxml_get_errors() ) ) {
			// there were parser errors
			error_log( sprintf( 'SSL_ALP parser errors: %s', print_r( libxml_get_errors(), true ) ) );

			// return content without building a table of contents
			return $content;
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

			// add new child to parent
			$child = new SSL_ALP_Menu_Level();
			$child->set_menu_data(
				array(
					'id'	=>	$this->_unique_id( $header, $document ),
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
	protected function _unique_id( &$tag, &$dom ) {
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

			// add increasing natural number onto end of id until unique is found
			while ( $this->_id_exists( $id, $dom ) ) {
				// number to add to end of id
				static $num = 1;

				// id with appended number
				$id = $original_id . $num;
					
				$num++;
			}
		}

		// set tag's id
		$tag->setAttribute( 'id', $id );

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
		$text = strtolower($text);

		// strip whitespace before and after id
		$text = trim($text, $delimiter);

		// replace inner whitespace with delimeter
		$text = preg_replace('/\s/', $delimiter, $text);

		// remove anything that isn't a word or delimiter
		$text = preg_replace('/[^\w\\' . $delimiter . ']/', '', $text);

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
}

class SSL_ALP_Page_Contents extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'ssl_alp_page_contents_widget', // base ID
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
		if ( ! $this->has_contents() ) {
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		// output the contents
		$this->the_contents();

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Contents', 'ssl-alp' );

		// TODO: add option for number of levels of contents to display

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
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

		return $instance;
	}

	/**
	 * Checks if the widget is being displayed on a page with contents
	 */
	protected function has_contents() {
		global $ssl_alp_page_toc;

		return ( ! empty( $ssl_alp_page_toc ) );
	}

	protected function the_contents() {
		global $ssl_alp_page_toc;

		// call recursive menu printer
		$this->_content_list( $ssl_alp_page_toc );
	}

	protected function _content_list( $contents ) {
		if ( ! $contents->is_root ) {
			$menu_data = $contents->get_menu_data();

			if ( is_array( $menu_data ) ) {	
				echo '<li>';

				printf(
					'<a href="#%1$s">%2$s</a>',
					esc_html( $menu_data['id'] ),
					esc_html( $menu_data['title'] )
				);

				echo '</li>';
			}
		}

		$children = $contents->get_child_menus();

		foreach ( $children as $child ) {
			echo '<ul>';

			$this->_content_list( $child );

			echo '</ul>';
		}
	}
}
