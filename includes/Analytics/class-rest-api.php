<?php
/**
 * Analytics REST routes.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Analytics;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Public collect route and the admin report route.
 */
class REST_API {

	const NAMESPACE = 'rsfv/v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/analytics/collect',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'collect' ),
				'permission_callback' => array( __CLASS__, 'collect_permissions' ),
				'args'                => array(
					'events' => array(
						'required' => true,
						'type'     => 'array',
						'maxItems' => 20,
						'items'    => array(
							'type'       => 'object',
							'properties' => array(
								'video_id' => array(
									'type'              => 'integer',
									'required'          => true,
									'sanitize_callback' => 'absint',
								),
								'event'    => array(
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_key',
								),
								'surface'  => array(
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_key',
								),
								'seconds'  => array(
									'type'              => 'integer',
									'sanitize_callback' => 'absint',
								),
								'referrer' => array(
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_text_field',
								),
							),
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/analytics/report',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'report' ),
				'permission_callback' => array( __CLASS__, 'report_permissions' ),
				'args'                => array(
					'from' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'   => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Collect is public on purpose.
	 *
	 * A full-page cache would keep serving an expired nonce, so this route does not use one.
	 * Same-site headers, an allowlist, and a rate limit stand in for it.
	 *
	 * @return true|WP_Error
	 */
	public static function collect_permissions() {
		if ( ! Stats::enabled() ) {
			return new WP_Error(
				'rsfv_analytics_disabled',
				__( 'Analytics is turned off.', 'rsfv' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Report matches the Video Tools capability.
	 *
	 * @return true|WP_Error
	 */
	public static function report_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'rsfv' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Accept a batch of view and play events.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function collect( $request ) {
		if ( ! self::is_same_site() ) {
			return new WP_Error(
				'rsfv_analytics_forbidden',
				__( 'This request was blocked.', 'rsfv' ),
				array( 'status' => 403 )
			);
		}

		if ( Stats::ignore_editors() && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			return new WP_REST_Response( null, 204 );
		}

		if ( self::is_bot() ) {
			return new WP_REST_Response( null, 204 );
		}

		$events = $request->get_param( 'events' );

		if ( ! is_array( $events ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$accepted = array();
		$types    = Stats::event_types();
		$surfaces = Stats::surfaces();

		foreach ( array_slice( $events, 0, 20 ) as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$video_id = isset( $event['video_id'] ) ? absint( $event['video_id'] ) : 0;
			$name     = isset( $event['event'] ) ? sanitize_key( $event['event'] ) : '';
			$surface  = isset( $event['surface'] ) ? sanitize_key( $event['surface'] ) : '';
			$seconds  = isset( $event['seconds'] ) ? min( 86400, absint( $event['seconds'] ) ) : 0;
			$referrer = ( 'view' === $name ) ? self::referrer_host( $event['referrer'] ?? '' ) : '';

			if ( ! $video_id || ! in_array( $name, $types, true ) || ! in_array( $surface, $surfaces, true ) ) {
				continue;
			}

			$key = $video_id . '|' . $name . '|' . $surface;

			if ( isset( $accepted[ $key ] ) && 'watch' === $name ) {
				$accepted[ $key ]['seconds'] = min( 86400, $accepted[ $key ]['seconds'] + $seconds );
				continue;
			}

			$accepted[ $key ] = array(
				'video_id' => $video_id,
				'event'    => $name,
				'surface'  => $surface,
				'seconds'  => $seconds,
				'referrer' => $referrer,
			);
		}

		if ( empty( $accepted ) ) {
			return new WP_REST_Response( null, 204 );
		}

		if ( ! self::within_rate_limit( count( $accepted ) ) ) {
			return new WP_Error(
				'rsfv_analytics_rate_limited',
				__( 'Too many analytics requests.', 'rsfv' ),
				array( 'status' => 429 )
			);
		}

		foreach ( $accepted as $item ) {
			if ( ! Registry::exists( $item['video_id'] ) ) {
				continue;
			}

			if ( in_array( $item['event'], array( 'view', 'play' ), true ) ) {
				Stats::increment( $item['video_id'], $item['event'], $item['surface'] );
			}

			/**
			 * Fires after one accepted analytics event.
			 *
			 * Pro can store a richer copy. The daily totals are already written for view and play.
			 *
			 * @since 0.90.0
			 *
			 * @param array $item video_id, event, surface.
			 */
			do_action( 'rsfv_analytics_recorded', $item );
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Report for the current retention window.
	 *
	 * Client-supplied days cannot exceed the server window.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function report( $request ) {
		if ( ! Install::tables_ready() ) {
			Install::create_tables();
		}

		$from = sanitize_text_field( (string) $request->get_param( 'from' ) );
		$to   = sanitize_text_field( (string) $request->get_param( 'to' ) );

		return new WP_REST_Response( Stats::report( $from, $to ) );
	}

	/**
	 * Keep only the site name from a referrer. Full addresses can carry private query data.
	 *
	 * @param string $value Raw referrer.
	 * @return string
	 */
	private static function referrer_host( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$host = wp_parse_url( $value, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$host = $value;
		}

		$host = strtolower( preg_replace( '/^www\./', '', $host ) );
		$host = preg_replace( '/[^a-z0-9.\-]/', '', $host );

		return substr( (string) $host, 0, 191 );
	}

	/**
	 * Same-site check. Sec-Fetch-Site is set by the browser and page script cannot change it.
	 *
	 * @return bool
	 */
	private static function is_same_site() {
		$fetch = isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ? sanitize_key( wp_unslash( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ) : '';

		if ( in_array( $fetch, array( 'same-origin', 'same-site' ), true ) ) {
			return true;
		}

		if ( '' !== $fetch ) {
			return false;
		}

		$home = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! is_string( $home ) || '' === $home ) {
			return false;
		}

		$candidates = array();

		if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
			$candidates[] = esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$candidates[] = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}

		foreach ( $candidates as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );

			if ( is_string( $host ) && strtolower( $host ) === strtolower( $home ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A short-lived cap keyed by a salted hash of the IP. The IP is not stored.
	 *
	 * @param int $count Events about to be written.
	 * @return bool
	 */
	private static function within_rate_limit( $count ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'rsfv_ar_' . substr( wp_hash( $ip ), 0, 32 );
		$used = (int) get_transient( $key );
		$count = absint( $count );

		if ( ( $used + $count ) > 600 ) {
			return false;
		}

		set_transient( $key, $used + $count, 10 * MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Skip common crawlers. This is extra filtering, not the security boundary.
	 *
	 * @return bool
	 */
	private static function is_bot() {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';

		if ( '' === $agent ) {
			return false;
		}

		$needles = array(
			'googlebot',
			'bingbot',
			'slurp',
			'duckduckbot',
			'baiduspider',
			'yandex',
			'facebookexternalhit',
			'twitterbot',
			'linkedinbot',
			'slackbot',
			'ahrefs',
			'semrush',
			'petalbot',
		);

		foreach ( $needles as $needle ) {
			if ( false !== strpos( $agent, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
