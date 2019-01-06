(function( $ ) {
    'use strict';

    var $checkbox = $( "#ssl_alp_tex_use_custom_urls_checkbox" );
    var $js_textbox = $( "#ssl_alp_katex_js_url_textbox" );
    var $css_textbox = $( "#ssl_alp_katex_css_url_textbox" );

    function toggle_textboxes( state ) {
        // readonly state
        var readonly = state ? '' : 'readonly';

        $js_textbox.prop( "readonly", readonly );
        $css_textbox.prop( "readonly", readonly );
    }

    // initial disabled state
    toggle_textboxes( $checkbox.is( ':checked' ) );

    // register change callback
    $checkbox.change(
        function() {
            toggle_textboxes( $(this).is( ':checked' ) );
        }
    );
})( jQuery );
