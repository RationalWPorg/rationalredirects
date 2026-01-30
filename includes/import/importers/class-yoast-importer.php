<?php
/**
 * RationalRedirects Yoast SEO Premium Redirects Importer
 *
 * Imports redirects from Yoast SEO Premium.
 * Adapted from RationalSEO's Yoast importer - redirect methods only.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Yoast SEO Premium redirects importer class.
 */
class RationalRedirects_Yoast_Redirects_Importer implements RationalRedirects_Importer_Interface {

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
		return 'yoast';
	}

	/**
	 * Get the display name for this importer.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Yoast SEO Premium';
	}

	/**
	 * Get the description for this importer.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Import redirects from Yoast SEO Premium.', 'rationalredirects' );
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
		return count( $this->get_yoast_redirects() );
	}

	/**
	 * Preview the import without making changes.
	 *
	 * @return RationalRedirects_Import_Result
	 */
	public function preview() {
		$result    = RationalRedirects_Import_Result::success( __( 'Preview generated successfully.', 'rationalredirects' ) );
		$redirects = $this->get_yoast_redirects();

		$result->set_preview_data(
			array(
				'total'   => count( $redirects ),
				'samples' => array_slice( $redirects, 0, 5 ),
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
		$redirects     = $this->get_yoast_redirects();

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
					__( 'Successfully imported %d redirects from Yoast SEO Premium.', 'rationalredirects' ),
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
	 * Get Yoast redirects from options.
	 *
	 * Adapted from RationalSEO_Yoast_Importer::get_yoast_redirects()
	 *
	 * @return array Parsed redirects array.
	 */
	private function get_yoast_redirects() {
		$redirects = array();

		$option_keys = array(
			'wpseo-premium-redirects-base',
			'wpseo_redirect',
			'wpseo-premium-redirects-export-plain',
		);

		foreach ( $option_keys as $key ) {
			$yoast_redirects = get_option( $key, array() );
			if ( ! empty( $yoast_redirects ) && is_array( $yoast_redirects ) ) {
				$redirects = $this->parse_yoast_redirects( $yoast_redirects );
				if ( ! empty( $redirects ) ) {
					break;
				}
			}
		}

		if ( empty( $redirects ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$option_row = $wpdb->get_row(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'wpseo%redirect%' AND option_value != '' LIMIT 1"
			);
			if ( $option_row && ! empty( $option_row->option_value ) ) {
				$maybe_redirects = maybe_unserialize( $option_row->option_value );
				if ( is_array( $maybe_redirects ) ) {
					$redirects = $this->parse_yoast_redirects( $maybe_redirects );
				}
			}
		}

		return $redirects;
	}

	/**
	 * Parse Yoast redirect data into a normalized format.
	 *
	 * Adapted from RationalSEO_Yoast_Importer::parse_yoast_redirects()
	 *
	 * @param array $yoast_redirects Raw Yoast redirects array.
	 * @return array Normalized redirects.
	 */
	private function parse_yoast_redirects( $yoast_redirects ) {
		$parsed = array();

		foreach ( $yoast_redirects as $key => $redirect ) {
			$url_from    = '';
			$url_to      = '';
			$status_code = 301;
			$is_regex    = false;

			if ( isset( $redirect['origin'] ) ) {
				$url_from    = isset( $redirect['origin'] ) ? $redirect['origin'] : '';
				$url_to      = isset( $redirect['url'] ) ? $redirect['url'] : '';
				$status_code = isset( $redirect['type'] ) ? absint( $redirect['type'] ) : 301;
				$is_regex    = isset( $redirect['format'] ) && 'regex' === $redirect['format'];
			} elseif ( is_string( $key ) && isset( $redirect['url'] ) ) {
				$url_from    = $key;
				$url_to      = isset( $redirect['url'] ) ? $redirect['url'] : '';
				$status_code = isset( $redirect['type'] ) ? absint( $redirect['type'] ) : 301;
				$is_regex    = false;
			}

			if ( empty( $url_from ) ) {
				continue;
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
