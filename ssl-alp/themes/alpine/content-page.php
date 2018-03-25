<?php
/**
 * The template used for displaying page content in page.php
 *
 * @package ssl-alp
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_page_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'ssl-alp' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->
</article><!-- #post-## -->
<?php ssl_alpine_the_references(); ?>
<?php ssl_alpine_the_revisions(); ?>
