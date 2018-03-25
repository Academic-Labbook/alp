<p>
    <label for="ssl_alp_tex_enabled_checkbox">
        <input name="ssl_alp_tex_enabled" type="checkbox" id="ssl_alp_tex_enabled_checkbox" value="1" <?php checked( get_option( 'ssl_alp_tex_enabled' ) ); ?> />
        <?php _e( 'Enable mathematics rendering in posts using <a href="https://khan.github.io/KaTeX/">KaTeX</a>', 'ssl-alp' ); ?>
        <p class="description"><?php _e( 'When enabled, inline <a href="https://en.wikibooks.org/wiki/LaTeX/Mathematics">TeX-formatted mathematics</a> can be added to posts by enclosing it in <code>[tex]...[/tex]</code> tags', 'ssl-alp' ); ?>.</p>
    </label>
</p>
<br/>
<p>
    <label for="ssl_alp_katex_js_url_textbox">
        <?php _e( 'Load the KaTeX JavaScript library from the following URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_js_url" id="ssl_alp_katex_js_url_textbox" value="<?php echo esc_url( get_option( 'ssl_alp_katex_js_url' ) ); ?>" class="large-text code" type="url">
    <label for="ssl_alp_katex_css_url_textbox">
        <?php _e( 'Load the KaTeX CSS library from the following URL:', 'ssl-alp' ); ?>
    </label>
    <input name="ssl_alp_katex_css_url" id="ssl_alp_katex_css_url_textbox" value="<?php echo esc_url( get_option( 'ssl_alp_katex_css_url' ) ); ?>" class="large-text code" type="url">
</fieldset>
<p class="description"><?php _e(' For self-hosted scripts, these can be relative URLs.', 'ssl-alp' ); ?></p>
