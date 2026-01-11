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
		$video_self   = get_post_meta( $post->ID, RSFV_META_KEY, true );
		$video_embed  = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );
		$has_video    = ! empty( $video_self ) || ! empty( $video_embed );

		$thumbnail = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );

		return array(
			'id'           => $post->ID,
			'title'        => get_the_title( $post ),
			'permalink'    => get_permalink( $post ),
			'edit_link'    => get_edit_post_link( $post->ID, 'raw' ),
			'thumbnail'    => $thumbnail ? $thumbnail : '',
			'has_video'    => $has_video,
			'video_source' => $video_source ? $video_source : '',
		);
	}
}
