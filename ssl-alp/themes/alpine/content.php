<?php
/**
 * The template part for displaying content in loop.
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

		<?php if ( 'post' === get_post_type() ) : ?>
		<div class="entry-meta">
			<?php ssl_alpine_the_post_meta(); ?>
		</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php if ( 'status' === get_post_format() ) : // status update theme type; don't show content ?>
	<?php else :
	$content_layout = ssl_alp_get_option( 'content_layout' );
	?>

	<?php if ( 'excerpt' === $content_layout ) : ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div>
	<?php else : ?>
	<div class="entry-content">
		<?php the_content( esc_html__( 'Continue reading', 'ssl-alp' ) . ' <span class="meta-nav">&rarr;</span>' ); ?>
		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ssl-alp' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->
	<?php endif; ?>

	<footer class="entry-footer">
		<?php if ( 'post' === get_post_type() ) : // Hide category and tag text for pages on Search. ?>
			<?php
			/* Translators: used between list items, there is a space after the comma. */
			$categories_list = get_the_category_list( esc_html__( ', ', 'ssl-alp' ) );

			if ( $categories_list && ssl_alp_categorized_blog() ) :
			?>
			<span class="cat-links">
				<i class="fa fa-folder-open" aria-hidden="true"></i>
				<?php printf( '%1$s', $categories_list ); ?>
			</span>
			<?php endif; // End if categories. ?>

			<?php
			/* Translators: used between list items, there is a space after the comma. */
			$tags_list = get_the_tag_list( '', esc_html__( ', ', 'ssl-alp' ) );
			
			if ( $tags_list ) :
			?>
			<span class="tags-links">
				<i class="fa fa-tags" aria-hidden="true"></i>
				<?php printf( '<span>&nbsp;%1$s</span>', $tags_list ); ?>
			</span>
			<?php endif; // End if $tags_list. ?>
		<?php endif; // End if 'post' == get_post_type(). ?>

		<?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
		<span class="comments-link">
			<i class="fa fa-comment" aria-hidden="true"></i>&nbsp;<?php comments_popup_link( esc_html__( 'Leave a comment', 'ssl-alp' ), esc_html__( '1 Comment', 'ssl-alp' ), esc_html__( '% Comments', 'ssl-alp' ) ); ?>
		</span>
		<?php endif; ?>

		<?php edit_post_link( esc_html__( 'Edit', 'ssl-alp' ), '<span class="edit-link pull-right"><i class="fa fa-edit" aria-hidden="true"></i>', '</span>' ); ?>
	</footer><!-- .entry-footer -->
	<?php endif; ?>
</article><!-- #post-## -->
