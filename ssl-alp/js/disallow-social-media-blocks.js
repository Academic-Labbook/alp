/**
 * Disallow social media blocks.
 *
 * @package ssl-alp
 */

( function( wp ) {
    wp.domReady( function() {
        wp.blocks.unregisterBlockType( 'core/embed' );
        wp.blocks.unregisterBlockType( 'core/social-links' );
    } );
} )(
	window.wp
);
