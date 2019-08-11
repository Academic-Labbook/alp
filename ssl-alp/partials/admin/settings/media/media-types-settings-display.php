<label for="ssl_alp_additional_media_types_textarea">
    <?php echo wp_kses_post( sprintf( __( 'Allow the following media types to be uploaded using the media manager in addition to the <a href="%s">WordPress defaults</a>:', 'ssl-alp' ), 'https://codex.wordpress.org/Function_Reference/get_allowed_mime_types#Default_allowed_mime_types' ) ); ?>
</label>
<?php
$ssl_alp_media_types = get_site_option( 'ssl_alp_additional_media_types' );
$ssl_alp_media_lines = array();

if ( is_array( $ssl_alp_media_types ) ) {
    // convert existing additional media types to text and display
    foreach ( $ssl_alp_media_types as $media_type ) {
        $comment = "";

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
<p class="description"><?php echo wp_kses_post(
    __( 'Specify one pair of file extension(s) and associated media type per line, in the form <code>extension(s) type-name/subtype-name // optional comment</code>, e.g. <code>jpg image/jpeg // JPEG images</code>. Multiple file extensions can be grouped under the same media type with <code>|</code>, e.g. <code>jpg|jpeg|jpe image/jpeg</code>. For a list of commonly required additional media types in the required format, see <a href="https://alp.attackllama.com/documentation/media-types/">here</a>.', 'ssl-alp' ) ); ?></p>
