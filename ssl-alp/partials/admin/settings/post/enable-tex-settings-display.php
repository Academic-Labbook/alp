<p>
    <label for="ssl_alp_tex_enabled_checkbox">
        <input name="ssl_alp_tex_enabled" type="checkbox" id="ssl_alp_tex_enabled_checkbox" value="1" <?php checked( get_option( 'ssl_alp_tex_enabled' ) ); ?> />
        <?php _e( 'Enable mathematics rendering in posts using <a href="https://khan.github.io/KaTeX/">KaTeX</a>', 'ssl-alp' ); ?>
        <p class="description"><?php _e( 'When enabled, inline <a href="https://en.wikibooks.org/wiki/LaTeX/Mathematics">TeX-formatted mathematics</a> can be added to posts by enclosing it in <code>[tex]...[/tex]</code> tags', 'ssl-alp' ); ?>.</p>
    </label>
</p>
<p>
    <label for="ssl_alp_tex_custom_urls_checkbox">
        <input name="ssl_alp_tex_custom_urls" type="checkbox" id="ssl_alp_tex_custom_urls_checkbox" value="1" <?php checked( get_option( 'ssl_alp_tex_custom_urls' ) ); ?> />
        <?php _e( 'Use custom KaTeX JavaScript and CSS script URLs', 'ssl-alp' ); ?>
        <p class="description"><?php _e( 'When enabled, the URLs specified below will be loaded instead of the defaults.', 'ssl-alp' ); ?></p>
    </label>
</p>
<p>
    <label for="ssl_alp_katex_js_url_textbox">
        <?php _e( 'Custom KaTeX JavaScript library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_js_url" id="ssl_alp_katex_js_url_textbox" value="<?php echo esc_url( get_option( 'ssl_alp_katex_js_url' ) ); ?>" class="large-text code" type="url">
</p>
<p class="description"><?php printf( __( 'Default: <code>%1$s</code>', 'ssl-alp' ), SSL_ALP_DEFAULT_KATEX_JS_URL ); ?></p>
<p>
    <label for="ssl_alp_katex_css_url_textbox">
        <?php _e( 'Custom KaTeX CSS library URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_css_url" id="ssl_alp_katex_css_url_textbox" value="<?php echo esc_url( get_option( 'ssl_alp_katex_css_url' ) ); ?>" class="large-text code" type="url">
    <p class="description"><?php printf( __( 'Default: <code>%1$s</code>', 'ssl-alp' ), SSL_ALP_DEFAULT_KATEX_CSS_URL ); ?></p>
</p>