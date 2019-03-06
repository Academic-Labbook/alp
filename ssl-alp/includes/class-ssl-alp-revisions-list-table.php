<?php
/**
 * Post revisions admin table.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

if ( ! class_exists('WP_List_Table') ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Revisions table.
 */
class SSL_ALP_Revisions_List_Table extends WP_List_Table {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Revision', 'ssl-alp' ),
				'plural'   => __( 'Revisions', 'ssl-alp' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define the columns that are displayed for each row.
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox"/>',
			'post_title'  => __( 'Title', 'ssl-alp' ),
			'author'      => __( 'Author', 'ssl-alp' ),
			'date'        => __( 'Date', 'ssl-alp' ),
		);
	}

	/**
	 * Handles the post author column output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_author( $revision ) {
		$args = array(
			'post_type' => $revision->post_type,
			'author'    => $revision->post_author,
		);

		echo $revision->post_author;
	}

	/**
	 * Handles the post date column output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_date( $revision ) {
		$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
		$m_time = $revision->post_date;
		$time   = get_post_time( 'G', true, $revision );
		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			$h_time = sprintf( __( '%s ago', 'ssl-alp' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
		}

		echo esc_html( 'Last Modified', 'ssl-alp' ) . '<br/>';
		echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				echo $item->$column_name;
		}
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $item  A row's data.
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="revision_' . $item->ID . '">' . sprintf( __( 'Select %s', 'ssl-alp' ), $item->ID ) . '</label>'
			. '<input type="checkbox" name="revisions[]" id="revision_' . $item->ID . '" value="' . $item->ID . '" />'
		);
	}

	public function prepare_items() {
		$revisions_per_page = $this->get_items_per_page( 'ssl_alp_revisions_per_page' );

		$args = array(
			'post_type'        => 'revision',
			'post_status'	   => 'inherit',
			'paged'            => $this->get_pagenum(),
			'posts_per_page'   => $revisions_per_page,
			'suppress_filters' => true,
		);

		$revisions_query = new WP_Query();
		$revisions       = $revisions_query->query( $args );
		$offset          = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$page            = (int) $args['paged'];
		$total_revisions = $revisions_query->found_posts;

		if ( $revisions_query->query_vars['posts_per_page'] > 0 ) {
			$max_pages = ceil( $total_revisions / (int) $revisions_query->query_vars['posts_per_page'] );
		} else {
			$max_pages = $total_revisions > 0 ? 1 : 0;
		}

		// Get revisions.
		$this->items = $revisions;

		// Pagination.
  		$this->set_pagination_args(
			array (
	  			'total_items' => $total_revisions,
	  			'per_page'    => $revisions_per_page,
	  			'total_pages' => $max_pages,
		    )
		);
	}

	public function no_items() {
		esc_html_e( 'No revisions.', 'ssl-alp' );
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param object $revision    Revision being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions output for revisions.
	 */
	protected function handle_row_actions( $revision, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		if ( current_user_can( 'delete_post', $revision->post_parent ) ) {
			$actions['delete'] = sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				'',
				/* translators: %s: revision parent post title */
				esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently', 'ssl-alp' ), $revision->post_title ) ),
				__( 'Delete Permanently' )
			);
		}

		return $this->row_actions( $actions );
	}
}
