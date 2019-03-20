<label for="ssl_alp_enable_abbreviations_checkbox">
    <input type="checkbox" name="ssl_alp_enable_abbreviations" id="ssl_alp_enable_abbreviations_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_abbreviations' ) ); ?> />
    <?php _e( 'Enable abbreviations', 'ssl-alp' ); ?>
</label>
<p class="description"><?php _e( 'When enabled, definitions of abbreviations are shown as hover text on posts and pages. Abbreviations and their definitions can be defined using the corresponding section within the admin area.', 'ssl-alp' ); ?></p>
