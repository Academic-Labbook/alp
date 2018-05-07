<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package ssl-alp
 */

?>
    </div> <!-- .row -->
	</div><!-- #content -->

	<footer id="colophon" class="site-footer container" role="contentinfo">
		<?php
		$footer_nav = wp_nav_menu( array(
			'theme_location'  => 'footer',
			'depth'           => 1,
			'container'       => 'div',
			'container_class' => 'footer-nav-wrapper',
			'menu_class'      => 'footer-nav',
			'fallback_cb'     => '',
			'link_after'      => '',
			'echo'            => false,
			)
		);
		?>
		<?php if ( ! empty( $footer_nav ) ) : ?>
		<nav class="social-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Footer Menu', 'ssl-alp' ); ?>">
			<?php echo $footer_nav; ?>
		</nav>
		<?php endif ?>
		<?php if ( ! empty( ssl_alp_get_option( 'copyright_text' ) ) ) : ?>
		<div id="copyright-wrap">
			<div class="copyright-text"><?php echo wp_kses_post( ssl_alp_get_option( 'copyright_text' ) ); ?></div>
		</div>
		<?php endif ?>
		<?php if ( true === ssl_alp_get_option( 'powered_by' ) ) : ?>
		<div class="site-info" id="powered-by-wrap">
			<a href="<?php echo esc_url( __( 'https://alp.attackllama.com/', 'ssl-alp' ) ); ?>"><?php printf( esc_html__( 'Powered by %s', 'ssl-alp' ), 'Academic Labbook Plugin for WordPress' ); ?></a>
		</div><!-- .site-info -->
		<?php endif ?>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
