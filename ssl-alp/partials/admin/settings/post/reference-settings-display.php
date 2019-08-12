<label for="ssl_alp_enable_crossreferences_checkbox">
	<input type="checkbox" name="ssl_alp_enable_crossreferences" id="ssl_alp_enable_crossreferences_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_crossreferences' ) ); ?> />
	<?php esc_html_e( 'Enable cross-references', 'ssl-alp' ); ?>
</label>
<p class="description">
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: ALP tools page URL */
			__( 'When enabled, links between posts and pages are tracked and made available to themes should they support displaying them. You may wish to <a href="%s">rebuild cross-references</a> after enabling this.', 'ssl-alp' ),
			'tools.php?page=ssl-alp-admin-tools'
		)
	);
	?>
</p>
