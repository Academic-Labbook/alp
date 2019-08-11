<?php

/**
 * Admin tools.
 */
?>

<?php if ( $override_core_settings_completed ) : ?>
<div class="notice notice-success is-dismissible">
	<p><?php esc_html_e( 'Core settings changed.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $role_conversion_completed ) : ?>
<div class="notice notice-success is-dismissible">
	<p><?php esc_html_e( 'User roles converted.', 'ssl-alp' ); ?></p>
</div>
<?php elseif ( $role_conversion_unconfirmed ) : ?>
<div class="notice notice-error is-dismissible">
	<p><?php esc_html_e( 'Please click the confirmation checkbox below.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $rebuild_references_completed ) : ?>
<div class="notice notice-success is-dismissible">
	<p><?php esc_html_e( 'References rebuilt.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $rebuild_coauthors_completed ) : ?>
<div class="notice notice-success is-dismissible">
	<p><?php esc_html_e( 'Coauthor terms rebuilt.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<div class="wrap">
	<h2><?php esc_html_e( 'Academic Labbook Tools', 'ssl-alp' ); ?></h2>
	<p class="description">
	<?php
	printf(
		/* translators: ALP version */
		esc_html__(
			'You are running Academic Labbook Plugin %s.',
			'ssl-alp'
		),
		SSL_ALP_VERSION
	);
	?>
	</p>
	<div class="ssl-alp-tools-cards">
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Activate theme', 'ssl-alp' ); ?></h2>
			<p><?php echo wp_kses_post( __( 'It is highly recommended to use the <em>Labbook</em> theme on this site. This theme, or a child theme derived from it, must be enabled in order for most of Academic Labbook Plugin\'s functionality to appear.', 'ssl-alp' ) ); ?></p>
			<?php if ( $supported_theme_active ) : ?>
			<p class="description"><?php echo wp_kses_post( __( '<em>Labbook</em>, or a child theme derived from it, is already active.', 'ssl-alp' ) ); ?></p>
			<?php elseif ( $supported_theme_installed ) : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: WordPress themes settings URL */
						__(
							'<em>Labbook</em>, or a child theme derived from it, is not active. Visit the <a href="%s">themes page</a> to activate it. On network sites, you may have to network activate the theme first.',
							'ssl-alp'
						),
						'themes.php'
					)
				);
				?>
				</p>
			<?php else : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: ALP theme documentation URL */
						__( '<em>Labbook</em> is not installed. Visit the <a href="%s">ALP website</a> to download it.', 'ssl-alp' ),
						'https://alp.attackllama.com/documentation/themes/'
					)
				);
				?>
			</p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Enable pretty permalinks', 'ssl-alp' ); ?></h2>
			<p><?php esc_html_e( 'It is recommended to enable pretty permalinks to allow cross-references to be made between different post types. WordPress is only capable of extracting links to standard posts and pages from post text when plain permalinks are used, and not links to custom post types added by Academic Labbook Plugin such as inventory pages. With pretty permalinks enabled, WordPress can detect and therefore display cross-references between all types of post.', 'ssl-alp' ); ?></p>
			<?php if ( $pretty_permalinks_enabled ) : ?>
			<p class="description"><?php esc_html_e( 'Pretty permalinks are enabled.', 'ssl-alp' ); ?></p>
			<?php else : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: WordPress permalinks settings URL */
						__( 'Pretty permalinks are disabled. Visit <a href="%s">this settings page</a> to enable them.', 'ssl-alp' ),
						'options-permalink.php'
					)
				);
				?>
			</p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Optimise core WordPress settings for private labbook', 'ssl-alp' ); ?></h2>
			<p><?php esc_html_e( 'This tool allows you to change core WordPress settings to make them appropriate for a private academic labbook. The presumption behind these setting changes is that you control access to the labbook and trust the users whom you grant access to.', 'ssl-alp' ); ?></p>
			<table class="widefat fixed striped ssl-alp-builtin-settings" cellspacing="0">
				<thead>
					<tr>
						<th class="column-setting"><?php esc_html_e( 'Setting to update', 'ssl-alp' ); ?></th>
						<th class="column-control"><?php esc_html_e( 'New value', 'ssl-alp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Attempt to notify any blogs linked to from the article', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'No', 'ssl-alp' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Allow link notifications from other blogs (pingbacks and trackbacks) on new articles', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php esc_html_e( 'No', 'ssl-alp' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Users must be registered and logged in to comment', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This prevents anonymous comments if the site is made publicly visible in the future.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php esc_html_e( 'Yes', 'ssl-alp' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Comment author must have a previously approved comment', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This is not necessary for private sites with trusted users.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php esc_html_e( 'No', 'ssl-alp' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Hold a comment in the moderation queue if it contains more than the following number of links', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This is not necessary for private sites with trusted users. Setting it to 0 disables this check.', 'ssl-alp' ); ?></p>
						</th>
						<td>0</td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'Discourage search engines from indexing this site', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php esc_html_e( 'Yes', 'ssl-alp' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php esc_html_e( 'For each article in a feed, show full text', 'ssl-alp' ); ?></strong>
							<p class="description"><?php esc_html_e( 'This allows your users to read full article texts via syndication feed aggregator clients and services.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php esc_html_e( 'Yes', 'ssl-alp' ); ?></td>
					</tr>
				</tbody>
			</table>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_manage_core_settings_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Change Settings', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $require_login || $core_settings_overridden ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-manage-core-settings', 'ssl_alp_manage_core_settings_nonce' ); ?>
			</form>
			<?php if ( ! $require_login ) : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: ALP site options page URL */
						__( 'The <a href="%s">Require login to access site</a> setting is not enabled. Please enable it first before running this tool.', 'ssl-alp' ),
						'options-general.php?page=ssl-alp-site-options'
					)
				);
				?>
			</p>
			<?php elseif ( $core_settings_overridden ) : ?>
			<p class="description"><?php esc_html_e( 'Core settings are already set to the above values.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Convert user roles', 'ssl-alp' ); ?></h2>
			<p><?php esc_html_e( 'This tool will convert the default WordPress user roles into roles more suitable for an academic labbook.', 'ssl-alp' ); ?></p>
			<ul>
				<li><?php echo wp_kses_post( __( 'The <strong>Administrator</strong> role is unchanged from the WordPress default. Users with this role can edit, delete and change the role of other users.', 'ssl-alp' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Editor</strong> role is changed to <strong>Researcher</strong>. This is intended for research group members. Users with this role can, among other capabilities, create, edit and delete their own and others\' posts, create, edit and delete pages, and manage comments, categories and uploaded media. This role is the default for new users.', 'ssl-alp' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Author</strong> role is changed to <strong>Intern</strong>. This is intended for temporary group members. Users with this role are able to create, edit and delete their own posts, but not those of other users. They cannot manage categories or others\' comments.', 'ssl-alp' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Contributer</strong> role is removed. Any existing users with this role are changed to <strong>Subscriber</strong>.', 'ssl-alp' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Subscriber</strong> role is unchanged from the WordPress default. This can be used to provide read-only access to a user. Subscribers can still comment on posts.', 'ssl-alp' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Exluded</strong> role is added, with no permissions to perform any actions on the site, including to read it. This is intended for users who are no longer to be given access to the site. On private sites, this avoids the need to delete a user\'s account in order to remove their access, which would also delete their contributions.', 'ssl-alp' ) ); ?></li>
			</ul>
			<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: WordPress Codex URL */
					__( 'This action <strong>deletes</strong> the default WordPress roles from the database, meaning that these roles will remain even if the Academic Labbook Plugin is disabled or uninstalled in the future. For more information on roles, please see <a href="%s">Roles and Capabilities</a> in the WordPress Codex.', 'ssl-alp' ),
					'https://codex.wordpress.org/Roles_and_Capabilities'
				)
			);
			?>
			</p>
			<p><strong><?php esc_html_e( 'This action cannot be undone.', 'ssl-alp' ); ?></strong></p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_convert_role_submitted" value="1"/>
				<input type="checkbox" id="ssl_alp_convert_role_confirm_checkbox" name="ssl_alp_convert_role_confirm" value="1"<?php if ( ! $roles_convertable ) : ?> disabled<?php endif; ?>/>
				<label for="ssl_alp_convert_role_confirm_checkbox"><?php esc_html_e( 'I have read and understood the above information', 'ssl-alp' ); ?></label>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Convert User Roles', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $roles_convertable ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-convert-user-roles', 'ssl_alp_convert_user_roles_nonce' ); ?>
			</form>
			<?php if ( $roles_converted ) : ?>
			<p class="description"><?php esc_html_e( 'User roles have already been converted.', 'ssl-alp' ); ?></p>
			<?php elseif ( ! $roles_convertable ) : ?>
			<p class="description"><?php esc_html_e( 'User roles are not currently set to WordPress defaults, and so cannot be converted.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Rebuild cross-references', 'ssl-alp' ); ?></h2>
			<p><?php esc_html_e( 'This tool will rebuild the cross-references related to each published post and page. This is useful for extracting cross-references from posts or pages created or edited during any time in which the cross-references feature was disabled, and from posts or pages created before the plugin was installed or activated.', 'ssl-alp' ); ?></p>
			<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: ALP cross-reference documentation URL */
					__( 'Note: this tool may take a long time to execute on large sites. Due to server configuration settings, the execution may time out. You may instead wish to <a href="%s">run this tool via WP-CLI</a>.', 'ssl-alp' ),
					'https://alp.attackllama.com/documentation/rebuilding-cross-references/'
				)
			);
			?>
			</p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_rebuild_references_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Rebuild Cross-References', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $references_enabled ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-rebuild-references', 'ssl_alp_rebuild_references_nonce' ); ?>
			</form>
			<?php if ( ! $references_enabled ) : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: ALP settings page URL */
						__( 'Cross-references are disabled. To enable them, go to <a href="%s">this settings page</a>.', 'ssl-alp' ),
						'options-general.php?page=ssl-alp-site-options'
					)
				);
				?>
				</p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php esc_html_e( 'Rebuild coauthor terms', 'ssl-alp' ); ?></h2>
			<p><?php esc_html_e( 'This tool will rebuild the coauthor terms used to show post coauthors and to allow the setting of coauthors for posts. This tool is intended to be run on sites which had users and posts before the Academic Labbook Plugin was installed, allowing these users to be chosen as coauthors on posts, and to show them as authors of their existing posts.', 'ssl-alp' ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_rebuild_coauthors_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Rebuild Coauthor Terms', 'ssl-alp' ); ?>" type="submit" <?php if ( ! $coauthors_enabled ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-rebuild-coauthors', 'ssl_alp_rebuild_coauthors_nonce' ); ?>
			</form>
			<?php if ( ! $coauthors_enabled ) : ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: ALP settings page URL */
						__( 'Coauthors are disabled. To enable them, go to <a href="%s">this settings page</a>.', 'ssl-alp' ),
						'options-general.php?page=ssl-alp-site-options'
					)
				);
				?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>
