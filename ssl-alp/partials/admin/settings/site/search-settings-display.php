<label for="ssl_alp_disallow_public_advanced_search_checkbox">
    <input type="checkbox" name="ssl_alp_disallow_public_advanced_search" id="ssl_alp_disallow_public_advanced_search_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disallow_public_advanced_search' ) ); ?> />
    <?php _e( 'Limit advanced searches to logged-in users', 'ssl-alp' ); ?>
    <p class="description"><?php _e( 'When enabled, users who are not logged-in will not be able to make computationally expensive advanced searches, such as those that match against multiple authors, categories and tags. They will still be able to make basic searches.', 'ssl-alp' ); ?></p>
</label>
