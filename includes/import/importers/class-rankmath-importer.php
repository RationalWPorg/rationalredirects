<?php
/**
 * RationalRedirects Rank Math Redirects Importer
 *
 * Imports redirects from Rank Math SEO.
 * Adapted from RationalSEO's Rank Math importer - redirect methods only.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rank Math redirects importer class.
 */
class RationalRedirects_RankMath_Redirects_Importer implements RationalRedirects_Importer_Interface {

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
		return 'rankmath';
	}

	/**
	 * Get the display name for this importer.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Rank Math';
	}

	/**
	 * Get the description for this importer.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Import redirects from Rank Math SEO.', 'rationalredirects' );
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
		global $wpdb;

		$table_name = $wpdb->prefix . 'rank_math_redirections';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( ! $table_exists ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
		$redirects = $this->get_rankmath_redirects();

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
		$redirects     = $this->get_rankmath_redirects();

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
					__( 'Successfully imported %d redirects from Rank Math.', 'rationalredirects' ),
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
	 * Get Rank Math redirects from database table.
	 *
	 * Adapted from RationalSEO_RankMath_Importer::get_rankmath_redirects()
	 *
	 * @return array Parsed redirects array.
	 */
	private function get_rankmath_redirects() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rank_math_redirections';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( ! $table_exists ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$redirects_raw = $wpdb->get_results(
			"SELECT sources, url_to, header_code FROM {$table_name} WHERE status = 'active'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( empty( $redirects_raw ) ) {
			return array();
		}

		return $this->parse_rankmath_redirects( $redirects_raw );
	}

	/**
	 * Parse Rank Math redirect data into a normalized format.
	 *
	 * Adapted from RationalSEO_RankMath_Importer::parse_rankmath_redirects()
	 *
	 * @param array $rankmath_redirects Raw Rank Math redirects from database.
	 * @return array Normalized redirects.
	 */
	private function parse_rankmath_redirects( $rankmath_redirects ) {
		$parsed = array();

		foreach ( $rankmath_redirects as $redirect ) {
			$sources = maybe_unserialize( $redirect['sources'] );

			if ( ! is_array( $sources ) || empty( $sources ) ) {
				continue;
			}

			$url_to      = isset( $redirect['url_to'] ) ? $redirect['url_to'] : '';
			$status_code = isset( $redirect['header_code'] ) ? absint( $redirect['header_code'] ) : 301;

			$valid_codes = array( 301, 302, 307, 410 );
			if ( ! in_array( $status_code, $valid_codes, true ) ) {
				$status_code = 301;
			}

			if ( 410 !== $status_code && empty( $url_to ) ) {
				continue;
			}

			foreach ( $sources as $source ) {
				$pattern    = isset( $source['pattern'] ) ? $source['pattern'] : '';
				$comparison = isset( $source['comparison'] ) ? $source['comparison'] : 'exact';

				if ( empty( $pattern ) ) {
					continue;
				}

				$is_regex = ( 'regex' === $comparison );

				if ( 'contains' === $comparison ) {
					$pattern  = '.*' . preg_quote( $pattern, '/' ) . '.*';
					$is_regex = true;
				} elseif ( 'start' === $comparison ) {
					$pattern  = '^' . preg_quote( $pattern, '/' ) . '.*';
					$is_regex = true;
				} elseif ( 'end' === $comparison ) {
					$pattern  = '.*' . preg_quote( $pattern, '/' ) . '$';
					$is_regex = true;
				}

				$parsed[] = array(
					'url_from'    => sanitize_text_field( $pattern ),
					'url_to'      => esc_url_raw( $url_to ),
					'status_code' => $status_code,
					'is_regex'    => $is_regex,
				);
			}
		}

		return $parsed;
	}
}
