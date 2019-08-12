<label for="ssl_alp_enable_tex_checkbox">
	<input name="ssl_alp_enable_tex" type="checkbox" id="ssl_alp_enable_tex_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_tex' ) ); ?> />
	<?php esc_html_e( 'Enable mathematics markup for posts and pages', 'ssl-alp' ); ?>
	<p class="description">
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: WikiBooks LaTeX URL */
			__( 'When enabled, a block is added to the editor that allows users to write <a href="%s">TeX-formatted mathematics</a>.', 'ssl-alp' ),
			'https://en.wikibooks.org/wiki/LaTeX/Mathematics'
		)
	);
	?>
	</p>
</label>
