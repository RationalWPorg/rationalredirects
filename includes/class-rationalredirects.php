<?php
/**
 * Main RationalRedirects Class
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RationalRedirects {

	/**
	 * Singleton instance.
	 *
	 * @var RationalRedirects|null
	 */
	private static $instance = null;

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
	 * Admin instance.
	 *
	 * @var RationalRedirects_Admin
	 */
	private $admin;

	/**
	 * Import manager instance.
	 *
	 * @var RationalRedirects_Import_Manager
	 */
	private $import_manager;

	/**
	 * Import admin instance.
	 *
	 * @var RationalRedirects_Import_Admin
	 */
	private $import_admin;

	/**
	 * Get the singleton instance.
	 *
	 * @return RationalRedirects
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings       = new RationalRedirects_Settings();
		$this->redirects      = new RationalRedirects_Redirects( $this->settings );
		$this->import_manager = new RationalRedirects_Import_Manager( $this->redirects );

		if ( is_admin() ) {
			$this->admin        = new RationalRedirects_Admin( $this->settings, $this->redirects );
			$this->import_admin = new RationalRedirects_Import_Admin( $this->import_manager );
		}
	}

	/**
	 * Get settings instance.
	 *
	 * @return RationalRedirects_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get redirects instance.
	 *
	 * @return RationalRedirects_Redirects
	 */
	public function get_redirects() {
		return $this->redirects;
	}

	/**
	 * Get admin instance.
	 *
	 * @return RationalRedirects_Admin|null
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get import manager instance.
	 *
	 * @return RationalRedirects_Import_Manager
	 */
	public function get_import_manager() {
		return $this->import_manager;
	}

	/**
	 * Get import admin instance.
	 *
	 * @return RationalRedirects_Import_Admin|null
	 */
	public function get_import_admin() {
		return $this->import_admin;
	}
}
