<?php
/**
 * Template part for displaying single post
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Labbook
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php labbook_the_post_title( $post, false, true, true ); ?>
		<div class="entry-meta">
			<?php labbook_the_post_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'labbook' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php labbook_the_footer(); ?>
	</footer>
</article><!-- #post-<?php the_ID(); ?> -->
<?php labbook_the_references(); ?>
<?php labbook_the_revisions(); ?>
