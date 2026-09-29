<?php
/**
 * Daily view and play totals.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

use RSFV\Options;
use RSFV\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Retention, increments, pruning, and the report payload.
 */
class Stats {

	/**
	 * Places a video can be shown.
	 *
	 * @return string[]
	 */
	public static function surfaces() {
		$surfaces = array( 'thumbnail', 'shortcode', 'elementor', 'bricks', 'block', 'woo_gallery', 'woo_archive', 'sticky' );

		/**
		 * Surfaces the collector will accept.
		 *
		 * @since 0.90.0
		 *
		 * @param string[] $surfaces Surface keys.
		 */
		$filtered = apply_filters( 'rsfv_analytics_surfaces', $surfaces );

		if ( ! is_array( $filtered ) ) {
			return $surfaces;
		}

		$clean = array();

		foreach ( $filtered as $surface ) {
			$key = sanitize_key( $surface );

			if ( '' !== $key ) {
				$clean[] = $key;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Event names the collector will accept.
	 *
	 * @return string[]
	 */
	public static function event_types() {
		/**
		 * Event names analytics will accept. Free stores view and play.
		 *
		 * @since 0.90.0
		 *
		 * @param string[] $types Event keys.
		 */
		$filtered = apply_filters( 'rsfv_analytics_event_types', array( 'view', 'play' ) );

		if ( ! is_array( $filtered ) ) {
			return array( 'view', 'play' );
		}

		$clean = array();

		foreach ( $filtered as $type ) {
			$key = sanitize_key( $type );

			if ( '' !== $key ) {
				$clean[] = $key;
			}
		}

		$clean = array_values( array_unique( $clean ) );

		if ( ! in_array( 'view', $clean, true ) ) {
			$clean[] = 'view';
		}

		if ( ! in_array( 'play', $clean, true ) ) {
			$clean[] = 'play';
		}

		return $clean;
	}

	/**
	 * How many days of totals to keep.
	 *
	 * Default is 14. Return 0 from the filter to keep all time.
	 * Any other value below 14 stays 14. This plugin does not list longer windows.
	 *
	 * @return int
	 */
	public static function retention_days() {
		/**
		 * Retention window in days.
		 *
		 * @since 0.90.0
		 *
		 * @param int $days Default 14. Zero means all time.
		 */
		$days = apply_filters( 'rsfv_analytics_retention_days', 14 );

		if ( 0 === $days ) {
			return 0;
		}

		$days = absint( $days );

		return $days < 14 ? 14 : $days;
	}

	/**
	 * Whether counting is turned on. Missing option means on.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) Options::get_instance()->get( 'analytics_enabled', true );
	}

	/**
	 * Whether logged-in editors are left out of the counts. Missing option means yes.
	 *
	 * @return bool
	 */
	public static function ignore_editors() {
		return (bool) Options::get_instance()->get( 'analytics_ignore_editors', true );
	}

	/**
	 * Add one view or one play for today.
	 *
	 * @param int    $video_id Registry id.
	 * @param string $event    view or play.
	 * @param string $surface  Surface key.
	 * @return void
	 */
	public static function increment( $video_id, $event, $surface ) {
		if ( ! Install::tables_ready() ) {
			return;
		}

		if ( ! in_array( $event, array( 'view', 'play' ), true ) ) {
			return;
		}

		if ( ! in_array( $surface, self::surfaces(), true ) ) {
			return;
		}

		global $wpdb;

		$table = Install::stats_table();
		$views = ( 'view' === $event ) ? 1 : 0;
		$plays = ( 'play' === $event ) ? 1 : 0;
		$date  = current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table; increment is atomic.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (video_id, stat_date, surface, views, plays) VALUES (%d, %s, %s, %d, %d) ON DUPLICATE KEY UPDATE views = views + %d, plays = plays + %d",
				absint( $video_id ),
				$date,
				$surface,
				$views,
				$plays,
				$views,
				$plays
			)
		);
	}

	/**
	 * Delete daily rows older than the current retention window.
	 *
	 * @return void
	 */
	public static function prune() {
		if ( ! Install::tables_ready() || 0 === self::retention_days() ) {
			return;
		}

		global $wpdb;

		$table  = Install::stats_table();
		$cutoff = self::window_start()->format( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table; retention cleanup.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE stat_date < %s",
				$cutoff
			)
		);
	}

