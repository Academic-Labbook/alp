<label for="ssl_alp_disable_post_trackbacks_checkbox">
	<input type="checkbox" name="ssl_alp_disable_post_trackbacks" id="ssl_alp_disable_post_trackbacks_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_trackbacks' ) ); ?> />
	<?php esc_html_e( 'Hide trackback controls on the post edit screen', 'ssl-alp' ); ?>
</label>
<p class="description"><?php esc_html_e( 'When enabled, post trackbacks are disabled and the control is hidden from the post edit screen. This feature is typically of little use to private sites.', 'ssl-alp' ); ?></p>
