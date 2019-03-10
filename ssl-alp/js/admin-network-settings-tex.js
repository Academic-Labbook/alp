(function( $ ) {
    'use strict';

    var $custom_urls_checkbox = $( "#ssl_alp_katex_use_custom_urls_checkbox" );
    var $katex_js_textbox = $( "#ssl_alp_katex_js_url_textbox" );
    var $katex_copy_js_textbox = $( "#ssl_alp_katex_copy_js_url_textbox" );
    var $katex_css_textbox = $( "#ssl_alp_katex_css_url_textbox" );
    var $katex_copy_css_textbox = $( "#ssl_alp_katex_copy_css_url_textbox" );

    function toggle_textboxes( state ) {
        var readonly = state ? '' : 'readonly';

        $katex_js_textbox.prop( "readonly", readonly );
        $katex_copy_js_textbox.prop( "readonly", readonly );
        $katex_css_textbox.prop( "readonly", readonly );
        $katex_copy_css_textbox.prop( "readonly", readonly );
    }

    // Initial disabled state.
    toggle_textboxes( $custom_urls_checkbox.is( ':checked' ) );

    // Register change callback.
    $custom_urls_checkbox.change(
        function() {
            toggle_textboxes( $(this).is( ':checked' ) );
        }
    );
})( jQuery );
