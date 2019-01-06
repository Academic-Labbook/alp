(function( $ ) {
    'use strict';

    var $checkbox = $( "#ssl_alp_tex_use_custom_urls_checkbox" );
    var $katex_js_textbox = $( "#ssl_alp_katex_js_url_textbox" );
    var $katex_copy_js_textbox = $( "#ssl_alp_katex_copy_js_url_textbox" );
    var $katex_css_textbox = $( "#ssl_alp_katex_css_url_textbox" );
    var $katex_copy_css_textbox = $( "#ssl_alp_katex_copy_css_url_textbox" );

    function toggle_textboxes( state ) {
        // readonly state
        var readonly = state ? '' : 'readonly';

        $katex_js_textbox.prop( "readonly", readonly );
        $katex_copy_js_textbox.prop( "readonly", readonly );
        $katex_css_textbox.prop( "readonly", readonly );
        $katex_copy_css_textbox.prop( "readonly", readonly );
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
