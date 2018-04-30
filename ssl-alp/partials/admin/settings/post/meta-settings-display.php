<fieldset>
    <legend class="screen-reader-text"><span><?php _e( 'Hide edit controls', 'ssl-alp' ); ?></span></legend>
    <?php _e( 'Hide the following controls from the post edit screen:', 'ssl-alp' ); ?>
    <br/>
    <label for="ssl_alp_disable_post_excerpts_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_excerpts" id="ssl_alp_disable_post_excerpts_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_excerpts' ) ); ?> />
        <?php _e( 'Excerpts', 'ssl-alp' ); ?>
    </label>
    <br/>
    <label for="ssl_alp_disable_post_trackbacks_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_trackbacks" id="ssl_alp_disable_post_trackbacks_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_trackbacks' ) ); ?> />
        <?php _e( 'Trackbacks', 'ssl-alp' ); ?>
    </label>
</fieldset>