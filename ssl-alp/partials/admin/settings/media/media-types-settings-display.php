<p>
<?php
echo wp_kses_post(
	__( 'These settings let you specify extra file types allowed in addition to the WordPress defaults. <strong>Consider the security implications before allowing a file type to be uploaded</strong>, in particular for executable file types. Since Academic Labbook Plugin does not perform <em>any</em> sanitisation on uploaded files, you may wish to avoid adding potentially dangerous file types (e.g. SVGs) to the textarea below and instead use separate plugins which properly sanitise them.', 'ssl-alp' )
);
?>
</p>
<p>
<?php
echo wp_kses_post(
	__( 'For more information, and a list of common additional media types in the required format, see <a href="https://alp.attackllama.com/documentation/for-administrators/media-types/">here</a>.', 'ssl-alp' )
);
?>
</p>
<br/>
<label for="ssl_alp_additional_media_types_textarea">
	<?php echo wp_kses_post(
		sprintf(
			/* translators: WordPress Codex URL */
			__( 'Allow the following media types to be uploaded using the media manager in addition to the <a href="%s">WordPress defaults</a>:', 'ssl-alp' ),
			'https://codex.wordpress.org/Function_Reference/get_allowed_mime_types#Default_allowed_mime_types'
		)
	);
?>
</label>
<?php
$ssl_alp_media_types = get_site_option( 'ssl_alp_additional_media_types' );
$ssl_alp_media_lines = array();

if ( is_array( $ssl_alp_media_types ) ) {
	// convert existing additional media types to text and display
	foreach ( $ssl_alp_media_types as $media_type ) {
		$comment = '';

		if ( array_key_exists( 'comment', $media_type ) && ! empty( $media_type['comment'] ) ) {
			// add comment
			$comment = $media_type['comment'];
		}

		$ssl_alp_media_lines[] = sprintf(
			'%s%s%s',
			esc_textarea( $media_type['extension'] ),
			esc_textarea( $media_type['media_type'] ),
			esc_textarea( $comment )
		);
	}
}
?>
<textarea name="ssl_alp_additional_media_types" rows="10" cols="50" id="ssl_alp_additional_media_types_textarea" class="large-text code"><?php echo implode( "\n", $ssl_alp_media_lines ); ?></textarea>
<p class="description">
<?php
echo wp_kses_post(
	__( 'Specify one pair of file extension(s) and associated media type per line, in the form <code>extension(s) type-name/subtype-name // optional comment</code>, e.g. <code>jpg image/jpeg // JPEG images</code>. Multiple file extensions can be grouped under the same media type with <code>|</code>, e.g. <code>jpg|jpeg|jpe image/jpeg</code>.', 'ssl-alp' )
);
?>
</p>
<br/>
<label for="ssl_alp_override_media_types_checkbox">
	<input type="checkbox" name="ssl_alp_override_media_types" id="ssl_alp_override_media_types_checkbox" value="1" <?php checked( get_site_option( 'ssl_alp_override_media_types' ) ); ?> />
	<?php esc_html_e( 'Override detected upload media types with those specified above', 'ssl-alp' ); ?>
	<p class="description">
	<?php
	echo wp_kses_post(
		__( 'If an uploaded file\'s media type as reported by the server disagrees with what is written in the allowed media types textarea above, WordPress will still disallow the upload to go ahead. When this setting is enabled, the detected media type for the file extensions specified above will be replaced by the corresponding media type specified above, allowing these files to be uploaded. <strong>This effectively bypasses WordPress\'s sanity checks for these file types, and can therefore have security implications.</strong>', 'ssl-alp' )
	);
	?>
	</p>
</label>
