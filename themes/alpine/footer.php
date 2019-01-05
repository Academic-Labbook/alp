<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Alpine
 */

?>

	<footer id="colophon" class="site-footer">
		<div id="imprint-left">
			<?php if ( ! empty( alpine_get_option( 'copyright_text' ) ) ) : ?>
			<span id="copyright"><?php echo wp_kses_post( alpine_get_option( 'copyright_text' ) ); ?></span>
			<?php endif; ?>
			<?php
			$show_privacy_policy = alpine_get_option( 'show_privacy_policy' ) && ! empty( get_privacy_policy_url() );

			if ( ! empty( alpine_get_option( 'copyright_text' ) ) && $show_privacy_policy ) : ?>
			<span class="separator" role="separator" aria-hidden="true">|</span>
			<?php endif; ?>
			<?php if ( $show_privacy_policy ) : ?>
			<span id="privacy-policy">
				<?php the_privacy_policy_link(); ?>
			</span>
			<?php endif; ?>
		</div>
		<div id="imprint-right">
			<?php if ( true === alpine_get_option( 'show_powered_by' ) ) : ?>
			<span id="powered-by">
				<a href="<?php echo esc_url( __( 'https://alp.attackllama.com/', 'alpine' ) ); ?>"><?php printf( esc_html__( 'Powered by %s', 'alpine' ), 'Academic Labbook Plugin for WordPress' ); ?></a>
			</span><!-- .site-info -->
			<?php endif; ?>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
