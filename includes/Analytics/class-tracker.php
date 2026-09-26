<?php
/**
 * Frontend analytics script.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the tracker only when this request printed a player.
 */
class Tracker {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'wp_footer', array( __CLASS__, 'maybe_enqueue' ), 1 );
		add_filter( 'rsfv_final_embed_url_with_params', array( __CLASS__, 'embed_api_params' ) );
	}

	/**
	 * Let provider players report playback time when analytics is on.
	 *
	 * @param string $url Embed URL.
	 * @return string
	 */
	public static function embed_api_params( $url ) {
		if ( ! Stats::enabled() || ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) ) {
			return $url;
		}

		$host  = strtolower( $host );
		$extra = array();

		if ( false !== strpos( $host, 'youtube' ) || false !== strpos( $host, 'youtu.be' ) ) {
			$extra['enablejsapi'] = '1';
			$extra['origin']      = home_url();
		} elseif ( false !== strpos( $host, 'vimeo' ) ) {
			$extra['api'] = '1';
		} elseif ( false !== strpos( $host, 'dailymotion' ) || false !== strpos( $host, 'dai.ly' ) ) {
			$extra['api'] = 'postMessage';
		}

		if ( empty( $extra ) ) {
			return $url;
		}

		return add_query_arg( $extra, $url );
	}

	/**
	 * Register the script and its endpoint.
	 *
	 * @return void
	 */
	public static function register_script() {
		if ( ! Stats::enabled() ) {
			return;
		}

		if ( wp_script_is( 'rsfv-analytics', 'registered' ) ) {
			return;
		}

		$path = RSFV_PLUGIN_DIR . 'assets/js/analytics.js';

		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_register_script(
			'rsfv-analytics',
			RSFV_PLUGIN_URL . 'assets/js/analytics.js',
			array(),
			(string) filemtime( $path ),
			true
		);

		wp_localize_script(
			'rsfv-analytics',
			'rsfvAnalytics',
			apply_filters(
				'rsfv_analytics_script_data',
				array(
					'endpoint' => esc_url_raw( rest_url( REST_API::NAMESPACE . '/analytics/collect' ) ),
					'audience' => 0,
				)
			)
		);
	}

	/**
	 * Enqueue on the front when a player was stamped.
	 *
	 * @return void
	 */
	public static function maybe_enqueue() {
		if ( is_admin() || ! Stats::enabled() || ! Stamp::needed() ) {
			return;
		}

		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only. The preview must not record counts.
			return;
		}

		self::register_script();
		wp_enqueue_script( 'rsfv-analytics' );
	}
}
