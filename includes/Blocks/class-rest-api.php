<?php
/**
 * REST routes used by the Featured Video block in the editor.
 *
 * @package RSFV
 * @since   1.1.0
 */

namespace RSFV\Blocks;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use RSFV\FrontEnd;
use RSFV\Renderer;
use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

/**
 * Preview and search endpoints. Both only hand out what the user may already see.
 */
class REST_API {

	/**
	 * API namespace, shared with the other RSFV routes.
	 *
	 * @var string
	 */
	const NAMESPACE = 'rsfv/v1';

	/**
	 * Class instance.
	 *
	 * @var REST_API
	 */
	protected static $instance;

	/**
	 * Get a class instance.
	 *
	 * @return REST_API
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/block/preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_preview' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/block/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_posts' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'search' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'   => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Only people who can use the editor get to preview or search.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to do this.', 'rsfv' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Describe the featured video of a post for the editor preview.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_preview( $request ) {
		$post = get_post( absint( $request->get_param( 'post_id' ) ) );

		$data = array(
			'exists'      => false,
			'typeEnabled' => false,
			'viewable'    => false,
			'hasVideo'    => false,
		);

		if ( ! $post instanceof WP_Post || ! Renderer::can_view_post( $post ) ) {
			return rest_ensure_response( $data );
		}

		$data['exists']      = true;
		$data['id']          = $post->ID;
		$data['title']       = wp_strip_all_tags( get_the_title( $post ) );
		$data['typeEnabled'] = in_array( $post->post_type, get_post_types(), true );
		$data['viewable']    = $data['typeEnabled'];
		$data['editLink']    = current_user_can( 'edit_post', $post->ID ) ? get_edit_post_link( $post->ID, 'raw' ) : '';

		if ( ! $data['viewable'] || ! FrontEnd::has_featured_video( $post->ID ) ) {
			return rest_ensure_response( $data );
		}

		$source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
		$source = $source ? $source : 'self';

		$data['hasVideo'] = true;
		$data['source']   = 'self' === $source ? 'self' : 'embed';

		if ( 'self' === $data['source'] ) {
			$video_id   = absint( get_post_meta( $post->ID, RSFV_META_KEY, true ) );
			$poster_id  = absint( get_post_meta( $post->ID, RSFV_POSTER_META_KEY, true ) );
			$video_url  = $video_id ? wp_get_attachment_url( $video_id ) : '';
			$poster_url = $poster_id ? wp_get_attachment_url( $poster_id ) : '';

			$data['url']      = $video_url ? esc_url_raw( $video_url ) : '';
			$data['poster']   = $poster_url ? esc_url_raw( $poster_url ) : '';
			$data['provider'] = 'self';
		} else {
			$embed_data       = FrontEnd::get_instance()->parse_embed_url( get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true ) );
			$data['provider'] = is_array( $embed_data ) && ! empty( $embed_data['host'] ) ? sanitize_key( $embed_data['host'] ) : 'unknown';
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Find posts that have a featured video the user is allowed to see.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function search_posts( $request ) {
		$per_page = 20;

		$query = new WP_Query(
			array(
				'post_type'              => get_post_types(),
				'post_status'            => 'any',
				'posts_per_page'         => $per_page,
				'paged'                  => max( 1, absint( $request->get_param( 'page' ) ) ),
				's'                      => (string) $request->get_param( 'search' ),
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				// Rows with a stale value for the source not in use are dropped below.
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Small, capped query for the editor only.
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => RSFV_META_KEY,
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => RSFV_EMBED_META_KEY,
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		$results = array();

		foreach ( $query->posts as $post ) {
			if ( ! Renderer::is_viewable( $post ) || ! FrontEnd::has_featured_video( $post->ID ) ) {
				continue;
			}

			$type_object = get_post_type_object( $post->post_type );
			$title       = wp_strip_all_tags( get_the_title( $post ) );

			$results[] = array(
				'id'       => $post->ID,
				'title'    => '' !== $title ? $title : __( '(no title)', 'rsfv' ),
				'postType' => $type_object ? $type_object->labels->singular_name : $post->post_type,
				'status'   => $post->post_status,
			);
		}

		return rest_ensure_response( $results );
	}
}
