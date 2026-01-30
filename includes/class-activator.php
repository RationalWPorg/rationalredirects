<?php
/**
 * RationalRedirects Activator Class
 *
 * Handles plugin activation and deactivation.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RationalRedirects_Activator {

	/**
	 * Current database schema version.
	 *
	 * Increment this when making schema changes.
	 *
	 * @var int
	 */
	const DB_VERSION = 1;

	/**
	 * Option name for storing the database version.
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'rationalredirects_db_version';

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::set_default_options();
		self::create_tables();
		self::run_upgrades();
	}

	/**
	 * Check and run upgrades if needed.
	 *
	 * Called on plugins_loaded to handle upgrades without reactivation.
	 */
	public static function maybe_upgrade() {
		$installed_version = (int) get_option( self::DB_VERSION_OPTION, 0 );

		if ( $installed_version < self::DB_VERSION ) {
			self::run_upgrades();
		}
	}

	/**
	 * Run all pending database upgrades.
	 */
	private static function run_upgrades() {
		// Update stored version.
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Create required database tables.
	 */
	private static function create_tables() {
		if ( class_exists( 'RationalRedirects_Redirects' ) ) {
			RationalRedirects_Redirects::create_table();
		}
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate() {
		// Clear redirect transients.
		delete_transient( 'rationalredirects_regex_redirects' );
	}

	/**
	 * Set default options if they don't exist.
	 */
	private static function set_default_options() {
		if ( false === get_option( RationalRedirects_Settings::OPTION_NAME ) ) {
			$settings = new RationalRedirects_Settings();
			add_option( RationalRedirects_Settings::OPTION_NAME, $settings->get_defaults() );
		}
	}
}
