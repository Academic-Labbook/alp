<?php

/**
 * Media tests
 * 
 * @group ms-required
 */
class MediaTest extends WP_UnitTestCase {
    public function setUp() {		
        parent::setUp();
    }

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

    public function test_media_types() {
        // media types to test
        // these get converted to "[ext] [type] // [comment]" and entered into
        // the `ssl_alp_additional_media_types` network setting
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

        // by default, it shouldn't be possible to upload the above files
        foreach ( $media_types as $type ) {
            $exts = explode( '|', $type[ 'ext' ] );

            // attempt each extension
            foreach ( $exts as $ext ) {
                // create test file
                $filename = $this->get_test_file( $ext );

                // get test file data
                $contents = file_get_contents( $filename );

                // do the upload
                $upload = wp_upload_bits( basename( $filename ), null, $contents );

                // there should be an error
                $this->assertNotEmpty( $upload['error'] );
            }
        }

        // get media type string
        $media_type_str = $this->format_media_types( $media_types );

        // run through sanitization filters
        $sanitized_media_types = sanitize_option( 'ssl_alp_additional_media_types', $media_type_str );

        // set option
        update_site_option( 'ssl_alp_additional_media_types', $sanitized_media_types );

        // it should now be possible to upload the above files
        foreach ( $media_types as $type ) {
            $exts = explode( '|', $type[ 'ext' ] );

            // attempt each extension
            foreach ( $exts as $ext ) {
                // create test file
                $filename = $this->get_test_file( $ext );

                // get test file data
                $contents = file_get_contents( $filename );

                // do the upload
                $upload = wp_upload_bits( basename( $filename ), null, $contents );

                // there should be an error
                $this->assertFalse( $upload['error'] );

                // remove the temporary file
                unlink( $filename );
            }
        }
    }
}