(function( $ ) {
	'use strict';

    var $dropdown = document.getElementById( ssl_alp_dropdown_id );

	function onCatChange() {
        let url = $dropdown.options[ $dropdown.selectedIndex ].value;

		if ( url ) {
            window.location.href = url;
        }
    }

    $dropdown.onchange = onCatChange;
})( jQuery );
