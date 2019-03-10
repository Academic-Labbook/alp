(function( $ ) {
    'use strict';

    var $access_checkbox = $( '#ssl_alp_require_login_checkbox' );
    var $search_checkbox = $( '#ssl_alp_disallow_public_advanced_search_checkbox' );

    function toggle_search_checkbox( state ) {
        $search_checkbox.prop( 'disabled', state ? 'disabled' : '' );
    }

    // Initial disabled state.
    toggle_search_checkbox( $access_checkbox.is( ':checked' ) );

    // Register change callback.
    $access_checkbox.change(
        function() {
            toggle_search_checkbox( $(this).is( ':checked' ) );
        }
    );
})( jQuery );
