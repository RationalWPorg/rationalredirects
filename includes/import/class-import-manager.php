<?php
/**
 * RationalRedirects Import Manager Class
 *
 * Registry and orchestration for redirect importers.
 *
 * @package RationalRedirects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import manager class.
 */
class RationalRedirects_Import_Manager {

	/**
	 * Registered importers.
	 *
	 * @var array<string, RationalRedirects_Importer_Interface>
	 */
	private $importers = array();

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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Allow other plugins/themes to register importers.
		add_action( 'rationalredirects_register_importers', array( $this, 'register_core_importers' ), 10 );

		// Fire the registration hook after plugins are loaded.
		add_action( 'init', array( $this, 'trigger_importer_registration' ), 20 );
	}

	/**
	 * Trigger importer registration.
	 */
	public function trigger_importer_registration() {
		/**
		 * Fires when importers should be registered.
		 *
		 * @param RationalRedirects_Import_Manager $manager The import manager instance.
		 */
		do_action( 'rationalredirects_register_importers', $this );
	}

	/**
	 * Register core importers.
	 *
	 * This is called during the rationalredirects_register_importers action.
	 *
	 * @param RationalRedirects_Import_Manager $manager The import manager instance.
	 */
	public function register_core_importers( $manager ) {
		$manager->register( new RationalRedirects_Yoast_Redirects_Importer( $this->redirects ) );
		$manager->register( new RationalRedirects_RankMath_Redirects_Importer( $this->redirects ) );
		$manager->register( new RationalRedirects_AIOSEO_Redirects_Importer( $this->redirects ) );
		$manager->register( new RationalRedirects_SEOPress_Redirects_Importer( $this->redirects ) );
		$manager->register( new RationalRedirects_Redirection_Importer( $this->redirects ) );
	}

	/**
	 * Register an importer.
	 *
	 * @param RationalRedirects_Importer_Interface $importer Importer instance.
	 * @return bool True on success, false if already registered.
	 */
	public function register( RationalRedirects_Importer_Interface $importer ) {
		$slug = $importer->get_slug();

		if ( isset( $this->importers[ $slug ] ) ) {
			return false;
		}

		$this->importers[ $slug ] = $importer;
		return true;
	}

	/**
	 * Unregister an importer.
	 *
	 * @param string $slug Importer slug.
	 * @return bool True on success, false if not found.
	 */
	public function unregister( $slug ) {
		if ( ! isset( $this->importers[ $slug ] ) ) {
			return false;
		}

		unset( $this->importers[ $slug ] );
		return true;
	}

	/**
	 * Get an importer by slug.
	 *
	 * @param string $slug Importer slug.
	 * @return RationalRedirects_Importer_Interface|null Importer or null if not found.
	 */
	public function get_importer( $slug ) {
		return isset( $this->importers[ $slug ] ) ? $this->importers[ $slug ] : null;
	}

	/**
	 * Get all registered importers.
	 *
	 * @return array<string, RationalRedirects_Importer_Interface>
	 */
	public function get_all_importers() {
		return $this->importers;
	}

	/**
	 * Get all available importers (those with importable data).
	 *
	 * @return array<string, RationalRedirects_Importer_Interface>
	 */
	public function get_available_importers() {
		$available = array();

		foreach ( $this->importers as $slug => $importer ) {
			if ( $importer->is_available() ) {
				$available[ $slug ] = $importer;
			}
		}

		return $available;
	}

	/**
	 * Check if any importers are available.
	 *
	 * @return bool
	 */
	public function has_available_importers() {
		foreach ( $this->importers as $importer ) {
			if ( $importer->is_available() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get importer data for display.
	 *
	 * Returns an array of importer information suitable for the admin UI.
	 *
	 * @param bool $only_available Whether to only include available importers.
	 * @return array
	 */
	public function get_importers_for_display( $only_available = true ) {
		$importers = $only_available ? $this->get_available_importers() : $this->get_all_importers();
		$display   = array();

		foreach ( $importers as $slug => $importer ) {
			$display[ $slug ] = array(
				'slug'        => $slug,
				'name'        => $importer->get_name(),
				'description' => $importer->get_description(),
				'available'   => $importer->is_available(),
				'count'       => $importer->is_available() ? $importer->get_redirect_count() : 0,
			);
		}

		return $display;
	}

	/**
	 * Preview an import.
	 *
	 * @param string $slug Importer slug.
	 * @return RationalRedirects_Import_Result
	 */
	public function preview( $slug ) {
		$importer = $this->get_importer( $slug );

		if ( ! $importer ) {
			return RationalRedirects_Import_Result::error(
				__( 'Importer not found.', 'rationalredirects' )
			);
		}

		if ( ! $importer->is_available() ) {
			return RationalRedirects_Import_Result::error(
				__( 'No data available to import from this source.', 'rationalredirects' )
			);
		}

		return $importer->preview();
	}

	/**
	 * Run an import.
	 *
	 * @param string $slug    Importer slug.
	 * @param array  $options Import options.
	 * @return RationalRedirects_Import_Result
	 */
	public function import( $slug, $options = array() ) {
		$importer = $this->get_importer( $slug );

		if ( ! $importer ) {
			return RationalRedirects_Import_Result::error(
				__( 'Importer not found.', 'rationalredirects' )
			);
		}

		if ( ! $importer->is_available() ) {
			return RationalRedirects_Import_Result::error(
				__( 'No data available to import from this source.', 'rationalredirects' )
			);
		}

		return $importer->import( $options );
	}

	/**
	 * Get redirects instance.
	 *
	 * @return RationalRedirects_Redirects
	 */
	public function get_redirects() {
		return $this->redirects;
	}
}