	/**
	 * Oldest day that still has counts, or today when the table is empty.
	 *
	 * @param string $fallback Y-m-d used when there are no rows.
	 * @return string
	 */
	private static function earliest_date( $fallback ) {
		global $wpdb;

		$table = Install::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table.
		$date = $wpdb->get_var( "SELECT MIN(stat_date) FROM {$table}" );

		if ( ! is_string( $date ) || '' === $date || '0000-00-00' === $date ) {
			return $fallback;
		}

		return $date;
	}

	/**
	 * First day included in the current window.
	 *
	 * @return \DateTimeImmutable
	 */
	public static function window_start() {
		$days  = self::retention_days();
		$today = current_datetime();

		if ( $days < 1 ) {
			return $today->setTime( 0, 0, 0 );
		}

		return $today->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );
	}

	/**
	 * Report payload for the Video Tools tab.
	 *
	 * Requested dates are clamped inside the retention window.
	 *
	 * @param string $from Requested start Y-m-d.
	 * @param string $to   Requested end Y-m-d.
	 * @return array
	 */
	public static function report( $from = '', $to = '' ) {
		$max   = self::retention_days();
		$today = current_datetime();
		$end   = $today->format( 'Y-m-d' );

		if ( 0 === $max ) {
			$window_start = self::earliest_date( $end );
		} else {
			$window_start = $today->modify( '-' . ( $max - 1 ) . ' days' )->format( 'Y-m-d' );
		}

		$start = self::is_date( $from ) ? $from : $window_start;
		$until = self::is_date( $to ) ? $to : $end;

		if ( $start < $window_start ) {
			$start = $window_start;
		}

		if ( $until > $end ) {
			$until = $end;
		}

		if ( $start > $until ) {
			$start = $window_start;
			$until = $end;
		}

		/**
		 * Report date range. Add-ons may widen the window before the clamp below.
		 *
		 * @since 0.90.0
		 *
		 * @param array  $range from, to, windowFrom, windowTo, retentionDays.
		 * @param string $from  Requested start.
		 * @param string $to    Requested end.
		 */
		$range = apply_filters(
			'rsfv_analytics_report_range',
			array(
				'from'          => $start,
				'to'            => $until,
				'windowFrom'    => $window_start,
				'windowTo'      => $end,
				'retentionDays' => $max,
			),
			$from,
			$to
		);

		if ( is_array( $range ) ) {
			if ( self::is_date( $range['windowFrom'] ?? '' ) ) {
				$window_start = $range['windowFrom'];
			}
			if ( self::is_date( $range['windowTo'] ?? '' ) ) {
				$end = $range['windowTo'];
			}
			if ( self::is_date( $range['from'] ?? '' ) ) {
				$start = $range['from'];
			}
			if ( self::is_date( $range['to'] ?? '' ) ) {
				$until = $range['to'];
			}
			if ( isset( $range['retentionDays'] ) && ( 0 === $range['retentionDays'] || absint( $range['retentionDays'] ) >= 14 ) ) {
				$max = 0 === $range['retentionDays'] ? 0 : absint( $range['retentionDays'] );
			}
		}

		if ( $start < $window_start ) {
			$start = $window_start;
		}

		if ( $until > $end ) {
			$until = $end;
		}

		if ( $start > $until ) {
			$start = $window_start;
			$until = $end;
		}

		$summary   = self::summary( $start, $until );
		$series    = self::series( $start, $until );
		$surfaces  = self::grouped_counts( 'surface', $start, $until );
		$providers = self::grouped_providers( $start, $until );
		$videos    = self::video_rows( $start, $until );

		$payload = array(
			'enabled'       => self::enabled(),
			'isPro'         => Plugin::get_instance()->has_pro_active(),
			'retentionDays' => $max,
			'from'          => $start,
			'to'            => $until,
			'windowFrom'    => $window_start,
			'windowTo'      => $end,
			'settingsUrl'   => admin_url( 'admin.php?page=rsfv-settings&tab=analytics' ),
			'summary'       => $summary,
			'series'        => $series,
			'surfaces'      => $surfaces,
			'providers'     => $providers,
			'videos'        => $videos,
		);

		/**
		 * Extra report panels.
		 *
		 * @since 0.90.0
		 *
		 * @param array $sections Extra sections.
		 * @param array $payload  Report payload.
		 */
		$sections = apply_filters( 'rsfv_analytics_report_sections', array(), $payload );

		$payload['sections'] = is_array( $sections ) ? $sections : array();

		/**
		 * Full report payload. Add-ons may attach their own numbers.
		 *
		 * @since 0.90.0
		 *
		 * @param array $payload Report payload.
		 */
		$filtered = apply_filters( 'rsfv_analytics_report', $payload );

		return is_array( $filtered ) ? $filtered : $payload;
	}

	/**
	 * Whether a string is Y-m-d.
	 *
	 * @param string $value Date.
	 * @return bool
	 */
	private static function is_date( $value ) {
		return is_string( $value ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
	}

	/**
	 * Headline numbers.
	 *
	 * @param string $start Start date Y-m-d.
	 * @param string $end   End date Y-m-d.
	 * @return array
	 */
	private static function summary( $start, $end ) {
		global $wpdb;

		$videos = Install::videos_table();
		$stats  = Install::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table.
		$video_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$videos} WHERE status = 'active'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table.
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(views), 0) AS views, COALESCE(SUM(plays), 0) AS plays FROM {$stats} WHERE stat_date >= %s AND stat_date <= %s",
				$start,
				$end
			),
			ARRAY_A
		);

		$views = isset( $totals['views'] ) ? (int) $totals['views'] : 0;
		$plays = isset( $totals['plays'] ) ? (int) $totals['plays'] : 0;

		return array(
			'videos'   => $video_count,
			'views'    => $views,
			'plays'    => $plays,
			'playRate' => self::play_rate( $views, $plays ),
		);
	}

	/**
	 * One point per day, including days with no counts.
	 *
	 * @param string $start Start date Y-m-d.
	 * @param string $end   End date Y-m-d.
	 * @return array
	 */
	private static function series( $start, $end ) {
		global $wpdb;

		$table = Install::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date, COALESCE(SUM(views), 0) AS views, COALESCE(SUM(plays), 0) AS plays FROM {$table} WHERE stat_date >= %s AND stat_date <= %s GROUP BY stat_date",
				$start,
				$end
			),
			ARRAY_A
		);

		$by_date = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$by_date[ $row['stat_date'] ] = array(
					'views' => (int) $row['views'],
					'plays' => (int) $row['plays'],
				);
			}
		}

		$series = array();
		$cursor = new \DateTimeImmutable( $start, wp_timezone() );
		$last   = new \DateTimeImmutable( $end, wp_timezone() );

		while ( $cursor <= $last ) {
			$key      = $cursor->format( 'Y-m-d' );
			$series[] = array(
				'date'  => $key,
				'views' => isset( $by_date[ $key ] ) ? $by_date[ $key ]['views'] : 0,
				'plays' => isset( $by_date[ $key ] ) ? $by_date[ $key ]['plays'] : 0,
			);
			$cursor = $cursor->modify( '+1 day' );
		}

		return $series;
	}

	/**
	 * Totals grouped by surface.
	 *
	 * @param string $column surface.
	 * @param string $start  Start date.
	 * @param string $end    End date.
	 * @return array
	 */
	private static function grouped_counts( $column, $start, $end ) {
		global $wpdb;

		$table = Install::stats_table();

		if ( 'surface' !== $column ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table. Column name is a fixed allowlisted value.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT surface AS group_key, COALESCE(SUM(views), 0) AS views, COALESCE(SUM(plays), 0) AS plays FROM {$table} WHERE stat_date >= %s AND stat_date <= %s GROUP BY surface ORDER BY views DESC",
				$start,
				$end
			),
			ARRAY_A
		);

		$items = array();

		if ( ! is_array( $rows ) ) {
			return $items;
		}

		foreach ( $rows as $row ) {
			$key     = sanitize_key( $row['group_key'] );
			$items[] = array(
				'key'   => $key,
				'label' => self::surface_label( $key ),
				'views' => (int) $row['views'],
				'plays' => (int) $row['plays'],
			);
		}

		return $items;
	}

	/**
	 * Totals grouped by provider.
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 * @return array
	 */
	private static function grouped_providers( $start, $end ) {
		global $wpdb;

		$videos = Install::videos_table();
		$stats  = Install::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics tables.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.provider AS group_key, COALESCE(SUM(s.views), 0) AS views, COALESCE(SUM(s.plays), 0) AS plays FROM {$stats} s INNER JOIN {$videos} v ON v.id = s.video_id WHERE s.stat_date >= %s AND s.stat_date <= %s GROUP BY v.provider ORDER BY views DESC",
				$start,
				$end
			),
			ARRAY_A
		);

		$items = array();

		if ( ! is_array( $rows ) ) {
			return $items;
		}

		foreach ( $rows as $row ) {
			$key     = sanitize_key( $row['group_key'] );
			$items[] = array(
				'key'   => $key,
				'label' => self::provider_label( $key ),
				'views' => (int) $row['views'],
				'plays' => (int) $row['plays'],
			);
		}

		return $items;
	}

	/**
	 * Per-video rows for the table.
	 *
	 * @param string $start Start date.
	 * @param string $end   End date.
	 * @return array
	 */
	private static function video_rows( $start, $end ) {
		global $wpdb;

		$videos = Install::videos_table();
		$stats  = Install::stats_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics tables.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.id, v.title, v.context, v.provider, v.status, v.object_id, COALESCE(SUM(s.views), 0) AS views, COALESCE(SUM(s.plays), 0) AS plays FROM {$videos} v LEFT JOIN {$stats} s ON s.video_id = v.id AND s.stat_date >= %s AND s.stat_date <= %s GROUP BY v.id, v.title, v.context, v.provider, v.status, v.object_id HAVING v.status = 'active' OR SUM(s.views) > 0 OR SUM(s.plays) > 0 ORDER BY views DESC, v.title ASC LIMIT 100",
				$start,
				$end
			),
			ARRAY_A
		);

		$items = array();

		if ( ! is_array( $rows ) ) {
			return $items;
		}

		foreach ( $rows as $row ) {
			$views     = (int) $row['views'];
			$plays     = (int) $row['plays'];
			$object_id = absint( $row['object_id'] );
			$edit      = get_edit_post_link( $object_id, 'raw' );
			$title     = sanitize_text_field( $row['title'] );

			if ( '' === $title ) {
				$title = sprintf(
					/* translators: %d: registry id. */
					__( 'Video #%d', 'rsfv' ),
					absint( $row['id'] )
				);
			}

			$items[] = array(
				'id'       => absint( $row['id'] ),
				'title'    => $title,
				'kind'     => ( Registry::CONTEXT_STICKY === $row['context'] ) ? __( 'Sticky', 'rsfv' ) : __( 'Featured', 'rsfv' ),
				'kindKey'  => sanitize_key( $row['context'] ),
				'provider' => self::provider_label( sanitize_key( $row['provider'] ) ),
				'status'   => ( 'inactive' === $row['status'] ) ? 'inactive' : 'active',
				'views'    => $views,
				'plays'    => $plays,
				'playRate' => self::play_rate( $views, $plays ),
				'editUrl'  => $edit ? esc_url_raw( $edit ) : '',
			);
		}

		return $items;
	}

	/**
	 * Play rate as a whole percent.
	 *
	 * @param int $views Views.
	 * @param int $plays Plays.
	 * @return int
	 */
	private static function play_rate( $views, $plays ) {
		if ( $views < 1 ) {
			return 0;
		}

		return (int) round( ( $plays / $views ) * 100 );
	}

	/**
	 * Translated surface label.
	 *
	 * @param string $key Surface key.
	 * @return string
	 */
	public static function surface_label( $key ) {
		$labels = array(
			'thumbnail'   => __( 'Featured image', 'rsfv' ),
			'shortcode'   => __( 'Shortcode', 'rsfv' ),
			'elementor'   => __( 'Elementor', 'rsfv' ),
			'bricks'      => __( 'Bricks', 'rsfv' ),
			'block'       => __( 'Block', 'rsfv' ),
			'woo_gallery' => __( 'Product gallery', 'rsfv' ),
			'woo_archive' => __( 'Shop', 'rsfv' ),
			'sticky'      => __( 'Sticky video', 'rsfv' ),
		);

		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	/**
	 * Translated provider label.
	 *
	 * @param string $key Provider key.
	 * @return string
	 */
	public static function provider_label( $key ) {
		$labels = array(
			'self'        => __( 'Self-hosted', 'rsfv' ),
			'youtube'     => __( 'YouTube', 'rsfv' ),
			'vimeo'       => __( 'Vimeo', 'rsfv' ),
			'dailymotion' => __( 'Dailymotion', 'rsfv' ),
			'unknown'     => __( 'Other', 'rsfv' ),
		);

		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}
}
