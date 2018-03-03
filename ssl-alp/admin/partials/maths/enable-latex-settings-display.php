<p>
    <label for="ssl_alp_latex_enabled_checkbox">
        <input name="ssl_alp_latex_enabled" type="checkbox" id="ssl_alp_latex_enabled_checkbox" value="1" <?php checked( get_option( 'ssl_alp_latex_enabled' ) ); ?> />
        <?php _e( 'Enable mathematics rendering in posts using MathJax', 'ssl-alp' ); ?>
        <p class="description"><?php _e( 'When enabled, inline <a href="https://en.wikibooks.org/wiki/LaTeX/Mathematics">LaTeX-formatted mathematics</a> can be added to posts by enclosing it in <code>[latex]...[/latex]</code> tags', 'ssl-alp' ); ?>.</p>
    </label>
</p>
