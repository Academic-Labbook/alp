<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Alpine
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="breadcrumbs">
		<?php alpine_the_page_breadcrumbs(); ?>
	</div>
	<header class="entry-header">
		<?php alpine_the_post_title( $post, false, false, true ); ?>
		<div class="entry-meta">
			<?php alpine_the_page_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages( array(
			'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'alpine' ),
			'after'  => '</div>',
		) );
		?>
	</div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
<?php alpine_the_references(); ?>
<?php alpine_the_revisions(); ?>
