<?php
/**
 * Template part for displaying single post
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Alpine
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
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ssl-alpine' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php ssl_alpine_the_footer(); ?>
	</footer>
</article><!-- #post-<?php the_ID(); ?> -->
<?php ssl_alpine_the_references(); ?>
<?php ssl_alpine_the_revisions(); ?>

