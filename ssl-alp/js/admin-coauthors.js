jQuery( document ).ready(function () {
	/*
	 * Click handler for the delete button
	 * @param event
	 */
	var coauthors_delete_onclick = function( e ) {
		return coauthors_delete( this );
	};

	// default hidden loading animation
	var $coauthors_loading = jQuery( '<span id="ajax-loading"></span>' );

	function coauthors_delete( elem ) {
		var $coauthor_row = jQuery( elem ).closest( '.coauthor-row' );
		$coauthor_row.remove();

		// hide the delete button when there's only one coauthor
		if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length <= 1 ) {
			jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).addClass( 'hidden' );
		}

		return true;
	}

	var coauthors_edit_onclick = function( event ) {
		var $author_span = jQuery( this );
		var $input_box = $author_span.prev();

		$author_span.hide();
		$input_box.show().focus();
	}

	/*
	 * Save coauthor
	 * @param int Author ID
	 * @param string Author Name
	 * @param object The autosuggest input box
	 */
	function coauthors_save_coauthor( author, input_box ) {
		// get sibling <span> and update
		input_box.siblings( '.coauthor-tag' )
			.html( author.display_name )
			.append( coauthors_create_author_avatar( author ) )
			.show();

		// update the value of the hidden input
		input_box.siblings( 'input[name="coauthors[]"]' ).val( author.login );
	}

	/*
	 * Add author to coauthor list.
	 * 
	 * @param string Author Name
	 * @param object The autosuggest input box
	 * @param boolean Initial set up or not?
	 */
	function coauthors_add_coauthor( author, input_box, init, count ) {
		if ( input_box && input_box.siblings( '.coauthor-tag' ).length ) {
			// user is editing an existing author input box
			coauthors_save_coauthor( author, input_box );
		} else {
			// not editing, so we create a new author entry
			if ( count == 0 ) {
				var input_box_name = ( count == 0 ) ? 'coauthors-main' : '';
			}

			var options = { addDelete: true, addEdit: false };

			// create autosuggest box and text tag
			if ( ! input_box ) {
				var input_box = coauthors_create_autosuggest( author.display_name, input_box_name );
			}

			var author_span = coauthors_create_author_tag( author );
			var input = coauthors_create_author_hidden_input( author );
			var $avatar = coauthors_create_author_avatar( author );

			// add avatar to span
			author_span.append( $avatar );

			coauthors_add_to_table( input_box, author_span, input, options );

			if ( ! init ) {
				// create new author-suggest and append it to a new row
				var new_input_box = coauthors_create_autosuggest( '', false );
				coauthors_add_to_table( new_input_box );
				move_loading( new_input_box );
			}
		}

		input_box.bind( 'blur', coauthors_stop_editing );

		// Set the value for the auto-suggest box to the Author's name and hide it
		input_box.val( unescape( author.display_name ) )
			.hide()
			.unbind( 'focus' );

		return true;
	}

	/*
	 * Add the autosuggest box and text tag to the Co-Authors table
	 * @param object Autosuggest input box
	 * @param object Text tag
	 * @param
	 */
	function coauthors_add_to_table( input_box, author_span, input, options ) {
		if ( input_box ) {
			// create div tag
			var $div = jQuery( '<div/>' )
						.addClass( 'suggest' )
						.addClass( 'coauthor-row' )
						.append( input_box )
						.append( author_span )
						.append( input );

			// add buttons to row
			if ( author_span ) {
				coauthors_insert_author_edit_cells( $div, options );
			}

			jQuery( '#coauthors-list' ).append( $div );
		}
	}

	/*
	 * Adds a delete and edit button next to an author
	 * @param object The row to which the new author should be added
	 */
	function coauthors_insert_author_edit_cells( $div, options ) {
		// create div tag
		var $options = jQuery( '<div/>' )
						.addClass( 'coauthors-author-options' );

		if ( options.addDelete ) {
			// create span tag
			var delete_btn = jQuery( '<span/>' )
								.addClass( 'delete-coauthor' )
								.text( ssl_alp_coauthors_strings.delete_label )
								.bind( 'click', coauthors_delete_onclick );
			$options.append( delete_btn );
		}

		$div.append( $options );

		return $div;
	}

	/*
	 * Create coauthor search box.
	 * 
	 * This is both the empty search box to add new authors, and existing ones.
	 * 
	 * @param string [optional] Name of the author
	 * @param string [optional] Name to be applied to the input box
	 */
	function coauthors_create_autosuggest( author_name, input_name ) {
		if ( ! input_name ) {
			input_name = 'coauthorsinput[]';
		}

		// create input tag
		var $input_box = jQuery( '<input/>' );

		$input_box.attr({
			'class': 'coauthor-suggest'
			, 'name': input_name
			})
			.appendTo( $coauthors_div )
			.autocomplete({
				minChars: 1,
				source: function( request, response ) {
					// add existing authors to request
					var existing_coauthors = jQuery( 'input[name="coauthors[]"]' ).map(function(){return jQuery( this ).val();}).get();
					existing_coauthors = existing_coauthors.join( ',' );

					jQuery.ajax({
						type: 'GET',
						url: rest.api_autosuggest_endpoint,
						data: {
							term: request.term,
							existing_coauthors: existing_coauthors
						},
						beforeSend: function ( xhr ) {
							// set WordPress nonce header
							xhr.setRequestHeader( 'X-WP-Nonce', rest.api_nonce );

							// show loading animation
							show_loading();
						},
						complete: function () {
							// hide loading animation
							hide_loading();
						},
						success: function ( data ) {
							// parse response as JSON
							var data = jQuery.parseJSON( JSON.stringify( data ) );

							// process JSON data into drop-down list
							// data includes "label" that is shown in the drop-down list
							response( data.suggestions );
						}
					});
				},
				select: function ( event, selection ) {
					// author is the only item
					author = selection.item;

					// add selected author to list
					coauthors_autosuggest_select( author, jQuery( this ) );

					// tell JQuery that select has set a value
					return false;
				}
			})
			.keydown( coauthors_autosuggest_keydown );

			$input_box.autocomplete( "instance" )
				._renderItem = function( ul, author ) {
					// content to display in list item
					var content = unescape( author.display_name );

					// highlight search term
					content = highlight_search_term( content, this.term );

					var $author_span = jQuery( '<span></span>' )
										.html( content )
										.addClass( 'coauthor-suggest-item' );

					var $list_item = jQuery( "<li>" )
										.append( $author_span )
										.appendTo( ul );

					return $list_item;
				}

		if ( ! author_name ) {
			$input_box.attr( 'value', ssl_alp_coauthors_strings.search_box_text )
				.focus( function() { $input_box.val( '' ) } )
				.blur( function() { $input_box.val( ssl_alp_coauthors_strings.search_box_text ) } );
		}

		return $input_box;
	}

	// highlight in bold any occurances of `term` in `content`
	function highlight_search_term( content, term ) {
		// case insensitive regular expression
		var expression = new RegExp( "(" + escape_regex( term ) + ")", 'gi' );
		
		return content.replace(
			expression,
			"<strong>$&</strong>"
		);
	}

	function escape_regex( str ) {
		return str.replace( /([.*+?^=!:${}()|\[\]\/\\])/g , "\\$1" );
	}

	// callback for when a user selects an author
	function coauthors_autosuggest_select( author, input_box ) {
		// add author to list
		coauthors_add_coauthor( author, input_box );

		// show the delete button if we now have more than one coauthor
		if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length > 1 ) {
			jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).removeClass( 'hidden' );
		}
	}

	// Prevent the enter key from triggering a submit
	function coauthors_autosuggest_keydown( e ) {
		if ( e.keyCode == 13 ) {
			return false;
		}
	}

	/*
	 * Auto-suggest input box hider.
	 * 
	 * This hides the auto-suggest input box and instead shows the author <span>
	 * once an edit has been finished.
	 * 
	 * @param event
	 */
	function coauthors_stop_editing( event ) {
		var $author_input_box = jQuery( this );
		var $author_span = jQuery( $author_input_box.next() );

		$author_input_box.attr( 'value', $author_span.text() );

		$author_input_box.hide();
		$author_span.show();
	}

	/*
	 * Creates the text tag for an author
	 * @param string Name of the author
	 */
	function coauthors_create_author_tag( author ) {
		// create span tag
		var $tag = jQuery( '<span></span>' )
			.html( unescape( author.display_name ) )
			.attr( 'title', ssl_alp_coauthors_strings.input_box_title )
			.addClass( 'coauthor-tag' )
			// Add Click event to edit
			.click( coauthors_edit_onclick );

		return $tag;
	}

	function coauthors_create_author_avatar( author ) {
		// create img tag
		var $avatar = jQuery( '<img/>' )
							.attr( 'alt', author.name )
							.attr( 'src', author.avatar )
							.addClass( 'coauthor-avatar' );

		return $avatar;
	}

	/*
	 * Creates the text tag for an author
	 * @param string Name of the author
	 */
	function coauthors_create_author_hidden_input ( author ) {
		var $input = jQuery( '<input />' )
			.attr({
				'type': 'hidden',
				'id': 'coauthors_hidden_input',
				'name': 'coauthors[]',
				'value': unescape( author.login )
			});

		return $input;
	}

	var $coauthors_div = null;

	/**
	 * Initialize the Coauthors UI.
	 *
	 * @param array List of coauthors objects.
	 *  Each coauthor object should have the (string) properties:
	 *    login
	 *    email
	 *    name
	 *    nicename
	 */
	function coauthors_initialize( post_coauthors ) {
		// add the controls to add co-authors
		$coauthors_div = jQuery( '#coauthors-edit' );

		if ( $coauthors_div.length ) {
			// create the co-authors table
			var table = jQuery( '<div/>' )
				.attr( 'id', 'coauthors-list' );

			$coauthors_div.append( table );
		}

		// select authors already added to the post
		var count = 0;

		jQuery.each( post_coauthors, function() {
			coauthors_add_coauthor( this, undefined, true, count );

			count++;
		});

		// hide the delete button if there's only one co-author
		if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length < 2 ) {
			jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).addClass( 'hidden' );
		}

		// create new author-suggest and append it to a new row
		var new_co = coauthors_create_autosuggest( '', false );
		coauthors_add_to_table( new_co );

		$coauthors_loading = jQuery( '#publishing-action .spinner' ).clone().attr( 'id', 'coauthors-loading' );
		move_loading( new_co );

		// make co-authors sortable so an editor can control the order of the authors
		jQuery( '#coauthors-edit' ).ready(function() {
			jQuery( '#coauthors-list' ).sortable({
				axis: 'y',
				handle: '.coauthor-tag',
				placeholder: 'ui-state-highlight',
				items: 'div.coauthor-row:not(div.coauthor-row:last)',
				containment: 'parent',
			});
		});
	}

	function show_loading() {
		$coauthors_loading.css( 'visibility', 'visible' );
	}

	function hide_loading() {
		$coauthors_loading.css( 'visibility', 'hidden' );
	}

	function move_loading( $input ) {
		$coauthors_loading.insertAfter( $input );
	}

	if ( 'post-php' == adminpage || 'post-new-php' == adminpage ) {
		// this is the full edit page

		var $post_coauthor_logins = jQuery( 'input[name="coauthors[]"]' );
		var $post_coauthor_names = jQuery( 'input[name="coauthorsinput[]"]' );
		var $post_coauthor_avatars = jQuery( 'input[name="coauthorsavatar[]"]' );

		var post_coauthors = [];

		for ( var i = 0; i < $post_coauthor_logins.length; i++ ) {
			post_coauthors.push({
				login: $post_coauthor_logins[i].value,
				display_name: $post_coauthor_names[i].value,
				avatar: $post_coauthor_avatars[i].value
			});
		}

		// remove the read-only coauthors so we don't get craziness
		jQuery( '#coauthors-readonly' ).remove();
		coauthors_initialize( post_coauthors );
	} else if ( 'edit-php' == adminpage ) {
		// this is an inline edit
		// not supported; do nothing
	}
});

if ( typeof( console ) === 'undefined' ) {
	var console = {}
	console.log = console.error = function() {};
}
