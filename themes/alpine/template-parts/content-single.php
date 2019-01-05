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
		<?php alpine_the_post_title( $post, false, true, true ); ?>
		<div class="entry-meta">
			<?php alpine_the_post_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'alpine' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php alpine_the_footer(); ?>
	</footer>
</article><!-- #post-<?php the_ID(); ?> -->
<?php alpine_the_references(); ?>
<?php alpine_the_revisions(); ?>

