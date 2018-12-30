<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Alpine
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'ssl-alpine' ); ?></a>

	<header id="masthead" class="site-header">
		<?php if ( has_nav_menu( 'network-menu' ) ): ?>
		<nav id="network-navigation" class="main-navigation">
			<button class="menu-toggle" aria-controls="network-menu" aria-expanded="false"><?php esc_html_e( 'Network Menu', 'ssl-alpine' ); ?></button>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'network-menu',
				'menu_id'        => 'network-menu',
			) );
			?>
		</nav><!-- #network-navigation -->
		<?php endif; ?>
		<div class="site-branding">
			<div class="custom-logo-container">
				<?php the_custom_logo(); ?>
			</div>
			<div class="site-title-container">
			<?php if ( is_front_page() && is_home() ) : ?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<?php else : ?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
			<?php endif;
			$alpine_description = get_bloginfo( 'description', 'display' );
			if ( $alpine_description || is_customize_preview() ) :
				?>
				<p class="site-description"><?php echo $alpine_description; /* WPCS: xss ok. */ ?></p>
			<?php endif; ?>
			</div>
		</div><!-- .site-branding -->

		<nav id="site-navigation" class="main-navigation">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Primary Menu', 'ssl-alpine' ); ?></button>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'site-menu',
				'menu_id'        => 'primary-menu',
			) );
			?>
		</nav><!-- #site-navigation -->
	</header><!-- #masthead -->
