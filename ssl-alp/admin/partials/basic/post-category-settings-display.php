<p>
    <label for="ssl_alp_require_post_category_checkbox">
        <input type="checkbox" name="ssl_alp_require_post_category" id="ssl_alp_require_post_category_checkbox" value="1" <?php checked(true, get_option('ssl_alp_require_post_category')); ?> />
        <?php _e('Require that new posts specify a category other than "' . __('Uncategorized') . '" when saving a draft or publishing', 'ssl-alp'); ?>
    </label>
</p>

<p>
    <label for="ssl_alp_disable_post_tags_checkbox">
        <input type="checkbox" name="ssl_alp_disable_post_tags" id="ssl_alp_disable_post_tags_checkbox" value="1" <?php checked(true, get_option('ssl_alp_disable_post_tags')); ?> />
        <?php _e('Disable tags for posts, instead relying only on categories', 'ssl-alp'); ?>
    </label>
</p>
