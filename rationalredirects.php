<?php
/**
 * Plugin Name: RationalRedirects
 * Plugin URI: https://rationalwp.com/plugins/rationalredirects
 * Description: Simple, fast URL redirects with regex support and automatic slug change tracking.
 * Version: 1.0.0
 * Author: RationalWP
 * Author URI: https://rationalwp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rationalredirects
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RATIONALREDIRECTS_VERSION', '1.0.0' );
define( 'RATIONALREDIRECTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RATIONALREDIRECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RATIONALREDIRECTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load shared RationalWP admin menu.
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/rationalwp-admin-menu.php';

// Load plugin classes.
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/class-settings.php';
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/class-redirects.php';
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/class-admin.php';
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/class-activator.php';
require_once RATIONALREDIRECTS_PLUGIN_DIR . 'includes/class-rationalredirects.php';

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'RationalRedirects_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RationalRedirects_Activator', 'deactivate' ) );

// Check for database upgrades on every load (runs early, before plugin init).
add_action( 'plugins_loaded', array( 'RationalRedirects_Activator', 'maybe_upgrade' ), 5 );

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'RationalRedirects', 'get_instance' ) );
