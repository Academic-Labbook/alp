<?php
/**
 * The template part for displaying content in loop.
 *
 * @package ssl-alp
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php ssl_alpine_the_post_title(); ?>
		<?php if ( 'post' === get_post_type() ) : ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_post_meta(); ?>
		</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php if ( 'status' === get_post_format() ) :
		// status update theme type; don't show content
	?>
	<?php else :
	$content_layout = ssl_alp_get_option( 'content_layout' );
	?>

	<?php if ( 'excerpt' === $content_layout ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div>
	<?php else : ?>
	<div class="entry-content">
		<?php the_content( esc_html__( 'Continue reading', 'ssl-alp' ) . ' <span class="meta-nav">&rarr;</span>' ); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ssl-alp' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
	<?php endif; ?>
	<footer class="entry-footer">
		<?php ssl_alpine_the_footer(); ?>
	</footer>
	<?php endif; ?>
</article><!-- #post-## -->
