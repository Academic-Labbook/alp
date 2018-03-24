(function( $ ) {
	'use strict';

    var dropdown = document.getElementById( ssl_alp_dropdown_id );
    
	function onCatChange() {
		if ( dropdown.options[ dropdown.selectedIndex ].value > 0 ) {
            dropdown.parentNode.submit();
        }
    }
    
    dropdown.onchange = onCatChange;
    
})( jQuery );
