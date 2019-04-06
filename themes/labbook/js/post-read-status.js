/**
 * File post-read-status.js.
 *
 * AJAX functionality to allow users to set/unset post read status.
 *
 * @package Labbook
 */

( function( $ ) {
	$( '.labbook-read-button' ).click( function() {
		// Icon element.
		var $button = $( this );

		// Post ID.
		var post_id = $button.data( 'post-id' );

		// Entry title link element, if present.
		var $entry_link = $( '.entry-title-link-' + post_id );

		// Add loading class.
		$button.addClass( 'labbook-read-button-loading' );

		// Endpoint from wpApiSetting variable passed from wp-api.
		var endpoint = wpApiSettings.root + 'ssl-alp/v1/post-read-status/';

		// Read and unread icon classes.
		var read_class = $button.data( 'read-class' );
		var unread_class = $button.data( 'unread-class' );

		// Current read flag.
		var current_read_status = $button.data( 'read-status' );

		if ( null === current_read_status ) {
			// Can't get element's read status.
			return;
		}

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
			$button.data( 'read-status', data.read );

			// Update icon class.
			if ( data.read ) {
				// Post now read.
				$button.removeClass( unread_class );
				$button.addClass( read_class );

				if ( $entry_link.length ) {
					$entry_link.addClass( 'entry-read' );
				}
			} else {
				// Post now unread.
				$button.removeClass( read_class );
				$button.addClass( unread_class );

				if ( $entry_link.length ) {
					$entry_link.removeClass( 'entry-read' );
				}
			}
		} ).always( function() {
			// Remove loading class.
			$button.removeClass( 'labbook-read-button-loading' );
		} );
	} );
} )( jQuery );
