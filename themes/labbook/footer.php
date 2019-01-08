<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Labbook
 */

?>

	<footer id="colophon" class="site-footer">
		<div id="imprint-left">
			<?php if ( ! empty( labbook_get_option( 'copyright_text' ) ) ) : ?>
			<span id="copyright"><?php echo wp_kses_post( labbook_get_option( 'copyright_text' ) ); ?></span>
			<?php endif; ?>
			<?php
			$show_privacy_policy = labbook_get_option( 'show_privacy_policy' ) && ! empty( get_privacy_policy_url() );

			if ( ! empty( labbook_get_option( 'copyright_text' ) ) && $show_privacy_policy ) : ?>
			<span class="separator" role="separator" aria-hidden="true">|</span>
			<?php endif; ?>
			<?php if ( $show_privacy_policy ) : ?>
			<span id="privacy-policy">
				<?php the_privacy_policy_link(); ?>
			</span>
			<?php endif; ?>
		</div>
		<div id="imprint-right">
			<?php if ( labbook_get_option( 'show_powered_by' ) ) : ?>
			<span id="powered-by">
				<?php _e( 'Powered by <a href="https://alp.attackllama.com/">Academic Labbook Plugin for WordPress</a>.', 'labbook' ); ?>
			</span><!-- .powered-by -->
			<span class="beta-notice">
				<?php _e( 'This is a beta release. Please report bugs <a href="https://alp.attackllama.com/bugs/">here</a>.', 'labbook' ); ?>
			</span>
			<?php endif; ?>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
