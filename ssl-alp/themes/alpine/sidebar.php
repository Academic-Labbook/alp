<?php
/**
 * The sidebar containing the main widget areas.
 *
 * @package ssl-alp
 */

?>
	<div id="secondary" <?php echo ssl_alp_sidebar_class( 'widget-area container clearfix' ); ?> role="complementary">
		<?php
		if ( is_page() ) {
			// this is a page, but is it special?
			if ( is_page_template( 'template/page-root.php' ) ) {
				// special page root
				// no sidebar
			} else {
				// standard page
				if ( ! dynamic_sidebar( 'sidebar-page' ) ) {
					/**
					 * default sidebar shown before admin customisation
					 */

					// search widget
					the_widget(
						'WP_Widget_Search',
						array(),
						array()
					);

					// contents widget
					// ...
				}
			}
		} else {
			// not a page
			if ( ! dynamic_sidebar( 'sidebar-standard' ) ) {
				/**
				 * default sidebar shown before admin customisation
				 */

				// search widget
				the_widget(
					'WP_Widget_Search',
					array(),
					array()
				);

				// categories widget
				the_widget(
					'WP_Widget_Categories',
					array(
						'count'			=>	true,
						'hierarchical'	=>	true,
						'dropdown'		=>	true
					),
					array()
				);

				// archives widget
				the_widget(
					'WP_Widget_Archives',
					array(
						'count'		=>	true,
						'dropdown'	=>	true
					),
					array()
				);
			}
		}
		?>
	</div>
