<?php
/**
 * Coauthor widget.
 *
 * @package ssl-alp
 */

/**
 * Coauthors widget.
 */
class SSL_ALP_Coauthors_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ssl-alp-users',
			esc_html__( 'Users', 'ssl-alp' ),
			array(
				'description' => __( 'A list of users and their post counts.', 'ssl-alp' ),
			)
		);
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 *
	 * @global $ssl_alp
	 */
	public function widget( $args, $instance ) {
		global $ssl_alp;

		echo $args['before_widget'];

		// Default title.
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Users' );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// Show dropdown by default.
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : true;

		// Default dropdown ID.
		$dropdown_id = 'ssl_alp_users_dropdown';

		// Get all users.
		$users = get_users(
			array(
				'order'   => 'ASC',
				'orderby' => 'display_name',
			)
		);

		// Empty list of user post counts.
		$user_post_counts = array();

		// Get users with non-zero post counts. This matches the behaviour of wp_list_authors.
		foreach ( (array) $users as $id => $user ) {
			$post_count = $ssl_alp->coauthors->get_user_post_count( $user );

			if ( is_null( $post_count ) || 0 === intval( $post_count ) ) {
				// Remove user from list.
				unset( $users[ $id ] );
			} else {
				$user_post_counts[ $user->ID ] = $post_count;
			}
		}

		if ( empty( $users ) ) {
			echo '<p>' . __( 'There are no users.', 'ssl-alp' ) . '</p>';
		} else {
			if ( $dropdown ) {
				// Enqueue script to take the user to the author's page.
				wp_enqueue_script(
					'ssl-alp-user-widget-js',
					SSL_ALP_BASE_URL . 'js/user-widget.js',
					array( 'jquery' ),
					$ssl_alp->get_version(),
					true
				);

				// Set element to handle click events for.
				wp_localize_script(
					'ssl-alp-user-widget-js',
					'ssl_alp_dropdown_id',
					esc_js( $dropdown_id )
				);

				// Enclose dropdown in a form so we can handle redirect to user page.
				printf( '<form action="%s" method="get">', esc_url( home_url() ) );

				// Make select name 'author' so the form redirects to the selected user page.
				printf( '<select name="author" id="%s">\n', esc_html( $dropdown_id ) );

				printf(
					'\t<option value="#">%1$s</option>',
					esc_html__( 'Select User', 'ssl-alp' )
				);

				foreach ( (array) $users as $user ) {
					printf(
						'<option value="%1$s">',
						esc_attr( $user->ID )
					);

					printf(
						/* translators: 1: user display name, 2: user post count */
						esc_html_x( '%1$s (%2$d)', 'User list', 'ssl-alp' ),
						esc_html( $user->display_name ),
						esc_html( $user_post_counts[ $user->ID ] )
					);

					echo '</option>';
				}

				echo '</select>';
				echo '</form>';
			} else {
				echo '<ul>';

				foreach ( (array) $users as $user ) {
					echo '<li>';

					$link = sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						get_author_posts_url( $user->ID, $user->user_nicename ),
						/* translators: %s: author's display name */
						esc_attr( sprintf( __( 'Posts by %s', 'ssl-alp' ), $user->display_name ) ),
						esc_html( $user->display_name )
					);

					printf(
						/* translators: 1: author posts link, 2: author post count */
						'%1$s (%2$s)',
						$link,
						esc_html( $user_post_counts[ $user->ID ] )
					);

					echo '</li>';
				}

				echo '</ul>';
			}
		}

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin.
	 *
	 * @param array $instance The widget options.
	 */
	public function form( $instance ) {
		$title    = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : true;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'ssl-alp' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'dropdown' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'dropdown' ) ); ?>"<?php checked( $dropdown ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'dropdown' ) ); ?>"><?php esc_html_e( 'Display as dropdown', 'ssl-alp' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']    = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['dropdown'] = ! empty( $new_instance['dropdown'] ) ? true : false;

		return $instance;
	}
}
