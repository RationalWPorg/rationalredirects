<?php
/**
 * RationalRedirects Importer Interface
 *
 * Contract that all redirect importers must implement.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for redirect importers.
 */
interface RationalRedirects_Importer_Interface {

	/**
	 * Get the unique slug for this importer.
	 *
	 * @return string Importer slug (e.g., 'yoast', 'rankmath').
	 */
	public function get_slug();

	/**
	 * Get the display name for this importer.
	 *
	 * @return string Importer name (e.g., 'Yoast SEO Premium').
	 */
	public function get_name();

	/**
	 * Get the description for this importer.
	 *
	 * @return string Description of what this importer handles.
	 */
	public function get_description();

	/**
	 * Check if this importer is available (source data exists).
	 *
	 * @return bool True if importable data exists, false otherwise.
	 */
	public function is_available();

	/**
	 * Get the count of redirects available to import.
	 *
	 * @return int Number of redirects available.
	 */
	public function get_redirect_count();

	/**
	 * Preview the import without making changes.
	 *
	 * @return RationalRedirects_Import_Result Preview result with sample data.
	 */
	public function preview();

	/**
	 * Perform the import.
	 *
	 * @param array $options Import options (e.g., ['skip_existing' => true]).
	 * @return RationalRedirects_Import_Result Import result with success/failure counts.
	 */
	public function import( $options = array() );
}
