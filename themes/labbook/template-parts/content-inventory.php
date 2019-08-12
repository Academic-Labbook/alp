<?php
/**
 * Template part for displaying inventory post type single post
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Labbook
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-header-container">
		<div class="breadcrumbs">
			<?php labbook_the_inventory_breadcrumbs(); ?>
		</div>
		<header class="entry-header">
			<?php labbook_the_post_title( $post, false, false ); ?>
			<div class="entry-meta">
				<?php labbook_the_post_meta(); ?>
				<?php labbook_the_inventory_item_posts_link(); ?>
			</div><!-- .entry-meta -->
		</header><!-- .entry-header -->
		<?php if ( has_post_thumbnail() ) : ?>
		<div class="entry-thumbnail">
			<a href="<?php the_post_thumbnail_url(); ?>">
				<?php the_post_thumbnail( 'thumbnail' ); ?>
			</a>
		</div>
		<?php endif; ?>
	</div>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'labbook' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
