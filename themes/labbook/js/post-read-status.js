/**
 * File post-read-status.js.
 *
 * AJAX functionality to allow users to set/unset post read status.
 *
 * @package Labbook
 */

( function( $ ) {
	$( '.logbook-read-button' ).click( function() {
		// Icon element.
		var $button = $( this );

		// Post ID.
		var post_id = $button.data( 'post-id' );

		// Entry title link element.
		var $entry_link = $( '.entry-title-link-' + post_id );

		// Add loading class.
		$button.addClass( 'logbook-read-button-loading' );

		// Endpoint from wpApiSetting variable passed from wp-api.
		var endpoint = wpApiSettings.root + 'ssl-alp/v1/post-read-status/';

		// Read and unread icon classes.
		var read_class = $button.data( 'read-class' );
		var unread_class = $button.data( 'unread-class' );

		// Current read flag.
		var current_read_status = $entry_link.hasClass( 'entry-read' );

		$.ajax( {
			url: endpoint,
			method: 'POST',
			beforeSend: function( xhr ) {
				// Set nonce.
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			// Build post data.
			data: {
				post_id: post_id,
				read: ! current_read_status,
			}
		} ).done( function( data ) {
			// Update icon class.
			if ( current_read_status ) {
				// Read -> unread.
				$button.removeClass( read_class );
				$button.addClass( unread_class );

				$entry_link.removeClass( 'entry-read' );
			} else {
				// Unread -> read.
				$button.removeClass( unread_class );
				$button.addClass( read_class );

				$entry_link.addClass( 'entry-read' );
			}
		} ).always( function() {
			// Remove loading class.
			$button.removeClass( 'logbook-read-button-loading' );
		} );
	} );
} )( jQuery );
