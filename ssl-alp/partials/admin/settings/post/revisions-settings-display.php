<label for="ssl_alp_enable_edit_summaries_checkbox">
    <input type="checkbox" name="ssl_alp_enable_edit_summaries" id="ssl_alp_enable_edit_summaries_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_edit_summaries' ) ); ?> />
    <?php esc_html_e( 'Enable edit summaries', 'ssl-alp' ); ?>
</label>
<p class="description"><?php esc_html_e( 'When enabled, a textbox is displayed when editing posts or pages allowing users to leave a message summarising their changes. These messages are shown next to posts on the revision screen, and they are available to themes should they support displaying them.', 'ssl-alp' ); ?></p>
<br/>
<label for="ssl_alp_flag_unread_posts_checkbox">
    <input type="checkbox" name="ssl_alp_flag_unread_posts" id="ssl_alp_flag_unread_posts_checkbox" value="1" <?php checked( get_option( 'ssl_alp_flag_unread_posts' ) ); ?> />
    <?php esc_html_e( 'Flag unread posts for logged-in users', 'ssl-alp' ); ?>
</label>
<p class="description"><?php esc_html_e( 'When enabled, unread posts are marked for logged in users, and themes may use this information to change the way the post is displayed. When an edit is made to a post, the unread status is reset for each user.', 'ssl-alp' ); ?></p>
