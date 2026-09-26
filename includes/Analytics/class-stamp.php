<?php
/**
 * Mark rendered players so the frontend script can count them.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Surface context and data attributes. Does not change the video URL.
 */
class Stamp {

	/**
	 * Surface used by the next shortcode render.
	 *
	 * @var string
	 */
	private static $surface = 'shortcode';

	/**
	 * Whether this request printed a player.
	 *
	 * @var bool
	 */
	private static $needed = false;

	/**
	 * Swap the current surface and return the previous one.
	 *
	 * @param string $surface Surface key.
	 * @return string
	 */
	public static function swap_surface( $surface ) {
		$previous      = self::$surface;
		self::$surface = self::sanitize_surface( $surface );

		return $previous;
	}

	/**
	 * Current surface.
	 *
	 * @return string
	 */
	public static function current_surface() {
		return self::$surface;
	}

	/**
	 * Whether the tracker script should load.
	 *
	 * @return bool
	 */
	public static function needed() {
		return self::$needed;
	}

	/**
	 * Add analytics attributes to the first wrapper div.
	 *
	 * @param string $html     Player HTML.
	 * @param int    $post_id  Post ID.
	 * @param string $surface  Surface key.
	 * @param string $context  featured or sticky.
	 * @return string
	 */
	public static function decorate( $html, $post_id, $surface, $context = 'featured' ) {
		if ( ! Stats::enabled() || ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		if ( is_admin() || is_feed() || is_customize_preview() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only. The editor preview must not record counts.
			return $html;
		}

		$surface = self::sanitize_surface( $surface );

		if ( ! in_array( $surface, Stats::surfaces(), true ) ) {
			return $html;
		}

		if ( Registry::CONTEXT_STICKY === $context ) {
			$registry_id = Registry::ensure_sticky( $post_id );
		} else {
			$registry_id = Registry::ensure_featured( $post_id );
		}

		if ( ! $registry_id ) {
			return $html;
		}

		$attrs = sprintf(
			' data-rsfv-analytics="1" data-rsfv-video-id="%d" data-rsfv-surface="%s"',
			$registry_id,
			esc_attr( $surface )
		);

		$updated = preg_replace( '/<div\b/', '<div' . $attrs, $html, 1 );

		if ( ! is_string( $updated ) ) {
			return $html;
		}

		self::$needed = true;

		return $updated;
	}

	/**
	 * Note that a sticky player will be printed from JavaScript.
	 *
	 * @return void
	 */
	public static function mark_needed() {
		self::$needed = true;
	}

	/**
	 * Keep a known surface, otherwise shortcode.
	 *
	 * @param string $surface Raw surface.
	 * @return string
	 */
	private static function sanitize_surface( $surface ) {
		$surface = sanitize_key( $surface );

		if ( in_array( $surface, Stats::surfaces(), true ) ) {
			return $surface;
		}

		return 'shortcode';
	}
}
