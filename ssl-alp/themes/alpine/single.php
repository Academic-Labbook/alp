<?php
/**
 * The template for displaying all single posts.
 *
 * @package ssl-alp
 */

get_header(); ?>

	<div id="primary" <?php echo ssl_alp_content_class( 'content-area' ); ?>>
		<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) : the_post(); ?>

			<?php get_template_part( 'content', 'single' ); ?>

			<?php
				the_post_navigation( array(
					'next_text' => '%title <i class="fa fa-chevron-right" aria-hidden="true"></i>',
					'prev_text' => '<i class="fa fa-chevron-left" aria-hidden="true"></i> %title',
				) );
			?>

			<?php
				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
			?>

		<?php endwhile; // End of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
