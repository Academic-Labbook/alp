<p>
    <label for="ssl_alp_disable_post_tags_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_tags" id="ssl_alp_disable_post_tags_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_tags' ) ); ?> />
        <?php _e( 'Disable tags', 'ssl-alp' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'Tags are very similar to categories, but do not support hierarchies and are mainly useful for search engine optimisation on commercial sites.', 'ssl-alp' ); ?></p>
<p>
    <label for="ssl_alp_disable_post_excerpts_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_excerpts" id="ssl_alp_disable_post_excerpts_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_excerpts' ) ); ?> />
        <?php _e( 'Disable excerpts', 'ssl-alp' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'These are used to specify an excerpt of the post contents, with a link to the full content.', 'ssl-alp' ); ?></p>
<p>
    <label for="ssl_alp_disable_post_trackbacks_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_trackbacks" id="ssl_alp_disable_post_trackbacks_checkbox" value="1" <?php checked( get_option( 'ssl_alp_disable_post_trackbacks' ) ); ?> />
        <?php _e( 'Disable trackbacks', 'ssl-alp' ); ?>
    </label>
</p>
<p class="description"><?php _e( 'These are used to inform other blogs when new posts have been published on this site. Note that this setting only hides the ability to control trackbacks for a post. To switch off the feature entirely, see <a href="options-discussion.php">discussion settings</a>.', 'ssl-alp' ); ?></p>
