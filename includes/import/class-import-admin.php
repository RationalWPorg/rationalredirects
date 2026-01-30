<?php
/**
 * RationalRedirects Import Admin Class
 *
 * Handles admin UI and AJAX handlers for the import system.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import admin class.
 */
class RationalRedirects_Import_Admin {

	/**
	 * Import manager instance.
	 *
	 * @var RationalRedirects_Import_Manager
	 */
	private $manager;

	/**
	 * Constructor.
	 *
	 * @param RationalRedirects_Import_Manager $manager Import manager instance.
	 */
	public function __construct( RationalRedirects_Import_Manager $manager ) {
		$this->manager = $manager;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// AJAX handlers.
		add_action( 'wp_ajax_rationalredirects_get_importers', array( $this, 'ajax_get_importers' ) );
		add_action( 'wp_ajax_rationalredirects_preview_import', array( $this, 'ajax_preview_import' ) );
		add_action( 'wp_ajax_rationalredirects_run_import', array( $this, 'ajax_run_import' ) );
	}

	/**
	 * Render the import tab content.
	 */
	public function render_import_tab() {
		$nonce = wp_create_nonce( 'rationalredirects_import' );
		?>
		<div class="rationalredirects-import-wrap">
			<h2><?php esc_html_e( 'Import Redirects', 'rationalredirects' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Import redirects from other SEO plugins. Select a source below to see available data and import options.', 'rationalredirects' ); ?>
			</p>

			<div id="rationalredirects-import-sources" class="rationalredirects-import-sources">
				<div class="rationalredirects-import-loading">
					<span class="spinner is-active"></span>
					<?php esc_html_e( 'Scanning for available importers...', 'rationalredirects' ); ?>
				</div>
			</div>

			<!-- Import Modal -->
			<div id="rationalredirects-import-modal" class="rationalredirects-modal" style="display: none;">
				<div class="rationalredirects-modal-content">
					<div class="rationalredirects-modal-header">
						<h3 id="rationalredirects-import-modal-title"><?php esc_html_e( 'Import Redirects', 'rationalredirects' ); ?></h3>
						<button type="button" class="rationalredirects-modal-close">&times;</button>
					</div>
					<div class="rationalredirects-modal-body">
						<div id="rationalredirects-import-modal-loading" class="rationalredirects-import-loading">
							<span class="spinner is-active"></span>
							<span id="rationalredirects-import-modal-loading-text"><?php esc_html_e( 'Loading...', 'rationalredirects' ); ?></span>
						</div>
						<div id="rationalredirects-import-modal-content" style="display: none;"></div>
						<div id="rationalredirects-import-modal-error" class="notice notice-error" style="display: none;">
							<p></p>
						</div>
						<div id="rationalredirects-import-modal-result" class="notice notice-success" style="display: none;">
							<p></p>
						</div>
					</div>
					<div class="rationalredirects-modal-footer">
						<button type="button" class="button" id="rationalredirects-import-modal-cancel">
							<?php esc_html_e( 'Cancel', 'rationalredirects' ); ?>
						</button>
						<button type="button" class="button button-primary" id="rationalredirects-import-modal-confirm" style="display: none;">
							<?php esc_html_e( 'Import Redirects', 'rationalredirects' ); ?>
						</button>
						<button type="button" class="button button-primary" id="rationalredirects-import-modal-done" style="display: none;">
							<?php esc_html_e( 'Done', 'rationalredirects' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>

		<style>
		.rationalredirects-import-wrap { margin-top: 20px; }
		.rationalredirects-import-sources { margin-top: 20px; }
		.rationalredirects-import-loading { padding: 20px; color: #666; }
		.rationalredirects-import-loading .spinner { float: left; margin-right: 10px; }
		.rationalredirects-import-empty { padding: 40px; text-align: center; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
		.rationalredirects-import-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #ccc; margin-bottom: 10px; }
		.rationalredirects-import-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
		.rationalredirects-import-card { background: #fff; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; }
		.rationalredirects-import-card-header { padding: 15px; background: #f9f9f9; border-bottom: 1px solid #ddd; }
		.rationalredirects-import-card-header h3 { margin: 0; font-size: 14px; }
		.rationalredirects-import-card-body { padding: 15px; }
		.rationalredirects-import-card-body .description { margin-top: 0; }
		.rationalredirects-import-card-body .redirect-count { margin: 10px 0 0; font-size: 13px; }
		.rationalredirects-import-card-body .redirect-count strong { color: #2271b1; }
		.rationalredirects-import-card-footer { padding: 15px; background: #f9f9f9; border-top: 1px solid #ddd; }

		.rationalredirects-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center; }
		.rationalredirects-modal-content { background: #fff; border-radius: 4px; width: 90%; max-width: 600px; max-height: 80vh; display: flex; flex-direction: column; }
		.rationalredirects-modal-header { padding: 15px 20px; background: #f9f9f9; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
		.rationalredirects-modal-header h3 { margin: 0; }
		.rationalredirects-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
		.rationalredirects-modal-close:hover { color: #d63638; }
		.rationalredirects-modal-body { padding: 20px; overflow-y: auto; flex: 1; }
		.rationalredirects-modal-body .notice { margin: 0; }
		.rationalredirects-modal-footer { padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #ddd; text-align: right; }
		.rationalredirects-modal-footer .button { margin-left: 10px; }

		.rationalredirects-import-preview { }
		.rationalredirects-import-options { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
		.rationalredirects-import-preview h4 { margin: 20px 0 10px; }
		.rationalredirects-import-preview table { margin-top: 10px; }
		.rationalredirects-import-preview table td { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
		.rationalredirects-import-preview .more-items { font-style: italic; color: #666; }
		</style>

		<script type="text/javascript">
		(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';
			var currentImporter = null;

			// Load importers on page load.
			$(document).ready(function() {
				loadImporters();
			});

			function loadImporters() {
				$.post(ajaxurl, {
					action: 'rationalredirects_get_importers',
					nonce: nonce
				}, function(response) {
					var $container = $('#rationalredirects-import-sources');
					$container.empty();

					if (!response.success) {
						$container.html('<div class="notice notice-error"><p>' + escapeHtml(response.data.message) + '</p></div>');
						return;
					}

					var importers = response.data.importers;
					if (Object.keys(importers).length === 0) {
						$container.html(
							'<div class="rationalredirects-import-empty">' +
								'<span class="dashicons dashicons-info"></span>' +
								'<p><?php echo esc_js( __( 'No plugins with importable redirects detected.', 'rationalredirects' ) ); ?></p>' +
								'<p class="description"><?php echo esc_js( __( 'RationalRedirects can import redirects from Yoast SEO Premium, Rank Math, AIOSEO, SEOPress, and the Redirection plugin.', 'rationalredirects' ) ); ?></p>' +
							'</div>'
						);
						return;
					}

					// Render importer cards.
					var html = '<div class="rationalredirects-import-grid">';
					for (var slug in importers) {
						var importer = importers[slug];
						html += renderImporterCard(importer);
					}
					html += '</div>';
					$container.html(html);

					// Bind click handlers.
					$('.rationalredirects-import-card-action').on('click', function() {
						var slug = $(this).data('slug');
						openImportModal(slug, importers[slug]);
					});
				}).fail(function() {
					$('#rationalredirects-import-sources').html(
						'<div class="notice notice-error"><p><?php echo esc_js( __( 'Failed to load importers. Please refresh the page.', 'rationalredirects' ) ); ?></p></div>'
					);
				});
			}

			function renderImporterCard(importer) {
				var countHtml = importer.count > 0
					? '<p class="redirect-count"><span class="dashicons dashicons-yes-alt"></span> <strong>' + importer.count + '</strong> <?php echo esc_js( __( 'redirects available', 'rationalredirects' ) ); ?></p>'
					: '<p class="redirect-count"><?php echo esc_js( __( 'No redirects to import', 'rationalredirects' ) ); ?></p>';

				return '<div class="rationalredirects-import-card">' +
					'<div class="rationalredirects-import-card-header">' +
						'<h3>' + escapeHtml(importer.name) + '</h3>' +
					'</div>' +
					'<div class="rationalredirects-import-card-body">' +
						'<p class="description">' + escapeHtml(importer.description) + '</p>' +
						countHtml +
					'</div>' +
					'<div class="rationalredirects-import-card-footer">' +
						(importer.count > 0 ?
							'<button type="button" class="button button-primary rationalredirects-import-card-action" data-slug="' + escapeHtml(importer.slug) + '">' +
								'<?php echo esc_js( __( 'Import Redirects', 'rationalredirects' ) ); ?>' +
							'</button>' :
							'<button type="button" class="button" disabled><?php echo esc_js( __( 'No Data', 'rationalredirects' ) ); ?></button>'
						) +
					'</div>' +
				'</div>';
			}

			function openImportModal(slug, importer) {
				currentImporter = slug;
				var $modal = $('#rationalredirects-import-modal');

				// Reset modal state.
				$('#rationalredirects-import-modal-title').text(
					'<?php echo esc_js( __( 'Import from', 'rationalredirects' ) ); ?> ' + importer.name
				);
				$('#rationalredirects-import-modal-loading').show();
				$('#rationalredirects-import-modal-loading-text').text('<?php echo esc_js( __( 'Loading preview...', 'rationalredirects' ) ); ?>');
				$('#rationalredirects-import-modal-content').hide().empty();
				$('#rationalredirects-import-modal-error').hide();
				$('#rationalredirects-import-modal-result').hide();
				$('#rationalredirects-import-modal-confirm').hide().prop('disabled', false).text('<?php echo esc_js( __( 'Import Redirects', 'rationalredirects' ) ); ?>');
				$('#rationalredirects-import-modal-done').hide();
				$('#rationalredirects-import-modal-cancel').show();

				$modal.css('display', 'flex');

				// Load preview.
				$.post(ajaxurl, {
					action: 'rationalredirects_preview_import',
					nonce: nonce,
					importer: slug
				}, function(response) {
					$('#rationalredirects-import-modal-loading').hide();

					if (!response.success) {
						showModalError(response.data.message);
						return;
					}

					renderPreview(importer, response.data);
				}).fail(function() {
					$('#rationalredirects-import-modal-loading').hide();
					showModalError('<?php echo esc_js( __( 'Failed to load preview. Please try again.', 'rationalredirects' ) ); ?>');
				});
			}

			function renderPreview(importer, data) {
				var html = '<div class="rationalredirects-import-preview">';

				// Options.
				html += '<div class="rationalredirects-import-options">';
				html += '<label><input type="checkbox" id="rationalredirects-import-skip-existing" checked> ';
				html += '<?php echo esc_js( __( 'Skip redirects that already exist (recommended)', 'rationalredirects' ) ); ?></label>';
				html += '</div>';

				// Summary.
				html += '<p><strong>' + data.preview_data.total + '</strong> <?php echo esc_js( __( 'redirects will be imported.', 'rationalredirects' ) ); ?></p>';

				// Preview table.
				if (data.preview_data.samples && data.preview_data.samples.length > 0) {
					html += '<h4><?php echo esc_js( __( 'Preview:', 'rationalredirects' ) ); ?></h4>';
					html += '<table class="widefat striped"><thead><tr>';
					html += '<th><?php echo esc_js( __( 'From', 'rationalredirects' ) ); ?></th>';
					html += '<th><?php echo esc_js( __( 'To', 'rationalredirects' ) ); ?></th>';
					html += '<th><?php echo esc_js( __( 'Type', 'rationalredirects' ) ); ?></th>';
					html += '<th><?php echo esc_js( __( 'Regex', 'rationalredirects' ) ); ?></th>';
					html += '</tr></thead><tbody>';

					for (var i = 0; i < data.preview_data.samples.length; i++) {
						var sample = data.preview_data.samples[i];
						html += '<tr>';
						html += '<td title="' + escapeHtml(sample.url_from) + '">' + escapeHtml(sample.url_from) + '</td>';
						html += '<td title="' + escapeHtml(sample.url_to || '') + '">' + escapeHtml(sample.url_to || '(Gone)') + '</td>';
						html += '<td>' + sample.status_code + '</td>';
						html += '<td>' + (sample.is_regex ? '<?php echo esc_js( __( 'Yes', 'rationalredirects' ) ); ?>' : '&mdash;') + '</td>';
						html += '</tr>';
					}

					if (data.preview_data.total > data.preview_data.samples.length) {
						html += '<tr><td colspan="4" class="more-items">';
						html += '<?php echo esc_js( __( 'And', 'rationalredirects' ) ); ?> ' + (data.preview_data.total - data.preview_data.samples.length) + ' <?php echo esc_js( __( 'more...', 'rationalredirects' ) ); ?>';
						html += '</td></tr>';
					}

					html += '</tbody></table>';
				}

				html += '</div>';

				$('#rationalredirects-import-modal-content').html(html).show();
				$('#rationalredirects-import-modal-confirm').show();
			}

			function showModalError(message) {
				$('#rationalredirects-import-modal-error').show().find('p').text(message);
				$('#rationalredirects-import-modal-done').show();
				$('#rationalredirects-import-modal-cancel').hide();
			}

			function showModalSuccess(message) {
				$('#rationalredirects-import-modal-result').show().find('p').text(message);
				$('#rationalredirects-import-modal-done').show();
				$('#rationalredirects-import-modal-cancel').hide();
				$('#rationalredirects-import-modal-confirm').hide();
			}

			// Modal close handlers.
			$('.rationalredirects-modal-close, #rationalredirects-import-modal-cancel, #rationalredirects-import-modal-done').on('click', function() {
				$('#rationalredirects-import-modal').hide();
				currentImporter = null;
			});

			$('#rationalredirects-import-modal').on('click', function(e) {
				if ($(e.target).is('#rationalredirects-import-modal')) {
					$(this).hide();
					currentImporter = null;
				}
			});

			// Import confirmation.
			$('#rationalredirects-import-modal-confirm').on('click', function() {
				if (!currentImporter) return;

				var skipExisting = $('#rationalredirects-import-skip-existing').is(':checked');

				// Show loading state.
				$('#rationalredirects-import-modal-content').hide();
				$('#rationalredirects-import-modal-loading').show();
				$('#rationalredirects-import-modal-loading-text').text('<?php echo esc_js( __( 'Importing redirects...', 'rationalredirects' ) ); ?>');
				$('#rationalredirects-import-modal-confirm').prop('disabled', true).text('<?php echo esc_js( __( 'Importing...', 'rationalredirects' ) ); ?>');
				$('#rationalredirects-import-modal-cancel').hide();

				$.post(ajaxurl, {
					action: 'rationalredirects_run_import',
					nonce: nonce,
					importer: currentImporter,
					skip_existing: skipExisting ? '1' : '0'
				}, function(response) {
					$('#rationalredirects-import-modal-loading').hide();

					if (!response.success) {
						showModalError(response.data.message);
						return;
					}

					showModalSuccess(response.data.message);

					// Reload importers to update counts.
					loadImporters();
				}).fail(function() {
					$('#rationalredirects-import-modal-loading').hide();
					showModalError('<?php echo esc_js( __( 'Import failed. Please try again.', 'rationalredirects' ) ); ?>');
				});
			});

			function escapeHtml(text) {
				if (!text) return '';
				var div = document.createElement('div');
				div.appendChild(document.createTextNode(text));
				return div.innerHTML;
			}
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * AJAX handler for getting available importers.
	 */
	public function ajax_get_importers() {
		check_ajax_referer( 'rationalredirects_import', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rationalredirects' ) ) );
		}

		$importers = $this->manager->get_importers_for_display( true );

		wp_send_json_success( array( 'importers' => $importers ) );
	}

	/**
	 * AJAX handler for previewing an import.
	 */
	public function ajax_preview_import() {
		check_ajax_referer( 'rationalredirects_import', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rationalredirects' ) ) );
		}

		$importer_slug = isset( $_POST['importer'] ) ? sanitize_key( $_POST['importer'] ) : '';

		if ( empty( $importer_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'No importer specified.', 'rationalredirects' ) ) );
		}

		$result = $this->manager->preview( $importer_slug );

		if ( ! $result->is_success() ) {
			wp_send_json_error( array( 'message' => $result->get_message() ) );
		}

		wp_send_json_success( $result->to_array() );
	}

	/**
	 * AJAX handler for running an import.
	 */
	public function ajax_run_import() {
		check_ajax_referer( 'rationalredirects_import', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rationalredirects' ) ) );
		}

		$importer_slug = isset( $_POST['importer'] ) ? sanitize_key( $_POST['importer'] ) : '';
		$skip_existing = isset( $_POST['skip_existing'] ) && '1' === $_POST['skip_existing'];

		if ( empty( $importer_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'No importer specified.', 'rationalredirects' ) ) );
		}

		$options = array(
			'skip_existing' => $skip_existing,
		);

		$result = $this->manager->import( $importer_slug, $options );

		if ( ! $result->is_success() && 0 === $result->get_imported() ) {
			wp_send_json_error( array( 'message' => $result->get_message() ) );
		}

		// Build success message.
		$message = sprintf(
			/* translators: 1: Number imported, 2: Number skipped, 3: Number failed */
			__( 'Import complete. Imported: %1$d, Skipped: %2$d, Failed: %3$d', 'rationalredirects' ),
			$result->get_imported(),
			$result->get_skipped(),
			$result->get_failed()
		);

		if ( $result->get_message() ) {
			$message = $result->get_message();
		}

		wp_send_json_success(
			array(
				'message'  => $message,
				'imported' => $result->get_imported(),
				'skipped'  => $result->get_skipped(),
				'failed'   => $result->get_failed(),
			)
		);
	}
}
