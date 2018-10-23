<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package ssl-alp
 */

get_header(); ?>

	<div id="primary" class="content-area col-sm-3">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found text-center">
				<header class="page-header">
					<h2 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'ssl-alp' ); ?></h2>
				</header><!-- .page-header -->

				<div class="page-content">
					<p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'ssl-alp' ); ?></p>
					<?php get_search_form(); ?>

				</div><!-- .page-content -->
			</section><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
