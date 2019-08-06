<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Labbook
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-header-container">
		<header class="entry-header">
			<?php labbook_the_post_title(); ?>
			<?php if ( 'post' === get_post_type() ) : ?>
			<div class="entry-meta">
				<?php labbook_the_post_meta(); ?>
			</div><!-- .entry-meta -->
			<?php endif; ?>
		</header><!-- .entry-header -->
	</div>

	<?php
	if ( 'status' === get_post_format() ) :
		// Do nothing.
	elseif ( 'excerpt' === labbook_get_option( 'content_layout' ) ) :
	?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div>
	<?php else : ?>
	<div class="entry-content">
		<?php the_content( esc_html__( 'Continue reading', 'labbook' ) . ' <span class="meta-nav">&rarr;</span>' ); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'labbook' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
	<?php endif; ?>

	<?php if ( 'status' !== get_post_format() ) : ?>
	<footer class="entry-footer">
		<?php labbook_the_footer(); ?>
	</footer>
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
