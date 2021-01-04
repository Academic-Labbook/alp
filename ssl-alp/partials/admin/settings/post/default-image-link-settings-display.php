<?php

// Possible values for the core 'image_default_link_type' setting.
$ssl_alp_default_link_types = array(
	'none'       => __( 'None', 'ssl-alp' ),
	'file'       => __( 'Media File', 'ssl-alp' ),
	'attachment' => __( 'Attachment Page', 'ssl-alp' ),
);

$ssl_alp_current_link_type = get_option( 'image_default_link_type' );

?>

<select name="image_default_link_type" id="image_default_link_type_dropdown">
	<?php foreach ( $ssl_alp_default_link_types as $value => $description ): ?>
	<option value="<?php esc_attr_e( $value ); ?>"<?php selected( $value, $ssl_alp_current_link_type ); ?>><?php esc_html_e( $description ); ?></option>
	<?php endforeach; ?>
</select>
