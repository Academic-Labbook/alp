<?php

/**
 * Network media tests.
 *
 * @group ms-required
 */
class MediaTest extends WP_UnitTestCase {
    private function get_test_file( $extension, $data_len = 50 ) {
        $count = 0;

        // generate unique file with specified extension
        do {
            // generate unique file
            $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'alp-test-data' . $count . '.' . $extension;

            $count++;
        } while ( file_exists( $file ) );

        // generate random data $data_len bytes long
        $data = str_repeat( rand( 0, 9 ), $data_len );

        // create file
        file_put_contents( $file, $data );

        return $file;
    }

    /**
     * Check media type setting.
     */
    private function assert_media_type_set( $set_value, $get_value ) {
        global $ssl_alp;

        // Reset option first.
        delete_site_option( 'ssl_alp_additional_media_types' );
        // Update option.
        update_site_option( 'ssl_alp_additional_media_types', $set_value );
        $this->assertEquals( $ssl_alp->core->get_allowed_media_types(), $get_value );
    }

    /**
     * Auto-format internal representation of media types the way they are entered into the settings
     * box.
     */
    private function format_media_types( $media_types ) {
        $str = "";

        foreach ( $media_types as $type ) {
            if ( ! is_null( $type[ 'comment' ] ) ) {
                $comment = " // " . $type[ 'comment' ];
            } else {
                $comment = "";
            }

            $str .= sprintf(
                '%1$s %2$s%3$s',
                $type[ 'ext' ],
                $type[ 'type' ],
                $comment
            );

            $str .= "\n";
        }

        return $str;
    }

    /**
     * Test media type sanitisation.
     */
	public function test_media_types() {
        /**
         * Text type to check.
         */
        $txt_type = array(
            array(
                'extension'  => 'txt',
                'media_type' => 'text/plain',
            )
        );

        // Valid, no comments.
        $this->assert_media_type_set( 'txt text/plain', $txt_type );
        $this->assert_media_type_set( 'txt text/plain ', $txt_type );
        $this->assert_media_type_set( 'txt   text/plain  ', $txt_type );
        // Invalid.
        $this->assert_media_type_set( '  txt text/plain', array() ); // Preceding spaces.
        $this->assert_media_type_set( '  txt text/plain//comment', array() ); // Preceding spaces.
        $this->assert_media_type_set( 'txt text/plain//comment', array() ); // No space between media type and comment.

        // Valid, with comments.
        $txt_type[0]['comment'] = '// comment';
        $this->assert_media_type_set( 'txt text/plain // comment', $txt_type );
        $this->assert_media_type_set( 'txt text/plain // comment ', $txt_type ); // Strip trailing space.
        $txt_type[0]['comment'] = '//comment';
        $this->assert_media_type_set( 'txt   text/plain //comment', $txt_type );
        $this->assert_media_type_set( 'txt   text/plain   //comment', $txt_type );
        $this->assert_media_type_set( 'txt   text/plain   //comment ', $txt_type ); // Strip trailing space.

        /**
         * Multiple types to check.
         */
        $multi_types = array(
            array(
                'extension'  => 'txt',
                'media_type' => 'text/plain',
            ),
            array(
                'extension'  => 'm|py|c',
                'media_type' => 'text/plain',
            ),
            array(
                'extension'  => 'rbd',
                'media_type' => 'application/octet-stream',
            ),
        );

        // Valid, no comments.
        $this->assert_media_type_set(
'txt text/plain
m|py|c text/plain
rbd application/octet-stream',
            $multi_types
        );
        // Valid, with comments.
        $multi_types[0]['comment'] = '// text';
        $multi_types[1]['comment'] = '// matlab';
        $multi_types[2]['comment'] = '// made-up';
        $this->assert_media_type_set(
'txt text/plain // text
m|py|c text/plain // matlab
rbd application/octet-stream // made-up',
            $multi_types
        );
	}

    public function test_custom_media_type_uploads() {
        // Media types to test.
        // These get converted to "[ext] [type] // [comment]" and entered into the
        // `ssl_alp_additional_media_types` network setting.
        $media_types = array(
            array(
                'ext'       =>  'py',
                'type'      =>  'text/plain',
                'comment'   =>  'Python scripts'
            ),
            array(
                'ext'       =>  'm|mat|mlb', // test pipes
                'type'      =>  'application/x-matlab',
                'comment'   =>  'Matlab scripts'
            )
        );

        // By default, it shouldn't be possible to upload the above files.
        foreach ( $media_types as $type ) {
            $exts = explode( '|', $type[ 'ext' ] );

            // Attempt each extension.
            foreach ( $exts as $ext ) {
                // Create test file.
                $filename = $this->get_test_file( $ext );

                // Get test file data.
                $contents = file_get_contents( $filename );

                // Do the upload.
                $upload = wp_upload_bits( basename( $filename ), null, $contents );

                // There should be an error.
                $this->assertNotFalse( $upload['error'] );
            }
        }

        // Get media type string.
        $media_type_str = $this->format_media_types( $media_types );

        // Run through sanitization filters.
        $sanitized_media_types = sanitize_option( 'ssl_alp_additional_media_types', $media_type_str );

        // Set option.
        update_site_option( 'ssl_alp_additional_media_types', $sanitized_media_types );

        // It should now be possible to upload the above files.
        foreach ( $media_types as $type ) {
            $exts = explode( '|', $type[ 'ext' ] );

            // Attempt each extension.
            foreach ( $exts as $ext ) {
                // Create test file.
                $filename = $this->get_test_file( $ext );

                // Get test file data.
                $contents = file_get_contents( $filename );

                // Do the upload.
                $upload = wp_upload_bits( basename( $filename ), null, $contents );

                // There should not be an error.
                $this->assertFalse( $upload['error'] );

                // Remove the temporary file.
                unlink( $filename );
            }
        }
    }
}
