<label for="ssl_alp_enable_crossreferences_checkbox">
    <input type="checkbox" name="ssl_alp_enable_crossreferences" id="ssl_alp_enable_crossreferences_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_crossreferences' ) ); ?> />
    <?php _e( 'Enable cross-references', 'ssl-alp' ); ?>
</label>
<p class="description"><?php _e( 'When enabled, a box is displayed containing links to posts and pages referenced from the current post or page, or by another post or page. You may wish to <a href="tools.php?page=ssl-alp-admin-tools">rebuild cross-references</a> after enabling this.', 'ssl-alp' ); ?></p>
<br/>
<fieldset>
    <legend class="screen-reader-text"><span><?php _e( 'Enable shortcodes', 'ssl-alp' ); ?></span></legend>
    <?php _e( 'Enable shortcodes for:', 'ssl-alp' ); ?>
    <br/>
    <label for="ssl_alp_enable_doi_shortcode_checkbox">
        <input name="ssl_alp_enable_doi_shortcode" type="checkbox" id="ssl_alp_enable_doi_shortcode_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_doi_shortcode' ) ); ?> />
        <?php _e( 'Digital object identifiers (DOIs)' ); ?>
    </label>
    <br/>
    <label for="ssl_alp_enable_arxiv_shortcode_checkbox">
        <input name="ssl_alp_enable_arxiv_shortcode" type="checkbox" id="ssl_alp_enable_arxiv_shortcode_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_doi_shortcode' ) ); ?> />
        <?php _e( 'ArXiv documents' ); ?>
    </label>
    <p class="description"><?php _e( 'When enabled, persistent URLs to journal or arXiv documents can be added to posts by enclosing their identifiers in <code>[doi id="..."]...[/doi]</code> or <code>[arxiv id="..."]...[/arxiv]</code> tags', 'ssl-alp' ); ?>.</p>
</fieldset>