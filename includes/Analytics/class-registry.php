<?php
/**
 * Registry of featured and sticky videos.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

use RSFV\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * One row per video slot. Writers reach this through post meta, not REST.
 */
class Registry {

	const CONTEXT_FEATURED = 'featured';
	const CONTEXT_STICKY   = 'sticky';
	const STICKY_POST_TYPE = 'rsfv_floating_video';

	/**
	 * Read the final featured-video meta and upsert one row.
	 *
	 * @param int $post_id Post ID.
	 * @return int Registry id, or 0 when there is nothing to store.
	 */
	public static function sync_featured( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! Install::tables_ready() ) {
			return 0;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return 0;
		}

		if ( 'revision' === $post->post_type || self::STICKY_POST_TYPE === $post->post_type || wp_is_post_autosave( $post_id ) ) {
			return 0;
		}

		$source = get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );
		$source = $source ? sanitize_key( $source ) : 'self';

		if ( ! in_array( $source, array( 'self', 'embed' ), true ) ) {
			$source = 'self';
		}

		$attachment_id = absint( get_post_meta( $post_id, RSFV_META_KEY, true ) );
		$embed_url     = esc_url_raw( (string) get_post_meta( $post_id, RSFV_EMBED_META_KEY, true ) );
		$active        = ( 'self' === $source && $attachment_id > 0 ) || ( 'embed' === $source && '' !== $embed_url );

		return self::upsert(
			self::CONTEXT_FEATURED,
			$post_id,
			$post,
			$source,
			$active,
			$attachment_id,
			$embed_url
		);
	}

	/**
	 * Read the final sticky-video meta and upsert one row.
	 *
	 * @param int $post_id Sticky video post ID.
	 * @return int Registry id, or 0 when there is nothing to store.
	 */
	public static function sync_sticky( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! Install::tables_ready() ) {
			return 0;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || self::STICKY_POST_TYPE !== $post->post_type ) {
			return 0;
		}

		$source = get_post_meta( $post_id, '_rsfv_fv_video_source', true );
		$source = $source ? sanitize_key( $source ) : 'self';

		if ( ! in_array( $source, array( 'self', 'embed' ), true ) ) {
			$source = 'self';
		}

		$attachment_id = absint( get_post_meta( $post_id, '_rsfv_fv_video_id', true ) );
		$embed_url     = esc_url_raw( (string) get_post_meta( $post_id, '_rsfv_fv_embed_url', true ) );
		$active        = ( 'self' === $source && $attachment_id > 0 ) || ( 'embed' === $source && '' !== $embed_url );

		if ( 'publish' !== $post->post_status ) {
			$active = false;
		}

		return self::upsert(
			self::CONTEXT_STICKY,
			$post_id,
			$post,
			$source,
			$active,
			$attachment_id,
			$embed_url
		);
	}

	/**
	 * Registry id for a featured slot, creating the row when it is missing.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function ensure_featured( $post_id ) {
		$id = self::find_id( self::CONTEXT_FEATURED, $post_id );

		if ( $id ) {
			return $id;
		}

		return self::sync_featured( $post_id );
	}

	/**
	 * Registry id for a sticky slot, creating the row when it is missing.
	 *
	 * @param int $post_id Sticky video post ID.
	 * @return int
	 */
	public static function ensure_sticky( $post_id ) {
		$id = self::find_id( self::CONTEXT_STICKY, $post_id );

		if ( $id ) {
			return $id;
		}

		return self::sync_sticky( $post_id );
	}

	/**
	 * Look up a registry id.
	 *
	 * @param string $context   featured or sticky.
	 * @param int    $object_id Post ID.
	 * @return int
	 */
	public static function find_id( $context, $object_id ) {
		if ( ! Install::tables_ready() ) {
			return 0;
		}

		global $wpdb;

		$table = Install::videos_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table; name is prefixed and not user input.
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE context = %s AND object_id = %d",
				sanitize_key( $context ),
				absint( $object_id )
			)
		);

		return absint( $id );
	}

	/**
	 * Mark a slot inactive. Existing daily counts stay.
	 *
	 * @param string $context   featured or sticky.
	 * @param int    $object_id Post ID.
	 * @return void
	 */
	public static function mark_inactive( $context, $object_id ) {
		if ( ! Install::tables_ready() ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom analytics table.
		$wpdb->update(
			Install::videos_table(),
			array(
				'status'     => 'inactive',
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'context'   => sanitize_key( $context ),
				'object_id' => absint( $object_id ),
			),
			array( '%s', '%s' ),
			array( '%s', '%d' )
		);
	}

	/**
	 * Whether a registry row exists.
	 *
	 * @param int $video_id Registry id.
	 * @return bool
	 */
	public static function exists( $video_id ) {
		$video_id = absint( $video_id );

		if ( ! $video_id || ! Install::tables_ready() ) {
			return false;
		}

		global $wpdb;

		$table = Install::videos_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom analytics table; name is prefixed and not user input.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE id = %d",
				$video_id
			)
		);

		return absint( $found ) === $video_id;
	}

	/**
	 * Insert or update one slot.
	 *
	 * @param string  $context       featured or sticky.
	 * @param int     $object_id     Post ID.
	 * @param \WP_Post $post         Post object.
	 * @param string  $source        self or embed.
	 * @param bool    $active        Whether the slot currently has a video.
	 * @param int     $attachment_id Self-hosted attachment id.
	 * @param string  $embed_url     Embed URL.
	 * @return int
	 */
	private static function upsert( $context, $object_id, $post, $source, $active, $attachment_id, $embed_url ) {
		$existing = self::find_id( $context, $object_id );

		if ( ! $active && ! $existing ) {
			return 0;
		}

		$provider = 'unknown';
		$asset    = '';

		if ( 'self' === $source && $attachment_id > 0 ) {
			$provider = 'self';
			$asset    = (string) $attachment_id;
		} elseif ( '' !== $embed_url ) {
			$parsed = array();

			if ( Plugin::get_instance()->frontend_provider ) {
				$parsed = Plugin::get_instance()->frontend_provider->parse_embed_url( $embed_url );
			}

			if ( is_array( $parsed ) && ! empty( $parsed['host'] ) ) {
				$provider = sanitize_key( $parsed['host'] );
				$asset_id = isset( $parsed['id'] ) ? sanitize_text_field( (string) $parsed['id'] ) : '';
				$asset    = $provider . ':' . $asset_id;
			} else {
				$provider = 'unknown';
				$asset    = md5( $embed_url );
			}
		}

		if ( ! in_array( $provider, array( 'self', 'youtube', 'vimeo', 'dailymotion', 'unknown' ), true ) ) {
			$provider = 'unknown';
		}

		$now  = current_time( 'mysql' );
		$data = array(
			'object_type' => sanitize_key( $post->post_type ),
			'source'      => $source,
			'provider'    => $provider,
			'asset_key'   => substr( sanitize_text_field( $asset ), 0, 191 ),
			'title'       => sanitize_text_field( get_the_title( $post ) ),
			'status'      => $active ? 'active' : 'inactive',
			'updated_at'  => $now,
		);

		global $wpdb;

		$table = Install::videos_table();

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom analytics table.
			$wpdb->update(
				$table,
				$data,
				array( 'id' => $existing ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			return $existing;
		}

		$data['context']    = $context;
		$data['object_id']  = $object_id;
		$data['created_at'] = $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table.
		$wpdb->insert(
			$table,
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $wpdb->insert_id ) {
			return absint( $wpdb->insert_id );
		}

		return self::find_id( $context, $object_id );
	}
}
