<?php
/**
 * Post revisions widget.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Recent revisions widget.
 */
class SSL_ALP_Revisions_Widget extends WP_Widget {
	const DEFAULT_NUMBER = 10;
	const DEFAULT_GROUP  = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ssl-alp-revisions',
			esc_html__( 'Recent Revisions', 'ssl-alp' ),
			array(
				'description' => __( "Your site's most recent revisions", 'ssl-alp' ),
			)
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		// Default title.
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Recent Revisions', 'ssl-alp' );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $title ) . $args['after_title'];
		}

		// Number of revisions to display.
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : self::DEFAULT_NUMBER;

		if ( ! $number ) {
			$number = self::DEFAULT_NUMBER;
		}

		$this->the_revisions( $number );

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance Widget options.
	 */
	public function form( $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : self::DEFAULT_NUMBER;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'ssl-alp' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of revisions to show:', 'ssl-alp' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3" />
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance New options.
	 * @param array $old_instance Previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']  = ! empty( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['number'] = absint( $new_instance['number'] );

		return $instance;
	}

	/**
	 * Print the revision list.
	 *
	 * @param int $number Number of revisions to show.
	 */
	private function the_revisions( $number ) {
		$revisions = $this->get_author_grouped_published_revisions( $number );

		if ( ! count( $revisions ) ) {
			echo '<p>There are no revisions yet.</p>';
		} else {
			echo '<ul id="recent-revisions-list" class="list-unstyled">';

			foreach ( $revisions as $revision ) {
				// Get the revision's parent.
				$parent = get_post( $revision->post_parent );

				// Revision author.
				$author = get_the_author_meta( 'display_name', $revision->post_author );

				// Human revision date.
				$post_date = sprintf(
					/* translators: 1: time ago */
					__( '%s ago', 'ssl-alp' ),
					human_time_diff( strtotime( $revision->post_date ) )
				);

				echo '<li class="recent-revision">';

				printf(
					/* translators: 1: author, 2: post title */
					esc_html__( '%1$s on %2$s', 'ssl-alp' ),
					esc_html( $author ),
					sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url( get_permalink( $parent->ID ) ),
						esc_attr( $post_date ),
						esc_html( $parent->post_title )
					)
				);

				// Post type (only for non-posts).
				if ( 'post' !== $parent->post_type ) {
					$post_type = get_post_type_object( $parent->post_type );
					$post_type_label = sprintf(
						/* translators: 1: referenced post type label */
						__( '(%1$s)', 'ssl-alp' ),
						$post_type->labels->singular_name
					);

					echo '&nbsp;';
					printf(
						'<span>%1$s</span>',
						esc_html( $post_type_label )
					);
				}

				echo '</li>';
			}

			echo '</ul>';
		}
	}

	/**
	 * Get revisions on published posts, grouped by author.
	 *
	 * @param int $number Number of revisions to show.
	 *
	 * @return array
	 */
	private function get_author_grouped_published_revisions( $number ) {
		global $ssl_alp;

		// Pass through to main revisions class.
		return $ssl_alp->revisions->get_author_grouped_published_revisions( $number );
	}
}
