<?php
/**
 * RationalRedirects Admin Class
 *
 * Handles admin settings page and redirect manager UI.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RationalRedirects_Admin {

	/**
	 * Settings instance.
	 *
	 * @var RationalRedirects_Settings
	 */
	private $settings;

	/**
	 * Redirects instance.
	 *
	 * @var RationalRedirects_Redirects
	 */
	private $redirects;

	/**
	 * Constructor.
	 *
	 * @param RationalRedirects_Settings  $settings  Settings instance.
	 * @param RationalRedirects_Redirects $redirects Redirects instance.
	 */
	public function __construct( RationalRedirects_Settings $settings, RationalRedirects_Redirects $redirects ) {
		$this->settings  = $settings;
		$this->redirects = $redirects;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'rationalwp',
			__( 'Redirects', 'rationalredirects' ),
			__( 'Redirects', 'rationalredirects' ),
			'manage_options',
			'rationalredirects',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'rationalredirects_settings_group',
			RationalRedirects_Settings::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		// Settings Section.
		add_settings_section(
			'rationalredirects_settings_section',
			__( 'Settings', 'rationalredirects' ),
			array( $this, 'render_section_settings' ),
			'rationalredirects'
		);

		add_settings_field(
			'redirect_auto_slug',
			__( 'Auto-Redirect on Slug Change', 'rationalredirects' ),
			array( $this, 'render_field_redirect_auto_slug' ),
			'rationalredirects',
			'rationalredirects_settings_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['redirect_auto_slug'] = isset( $input['redirect_auto_slug'] ) && '1' === $input['redirect_auto_slug'];

		// Set a transient to show success message on redirect.
		set_transient( 'rationalredirects_settings_saved', true, 30 );

		return $sanitized;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'rationalwp_page_rationalredirects' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'rationalredirects-admin',
			RATIONALREDIRECTS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			RATIONALREDIRECTS_VERSION
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'redirects';
		?>
		<div class="wrap rationalredirects-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( get_transient( 'rationalredirects_settings_saved' ) ) : ?>
				<?php delete_transient( 'rationalredirects_settings_saved' ); ?>
				<div id="rationalredirects-settings-message" class="notice notice-success rationalredirects-settings-saved">
					<p><?php esc_html_e( 'Settings saved successfully.', 'rationalredirects' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rationalredirects&tab=redirects' ) ); ?>"
				   class="nav-tab <?php echo 'redirects' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Redirects', 'rationalredirects' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rationalredirects&tab=settings' ) ); ?>"
				   class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'rationalredirects' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rationalredirects&tab=import' ) ); ?>"
				   class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Import', 'rationalredirects' ); ?>
				</a>
			</nav>

			<div class="rationalredirects-tab-content">
				<?php
				switch ( $active_tab ) {
					case 'settings':
						$this->render_settings_tab();
						break;
					case 'import':
						$this->render_import_tab();
						break;
					case 'redirects':
					default:
						$this->render_redirect_manager();
						break;
				}
				?>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Auto-hide settings saved message after 4 seconds.
			var $msg = $('.rationalredirects-settings-saved');
			if ($msg.length) {
				setTimeout(function() {
					$msg.fadeOut(300);
				}, 4000);

				// Clean URL without page reload.
				var newUrl = window.location.pathname + '?page=rationalredirects&tab=settings';
				window.history.replaceState({}, '', newUrl);
			}
		});
		</script>
		<?php
	}

	/**
	 * Render the settings tab content.
	 */
	private function render_settings_tab() {
		?>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'rationalredirects_settings_group' );
			do_settings_sections( 'rationalredirects' );
			submit_button( __( 'Save Settings', 'rationalredirects' ) );
			?>
		</form>
		<?php
	}

	/**
	 * Render the import tab content.
	 */
	private function render_import_tab() {
		$import_admin = RationalRedirects::get_instance()->get_import_admin();
		if ( $import_admin ) {
			$import_admin->render_import_tab();
		}
	}

	/**
	 * Render Settings section description.
	 */
	public function render_section_settings() {
		echo '<p>' . esc_html__( 'Configure URL redirect settings.', 'rationalredirects' ) . '</p>';
	}

	/**
	 * Render Auto Slug Redirect field.
	 */
	public function render_field_redirect_auto_slug() {
		$value = $this->settings->get( 'redirect_auto_slug', true );
		?>
		<label>
			<input type="checkbox"
				name="<?php echo esc_attr( RationalRedirects_Settings::OPTION_NAME ); ?>[redirect_auto_slug]"
				id="redirect_auto_slug"
				value="1"
				<?php checked( $value, true ); ?>>
			<?php esc_html_e( 'Automatically create redirects when post slugs change', 'rationalredirects' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, changing a published post\'s URL slug will automatically create a 301 redirect from the old URL to the new one.', 'rationalredirects' ); ?></p>
		<?php
	}

	/**
	 * Render the redirect manager interface.
	 */
	private function render_redirect_manager() {
		$redirects = $this->redirects->get_all_redirects();
		$nonce     = wp_create_nonce( 'rationalredirects_nonce' );
		?>
		<div class="rationalredirects-redirect-header">
			<h2><?php esc_html_e( 'Redirect Manager', 'rationalredirects' ); ?></h2>
		</div>

		<div class="rationalredirects-redirect-manager">
			<table class="wp-list-table widefat fixed striped rationalredirects-table">
				<thead>
					<tr>
						<th class="column-from"><?php esc_html_e( 'From URL', 'rationalredirects' ); ?></th>
						<th class="column-to"><?php esc_html_e( 'To URL', 'rationalredirects' ); ?></th>
						<th class="column-status"><?php esc_html_e( 'Type', 'rationalredirects' ); ?></th>
						<th class="column-regex"><?php esc_html_e( 'Regex', 'rationalredirects' ); ?></th>
						<th class="column-hits"><?php esc_html_e( 'Hits', 'rationalredirects' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'rationalredirects' ); ?></th>
					</tr>
				</thead>
				<tbody id="rationalredirects-list">
					<tr class="rationalredirects-add-row">
						<td>
							<input type="text" id="rationalredirects-new-from" placeholder="/old-url" class="regular-text">
						</td>
						<td>
							<input type="url" id="rationalredirects-new-to" placeholder="<?php echo esc_attr( home_url( '/new-url' ) ); ?>" class="regular-text">
						</td>
						<td>
							<select id="rationalredirects-new-status">
								<option value="301"><?php esc_html_e( '301 Permanent', 'rationalredirects' ); ?></option>
								<option value="302"><?php esc_html_e( '302 Temporary', 'rationalredirects' ); ?></option>
								<option value="307"><?php esc_html_e( '307 Temporary', 'rationalredirects' ); ?></option>
								<option value="410"><?php esc_html_e( '410 Gone', 'rationalredirects' ); ?></option>
							</select>
						</td>
						<td class="column-regex">
							<input type="checkbox" id="rationalredirects-new-regex" value="1">
						</td>
						<td class="column-hits">&mdash;</td>
						<td>
							<button type="button" class="button button-primary" id="rationalredirects-add-redirect">
								<?php esc_html_e( 'Add', 'rationalredirects' ); ?>
							</button>
						</td>
					</tr>
					<?php if ( empty( $redirects ) ) : ?>
						<tr class="no-redirects">
							<td colspan="6"><?php esc_html_e( 'No redirects found. Add one above.', 'rationalredirects' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $redirects as $redirect ) : ?>
							<?php $is_regex = isset( $redirect->is_regex ) && (int) $redirect->is_regex === 1; ?>
							<tr data-id="<?php echo esc_attr( $redirect->id ); ?>">
								<td class="column-from"><code><?php echo esc_html( $redirect->url_from ); ?></code></td>
								<td class="column-to">
									<?php if ( 410 === (int) $redirect->status_code ) : ?>
										<em><?php esc_html_e( '(Gone)', 'rationalredirects' ); ?></em>
									<?php else : ?>
										<a href="<?php echo esc_url( $redirect->url_to ); ?>" target="_blank" rel="noopener">
											<?php echo esc_html( $redirect->url_to ); ?>
										</a>
									<?php endif; ?>
								</td>
								<td class="column-status"><?php echo esc_html( $redirect->status_code ); ?></td>
								<td class="column-regex">
									<?php if ( $is_regex ) : ?>
										<span class="rationalredirects-regex-badge"><?php esc_html_e( 'Yes', 'rationalredirects' ); ?></span>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
								<td class="column-hits"><?php echo esc_html( number_format_i18n( $redirect->count ) ); ?></td>
								<td class="column-actions">
									<button type="button" class="button button-link-delete rationalredirects-delete-redirect" data-id="<?php echo esc_attr( $redirect->id ); ?>">
										<?php esc_html_e( 'Delete', 'rationalredirects' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<div id="rationalredirects-message" class="notice" style="display: none;"></div>
		</div>

		<script type="text/javascript">
		(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';
			var homeUrl = '<?php echo esc_js( home_url() ); ?>';

			// Add redirect.
			$('#rationalredirects-add-redirect').on('click', function() {
				var $btn = $(this);
				var urlFrom = $('#rationalredirects-new-from').val().trim();
				var urlTo = $('#rationalredirects-new-to').val().trim();
				var statusCode = $('#rationalredirects-new-status').val();
				var isRegex = $('#rationalredirects-new-regex').is(':checked') ? '1' : '0';

				if (!urlFrom) {
					showMessage('<?php echo esc_js( __( 'Please enter a source URL.', 'rationalredirects' ) ); ?>', 'error');
					return;
				}

				if (statusCode !== '410' && !urlTo) {
					showMessage('<?php echo esc_js( __( 'Please enter a destination URL.', 'rationalredirects' ) ); ?>', 'error');
					return;
				}

				$btn.prop('disabled', true);

				$.post(ajaxurl, {
					action: 'rationalredirects_add_redirect',
					nonce: nonce,
					url_from: urlFrom,
					url_to: urlTo,
					status_code: statusCode,
					is_regex: isRegex
				}, function(response) {
					$btn.prop('disabled', false);

					if (response.success) {
						var redirect = response.data.redirect;
						var toDisplay = statusCode === '410'
							? '<em><?php echo esc_js( __( '(Gone)', 'rationalredirects' ) ); ?></em>'
							: '<a href="' + redirect.url_to + '" target="_blank" rel="noopener">' + redirect.url_to + '</a>';
						var regexDisplay = redirect.is_regex == 1
							? '<span class="rationalredirects-regex-badge"><?php echo esc_js( __( 'Yes', 'rationalredirects' ) ); ?></span>'
							: '&mdash;';

						var newRow = '<tr data-id="' + redirect.id + '">' +
							'<td class="column-from"><code>' + redirect.url_from + '</code></td>' +
							'<td class="column-to">' + toDisplay + '</td>' +
							'<td class="column-status">' + redirect.status_code + '</td>' +
							'<td class="column-regex">' + regexDisplay + '</td>' +
							'<td class="column-hits">0</td>' +
							'<td class="column-actions">' +
								'<button type="button" class="button button-link-delete rationalredirects-delete-redirect" data-id="' + redirect.id + '">' +
									'<?php echo esc_js( __( 'Delete', 'rationalredirects' ) ); ?>' +
								'</button>' +
							'</td>' +
						'</tr>';

						$('.rationalredirects-add-row').after(newRow);
						$('.no-redirects').remove();

						// Clear inputs.
						$('#rationalredirects-new-from').val('');
						$('#rationalredirects-new-to').val('');
						$('#rationalredirects-new-status').val('301');
						$('#rationalredirects-new-regex').prop('checked', false);

						showMessage(response.data.message, 'success');
					} else {
						showMessage(response.data.message, 'error');
					}
				}).fail(function() {
					$btn.prop('disabled', false);
					showMessage('<?php echo esc_js( __( 'An error occurred. Please try again.', 'rationalredirects' ) ); ?>', 'error');
				});
			});

			// Delete redirect.
			$(document).on('click', '.rationalredirects-delete-redirect', function() {
				var $btn = $(this);
				var id = $btn.data('id');

				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this redirect?', 'rationalredirects' ) ); ?>')) {
					return;
				}

				$btn.prop('disabled', true);

				$.post(ajaxurl, {
					action: 'rationalredirects_delete_redirect',
					nonce: nonce,
					id: id
				}, function(response) {
					if (response.success) {
						$btn.closest('tr').fadeOut(300, function() {
							$(this).remove();
							if ($('#rationalredirects-list tr').length === 1) {
								$('.rationalredirects-add-row').after(
									'<tr class="no-redirects"><td colspan="6"><?php echo esc_js( __( 'No redirects found. Add one above.', 'rationalredirects' ) ); ?></td></tr>'
								);
							}
						});
						showMessage(response.data.message, 'success');
					} else {
						$btn.prop('disabled', false);
						showMessage(response.data.message, 'error');
					}
				}).fail(function() {
					$btn.prop('disabled', false);
					showMessage('<?php echo esc_js( __( 'An error occurred. Please try again.', 'rationalredirects' ) ); ?>', 'error');
				});
			});

			function showMessage(message, type) {
				var $msg = $('#rationalredirects-message');
				$msg.removeClass('notice-success notice-error')
					.addClass('notice-' + type)
					.html('<p>' + message + '</p>')
					.fadeIn();

				setTimeout(function() {
					$msg.fadeOut();
				}, 4000);
			}
		})(jQuery);
		</script>
		<?php
	}
}
