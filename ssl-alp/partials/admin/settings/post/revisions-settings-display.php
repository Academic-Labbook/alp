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
    <p class="description"><?php _e( 'When enabled, a textbox is displayed on post/page edit forms allowing users to write a message summarising their edit.', 'ssl-alp' ); ?></p>
</fieldset>
<br/>
<label for="ssl_alp_edit_summary_max_length_textbox">
    <?php _e( 'Allow up to', 'ssl-alp' ); ?>
    <input name="ssl_alp_edit_summary_max_length" type="number" step="1" min="3" id="ssl_alp_edit_summary_max_length_textbox" value="<?php form_option( 'ssl_alp_edit_summary_max_length' ); ?>" class="small-text" /> <?php _e( 'characters in edit summaries', 'ssl-alp' ); ?>
</label>