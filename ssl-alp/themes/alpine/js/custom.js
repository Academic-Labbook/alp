( function( $ ) {
	'use strict';

	$( document ).ready( function( $ ) {
		// remove list bullet points because we use icons instead
		$( '.widget' ).find( 'ul' ).addClass( 'list-unstyled' );
	});
} )( jQuery );
