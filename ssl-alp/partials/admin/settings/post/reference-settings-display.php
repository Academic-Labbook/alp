<p>
    <label for="ssl_alp_enable_crossreferences_checkbox">
        <input type="checkbox" name="ssl_alp_enable_crossreferences" id="ssl_alp_enable_crossreferences_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_crossreferences' ) ); ?> />
        <?php _e( 'Enable cross-references', 'ssl-alp' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'When enabled, a box is displayed containing links to posts and pages referenced from the current post or page, or by another post or page.', 'ssl-alp' ); ?></p>
<p><?php _e( 'Enable shortcodes for:' ); ?></p>
<p>
    <label for="ssl_alp_doi_shortcode_checkbox">
        <input name="ssl_alp_doi_shortcode" type="checkbox" id="ssl_alp_doi_shortcode_checkbox" value="1" <?php checked( get_option( 'ssl_alp_doi_shortcode' ) ); ?> />
        <?php _e( 'Digital object identifiers (DOIs)' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'When enabled, persistent URLs to online information such as journal articles can be added to posts by enclosing their <a href="https://en.wikipedia.org/wiki/Digital_object_identifier">digital object identifiers (DOIs)</a> in <code>[doi]...[/doi]</code> tags', 'ssl-alp' ); ?>.</p>
<p>
    <label for="ssl_alp_arxiv_shortcode_checkbox">
        <input name="ssl_alp_arxiv_shortcode" type="checkbox" id="ssl_alp_arxiv_shortcode_checkbox" value="1" <?php checked( get_option( 'ssl_alp_doi_shortcode' ) ); ?> />
        <?php _e( 'ArXiv documents' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'When enabled, persistent URLs to arXiv documents can be added to posts by enclosing their identifiers in <code>[arxiv]...[/arxiv]</code> tags', 'ssl-alp' ); ?>.</p>
