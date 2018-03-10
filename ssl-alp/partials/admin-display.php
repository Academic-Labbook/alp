<?php

/**
 * Main admin settings.
 */
?>
<div class="wrap">
	<h2><?php _e('Academic Labbook Settings', 'ssl-alp'); ?></h2>
	<div>
		 <form method="post" action="options.php">
			 <?php settings_fields('ssl-alp-admin-options'); ?>
			 <?php do_settings_sections('ssl-alp-admin-options'); ?>
		     <?php submit_button('Save Changes'); ?>
		 </form>
	</div>
</div>
