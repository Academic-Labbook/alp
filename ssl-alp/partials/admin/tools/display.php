<?php

/**
 * Admin tools.
 */
?>

<?php if ( $override_core_settings_completed ): ?>
<div class="notice notice-success is-dismissible">
	<p><?php _e( 'Core settings changed.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $role_conversion_completed ): ?>
<div class="notice notice-success is-dismissible">
	<p><?php _e( 'User roles converted.', 'ssl-alp' ); ?></p>
</div>
<?php elseif ( $role_conversion_unconfirmed ): ?>
<div class="notice notice-error is-dismissible">
	<p><?php _e( 'Please click the confirmation checkbox below.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $rebuild_references_completed ): ?>
<div class="notice notice-success is-dismissible">
	<p><?php _e( 'References rebuilt.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<?php if ( $rebuild_coauthors_completed ): ?>
<div class="notice notice-success is-dismissible">
	<p><?php _e( 'Coauthor terms rebuilt.', 'ssl-alp' ); ?></p>
</div>
<?php endif; ?>
<div class="wrap">
	<h2><?php _e('Academic Labbook Tools', 'ssl-alp'); ?></h2>
	<div class="ssl-alp-tools-cards">
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php _e( 'Activate theme', 'ssl-alp' ); ?></h2>
			<p><?php _e( 'The Academic Labbook Plugin is bundled with a theme, <em>Alpine</em>. This theme, or a child theme derived from it, must be active in order for most of the plugin\'s functionality to appear.', 'ssl-alp' ); ?></p>
			<?php if ( $alpine_active ) : ?>
			<p class="description"><?php _e( 'Alpine, or a child theme derived from it, is already active.', 'ssl-alp' ); ?></p>
			<?php else : ?>
			<p class="description"><?php _e( 'Alpine, or a child theme derived from it, is not active. Visit the <a href="themes.php">themes page</a> to activate it.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php _e( 'Optimise core WordPress settings for private labbook', 'ssl-alp' ); ?></h2>
			<p><?php _e( 'This tool allows you to change core WordPress settings to make them appropriate for a private academic labbook. The presumption behind these setting changes is that you control access to the labbook and trust the users whom you grant access to.', 'ssl-alp' ); ?></p>
			<table class="widefat fixed striped ssl-alp-builtin-settings" cellspacing="0">
				<thead>
					<tr>
						<th class="column-setting"><?php _e( 'Setting to update', 'ssl-alp' ); ?></th>
						<th class="column-control"><?php _e( 'New value', 'ssl-alp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>
							<strong><?php _e( 'Attempt to notify any blogs linked to from the article' ); ?></strong>
							<p class="description"><?php _e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'No' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'Allow link notifications from other blogs (pingbacks and trackbacks) on new articles' ); ?></strong>
							<p class="description"><?php _e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'No' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'Users must be registered and logged in to comment' ); ?></strong>
							<p class="description"><?php _e( 'This prevents anonymous comments if the site is made publicly visible in the future.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'Yes' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'Comment author must have a previously approved comment' ); ?></strong>
							<p class="description"><?php _e( 'This is not necessary for private sites with trusted users.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'No' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'Hold a comment in the moderation queue if it contains more than the following number of links', 'ssl-alp' ); ?></strong>
							<p class="description"><?php _e( 'This is not necessary for private sites with trusted users. Setting it to 0 disables this check.', 'ssl-alp' ); ?></p>
						</th>
						<td>0</td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'Discourage search engines from indexing this site' ); ?></strong>
							<p class="description"><?php _e( 'This is not necessary for private sites.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'Yes' ); ?></td>
					</tr>
					<tr>
						<th>
							<strong><?php _e( 'For each article in a feed, show full text', 'ssl-alp' ); ?></strong>
							<p class="description"><?php _e( 'This allows your users to read full article texts via syndication feed aggregator clients and services.', 'ssl-alp' ); ?></p>
						</th>
						<td><?php _e( 'Yes' ); ?></td>
					</tr>
				</tbody>
			</table>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_manage_core_settings_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Change Settings', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $require_login || $core_settings_overridden ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-manage-core-settings', 'ssl_alp_manage_core_settings_nonce' ); ?>
			</form>
			<?php if ( $core_settings_overridden ) : ?>
			<p class="description"><?php _e( 'Core settings are already set to the above values.', 'ssl-alp' ); ?></p>
			<?php elseif ( ! $require_login ) : ?>
			<p class="description"><?php _e( sprintf( 'The <a href="options-general.php?page=ssl-alp-admin-options">%1$s</a> setting is not enabled. Please enable it first before running this tool.', __( ' Require login to access site', 'ssl-alp' ) ), 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php _e( 'Convert user roles', 'ssl-alp' ); ?></h2>
			<p><?php _e( 'This tool will convert the default WordPress user roles into roles more suitable for an academic labbook.', 'ssl-alp' ); ?></p>
			<ul>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is unchanged from the WordPress default. Users with this role can edit, delete and change the role of other users.', 'ssl-alp' ), __( 'Administrator' ) ); ?></li>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is changed to <strong>%2$s</strong>. This is intended for research group members. Users with this role can, among other capabilities, create, edit and delete their own and others\' posts, create, edit and delete pages, and manage comments, categories and uploaded media. This role is the default for new users.', 'ssl-alp' ), __( 'Editor' ), __( 'Researcher', 'ssl-alp' ) ); ?></li>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is changed to <strong>%2$s</strong>. This is intended for temporary group members. Users with this role are able to create, edit and delete their own posts, but not those of other users. They cannot manage categories or others\' comments.', 'ssl-alp' ), __( 'Author' ), __( 'Intern', 'ssl-alp' ) ); ?></li>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is removed. Any existing users with this role are changed to <strong>%2$s</strong>.', 'ssl-alp' ), __( 'Contributer' ), __( 'Subscriber' ) ); ?></li>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is unchanged from the WordPress default. This can be used to provide read-only access to a user. %2$s can still comment on posts.', 'ssl-alp' ), __( 'Subscriber' ), __( 'Subscribers' ) ); ?></li>
				<li><?php printf( __( 'The <strong>%1$s</strong> role is added, with no permissions to perform any actions on the site, including to read it. This is intended for users who are no longer to be given access to the site. On private sites, this avoids the need to delete a user\'s account in order to remove their access, which would also delete their contributions.', 'ssl-alp' ), __( 'Excluded', 'ssl-alp' ) ); ?></li>
			</ul>
			<p><?php _e( 'This action <strong>deletes</strong> the default WordPress roles from the database, meaning that these roles will remain even if the Academic Labbook Plugin is disabled or uninstalled in the future. For more information on roles, please see <a href="https://codex.wordpress.org/Roles_and_Capabilities">Roles and Capabilities</a> in the WordPress Codex.', 'ssl-alp' ); ?></p>
			<p><strong><?php _e( 'This action cannot be undone.', 'ssl-alp' ); ?></strong></p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_convert_role_submitted" value="1"/>
				<input type="checkbox" id="ssl_alp_convert_role_confirm_checkbox" name="ssl_alp_convert_role_confirm" value="1"<?php if ( ! $roles_convertable ) : ?> disabled<?php endif; ?>/>
				<label for="ssl_alp_convert_role_confirm_checkbox"><?php _e( 'I have read and understood the above information', 'ssl-alp' ); ?></label>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Convert User Roles', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $roles_convertable ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-convert-user-roles', 'ssl_alp_convert_user_roles_nonce' ); ?>
			</form>
			<?php if ( $roles_converted ) : ?>
			<p class="description"><?php _e( 'User roles have already been converted.', 'ssl-alp' ); ?></p>
			<?php else : ?>
			<p class="description"><?php _e( 'User roles are not currently set to WordPress defaults, and so cannot be converted.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php _e( 'Rebuild cross-references', 'ssl-alp' ); ?></h2>
			<p><?php _e( 'This tool will rebuild the cross-references related to each published post and page. This is useful for extracting cross-references from posts or pages created or edited during any time in which the cross-references feature was disabled, and from posts or pages created before the plugin was installed or activated.', 'ssl-alp' ); ?></p>
			<p class="description"><?php echo sprintf( __( 'Note: this tool may take a long time to execute on large sites. Due to server configuration settings, the execution may time out. You may instead wish to run this tool via <a href="%s">WP-CLI</a>.', 'ssl-alp' ), esc_url( "https://wp-cli.org/" ) ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_rebuild_references_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Rebuild Cross-References', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $references_enabled ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-rebuild-references', 'ssl_alp_rebuild_references_nonce' ); ?>
			</form>
			<?php if ( ! $references_enabled ) : ?>
			<p class="description"><?php _e( 'Cross-references are disabled. To enable them, go to <a href="options-general.php?page=ssl-alp-admin-options">this settings page</a>.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="ssl-alp-tools-card">
			<h2 class="title"><?php _e( 'Rebuild coauthor terms', 'ssl-alp' ); ?></h2>
			<p><?php _e( 'This tool will rebuild the coauthor terms used to allow the setting of multiple authors for posts. This tool is intended to be run on sites which had users before the Academic Labbook Plugin was installed, allowing these users to be chosen as coauthors on posts.', 'ssl-alp' ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="ssl_alp_rebuild_coauthors_submitted" value="1"/>
				<p class="submit">
					<input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Rebuild Coauthor Terms', 'ssl-alp' ); ?>" type="submit"<?php if ( ! $coauthors_enabled ) : ?> disabled<?php endif; ?>/>
				</p>
				<?php wp_nonce_field( 'ssl-alp-rebuild-coauthors', 'ssl_alp_rebuild_coauthors_nonce' ); ?>
			</form>
			<?php if ( ! $coauthors_enabled ) : ?>
			<p class="description"><?php _e( 'Coauthors are disabled. To enable them, go to <a href="options-general.php?page=ssl-alp-admin-options">this settings page</a>.', 'ssl-alp' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
