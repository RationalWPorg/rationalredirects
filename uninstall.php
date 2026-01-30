<?php
/**
 * RationalRedirects Uninstall
 *
 * Handles cleanup when the plugin is uninstalled.
 *
 * @package RationalRedirects
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete the redirects table.
$rationalredirects_table = $wpdb->prefix . 'rationalredirects_redirects';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$rationalredirects_table}`" );

// Delete plugin options.
delete_option( 'rationalredirects_settings' );
delete_option( 'rationalredirects_db_version' );

// Delete transients.
delete_transient( 'rationalredirects_regex_redirects' );
