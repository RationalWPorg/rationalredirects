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
		$this->settings  = new RationalRedirects_Settings();
		$this->redirects = new RationalRedirects_Redirects( $this->settings );

		if ( is_admin() ) {
			$this->admin = new RationalRedirects_Admin( $this->settings, $this->redirects );
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
}
