(function( $ ) {
    'use strict';

    var $access_checkbox = $( '#ssl_alp_require_login_checkbox' );
    var $feed_checkbox = $( '#ssl_alp_allow_application_password_feed_access_checkbox' );
    var $search_checkbox = $( '#ssl_alp_disallow_public_advanced_search_checkbox' );

    function toggle_checkboxes( state ) {
        $feed_checkbox.prop( 'disabled', state ? '' : 'disabled' );
        $search_checkbox.prop( 'disabled', state ? 'disabled' : '' );
    }

    // Initial disabled state.
    toggle_checkboxes( $access_checkbox.is( ':checked' ) );

    // Register change callback.
    $access_checkbox.change(
        function() {
            toggle_checkboxes( $(this).is( ':checked' ) );
        }
    );
})( jQuery );
