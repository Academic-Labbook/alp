<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package ssl-alp
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'ssl-alp' ); ?></a>

	<?php do_action( 'ssl_alp_action_before_header' ); ?>

	<header id="masthead" class="site-header container" role="banner">
		<div id="site-branding">
			<?php if ( has_custom_logo() ) : ?>
			<div id="site-logo">
				<?php the_custom_logo(); ?>
			</div>
			<?php endif; ?>
			<div id="site-title-group">
  				<h1 class="site-title text-center">
					  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</h1>
				<p class="site-description text-center">
					<?php bloginfo( 'description' ); ?>
				</p>
			</div>
		</div>

		<nav id="site-navigation" class="main-navigation" role="navigation">
			<button class="menu-toggle" aria-hidden="true"><?php esc_html_e( 'Primary Menu', 'ssl-alp' ); ?></button>
			<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'fallback_cb'    => 'ssl_alp_primary_menu_fallback',
				) );
			?>
		</nav><!-- #site-navigation -->

	</header><!-- #masthead -->

	<?php do_action( 'ssl_alp_action_after_header' ); ?>

	<div id="content" class="site-content container">
		<div class="row">
