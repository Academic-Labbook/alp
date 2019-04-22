<label for="ssl_alp_require_login_checkbox">
    <input type="checkbox" name="ssl_alp_require_login" id="ssl_alp_require_login_checkbox" value="1" <?php checked( get_option( 'ssl_alp_require_login' ) ); ?> />
    <?php _e( 'Require login to access site', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'Note that this will <strong>not</strong> prevent direct access to media (images, documents, videos, etc.) uploaded to the site for those knowing the URL. If desired, this behaviour should be configured using your HTTP server. You may wish to <a href="tools.php?page=ssl-alp-admin-tools">optimise private labbook settings</a> after enabling this.', 'ssl-alp' ); ?></p>
</label>
<br/>
<label for="ssl_alp_enable_applications_checkbox">
    <input type="checkbox" name="ssl_alp_enable_applications" id="ssl_alp_enable_applications_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_applications' ) ); ?> />
    <?php _e( 'Enable application passwords', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'Allow users to generate application passwords to allow external programs to access the site using their account without using their main password. This can be used by users to allow feed readers to access the site\'s feeds when the "Require login to access site" setting is on. It also allows external programs to access the <a href="https://developer.wordpress.org/rest-api/">REST API</a>. This setting does not change user permissions in any way, and application passwords cannot be used to log in to the site.', 'ssl-alp' ); ?></p>
</label>
