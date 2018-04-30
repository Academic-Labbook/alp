<label for="ssl_alp_tex_enabled_checkbox">
    <input name="ssl_alp_tex_enabled" type="checkbox" id="ssl_alp_tex_enabled_checkbox" value="1" <?php checked( get_option( 'ssl_alp_tex_enabled' ) ); ?> />
    <?php _e( 'Enable mathematics rendering in posts', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'When enabled, inline <a href="https://en.wikibooks.org/wiki/LaTeX/Mathematics">TeX-formatted mathematics</a> can be added to posts by enclosing it in <code>[tex]...[/tex]</code> tags', 'ssl-alp' ); ?>.</p>
</label>