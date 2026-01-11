<?php
/**
 * REST API Handler for Tools.
 *
 * @package RSFV
 */

namespace RSFV\Tools;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

/**
 * REST_API Class.
 */
class REST_API {
	/**
	 * API Namespace.
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
	 * Get class instance.
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
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'      => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page'  => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/posts/update-source',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_video_source' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'video_source' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/posts/update-video',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_video' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'video_source' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'video_id'     => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'embed_url'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Check if user has permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
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
	 * Get posts with featured video data.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_posts( WP_REST_Request $request ) {
		$post_type = $request->get_param( 'post_type' );
		$page      = $request->get_param( 'page' );
		$per_page  = $request->get_param( 'per_page' );

		// Validate post type is enabled.
		$enabled_types = get_post_types();

		if ( ! in_array( $post_type, $enabled_types, true ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'The specified post type is not enabled for featured videos.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		// Query posts.
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$posts[] = $this->prepare_post_data( $post );
		}

		$response = new WP_REST_Response( $posts, 200 );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );

		return $response;
	}

	/**
	 * Prepare post data for response.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array
	 */
	protected function prepare_post_data( $post ) {
		$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
		$video_id     = get_post_meta( $post->ID, RSFV_META_KEY, true );
		$embed_url    = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );
		$has_video    = ! empty( $video_id ) || ! empty( $embed_url );

		$thumbnail = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );

		return array(
			'id'           => $post->ID,
			'title'        => get_the_title( $post ),
			'permalink'    => get_permalink( $post ),
			'edit_link'    => get_edit_post_link( $post->ID, 'raw' ),
			'thumbnail'    => $thumbnail ? $thumbnail : '',
			'has_video'    => $has_video,
			'video_source' => $video_source ? $video_source : '',
			'video_id'     => $video_id ? (int) $video_id : 0,
			'embed_url'    => $embed_url ? $embed_url : '',
		);
	}

	/**
	 * Update video source for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_video_source( WP_REST_Request $request ) {
		$post_id      = $request->get_param( 'post_id' );
		$video_source = $request->get_param( 'video_source' );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		// Validate video source.
		if ( ! in_array( $video_source, array( 'self', 'embed', '' ), true ) ) {
			return new WP_Error(
				'invalid_source',
				__( 'Invalid video source type.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $video_source ) ) {
			delete_post_meta( $post_id, RSFV_SOURCE_META_KEY );
		} else {
			update_post_meta( $post_id, RSFV_SOURCE_META_KEY, $video_source );
		}

		return new WP_REST_Response(
			array(
				'success'      => true,
				'post_id'      => $post_id,
				'video_source' => $video_source,
			),
			200
		);
	}

	/**
	 * Update video for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_video( WP_REST_Request $request ) {
		$post_id      = $request->get_param( 'post_id' );
		$video_source = $request->get_param( 'video_source' );
		$video_id     = $request->get_param( 'video_id' );
		$embed_url    = $request->get_param( 'embed_url' );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		// Update video source.
		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, $video_source );

		if ( 'self' === $video_source && $video_id ) {
			update_post_meta( $post_id, RSFV_META_KEY, $video_id );
			// Clear embed URL when switching to self-hosted.
			delete_post_meta( $post_id, RSFV_EMBED_META_KEY );
		} elseif ( 'embed' === $video_source ) {
			update_post_meta( $post_id, RSFV_EMBED_META_KEY, $embed_url );
			// Clear self-hosted video when switching to embed.
			delete_post_meta( $post_id, RSFV_META_KEY );
		}

		return new WP_REST_Response(
			array(
				'success'      => true,
				'post_id'      => $post_id,
				'video_source' => $video_source,
				'video_id'     => $video_id,
				'embed_url'    => $embed_url,
			),
			200
		);
	}
}
