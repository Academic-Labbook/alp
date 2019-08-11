<?php

/**
 * User applications.
 */
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Applications', 'ssl-alp' ); ?></h2>
	<p><?php esc_html_e( 'Passwords can be generated here for external applications to allow them to have access to your account without having to providing your actual password. Application passwords can be used to access feeds and the REST API but cannot be used to log in to the normal site. They can be easily revoked using the table below.', 'ssl-alp' ); ?></p>
	<div id="col-container" class="wp-clearfix">
		<div id="col-left">
			<div class="form-wrap">
				<h2><?php esc_html_e( 'Add New Application', 'ssl-alp' ); ?></h2>
				<form id="addapplicationpassword" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<input type="hidden" name="action" value="ssl-alp-add-application" />
					<?php wp_nonce_field( 'ssl-alp-add-application', 'ssl_alp_add_application_nonce' ); ?>
					<div class="form-field">
						<label for="application-name"><?php esc_html_e( 'Name', 'ssl-alp' ); ?></label>
						<input name="application_name" id="application-name" type="text" value="" aria-required="true" />
						<p><?php esc_html_e( 'A password will be generated automatically and shown in the table opposite.', 'ssl-alp' ); ?></p>
						<?php submit_button( __( 'Add New Application', 'ssl-alp' ) ); ?>
					</div>
				</form>
			</div>
		</div>
		<div id="col-right">
			<div class="col-wrap">
				<form id="ssl-alp-applications-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<?php wp_nonce_field( 'ssl-alp-manage-applications', 'ssl_alp_manage_applications_nonce' ); ?>
					<div id="ssl-alp-applications-list-table">
						<?php $this->applications_list_table->display(); ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
