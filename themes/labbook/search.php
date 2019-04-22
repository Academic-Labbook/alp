<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Labbook
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php if ( defined( 'LABBOOK_PAGE_SHOW_ADVANCED_SEARCH_FORM' ) && LABBOOK_PAGE_SHOW_ADVANCED_SEARCH_FORM ) : ?>

			<header class="page-header">
				<h1 class="page-title"><?php esc_html_e( 'Advanced Search', 'labbook' ); ?></h1>
			</header><!-- .page-header -->

			<?php labbook_the_advanced_search_form(); ?>

		<?php
		else :

			if ( have_posts() ) :
			?>

				<header class="page-header">
					<h1 class="page-title">
						<?php
						/* translators: %s: search query. */
						printf( esc_html__( 'Search Results for "%s"', 'labbook' ), '<span>' . get_search_query() . '</span>' );
						?>
					</h1>
				</header><!-- .page-header -->

				<div class="page-content">
					<p><a href="#search-again"><?php esc_html_e( 'Skip to search form', 'labbook' ); ?></a></p>
				</div>

				<?php
				/* Start the Loop */
				while ( have_posts() ) :
					the_post();

					/**
					 * Run the loop for the search to output the results.
					 * If you want to overload this in a child theme then include a file
					 * called content-search.php and that will be used instead.
					 */
					get_template_part( 'template-parts/content', 'search' );

				endwhile;

				the_posts_navigation();

				?>

				<h2 id="search-again"><?php esc_html_e( 'Search again', 'labbook' ); ?></h2>

				<?php

				/* Show advanced search form. */
				labbook_the_advanced_search_form();

			else :

				get_template_part( 'template-parts/content', 'none' );

			endif;

		endif;
		?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_sidebar();
get_footer();
