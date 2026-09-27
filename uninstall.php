<?php
/**
 * Remove analytics tables when the plugin is deleted.
 *
 * @package RSFV
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$videos = $wpdb->prefix . 'rsfv_videos';
$stats  = $wpdb->prefix . 'rsfv_stats_daily';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall drops this plugin's analytics tables. Names are prefixed constants.
$wpdb->query( "DROP TABLE IF EXISTS {$videos}" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall drops this plugin's analytics tables. Names are prefixed constants.
$wpdb->query( "DROP TABLE IF EXISTS {$stats}" );

delete_option( 'rsfv_analytics_backfill' );
wp_clear_scheduled_hook( 'rsfv_analytics_prune' );

$options = get_option( 'rsfv_options', array() );

if ( is_array( $options ) ) {
	unset( $options['analytics_enabled'], $options['analytics_ignore_editors'], $options['analytics_retention'] );
	update_option( 'rsfv_options', $options );
}
