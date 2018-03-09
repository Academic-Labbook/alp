( function( $ ) {
	'use strict';

	$( document ).ready( function( $ ) {
		// remove list bullet points because we use icons instead
		$( '.widget' ).find( 'ul' ).addClass( 'list-unstyled' );

		// implement go-to-top button's behaviour
		if ( $( '#btn-scrollup' ).length > 0 ) {
			$( window ).scroll( function() {
				if ( $( this ).scrollTop() > 100 ) {
					$( '#btn-scrollup' ).fadeIn();
				} else {
					$( '#btn-scrollup' ).fadeOut();
				}
			});

			$( '#btn-scrollup' ).click( function() {
				$( 'html, body' ).animate( { scrollTop: 0 }, 600 );
				return false;
			});
		}
	});
} )( jQuery );
