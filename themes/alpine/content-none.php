<?php
/**
 * The template part for displaying a message that posts cannot be found.
 *
 * @package ssl-alp
 */

?>

<section class="no-results not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Nothing Found', 'ssl-alp' ); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content">
		<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
		<p><?php printf( esc_html__( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'ssl-alp' ), esc_url( admin_url( 'post-new.php' ) ) ); ?></p>
		<?php elseif ( is_search() ) : ?>
		<p><?php esc_html_e( 'Nothing matched your search terms. Please try again with some different keywords.', 'ssl-alp' ); ?></p>
		<?php get_search_form(); ?>
		<?php elseif ( is_author() ) : ?>
		<p><?php esc_html_e( 'This author has no posts.', 'ssl-alp' ); ?></p>
		<?php else : ?>
		<p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'ssl-alp' ); ?></p>
		<?php get_search_form(); ?>
		<?php endif; ?>
	</div><!-- .page-content -->
</section><!-- .no-results -->
