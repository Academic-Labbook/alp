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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Revisions table.
 */
class SSL_ALP_Revisions_List_Table extends WP_List_Table {
	/**
	 * Revisions post type. Sets whether posts or pages are displayed.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type to show revisions for.
	 */
	public function __construct( $post_type = 'post' ) {
		parent::__construct(
			array(
				'singular' => __( 'Revision', 'ssl-alp' ),
				'plural'   => __( 'Revisions', 'ssl-alp' ),
				'ajax'     => false,
			)
		);

		if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			$post_type = 'post';
		}

		$this->post_type = $post_type;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links    = array();
		$current_user_id = get_current_user_id();

		$revisions_count = $this->count_revisions();
		$mine_count      = $this->count_revisions( $current_user_id );

		$all_class = '';

		if ( empty( $all_class ) && ( $this->is_base_request() ) ) {
			$all_class = 'current';
		}

		$all_inner_html = sprintf(
			/* translators: total revision count */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$revisions_count,
				'revisions',
				'ssl-alp'
			),
			number_format_i18n( $revisions_count )
		);

		$status_links['all'] = $this->get_edit_link(
			$this->get_page_args(),
			$all_inner_html,
			$all_class
		);

		$mine_class = '';

		if ( isset( $_GET['author'] ) && ( absint( $_GET['author'] ) === $current_user_id ) ) {
			$mine_class = 'current';
		}

		$mine_args = wp_parse_args(
			array(
				'author' => $current_user_id,
			),
			$this->get_page_args()
		);

		$mine_inner_html = sprintf(
			/* translators: total user revision count */
			_nx(
				'Mine <span class="count">(%s)</span>',
				'Mine <span class="count">(%s)</span>',
				$mine_count,
				'revisions',
				'ssl-alp'
			),
			number_format_i18n( $mine_count )
		);

		$mine = $this->get_edit_link(
			$mine_args,
			$mine_inner_html,
			$mine_class
		);

		if ( $mine ) {
			$status_links['mine'] = $mine;
		}

		return $status_links;
	}

	/**
	 * Get URL query arguments depending on post type.
	 */
	private function get_page_args() {
		$args = array();

		if ( 'post' === $this->post_type ) {
			$args['page'] = SSL_ALP_POST_REVISIONS_MENU_SLUG;
		} elseif ( 'page' === $this->post_type ) {
			$args['page']      = SSL_ALP_PAGE_REVISIONS_MENU_SLUG;
			$args['post_type'] = 'page';
		}

		return $args;
	}

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @return bool Whether the current view is the "All" view.
	 */
	protected function is_base_request() {
		$vars = $_GET;

		// Remove standard query vars.
		unset( $vars['page'] );
		unset( $vars['post_type'] );
		unset( $vars['paged'] );

		if ( empty( $vars ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Helper to create links to edit.php with params.
	 *
	 * @param string[] $args  Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 * @return string The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url          = add_query_arg( $args, 'edit.php' );
		$class_html   = '';
		$aria_current = '';

		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);
			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Define the columns that are displayed for each row.
	 */
	public function get_columns() {
		return array(
			'title'   => esc_html__( 'Title', 'ssl-alp' ),
			'changes' => '<abbr title="' . esc_html__( 'Lines added to and removed from the post text', 'ssl-alp' ) . '">' . esc_html__( 'Line changes', 'ssl-alp' ) . '</abbr>',
			'author'  => esc_html__( 'Author', 'ssl-alp' ),
			'date'    => esc_html__( 'Date', 'ssl-alp' ),
		);
	}

	/**
	 * Define the columns that can be sorted.
	 */
	protected function get_sortable_columns() {
		return array(
			'title' => 'post_title',
			'date'  => array( 'post_date', true ),
		);
	}

	/**
	 * Handle column title output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_title( $revision ) {
		global $ssl_alp;

		$parent          = get_post( $revision->post_parent );
		$latest_revision = $ssl_alp->revisions->get_latest_revision( $revision );

		$title = '<strong>';

		/**
		 * Note: interns are not shown the edit link below (it is empty) because they fail
		 * the edit_post permission check against the *revision* here. This is a subtle bug
		 * that would take a lot of effort to fix.
		 *
		 * Instead, interns simply aren't shown the revision link.
		 */
		$edit_link = get_edit_post_link( $revision->ID );

		$revision_title = $revision->post_title;
		$latest_title   = $parent->post_title;

		if ( ! empty( $edit_link ) && $ssl_alp->revisions->current_user_can_view_revision( $revision ) ) {
			$title .= sprintf(
				'<a class="row-title" href="%s" aria-label="%s">%s</a>',
				esc_url( $edit_link ),
				/* translators: %s: post title */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Difference)', 'ssl-alp' ), $revision_title ) ),
				esc_html( $revision_title )
			);
		} else {
			$title .= sprintf(
				'<span>%s</span>',
				esc_html( $revision_title )
			);
		}

		if ( $revision_title !== $latest_title ) {
			// The title has changed since this revision.
			$title .= '&nbsp;';
			$title .= sprintf(
				/* translators: current post title */
				esc_html__( '(now %s)', 'ssl-alp' ),
				'<em>' . esc_html( $latest_title ) . '</em>'
			);
		}

		if ( ! is_null( $latest_revision ) && $revision->ID === $latest_revision->ID ) {
			$title .= ' &mdash; <span class="post-state">' . esc_html__( 'Latest', 'ssl-alp' ) . '</span>';
		}

		$title .= '</strong>';

		return $title;
	}

	/**
	 * Handle changes column output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_changes( $revision ) {
		global $ssl_alp;

		$diff = $ssl_alp->revisions->get_post_text_differences( $revision );

		$title = '';

		if ( is_null( $diff ) ) {
			// Differences couldn't be determined.
			if ( $ssl_alp->revisions->revision_was_autogenerated_on_publication( $revision ) ) {
				$title .= 'first';
			}
			return;
		}

		$pieces = array();

		if ( $diff['added'] > 0 ) {
			$pieces[] = '<span class="ssl-alp-text-added">+' . $diff['added'] . '</span>';
		}

		if ( $diff['removed'] > 0 ) {
			$pieces[] = '<span class="ssl-alp-text-removed">-' . $diff['removed'] . '</span>';
		}

		$title .= implode( ', ', $pieces );

		return $title;
	}

	/**
	 * Handle post author column output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_author( $revision ) {
		$author = get_user_by( 'ID', $revision->post_author );

		if ( is_null( $author ) ) {
			return;
		}

		$args = wp_parse_args(
			array(
				'author' => $author->ID,
			),
			$this->get_page_args()
		);

		return $this->get_edit_link(
			$args,
			esc_html( $author->display_name )
		);
	}

	/**
	 * Handle post date column output.
	 *
	 * @param WP_Post $revision The current revision object.
	 */
	public function column_date( $revision ) {
		$t_time    = get_the_time( __( 'Y/m/d g:i:s a' ), $revision );
		$m_time    = $revision->post_date;
		$time      = get_post_time( 'G', true, $revision );
		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: time since revision */
			$h_time = sprintf( __( '%s ago', 'ssl-alp' ), human_time_diff( $time, current_time( 'timestamp' ) ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
		}

		$title = esc_html( 'Last Modified', 'ssl-alp' ) . '<br/>';
		$title .= '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</abbr>';

		return $title;
	}

	/**
	 * Handle column output not provided by any other function.
	 *
	 * @param string $item        Row item.
	 * @param string $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return $item->$column_name;
		}
	}

	/**
	 * Get items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$revisions_per_page = $this->get_items_per_page( 'ssl_alp_revisions_per_page' );
		$orderby            = ( isset( $_GET['orderby'] ) ) ? esc_sql( wp_unslash( $_GET['orderby'] ) ) : 'post_date';
		$order              = ( isset( $_GET['order'] ) ) ? esc_sql( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$author_sql = '';

		if ( isset( $_GET['author'] ) ) {
			$author_id = absint( wp_unslash( $_GET['author'] ) );

			if ( $author_id > 0 ) {
				$author_sql = esc_sql( 'AND posts.post_author = ' . $author_id );
			}
		}

		/**
		 * Custom query for revisions.
		 *
		 * We can't use WP_Query here because we want to avoid selecting autogenerated revisions
		 * when a post is published, and revisions where the post parent is not yet published.
		 *
		 * Ignores autosaves, revisions autogenerated on publication, and those of unpublished
		 * posts.
		 */
		$revisions_query = $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS posts.* FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->posts} AS parent_posts
			ON posts.post_parent = parent_posts.ID
			WHERE posts.post_type = 'revision'
			AND LOCATE(CONCAT(posts.post_parent, '-autosave'), posts.post_name) = 0
			AND parent_posts.post_type = %s
			AND posts.post_status = 'inherit'
			AND posts.post_date <> parent_posts.post_date
			AND parent_posts.post_status = 'publish'
			{$author_sql}
			ORDER BY posts." . $orderby . ' ' . $order . ', posts.ID DESC
			LIMIT %d, %d',
			$this->post_type,
			absint( ( $this->get_pagenum() - 1 ) * $revisions_per_page ),
			$revisions_per_page
		);

		$revisions = $wpdb->get_results( $revisions_query );

		// Get found rows.
		$total_revisions = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		if ( $revisions_per_page > 0 ) {
			$max_pages = ceil( $total_revisions / $revisions_per_page );
		} else {
			$max_pages = $total_revisions > 0 ? 1 : 0;
		}

		// Get revisions.
		$this->items = $revisions;

		// Pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_revisions,
				'per_page'    => $revisions_per_page,
				'total_pages' => $max_pages,
			)
		);
	}

	/**
	 * Count total revisions.
	 *
	 * @param int|null $user_id User ID to count revisions for. If null, all revisions are counted.
	 * @return int Number of revisions.
	 */
	private function count_revisions( $user_id = null ) {
		global $wpdb;

		$user_sql = '';

		if ( ! is_null( $user_id ) ) {
			$user_id  = absint( $user_id );
			$user_sql = esc_sql( 'AND posts.post_author = ' . $user_id );
		}

		$count_query = $wpdb->prepare(
			"SELECT COUNT(1) FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->posts} AS parent_posts
			ON posts.post_parent = parent_posts.ID
			WHERE posts.post_type = 'revision'
			AND LOCATE(CONCAT(posts.post_parent, '-autosave'), posts.post_name) = 0
			{$user_sql}
			AND parent_posts.post_type = %s
			AND posts.post_status = 'inherit'
			AND posts.post_date <> parent_posts.post_date
			AND parent_posts.post_status = 'publish'",
			$this->post_type
		);

		return $wpdb->get_var( $count_query );
	}

	/**
	 * Message for when there are no revisions found.
	 */
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
		global $ssl_alp;

		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		/**
		 * Note: interns are not shown the edit link below (it is empty) because they fail
		 * the edit_post permission check against the *revision* here. This is a subtle bug
		 * that would take a lot of effort to fix.
		 *
		 * Instead, interns simply aren't shown the revision link.
		 */
		$edit_link = get_edit_post_link( $revision->ID );

		if ( ! empty( $edit_link ) && $ssl_alp->revisions->current_user_can_view_revision( $revision ) ) {
			$actions['view_diff'] = sprintf(
				'<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $edit_link ),
				esc_attr( __( 'View changes made in this revision', 'ssl-alp' ) ),
				esc_html__( 'View Changes', 'ssl-alp' )
			);

			$latest_revision = $ssl_alp->revisions->get_latest_revision( $revision );

			if ( ! is_null( $latest_revision ) && $revision->ID !== $latest_revision->ID ) {
				$cur_link = admin_url(
					add_query_arg(
						array(
							'from' => $revision->ID,
							'to'   => $latest_revision->ID,
						),
						'revision.php'
					)
				);

				$actions['view_cur'] = sprintf(
					'<a href="%1$s" aria-label="%2$s">%3$s</a>',
					esc_url( $cur_link ),
					esc_attr( __( 'View difference between this revision and current published version', 'ssl-alp' ) ),
					esc_html__( 'View Difference to Latest', 'ssl-alp' )
				);
			}
		}

		return $this->row_actions( $actions );
	}
}
