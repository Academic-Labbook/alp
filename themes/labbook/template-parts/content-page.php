<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Labbook
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="breadcrumbs">
		<?php labbook_the_page_breadcrumbs(); ?>
	</div>
	<header class="entry-header">
		<?php labbook_the_post_title( $post, false, false ); ?>
		<div class="entry-meta">
			<?php labbook_the_post_meta(); ?>
		</div><!-- .entry-meta -->
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages( array(
			'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'labbook' ),
			'after'  => '</div>',
		) );
		?>
	</div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
<?php labbook_the_references(); ?>
<?php labbook_the_revisions(); ?>
