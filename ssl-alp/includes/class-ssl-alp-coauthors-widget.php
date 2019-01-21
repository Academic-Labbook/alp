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

		if ( $dropdown ) {
			// Unfortunately wp_dropdown_users doesn't support displaying post counts, so we have to
			// do it ourselves.
			$users = get_users(
				array(
					'fields'  => array(
						'ID',
						'display_name',
					),
					'order'   => 'ASC',
					'orderby' => 'display_name',
				)
			);

			// Get user post counts.
			$user_ids    = wp_list_pluck( $users, 'ID' );
			$post_counts = $ssl_alp->coauthors->count_many_users_posts( $user_ids );

			if ( ! empty( $users ) ) {
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
					$name       = esc_html( $user->display_name );
					$post_count = $post_counts[ $user->ID ];

					printf(
						'<option value="%1$s">',
						esc_attr( $user->ID )
					);

					printf(
						/* translators: 1: user display name, 2: user post count */
						esc_html_x( '%1$s (%2$d)', 'User list', 'ssl-alp' ),
						esc_html( $name ),
						esc_html( $post_count )
					);

					echo '</option>';
				}

				echo '</select>';
				echo '</form>';
			}
		} else {
			echo '<ul>';

			wp_list_authors(
				array(
					'optioncount' => true,
					'show'        => false, // Use display_name.
				)
			);

			echo '</ul>';
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
