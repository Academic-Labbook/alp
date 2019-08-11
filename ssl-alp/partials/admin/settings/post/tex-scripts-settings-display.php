<fieldset>
	<legend class="screen-reader-text"><span><?php esc_html_e( 'Custom KaTeX URLs', 'ssl-alp' ); ?></span></legend>
	<label for="ssl_alp_katex_use_custom_urls_checkbox">
		<input name="ssl_alp_katex_use_custom_urls" type="checkbox" id="ssl_alp_katex_use_custom_urls_checkbox" value="1" <?php checked( get_site_option( 'ssl_alp_katex_use_custom_urls' ) ); ?> />
		<?php esc_html_e( 'Use custom KaTeX JavaScript and CSS script URLs', 'ssl-alp' ); ?>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: KaTeX homepage URL, 2: KaTeX extensions URL  */
					__( 'When enabled, the <a href="%1$s">KaTeX</a> JavaScript and CSS scripts used to render mathematical markup on posts and pages will be loaded using the URLs specified below instead of the defaults. The <a href="%2$s">Copy-tex extension</a> provides the ability to copy TeX source code from rendered mathematics.', 'ssl-alp' ),
					'https://katex.org/',
					'https://katex.org/docs/libs.html#extensions'
				)
			);
			?>
			</p>
	</label>
	<br/>
	<label for="ssl_alp_katex_js_url_textbox">
		<?php esc_html_e( 'KaTeX JavaScript library URL:', 'ssl-alp' ); ?>
	</label>
	<input name="ssl_alp_katex_copy_js_url" id="ssl_alp_katex_copy_js_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_copy_js_url', '' ) ); ?>" class="large-text code" type="url">
	<label for="ssl_alp_katex_css_url_textbox">
		<?php esc_html_e( 'KaTeX CSS library URL:', 'ssl-alp' ); ?>
	</label>
	<input name="ssl_alp_katex_js_url" id="ssl_alp_katex_js_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_js_url', '' ) ); ?>" class="large-text code" type="url">
	<label for="ssl_alp_katex_copy_js_url_textbox">
		<?php esc_html_e( 'KaTeX Copy-tex extension JavaScript library URL:', 'ssl-alp' ); ?>
	</label>
	<input name="ssl_alp_katex_css_url" id="ssl_alp_katex_css_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_css_url', '' ) ); ?>" class="large-text code" type="url">
	<label for="ssl_alp_katex_copy_css_url_textbox">
		<?php esc_html_e( 'KaTeX Copy-tex extension CSS library URL:', 'ssl-alp' ); ?>
	</label>
	<input name="ssl_alp_katex_copy_css_url" id="ssl_alp_katex_copy_css_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_copy_css_url', '' ) ); ?>" class="large-text code" type="url">
</fieldset>
