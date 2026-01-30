<?php
/**
 * RationalRedirects SEOPress Redirects Importer
 *
 * Imports redirects from SEOPress.
 * Adapted from RationalSEO's SEOPress importer - redirect methods only.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEOPress redirects importer class.
 */
class RationalRedirects_SEOPress_Redirects_Importer implements RationalRedirects_Importer_Interface {

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
		return 'seopress';
	}

	/**
	 * Get the display name for this importer.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'SEOPress';
	}

	/**
	 * Get the description for this importer.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Import redirects from SEOPress.', 'rationalredirects' );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_seopress_redirections_enabled' AND meta_value = 'yes'"
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
		$redirects = $this->get_seopress_redirects();

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
		$redirects     = $this->get_seopress_redirects();

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
					__( 'Successfully imported %d redirects from SEOPress.', 'rationalredirects' ),
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
	 * Get SEOPress redirects from post meta.
	 *
	 * Adapted from RationalSEO_SEOPress_Importer::get_seopress_redirects()
	 *
	 * @return array Parsed redirects array.
	 */
	private function get_seopress_redirects() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_seopress_redirections_enabled' AND meta_value = 'yes'"
		);

		if ( empty( $post_ids ) ) {
			return array();
		}

		$redirects = array();

		foreach ( $post_ids as $post_id ) {
			$redirect_value = get_post_meta( $post_id, '_seopress_redirections_value', true );
			$redirect_type  = get_post_meta( $post_id, '_seopress_redirections_type', true );

			if ( empty( $redirect_value ) ) {
				continue;
			}

			$source_url = get_permalink( $post_id );
			if ( ! $source_url ) {
				continue;
			}

			$parsed_url = wp_parse_url( $source_url );
			$url_from   = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';

			$status_code = absint( $redirect_type );
			$valid_codes = array( 301, 302, 307 );
			if ( ! in_array( $status_code, $valid_codes, true ) ) {
				$status_code = 301;
			}

			$redirects[] = array(
				'url_from'    => sanitize_text_field( $url_from ),
				'url_to'      => esc_url_raw( $redirect_value ),
				'status_code' => $status_code,
				'is_regex'    => false,
			);
		}

		return $redirects;
	}
}
