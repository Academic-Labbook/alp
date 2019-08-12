<label for="ssl_alp_enable_inventory_checkbox">
	<input type="checkbox" name="ssl_alp_enable_inventory" id="ssl_alp_enable_inventory_checkbox" value="1" <?php checked( get_option( 'ssl_alp_enable_inventory' ) ); ?> />
	<?php esc_html_e( 'Enable inventory management', 'ssl-alp' ); ?>
	<p class="description"><?php esc_html_e( 'Allow users to define and manage inventory items, and tag posts with them.', 'ssl-alp' ); ?></p>
</label>
