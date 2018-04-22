<?php
/**
 * The template part for displaying content of single post.
 *
 * @package ssl-alp
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
		the_title(
			sprintf(
				'<h2 class="entry-title"><a href="%2$s" class="%1$s" rel="bookmark" >',
				( 'status' === get_post_format() ) ? "status-post-title" : "", // add icon class for status updates
				esc_url( get_permalink() )
			),
			'</a></h2>'
		);
		?>

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
	<?php ssl_alpine_the_footer(); ?>
</article><!-- #post-## -->
<?php ssl_alpine_the_references(); ?>
<?php ssl_alpine_the_revisions(); ?>
