<label for="ssl_alp_allow_multiple_authors_checkbox">
    <input type="checkbox" name="ssl_alp_allow_multiple_authors" id="ssl_alp_allow_multiple_authors_checkbox" value="1" <?php checked( get_option( 'ssl_alp_allow_multiple_authors' ) ); ?> />
    <?php _e('Allow multiple authors to be specified for posts', 'ssl-alp'); ?>
</label>
<p class="description"><?php _e( 'When enabled, posts may have multiple users assigned as authors. Posts appear on all of their corresponding authors\' archive pages, and contribute to their post counts.', 'ssl-alp' ); ?></p>
