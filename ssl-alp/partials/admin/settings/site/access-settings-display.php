<label for="ssl_alp_require_login_checkbox">
    <input type="checkbox" name="ssl_alp_require_login" id="ssl_alp_require_login_checkbox" value="1" <?php checked( get_option( 'ssl_alp_require_login' ) ); ?> />
    <?php _e( 'Require login to access site', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'Note that this will <strong>not</strong> prevent direct access to media (images, documents, videos, etc.) uploaded to the site for those knowing the URL. If desired, this behaviour should be configured using your HTTP server. You may wish to <a href="tools.php?page=ssl-alp-admin-tools">optimise private labbook settings</a> after enabling this.', 'ssl-alp' ); ?></p>
</label>
<br/>
<label for="ssl_alp_enable_applications_checkbox">
    <input type="checkbox" name="ssl_alp_enable_applications" id="ssl_alp_enable_applications_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_applications' ) ); ?> />
    <?php _e( 'Enable application passwords', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'Allow users to generate application passwords to allow external programs to access the site using their account.', 'ssl-alp' ); ?></p>
</label>
