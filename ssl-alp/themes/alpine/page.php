<?php
/**
 * The default template for displaying pages.
 *
 * @package ssl-alp
 */

 get_header(); ?>

   <div id="primary" <?php echo ssl_alp_content_class( 'content-area' ); ?>>
     <main id="main" class="site-main" role="main">

 		<?php while ( have_posts() ) : the_post(); ?>

         <?php get_template_part( 'content', 'page' ); ?>

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
