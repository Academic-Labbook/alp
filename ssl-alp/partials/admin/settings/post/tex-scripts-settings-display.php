<fieldset>
    <legend class="screen-reader-text"><span><?php _e( 'Custom KaTeX URLs', 'ssl-alp' ); ?></span></legend>
    <label for="ssl_alp_tex_use_custom_urls_checkbox">
        <input name="ssl_alp_tex_use_custom_urls" type="checkbox" id="ssl_alp_tex_use_custom_urls_checkbox" value="1" <?php checked( get_site_option( 'ssl_alp_tex_use_custom_urls' ) ); ?> />
        <?php _e( 'Use custom KaTeX JavaScript and CSS script URLs', 'ssl-alp' ); ?>
        <p class="description"><?php _e( 'When enabled, the URLs specified below will be loaded on posts containing mathematics instead of the defaults.', 'ssl-alp' ); ?></p>
    </label>
    <br/>
    <label for="ssl_alp_katex_js_url_textbox">
        <?php _e( 'Custom KaTeX JavaScript library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_js_url" id="ssl_alp_katex_js_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_js_url', '' ) ); ?>" placeholder="<?php echo esc_url( SSL_ALP_DEFAULT_KATEX_JS_URL ); ?>" class="large-text code" type="url">
    <label for="ssl_alp_katex_copy_js_url_textbox">
        <?php _e( 'Custom KaTeX Copy JavaScript library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_copy_js_url" id="ssl_alp_katex_copy_js_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_copy_js_url', '' ) ); ?>" placeholder="<?php echo esc_url( SSL_ALP_DEFAULT_KATEX_COPY_JS_URL ); ?>" class="large-text code" type="url">
    <label for="ssl_alp_katex_css_url_textbox">
        <?php _e( 'Custom KaTeX CSS library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_css_url" id="ssl_alp_katex_css_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_css_url', '' ) ); ?>" placeholder="<?php echo esc_url( SSL_ALP_DEFAULT_KATEX_CSS_URL ); ?>" class="large-text code" type="url">
    <label for="ssl_alp_katex_copy_css_url_textbox">
        <?php _e( 'Custom KaTeX Copy CSS library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_copy_css_url" id="ssl_alp_katex_copy_css_url_textbox" value="<?php echo esc_url( get_site_option( 'ssl_alp_katex_copy_css_url', '' ) ); ?>" placeholder="<?php echo esc_url( SSL_ALP_DEFAULT_KATEX_COPY_CSS_URL ); ?>" class="large-text code" type="url">
</fieldset>
