<?php
/**
 * RationalRedirects Redirection Plugin Importer
 *
 * Imports redirects from the Redirection plugin.
 * Adapted from RationalSEO's Redirection importer.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirection plugin importer class.
 */
class RationalRedirects_Redirection_Importer implements RationalRedirects_Importer_Interface {

	/**
	 * Redirects instance.
	 *
	 * @var RationalRedirects_Redirects
	 */
	private $redirects;

	/**
	 * Constructor.
	 *
	 * @param RationalRedirects_Redirects $redirects Redirects instance.
	 */
	public function __construct( RationalRedirects_Redirects $redirects ) {
		$this->redirects = $redirects;
	}

	/**
	 * Get the unique slug for this importer.
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'redirection';
	}

	/**
	 * Get the display name for this importer.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Redirection';
	}

	/**
	 * Get the description for this importer.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Import redirects from the Redirection plugin by John Godley.', 'rationalredirects' );
	}

	/**
	 * Check if this importer is available (source data exists).
	 *
	 * @return bool
	 */
	public function is_available() {
		return $this->get_redirect_count() > 0;
	}

	/**
	 * Get the count of redirects available to import.
	 *
	 * @return int
	 */
	public function get_redirect_count() {
		if ( ! $this->table_exists() ) {
			return 0;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'redirection_items';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE action_type = 'url' AND match_type = 'url' AND status = 'enabled'" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return absint( $count );
	}

	/**
	 * Preview the import without making changes.
	 *
	 * @return RationalRedirects_Import_Result
	 */
	public function preview() {
		$result    = RationalRedirects_Import_Result::success( __( 'Preview generated successfully.', 'rationalredirects' ) );
		$redirects = $this->get_redirection_redirects( 5 );

		$result->set_preview_data(
			array(
				'total'   => $this->get_redirect_count(),
				'samples' => $redirects,
			)
		);

		return $result;
	}

	/**
	 * Perform the import.
	 *
	 * @param array $options Import options.
	 * @return RationalRedirects_Import_Result
	 */
	public function import( $options = array() ) {
		$result        = RationalRedirects_Import_Result::success();
		$skip_existing = ! empty( $options['skip_existing'] );
		$redirects     = $this->get_redirection_redirects();

		if ( empty( $redirects ) ) {
			$result->set_message( __( 'No redirects found to import.', 'rationalredirects' ) );
			return $result;
		}

		foreach ( $redirects as $redirect ) {
			if ( $skip_existing && $this->redirects->redirect_exists( $redirect['url_from'], $redirect['is_regex'] ) ) {
				$result->increment_skipped();
				continue;
			}

			$insert_id = $this->redirects->add_redirect(
				$redirect['url_from'],
				$redirect['url_to'],
				$redirect['status_code'],
				$redirect['is_regex']
			);

			if ( false !== $insert_id ) {
				$result->increment_imported();
			} else {
				$result->increment_failed();
				$result->add_error(
					sprintf(
						/* translators: %s: source URL */
						__( 'Failed to import redirect: %s', 'rationalredirects' ),
						$redirect['url_from']
					)
				);
			}
		}

		if ( $result->get_imported() > 0 ) {
			$result->set_message(
				sprintf(
					/* translators: %d: number of redirects imported */
					__( 'Successfully imported %d redirects from Redirection.', 'rationalredirects' ),
					$result->get_imported()
				)
			);
		} elseif ( $result->get_skipped() > 0 ) {
			$result->set_message( __( 'All redirects were skipped (already exist).', 'rationalredirects' ) );
		} else {
			$result->set_message( __( 'No redirects were imported.', 'rationalredirects' ) );
		}

		return $result;
	}

	/**
	 * Check if the Redirection plugin table exists.
	 *
	 * @return bool
	 */
	private function table_exists() {
		global $wpdb;

		$table = $wpdb->prefix . 'redirection_items';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table
			)
		);

		return (int) $result > 0;
	}

	/**
	 * Get redirects from Redirection plugin.
	 *
	 * Adapted from RationalSEO_Redirection_Importer::get_redirection_redirects()
	 *
	 * @param int $limit Maximum number of redirects to retrieve. 0 for all.
	 * @return array Parsed redirects array.
	 */
	private function get_redirection_redirects( $limit = 0 ) {
		if ( ! $this->table_exists() ) {
			return array();
		}

		global $wpdb;

		$table = $wpdb->prefix . 'redirection_items';

		$sql = "SELECT url, action_data, action_code, regex FROM {$table} WHERE action_type = 'url' AND match_type = 'url' AND status = 'enabled'"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return array();
		}

		return $this->parse_redirection_redirects( $results );
	}

	/**
	 * Parse Redirection plugin data into a normalized format.
	 *
	 * Adapted from RationalSEO_Redirection_Importer::parse_redirection_redirects()
	 *
	 * @param array $redirects Raw redirects from database.
	 * @return array Normalized redirects.
	 */
	private function parse_redirection_redirects( $redirects ) {
		$parsed = array();

		foreach ( $redirects as $redirect ) {
			$url_from = isset( $redirect->url ) ? $redirect->url : '';

			if ( empty( $url_from ) ) {
				continue;
			}

			$url_to = $this->parse_action_data( $redirect->action_data );

			$status_code = isset( $redirect->action_code ) ? absint( $redirect->action_code ) : 301;
			$is_regex    = isset( $redirect->regex ) && (int) $redirect->regex === 1;

			if ( 308 === $status_code ) {
				$status_code = 301;
			}

			if ( 410 !== $status_code && empty( $url_to ) ) {
				continue;
			}

			$valid_codes = array( 301, 302, 307, 410 );
			if ( ! in_array( $status_code, $valid_codes, true ) ) {
				$status_code = 301;
			}

			$parsed[] = array(
				'url_from'    => sanitize_text_field( $url_from ),
				'url_to'      => $this->sanitize_target_url( $url_to ),
				'status_code' => $status_code,
				'is_regex'    => $is_regex,
			);
		}

		return $parsed;
	}

	/**
	 * Parse action_data field to extract target URL.
	 *
	 * Redirection stores action_data in multiple formats:
	 * - Plain URL string (most common)
	 * - JSON object: {"url": "/target"}
	 * - Serialized PHP array: a:1:{s:3:"url";s:7:"/target";}
	 *
	 * @param string $action_data The action_data field from database.
	 * @return string The target URL.
	 */
	private function parse_action_data( $action_data ) {
		if ( empty( $action_data ) ) {
			return '';
		}

		$unserialized = @unserialize( $action_data ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $unserialized && is_array( $unserialized ) ) {
			return isset( $unserialized['url'] ) ? $unserialized['url'] : '';
		}

		$json_data = json_decode( $action_data, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $json_data ) ) {
			return isset( $json_data['url'] ) ? $json_data['url'] : '';
		}

		return $action_data;
	}

	/**
	 * Sanitize target URL, preserving relative paths.
	 *
	 * @param string $url The target URL to sanitize.
	 * @return string Sanitized URL.
	 */
	private function sanitize_target_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		if ( strpos( $url, '/' ) === 0 ) {
			return sanitize_text_field( $url );
		}

		if ( preg_match( '/^https?:\/\//i', $url ) ) {
			return esc_url_raw( $url );
		}

		return sanitize_text_field( $url );
	}
}
