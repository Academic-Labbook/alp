/**
 * Set default image block link target.
 *
 * @package ssl-alp
 */

( function( wp ) {
    /**
     * Modify default image block link destination.
     *
     * @param {*} settings
     * @param {*} name
     */
    function modifyImageLinkDestinationDefault( settings, name ) {
        // Link target to override core default.
        var target = "media";

        if ( name == "core/image" ) {
            // Set image block default destination.
            settings.attributes.linkDestination.default = target;
        } else if ( name == "core/gallery" ) {
            // Set gallery block default destination.
            settings.attributes.linkTo.default = target;
        }

        return settings;
    }

    // Add settings filter to block registration.
    wp.hooks.addFilter(
        "blocks.registerBlockType",
        "ssl-alp/modify-image-link-destination-default",
        modifyImageLinkDestinationDefault
    );
} )(
	window.wp
);
