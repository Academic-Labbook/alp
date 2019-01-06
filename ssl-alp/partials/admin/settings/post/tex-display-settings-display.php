<label for="ssl_alp_enable_tex_checkbox">
    <input name="ssl_alp_enable_tex" type="checkbox" id="ssl_alp_enable_tex_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_tex' ) ); ?> />
    <?php _e( 'Enable mathematics markup for posts and pages', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'When enabled, a block is added to the editor that allows users to write <a href="https://en.wikibooks.org/wiki/LaTeX/Mathematics">TeX-formatted mathematics</a>.', 'ssl-alp' ); ?></p>
</label>
