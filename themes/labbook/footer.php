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
		<div class="imprint-left">
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
		<div class="imprint-right">
			<?php if ( labbook_get_option( 'show_powered_by' ) ) : ?>
			<span id="powered-by">
				<a href="<?php echo esc_url( 'https://alp.attackllama.com/', 'labbook' ); ?>">
					<?php
					esc_html_e( 'Powered by Academic Labbook Plugin for WordPress', 'labbook' );
					?>
				</a>
			</span><!-- .powered-by -->
			<span class="beta-notice">
				<?php
				printf(
					wp_kses(
						/* translators: 1: link to Academic Labbook Plugin bug information page */
						__( 'This is a beta release. Please report bugs <a href="%1$s">here</a>.', 'labbook' ),
						array(
							'a'	=> array(
								'href'	=> array(),
							),
						)
					),
					esc_url( 'https://alp.attackllama.com/bugs/' )
				);
				?>
			</span>
			<?php endif; ?>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
