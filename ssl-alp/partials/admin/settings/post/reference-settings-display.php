<label for="ssl_alp_enable_post_crossreferences_checkbox">
    <input type="checkbox" name="ssl_alp_enable_post_crossreferences" id="ssl_alp_enable_post_crossreferences_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_post_crossreferences' ) ); ?> />
    <?php _e( 'Enable cross-references', 'ssl-alp' ); ?>
</label>
<p class="description"><?php _e( 'When enabled, a box is displayed containing links to posts and pages referenced from the current post or page, or by another post or page. You may wish to <a href="tools.php?page=ssl-alp-admin-tools">rebuild cross-references</a> after enabling this.', 'ssl-alp' ); ?></p>
