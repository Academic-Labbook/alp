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
		if ( ! $this->is_page() ) {
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		//echo esc_html__( 'Contents!', 'ssl-alp' );
		echo $this->get_contents();

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
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'ssl-alp' ); ?></label>
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
	 * Get current page, or null if no page exists
	 */
	protected function get_page() {
		// cannot use get_the_page() etc., so use get_queried_object instead
		$obj = get_queried_object();

		if ( ! is_object( $obj ) ) {
			// not valid object
			return null;
		} elseif ( ! property_exists( $obj, 'post_type' ) ) {
			// no post type field
			return null;
		}

		return ( $obj->post_type === 'page' ) ? $obj : null;
	}

	/**
	 * Checks if the widget is being displayed on a page
	 */
	protected function is_page() {
		return ! is_null( $this->get_page() );
	}

	protected function get_contents() {
		$page = $this->get_page();

		if ( is_null( $page ) ) {
			// no page
			return;
		}

		$content = $page->post_content;

		// create DOM parser
		$dom = new DOMDocument();
		$dom->loadHTML($content);

		echo "<ul>";
		// get all headers
		foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) as $header_tag ) {
			$header_tags = $dom->getElementsByTagName( $header_tag );

			foreach ( $header_tags as $tag ) {
				if ( empty( $tag->getAttribute( 'id' ) ) ) {
					// skip tag without id
					continue;
				}

				// strip out any inner tags and filter
				$tag_text = esc_html( $tag->textContent );

				$url = sprintf(
					'<a href="#%1$s">%2$s</a>',
					$tag->getAttribute('id'),
					$tag_text
				);

				printf(
					'<li>%1$s</li>',
					$url
				);
			}
		}
		echo "</ul>";
	}
}
