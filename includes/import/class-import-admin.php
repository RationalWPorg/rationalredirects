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

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue import admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'rationalwp_page_rationalredirects' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'rationalredirects-admin-import',
			RATIONALREDIRECTS_PLUGIN_URL . 'assets/js/admin-import.js',
			array( 'jquery' ),
			RATIONALREDIRECTS_VERSION,
			true
		);

		wp_localize_script(
			'rationalredirects-admin-import',
			'rationalredirectsImport',
			array(
				'nonce'   => wp_create_nonce( 'rationalredirects_import' ),
				'strings' => array(
					'noImporters'        => __( 'No plugins with importable redirects detected.', 'rationalredirects' ),
					'supportedPlugins'   => __( 'RationalRedirects can import redirects from Yoast SEO Premium, Rank Math, AIOSEO, SEOPress, and the Redirection plugin.', 'rationalredirects' ),
					'redirectsAvailable' => __( 'redirects available', 'rationalredirects' ),
					'noRedirects'        => __( 'No redirects to import', 'rationalredirects' ),
					'importRedirects'    => __( 'Import Redirects', 'rationalredirects' ),
					'noData'             => __( 'No Data', 'rationalredirects' ),
					'importFrom'         => __( 'Import from', 'rationalredirects' ),
					'loadingPreview'     => __( 'Loading preview...', 'rationalredirects' ),
					'skipExisting'       => __( 'Skip redirects that already exist (recommended)', 'rationalredirects' ),
					'willBeImported'     => __( 'redirects will be imported.', 'rationalredirects' ),
					'preview'            => __( 'Preview:', 'rationalredirects' ),
					'colFrom'            => __( 'From', 'rationalredirects' ),
					'colTo'              => __( 'To', 'rationalredirects' ),
					'colType'            => __( 'Type', 'rationalredirects' ),
					'colRegex'           => __( 'Regex', 'rationalredirects' ),
					'yes'                => __( 'Yes', 'rationalredirects' ),
					'gone'               => __( '(Gone)', 'rationalredirects' ),
					'and'                => __( 'And', 'rationalredirects' ),
					'more'               => __( 'more...', 'rationalredirects' ),
					'loadFailed'         => __( 'Failed to load importers. Please refresh the page.', 'rationalredirects' ),
					'previewFailed'      => __( 'Failed to load preview. Please try again.', 'rationalredirects' ),
					'importing'          => __( 'Importing redirects...', 'rationalredirects' ),
					'importingBtn'       => __( 'Importing...', 'rationalredirects' ),
					'importFailed'       => __( 'Import failed. Please try again.', 'rationalredirects' ),
				),
			)
		);
	}

	/**
	 * Render the import tab content.
	 */
	public function render_import_tab() {
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
