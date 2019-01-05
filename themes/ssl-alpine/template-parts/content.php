<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Alpine
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

	<?php
	if ( 'status' == get_post_format() ) :
		// do nothing
	elseif ( 'excerpt' === ssl_alpine_get_option( 'content_layout' ) ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div>
	<?php else : ?>
	<div class="entry-content">
		<?php the_content( esc_html__( 'Continue reading', 'ssl-alpine' ) . ' <span class="meta-nav">&rarr;</span>' ); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ssl-alpine' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
	<?php endif; ?>

	<?php if ( 'status' !== get_post_format() ) : ?>
	<footer class="entry-footer">
		<?php ssl_alpine_the_footer(); ?>
	</footer>
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
