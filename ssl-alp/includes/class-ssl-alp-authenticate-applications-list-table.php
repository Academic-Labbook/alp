<?php
/**
 * Application passwords admin table.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Application passwords table.
 */
class SSL_ALP_Authenticate_Applications_List_Table extends WP_List_Table {
	/**
	 * Get a list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox"/>',
			'name'      => esc_html__( 'Name', 'ssl-alp' ),
			'password'  => '<abbr title="' . esc_attr__( 'Application password (spaces do not matter)', 'ssl-alp' ) . '">' . esc_html__( 'Password', 'ssl-alp' ) . '</abbr>',
			'created'   => esc_html__( 'Created', 'ssl-alp' ),
			'last_used' => esc_html__( 'Last Used', 'ssl-alp' ),
			'last_ip'   => esc_html__( 'Last IP', 'ssl-alp' ),
		);
	}

	/**
	 * Define the columns that can be sorted.
	 */
	protected function get_sortable_columns() {
		return array(
			'name'      => 'name',
			'created'   => 'created',
			'last_used' => array( 'last_used', true ),
		);
	}

	public function no_items() {
		esc_html_e( 'No application passwords found.', 'ssl-alp' );
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'ssl_alp_applications_per_page' );
		$orderby  = ( isset( $_GET['orderby'] ) ) ? wp_unslash( $_GET['orderby'] ) : 'last_used';
		$order    = ( isset( $_GET['order'] ) ) ? wp_unslash( $_GET['order'] ) : 'desc';

		if ( array_key_exists( $orderby, $this->get_sortable_columns() )) {
			// Sort columns.
			$this->items = wp_list_sort( $this->items, $orderby, $order );
		}

		// Get current page number.
		$start_item = absint( ( $this->get_pagenum() - 1 ) * $per_page );

		// Total items.
		$total_items = count( $this->items );

		if ( $per_page > 0 ) {
			$max_pages = ceil( $total_items / $per_page );
		} else {
			$max_pages = $total_items > 0 ? 1 : 0;
		}

		// Slice items to show only this page's.
		$this->items = array_slice( $this->items, $start_item, $per_page );

		// Pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => $max_pages,
			)
		);
	}

	public function column_cb( $item ) {
        return sprintf(
			'<input type="checkbox" name="ssl_alp_applications[]" value="%s" />',
			$item['slug']
        );
    }

	public function column_password( $item ) {
		// Break password into chunks.
		$chunks = str_split( $item['password'], 5 );

		// Recombine with spaces.
		$password = implode( ' ', $chunks );

		return '<pre class="ssl-alp-application-password">' . esc_html( $password ) . '</pre>';
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param object $item The current item.
	 * @param string $column_name The current column name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return esc_html( $item['name'] );
			case 'created':
				return date( get_option( 'date_format', 'r' ), $item['created'] );
			case 'last_used':
				if ( empty( $item['last_used'] ) ) {
					return '&mdash;';
				}

				return date( get_option( 'date_format', 'r' ), $item['last_used'] );
			case 'last_ip':
				if ( empty( $item['last_ip'] ) ) {
					return '&mdash;';
				}

				return esc_html( $item['last_ip'] );
			default:
				return;
		}
	}

	public function get_bulk_actions() {
		return array(
			'ssl-alp-revoke-application' => esc_html__( 'Revoke', 'ssl-alp' ),
		);
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param object $revision    Application being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions output for application password.
	 */
	protected function handle_row_actions( $application, $column_name, $primary ) {
		global $ssl_alp;

		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array(
			'revoke' => $ssl_alp->auth->get_revoke_url( $application )
		);

		return $this->row_actions( $actions );
	}
}
