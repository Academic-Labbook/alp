<?php
/**
 * The template part for displaying results in search pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package ssl-alp
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php ssl_alpine_the_post_title(); ?>
		<?php if ( 'post' == get_post_type() ) : ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_post_meta(); ?>
		</div><!-- .entry-meta -->
		<?php elseif ( 'page' == get_post_type() ) : ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_page_meta(); ?>
		</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php if ( 'status' !== get_post_format() ) : // status update theme type; don't show content ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php if ( 'post' === get_post_type() ) : ?>
	<footer class="entry-footer">
		<?php ssl_alpine_the_footer(); ?>
	</footer>
	<?php endif; ?>
	<?php endif; ?>
</article><!-- #post-## -->
