<p>
    <label for="ssl_alp_doi_shortcode_checkbox">
        <input name="ssl_alp_doi_shortcode" type="checkbox" id="ssl_alp_doi_shortcode_checkbox" value="1" <?php checked( get_option( 'ssl_alp_doi_shortcode' ) ); ?> />
        <?php _e( 'Enable shortcode for digital object identifiers (DOIs)' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'When enabled, persistent URLs to journal articles can be added to posts by enclosing its <a href="https://en.wikipedia.org/wiki/Digital_object_identifier">digital object identifier (DOI)</a> in <code>[doi]...[/doi]</code> tags', 'ssl-alp' ); ?>.</p>
