<?php
/**
 * The template part for displaying results in search pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package ssl-alp
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

		<?php if ( 'post' == get_post_type() ) : ?>
		<div class="entry-meta">
			<?php ssl_alp_post_meta(); ?>
		</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->

	<footer class="entry-footer">
		<?php if ( 'post' == get_post_type() ) : // Hide category and tag text for pages on Search. ?>
			<?php
				/* Translators: used between list items, there is a space after the comma. */
				$categories_list = get_the_category_list( __( ', ', 'ssl-alp' ) );
				if ( $categories_list && ssl_alp_categorized_blog() ) :
			?>
			<span class="cat-links">
				<i class="fa fa-folder-open" aria-hidden="true"></i>
				<?php printf( '%1$s', $categories_list ); ?>
			</span>
			<?php endif; // End if categories. ?>

			<?php
				/* Translators: used between list items, there is a space after the comma. */
				$tags_list = get_the_tag_list( '', __( ', ', 'ssl-alp' ) );
				if ( $tags_list ) :
			?>
			<span class="tags-links">
				<i class="fa fa-tags" aria-hidden="true"></i>
				<?php printf( '<span>&nbsp;%1$s', $tags_list ); ?>
			</span>
			<?php endif; // End if $tags_list. ?>
		<?php endif; // End if 'post' == get_post_type(). ?>

		<?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
		<span class="comments-link"><i class="fa fa-comment" aria-hidden="true"></i>&nbsp;<?php comments_popup_link( esc_html__( 'Leave a comment', 'ssl-alp' ), esc_html__( '1 Comment', 'ssl-alp' ), esc_html__( '% Comments', 'ssl-alp' ) ); ?></span>
		<?php endif; ?>

		<?php edit_post_link( esc_html__( 'Edit', 'ssl-alp' ), '<span class="edit-link pull-right"><i class="fa fa-edit" aria-hidden="true"></i>', '</span>' ); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
