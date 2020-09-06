/**
 * Disallow social media blocks.
 *
 * @package ssl-alp
 */

( function( wp ) {
    wp.domReady( function() {
        const blockTypes = wp.blocks.getBlockTypes();

        blockTypes.forEach( ( block ) => {
            // Remove embeds (except HTML embed) and social links (including the container widget).
            if ( block.name.startsWith( 'core-embed' ) || block.name.startsWith( 'core/social-link' ) ) {
                wp.blocks.unregisterBlockType( block.name );
            }
        } );
    } );
} )(
	window.wp
);
