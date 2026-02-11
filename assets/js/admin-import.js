/**
 * RationalRedirects - Import System JavaScript
 *
 * Handles the import tab UI: loading importers, preview modal,
 * and running imports.
 *
 * @package RationalRedirects
 */

(function( $ ) {
	'use strict';

	var config = window.rationalredirectsImport || {};
	var strings = config.strings || {};
	var currentImporter = null;

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
	 * Load available importers via AJAX.
	 */
	function loadImporters() {
		$.post( ajaxurl, {
			action: 'rationalredirects_get_importers',
			nonce: config.nonce
		}, function( response ) {
			var $container = $( '#rationalredirects-import-sources' );
			$container.empty();

			if ( ! response.success ) {
				$container.html( '<div class="notice notice-error"><p>' + escapeHtml( response.data.message ) + '</p></div>' );
				return;
			}

			var importers = response.data.importers;
			if ( Object.keys( importers ).length === 0 ) {
				$container.html(
					'<div class="rationalredirects-import-empty">' +
						'<span class="dashicons dashicons-info"></span>' +
						'<p>' + escapeHtml( strings.noImporters ) + '</p>' +
						'<p class="description">' + escapeHtml( strings.supportedPlugins ) + '</p>' +
					'</div>'
				);
				return;
			}

			// Render importer cards.
			var html = '<div class="rationalredirects-import-grid">';
			for ( var slug in importers ) {
				html += renderImporterCard( importers[ slug ] );
			}
			html += '</div>';
			$container.html( html );

			// Bind click handlers.
			$( '.rationalredirects-import-card-action' ).on( 'click', function() {
				var cardSlug = $( this ).data( 'slug' );
				openImportModal( cardSlug, importers[ cardSlug ] );
			} );
		} ).fail( function() {
			$( '#rationalredirects-import-sources' ).html(
				'<div class="notice notice-error"><p>' + escapeHtml( strings.loadFailed ) + '</p></div>'
			);
		} );
	}

	/**
	 * Render an importer card.
	 *
	 * @param {Object} importer The importer data.
	 * @return {string} The card HTML.
	 */
	function renderImporterCard( importer ) {
		var countHtml = importer.count > 0
			? '<p class="redirect-count"><span class="dashicons dashicons-yes-alt"></span> <strong>' + importer.count + '</strong> ' + escapeHtml( strings.redirectsAvailable ) + '</p>'
			: '<p class="redirect-count">' + escapeHtml( strings.noRedirects ) + '</p>';

		return '<div class="rationalredirects-import-card">' +
			'<div class="rationalredirects-import-card-header">' +
				'<h3>' + escapeHtml( importer.name ) + '</h3>' +
			'</div>' +
			'<div class="rationalredirects-import-card-body">' +
				'<p class="description">' + escapeHtml( importer.description ) + '</p>' +
				countHtml +
			'</div>' +
			'<div class="rationalredirects-import-card-footer">' +
				( importer.count > 0 ?
					'<button type="button" class="button button-primary rationalredirects-import-card-action" data-slug="' + escapeHtml( importer.slug ) + '">' +
						escapeHtml( strings.importRedirects ) +
					'</button>' :
					'<button type="button" class="button" disabled>' + escapeHtml( strings.noData ) + '</button>'
				) +
			'</div>' +
		'</div>';
	}

	/**
	 * Open the import modal for a specific importer.
	 *
	 * @param {string} slug     The importer slug.
	 * @param {Object} importer The importer data.
	 */
	function openImportModal( slug, importer ) {
		currentImporter = slug;
		var $modal = $( '#rationalredirects-import-modal' );

		// Reset modal state.
		$( '#rationalredirects-import-modal-title' ).text(
			strings.importFrom + ' ' + importer.name
		);
		$( '#rationalredirects-import-modal-loading' ).show();
		$( '#rationalredirects-import-modal-loading-text' ).text( strings.loadingPreview );
		$( '#rationalredirects-import-modal-content' ).hide().empty();
		$( '#rationalredirects-import-modal-error' ).hide();
		$( '#rationalredirects-import-modal-result' ).hide();
		$( '#rationalredirects-import-modal-confirm' ).hide().prop( 'disabled', false ).text( strings.importRedirects );
		$( '#rationalredirects-import-modal-done' ).hide();
		$( '#rationalredirects-import-modal-cancel' ).show();

		$modal.css( 'display', 'flex' );

		// Load preview.
		$.post( ajaxurl, {
			action: 'rationalredirects_preview_import',
			nonce: config.nonce,
			importer: slug
		}, function( response ) {
			$( '#rationalredirects-import-modal-loading' ).hide();

			if ( ! response.success ) {
				showModalError( response.data.message );
				return;
			}

			renderPreview( importer, response.data );
		} ).fail( function() {
			$( '#rationalredirects-import-modal-loading' ).hide();
			showModalError( strings.previewFailed );
		} );
	}

	/**
	 * Render the import preview in the modal.
	 *
	 * @param {Object} importer The importer data.
	 * @param {Object} data     The preview response data.
	 */
	function renderPreview( importer, data ) {
		var html = '<div class="rationalredirects-import-preview">';

		// Options.
		html += '<div class="rationalredirects-import-options">';
		html += '<label><input type="checkbox" id="rationalredirects-import-skip-existing" checked> ';
		html += escapeHtml( strings.skipExisting ) + '</label>';
		html += '</div>';

		// Summary.
		html += '<p><strong>' + data.preview_data.total + '</strong> ' + escapeHtml( strings.willBeImported ) + '</p>';

		// Preview table.
		if ( data.preview_data.samples && data.preview_data.samples.length > 0 ) {
			html += '<h4>' + escapeHtml( strings.preview ) + '</h4>';
			html += '<table class="widefat striped"><thead><tr>';
			html += '<th>' + escapeHtml( strings.colFrom ) + '</th>';
			html += '<th>' + escapeHtml( strings.colTo ) + '</th>';
			html += '<th>' + escapeHtml( strings.colType ) + '</th>';
			html += '<th>' + escapeHtml( strings.colRegex ) + '</th>';
			html += '</tr></thead><tbody>';

			for ( var i = 0; i < data.preview_data.samples.length; i++ ) {
				var sample = data.preview_data.samples[ i ];
				html += '<tr>';
				html += '<td title="' + escapeHtml( sample.url_from ) + '">' + escapeHtml( sample.url_from ) + '</td>';
				html += '<td title="' + escapeHtml( sample.url_to || '' ) + '">' + escapeHtml( sample.url_to || strings.gone ) + '</td>';
				html += '<td>' + sample.status_code + '</td>';
				html += '<td>' + ( sample.is_regex ? escapeHtml( strings.yes ) : '&mdash;' ) + '</td>';
				html += '</tr>';
			}

			if ( data.preview_data.total > data.preview_data.samples.length ) {
				html += '<tr><td colspan="4" class="more-items">';
				html += escapeHtml( strings.and ) + ' ' + ( data.preview_data.total - data.preview_data.samples.length ) + ' ' + escapeHtml( strings.more );
				html += '</td></tr>';
			}

			html += '</tbody></table>';
		}

		html += '</div>';

		$( '#rationalredirects-import-modal-content' ).html( html ).show();
		$( '#rationalredirects-import-modal-confirm' ).show();
	}

	/**
	 * Show an error message in the modal.
	 *
	 * @param {string} message The error message.
	 */
	function showModalError( message ) {
		$( '#rationalredirects-import-modal-error' ).show().find( 'p' ).text( message );
		$( '#rationalredirects-import-modal-done' ).show();
		$( '#rationalredirects-import-modal-cancel' ).hide();
	}

	/**
	 * Show a success message in the modal.
	 *
	 * @param {string} message The success message.
	 */
	function showModalSuccess( message ) {
		$( '#rationalredirects-import-modal-result' ).show().find( 'p' ).text( message );
		$( '#rationalredirects-import-modal-done' ).show();
		$( '#rationalredirects-import-modal-cancel' ).hide();
		$( '#rationalredirects-import-modal-confirm' ).hide();
	}

	// Load importers on page load.
	$( document ).ready( function() {
		if ( $( '#rationalredirects-import-sources' ).length ) {
			loadImporters();
		}
	} );

	// Modal close handlers.
	$( '.rationalredirects-modal-close, #rationalredirects-import-modal-cancel, #rationalredirects-import-modal-done' ).on( 'click', function() {
		$( '#rationalredirects-import-modal' ).hide();
		currentImporter = null;
	} );

	$( '#rationalredirects-import-modal' ).on( 'click', function( e ) {
		if ( $( e.target ).is( '#rationalredirects-import-modal' ) ) {
			$( this ).hide();
			currentImporter = null;
		}
	} );

	// Import confirmation.
	$( '#rationalredirects-import-modal-confirm' ).on( 'click', function() {
		if ( ! currentImporter ) {
			return;
		}

		var skipExisting = $( '#rationalredirects-import-skip-existing' ).is( ':checked' );

		// Show loading state.
		$( '#rationalredirects-import-modal-content' ).hide();
		$( '#rationalredirects-import-modal-loading' ).show();
		$( '#rationalredirects-import-modal-loading-text' ).text( strings.importing );
		$( '#rationalredirects-import-modal-confirm' ).prop( 'disabled', true ).text( strings.importingBtn );
		$( '#rationalredirects-import-modal-cancel' ).hide();

		$.post( ajaxurl, {
			action: 'rationalredirects_run_import',
			nonce: config.nonce,
			importer: currentImporter,
			skip_existing: skipExisting ? '1' : '0'
		}, function( response ) {
			$( '#rationalredirects-import-modal-loading' ).hide();

			if ( ! response.success ) {
				showModalError( response.data.message );
				return;
			}

			showModalSuccess( response.data.message );

			// Reload importers to update counts.
			loadImporters();
		} ).fail( function() {
			$( '#rationalredirects-import-modal-loading' ).hide();
			showModalError( strings.importFailed );
		} );
	} );

})( jQuery );
