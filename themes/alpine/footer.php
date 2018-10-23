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
		<div id="imprint-left">
			<?php if ( ! empty( ssl_alp_get_option( 'copyright_text' ) ) ) : ?>
			<span id="copyright"><?php echo wp_kses_post( ssl_alp_get_option( 'copyright_text' ) ); ?></span>
			<?php endif ?>
			<?php if ( ! empty( ssl_alp_get_option( 'copyright_text' ) ) && ! empty( get_privacy_policy_url() ) ) : ?>
			<span class="separator" role="separator" aria-hidden="true">|</span>
			<?php endif; ?>
			<?php if ( ! empty( get_privacy_policy_url() ) ): ?>
			<span id="privacy-policy">
				<?php the_privacy_policy_link(); ?>
			</span>
			<?php endif; ?>
		</div>
		<div id="imprint-right">
			<?php if ( true === ssl_alp_get_option( 'powered_by' ) ) : ?>
			<span id="powered-by">
				<a href="<?php echo esc_url( __( 'https://alp.attackllama.com/', 'ssl-alp' ) ); ?>"><?php printf( esc_html__( 'Powered by %s', 'ssl-alp' ), 'Academic Labbook Plugin for WordPress' ); ?></a>
			</span><!-- .site-info -->
			<?php endif ?>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
