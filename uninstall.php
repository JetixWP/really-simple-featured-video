<?php
/**
 * Remove analytics tables when the plugin is deleted.
 *
 * @package RSFV
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove analytics data from the current site.
 *
 * @return void
 */
function rsfv_uninstall_site() {
	global $wpdb;

	$videos = $wpdb->prefix . 'rsfv_videos';
	$stats  = $wpdb->prefix . 'rsfv_stats_daily';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall drops this plugin's analytics tables. Names are prefixed constants.
	$wpdb->query( "DROP TABLE IF EXISTS {$videos}" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall drops this plugin's analytics tables. Names are prefixed constants.
	$wpdb->query( "DROP TABLE IF EXISTS {$stats}" );

	delete_option( 'rsfv_analytics_backfill' );
	wp_clear_scheduled_hook( 'rsfv_analytics_prune' );

	$options = get_option( 'rsfv_options' );

	if ( is_array( $options ) ) {
		unset( $options['analytics_enabled'], $options['analytics_ignore_editors'], $options['analytics_retention'] );
		update_option( 'rsfv_options', $options );
	}
}

// Deleting a plugin removes it from the whole network, so every site's data goes with it.
if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $rsfv_site_id ) {
		switch_to_blog( (int) $rsfv_site_id );
		rsfv_uninstall_site();
		restore_current_blog();
	}
} else {
	rsfv_uninstall_site();
}
