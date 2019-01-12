<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Labbook
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php labbook_the_post_title(); ?>
		<div class="entry-meta">
			<?php labbook_the_post_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<?php if ( 'status' !== get_post_format() ) : // status update theme type; don't show content ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php if ( 'post' === get_post_type() ) : ?>
	<footer class="entry-footer">
		<?php labbook_the_footer(); ?>
	</footer>
	<?php endif; ?>
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
