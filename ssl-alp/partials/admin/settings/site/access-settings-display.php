<label for="ssl_alp_require_login_checkbox">
	<input type="checkbox" name="ssl_alp_require_login" id="ssl_alp_require_login_checkbox" value="1" <?php checked( get_option( 'ssl_alp_require_login' ) ); ?> />
	<?php esc_html_e( 'Require login to access site', 'ssl-alp' ); ?>
	<p class="description">
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: ALP tools page URL */
			__( 'Note that this will <strong>not</strong> prevent direct access to media (images, documents, videos, etc.) uploaded to the site for those knowing the URL. If desired, this behaviour should be configured using your HTTP server. You may wish to <a href="%s">optimise private labbook settings</a> after enabling this.', 'ssl-alp' ),
			'tools.php?page=ssl-alp-admin-tools'
		)
	);
	?>
	</p>
</label>
<br/>
<label for="ssl_alp_allow_application_password_feed_access_checkbox">
	<input type="checkbox" name="ssl_alp_allow_application_password_feed_access" id="ssl_alp_allow_application_password_feed_access_checkbox" value="1" <?php checked( get_option( 'ssl_alp_allow_application_password_feed_access' ) ); ?> />
	<?php esc_html_e( 'Enable feed access using application passwords', 'ssl-alp' ); ?>
	<p class="description">
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: WordPress documentation URL */
			__(
				'Normally application passwords only allow access to the <a href="%s">REST API</a>. Enabling this option will allow users to access the RSS feeds using application passwords specified using HTTP basic authentication, allowing them to use such passwords with external feed reader programs and services even if the "Require login to access site" setting is enabled above.',
				'ssl-alp'
			),
			'https://developer.wordpress.org/rest-api/'
		)
	);
	?>
	</p>
</label>
