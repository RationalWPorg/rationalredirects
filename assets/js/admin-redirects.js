/**
 * RationalRedirects - Redirect Manager JavaScript
 *
 * Handles the redirect manager AJAX operations and settings
 * saved message auto-hide.
 *
 * @package RationalRedirects
 */

(function( $ ) {
	'use strict';

	var config = window.rationalredirectsRedirects || {};
	var strings = config.strings || {};

	/**
	 * Escape HTML entities in a string.
	 *
	 * @param {string} text The text to escape.
	 * @return {string} The escaped text.
	 */
	function escapeHtml( text ) {
		if ( ! text ) {
			return '';
		}
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( text ) );
		return div.innerHTML;
	}

	/**
	 * Show a notice message.
	 *
	 * @param {string} message The message to display.
	 * @param {string} type    The notice type ('success' or 'error').
	 */
	function showMessage( message, type ) {
		var $msg = $( '#rationalredirects-message' );
		$msg.removeClass( 'notice-success notice-error' )
			.addClass( 'notice-' + type )
			.empty()
			.append( $( '<p>' ).text( message ) )
			.fadeIn();

		setTimeout( function() {
			$msg.fadeOut();
		}, 4000 );
	}

	$( document ).ready( function() {

		// Auto-hide settings saved message after 4 seconds.
		var $settingsMsg = $( '.rationalredirects-settings-saved' );
		if ( $settingsMsg.length ) {
			setTimeout( function() {
				$settingsMsg.fadeOut( 300 );
			}, 4000 );

			// Clean URL without page reload.
			var newUrl = window.location.pathname + '?page=rationalredirects&tab=settings';
			window.history.replaceState( {}, '', newUrl );
		}

		// Add redirect.
		$( '#rationalredirects-add-redirect' ).on( 'click', function() {
			var $btn = $( this );
			var urlFrom = $( '#rationalredirects-new-from' ).val().trim();
			var urlTo = $( '#rationalredirects-new-to' ).val().trim();
			var statusCode = $( '#rationalredirects-new-status' ).val();
			var isRegex = $( '#rationalredirects-new-regex' ).is( ':checked' ) ? '1' : '0';

			if ( ! urlFrom ) {
				showMessage( strings.enterSourceUrl, 'error' );
				return;
			}

			if ( statusCode !== '410' && ! urlTo ) {
				showMessage( strings.enterDestUrl, 'error' );
				return;
			}

			$btn.prop( 'disabled', true );

			$.post( ajaxurl, {
				action: 'rationalredirects_add_redirect',
				nonce: config.nonce,
				url_from: urlFrom,
				url_to: urlTo,
				status_code: statusCode,
				is_regex: isRegex
			}, function( response ) {
				$btn.prop( 'disabled', false );

				if ( response.success ) {
					var redirect = response.data.redirect;
					var $toDisplay;

					if ( statusCode === '410' ) {
						$toDisplay = $( '<em>' ).text( strings.gone );
					} else {
						$toDisplay = $( '<a>', {
							href: redirect.url_to,
							target: '_blank',
							rel: 'noopener'
						} ).text( redirect.url_to );
					}

					var $regexCell = $( '<td>' ).addClass( 'column-regex' );
					if ( redirect.is_regex == 1 ) {
						$regexCell.append(
							$( '<span>' ).addClass( 'rationalredirects-regex-badge' ).text( strings.yes )
						);
					} else {
						$regexCell.html( '&mdash;' );
					}

					var $newRow = $( '<tr>' ).attr( 'data-id', redirect.id ).append(
						$( '<td>' ).addClass( 'column-from' ).append(
							$( '<code>' ).text( redirect.url_from )
						),
						$( '<td>' ).addClass( 'column-to' ).append( $toDisplay ),
						$( '<td>' ).addClass( 'column-status' ).text( redirect.status_code ),
						$regexCell,
						$( '<td>' ).addClass( 'column-hits' ).text( '0' ),
						$( '<td>' ).addClass( 'column-actions' ).append(
							$( '<button>', {
								type: 'button',
								'class': 'button button-link-delete rationalredirects-delete-redirect',
								'data-id': redirect.id
							} ).text( strings.deleteBtn )
						)
					);

					$( '.rationalredirects-add-row' ).after( $newRow );
					$( '.no-redirects' ).remove();

					// Clear inputs.
					$( '#rationalredirects-new-from' ).val( '' );
					$( '#rationalredirects-new-to' ).val( '' );
					$( '#rationalredirects-new-status' ).val( '301' );
					$( '#rationalredirects-new-regex' ).prop( 'checked', false );

					showMessage( response.data.message, 'success' );
				} else {
					showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				$btn.prop( 'disabled', false );
				showMessage( strings.error, 'error' );
			} );
		} );

		// Delete redirect.
		$( document ).on( 'click', '.rationalredirects-delete-redirect', function() {
			var $btn = $( this );
			var id = $btn.data( 'id' );

			if ( ! confirm( strings.confirmDelete ) ) {
				return;
			}

			$btn.prop( 'disabled', true );

			$.post( ajaxurl, {
				action: 'rationalredirects_delete_redirect',
				nonce: config.nonce,
				id: id
			}, function( response ) {
				if ( response.success ) {
					$btn.closest( 'tr' ).fadeOut( 300, function() {
						$( this ).remove();
						if ( $( '#rationalredirects-list tr' ).length === 1 ) {
							$( '.rationalredirects-add-row' ).after(
								$( '<tr>' ).addClass( 'no-redirects' ).append(
									$( '<td>', { colspan: 6 } ).text( strings.noRedirects )
								)
							);
						}
					} );
					showMessage( response.data.message, 'success' );
				} else {
					$btn.prop( 'disabled', false );
					showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				$btn.prop( 'disabled', false );
				showMessage( strings.error, 'error' );
			} );
		} );

	} );

})( jQuery );
