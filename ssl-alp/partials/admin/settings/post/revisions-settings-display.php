<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable edit summaries', 'ssl-alp' ); ?></span></legend>
    <?php _e( 'Enable edit summaries for:' , 'ssl-alp' ); ?>
    <br/>
    <label for="ssl_alp_enable_post_edit_summaries_checkbox">
        <input name="ssl_alp_enable_post_edit_summaries" type="checkbox" id="ssl_alp_enable_post_edit_summaries_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_post_edit_summaries' ) ); ?> />
        <?php _e( 'Posts' ); ?>
    </label>
    <br/>
    <label for="ssl_alp_enable_page_edit_summaries_checkbox">
        <input name="ssl_alp_enable_page_edit_summaries" type="checkbox" id="ssl_alp_enable_page_edit_summaries_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_page_edit_summaries' ) ); ?> />
        <?php _e( 'Pages' ); ?>
    </label>
    <p class="description"><?php _e( 'When enabled, a textbox is displayed on post/page edit screens allowing users to write a message summarising their changes. These messages are shown next to posts on the revision screen, and they are available to themes should they support displaying them.', 'ssl-alp' ); ?></p>
</fieldset>
