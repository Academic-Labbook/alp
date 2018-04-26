<?php
/**
 * The template part for displaying content of single post.
 *
 * @package ssl-alp
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php ssl_alpine_the_post_title( $post, false, true, true ); ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_post_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ssl-alp' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->
	<footer class="entry-footer">
		<?php ssl_alpine_the_footer(); ?>
	</footer>
</article><!-- #post-## -->
<?php ssl_alpine_the_references(); ?>
<?php ssl_alpine_the_revisions(); ?>
