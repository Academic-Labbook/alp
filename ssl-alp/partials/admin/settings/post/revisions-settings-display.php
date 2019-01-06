<label for="ssl_alp_enable_edit_summaries_checkbox">
    <input type="checkbox" name="ssl_alp_enable_edit_summaries" id="ssl_alp_enable_edit_summaries_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_edit_summaries' ) ); ?> />
    <?php _e( 'Enable edit summaries', 'ssl-alp' ); ?>
</label>
<p class="description"><?php _e( 'When enabled, a textbox is displayed when editing posts or pages allowing users to leave a message summarising their changes. These messages are shown next to posts on the revision screen, and they are available to themes should they support displaying them.', 'ssl-alp' ); ?></p>
