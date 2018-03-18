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
	<footer class="entry-footer">
		<?php
			/* translators: used between list items, there is a space after the comma */
			$category_list = get_the_category_list( esc_html__( ', ', 'ssl-alp' ) );

			/* translators: used between list items, there is a space after the comma */
			$tag_list = get_the_tag_list( '', esc_html__( ', ', 'ssl-alp' ) );

			if ( ! empty( $category_list ) ) {
				echo '<span class="cat-links"><i class="fa fa-folder-open" aria-hidden="true"></i> ' . $category_list . '</span>';
			}
			if ( ! empty( $tag_list ) ) {
				echo '<span class="sl-tags"><i class="fa fa-tags" aria-hidden="true"></i> ' . $tag_list . '</span>';
			}

		?>

		<?php edit_post_link( esc_html__( 'Edit', 'ssl-alp' ), '<span class="edit-link pull-right"><i class="fa fa-edit" aria-hidden="true"></i>', '</span>' ); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
<?php ssl_alpine_the_references(); ?>
<?php ssl_alpine_the_revisions(); ?>
