<?php
/**
 * Analytics tables and scheduled cleanup.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the analytics tables and the daily prune event.
 */
class Install {

	const CRON_HOOK       = 'rsfv_analytics_prune';
	const BACKFILL_OPTION = 'rsfv_analytics_backfill';

	/**
	 * Activation: tables, cron, and a backfill queue for videos already saved.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cron();
		self::queue_backfill();
	}

	/**
	 * Deactivation clears the cron and leaves stored counts in place.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Create or update both analytics tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$videos          = self::videos_table();
		$stats           = self::stats_table();

		$sql = "CREATE TABLE {$videos} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			context varchar(20) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			object_type varchar(20) NOT NULL DEFAULT '',
			source varchar(20) NOT NULL DEFAULT '',
			provider varchar(20) NOT NULL DEFAULT '',
			asset_key varchar(191) NOT NULL DEFAULT '',
			title text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY context_object (context,object_id),
			KEY status (status)
		) {$charset_collate};
		CREATE TABLE {$stats} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			video_id bigint(20) unsigned NOT NULL DEFAULT 0,
			stat_date date NOT NULL DEFAULT '0000-00-00',
			surface varchar(20) NOT NULL DEFAULT '',
			views bigint(20) unsigned NOT NULL DEFAULT 0,
			plays bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY video_day_surface (video_id,stat_date,surface),
			KEY stat_date (stat_date)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Schedule the daily prune when it is not already scheduled.
	 *
	 * @return void
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Start the backfill queue once. A finished queue is left alone.
	 *
	 * @return void
	 */
	public static function queue_backfill() {
		if ( false !== get_option( self::BACKFILL_OPTION, false ) ) {
			return;
		}

		add_option(
			self::BACKFILL_OPTION,
			array(
				'featured_last_id' => 0,
				'sticky_last_id'   => 0,
				'phase'            => 'featured',
				'done'             => false,
			),
			'',
			false
		);
	}

	/**
	 * Videos table name.
	 *
	 * @return string
	 */
	public static function videos_table() {
		global $wpdb;

		return $wpdb->prefix . 'rsfv_videos';
	}

	/**
	 * Daily stats table name.
	 *
	 * @return string
	 */
	public static function stats_table() {
		global $wpdb;

		return $wpdb->prefix . 'rsfv_stats_daily';
	}

	/**
	 * Whether both tables exist.
	 *
	 * @return bool
	 */
	public static function tables_ready() {
		static $ready = null;

		if ( null !== $ready ) {
			return $ready;
		}

		global $wpdb;

		$videos = self::videos_table();
		$stats  = self::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom analytics tables; existence check before any write.
		$found_videos = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $videos ) ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom analytics tables; existence check before any write.
		$found_stats = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $stats ) ) );

		$ready = ( $found_videos === $videos && $found_stats === $stats );

		return $ready;
	}
}
