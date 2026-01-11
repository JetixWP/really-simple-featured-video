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
					'search'    => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
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

		register_rest_route(
			self::NAMESPACE,
			'/posts/update-poster',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_poster' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'poster_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/posts/update-thumbnail',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_thumbnail' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'      => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'thumbnail_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		/**
		 * Fires after RSFV Tools REST routes are registered.
		 *
		 * Use this hook to register additional REST routes for the Tools page.
		 *
		 * @since 0.70.0
		 *
		 * @param REST_API $this REST_API instance.
		 */
		do_action( 'rsfv_tools_register_routes', $this );
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

		// Validate post type is enabled in plugin settings.
		$enabled_types = get_post_types();

		if ( ! in_array( $post_type, $enabled_types, true ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'The specified post type is not enabled for featured videos.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		// Double-check post type exists in WordPress.
		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'Invalid post type.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		// Query posts.
		$args = array(
			'post_type'      => sanitize_key( $post_type ),
			'post_status'    => 'any',
			'posts_per_page' => min( absint( $per_page ), 100 ), // Limit max per page to 100.
			'paged'          => absint( $page ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Add search if provided.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

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
		$poster_id    = get_post_meta( $post->ID, RSFV_POSTER_META_KEY, true );

		// Determine has_video based on the selected video source.
		$has_video = false;
		if ( 'self' === $video_source && ! empty( $video_id ) ) {
			$has_video = true;
		} elseif ( 'embed' === $video_source && ! empty( $embed_url ) ) {
			$has_video = true;
		}

		$thumbnail = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );

		// Get video URL for self-hosted videos.
		$video_url = '';
		if ( $video_id ) {
			$video_url = wp_get_attachment_url( $video_id );
		}

		// Get poster URL.
		$poster_url = '';
		if ( $poster_id ) {
			$poster_url = wp_get_attachment_url( $poster_id );
		}

		// Build edit link manually to avoid context issues.
		$edit_link = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );

		$data = array(
			'id'           => absint( $post->ID ),
			'title'        => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'permalink'    => esc_url_raw( get_permalink( $post ) ),
			'edit_link'    => esc_url_raw( $edit_link ),
			'thumbnail'    => $thumbnail ? esc_url_raw( $thumbnail ) : '',
			'has_video'    => (bool) $has_video,
			'video_source' => sanitize_key( $video_source ),
			'video_id'     => $video_id ? absint( $video_id ) : 0,
			'video_url'    => $video_url ? esc_url_raw( $video_url ) : '',
			'embed_url'    => $embed_url ? esc_url_raw( $embed_url ) : '',
			'poster_id'    => $poster_id ? absint( $poster_id ) : 0,
			'poster_url'   => $poster_url ? esc_url_raw( $poster_url ) : '',
		);

		/**
		 * Filter post data returned by the Tools REST API.
		 *
		 * @since 0.70.0
		 *
		 * @param array    $data Post data array.
		 * @param \WP_Post $post Post object.
		 */
		return apply_filters( 'rsfv_tools_post_data', $data, $post );
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
			update_post_meta( $post_id, RSFV_SOURCE_META_KEY, sanitize_key( $video_source ) );
		}

		return new WP_REST_Response(
			array(
				'success'      => true,
				'post_id'      => absint( $post_id ),
				'video_source' => sanitize_key( $video_source ),
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
		$post_id      = absint( $request->get_param( 'post_id' ) );
		$video_source = sanitize_key( $request->get_param( 'video_source' ) );
		$video_id     = absint( $request->get_param( 'video_id' ) );
		$embed_url    = esc_url_raw( $request->get_param( 'embed_url' ) );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		// Validate video source.
		if ( ! in_array( $video_source, array( 'self', 'embed' ), true ) ) {
			return new WP_Error(
				'invalid_source',
				__( 'Invalid video source type.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		// Validate video_id is a valid attachment.
		if ( 'self' === $video_source && $video_id ) {
			$attachment = get_post( $video_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error(
					'invalid_video',
					__( 'Invalid video attachment.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}

			// Verify it's a video mime type.
			$mime_type = get_post_mime_type( $video_id );
			if ( strpos( $mime_type, 'video/' ) !== 0 ) {
				return new WP_Error(
					'invalid_mime_type',
					__( 'Selected file is not a video.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}
		}

		// Validate embed URL.
		if ( 'embed' === $video_source && ! empty( $embed_url ) ) {
			// Use wp_http_validate_url for additional URL validation.
			if ( ! wp_http_validate_url( $embed_url ) ) {
				return new WP_Error(
					'invalid_url',
					__( 'Invalid embed URL.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}
		}

		// Update video source.
		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, sanitize_key( $video_source ) );

		if ( 'self' === $video_source ) {
			if ( $video_id ) {
				update_post_meta( $post_id, RSFV_META_KEY, absint( $video_id ) );
			} else {
				// Remove video if video_id is 0.
				delete_post_meta( $post_id, RSFV_META_KEY );
			}
			// Clear embed URL when switching to self-hosted.
			delete_post_meta( $post_id, RSFV_EMBED_META_KEY );
		} elseif ( 'embed' === $video_source ) {
			update_post_meta( $post_id, RSFV_EMBED_META_KEY, esc_url_raw( $embed_url ) );
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

	/**
	 * Update poster image for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_poster( WP_REST_Request $request ) {
		$post_id   = absint( $request->get_param( 'post_id' ) );
		$poster_id = absint( $request->get_param( 'poster_id' ) );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		// Validate poster_id is a valid image attachment.
		if ( $poster_id ) {
			$attachment = get_post( $poster_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error(
					'invalid_poster',
					__( 'Invalid poster image.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}

			// Verify it's an image mime type.
			$mime_type = get_post_mime_type( $poster_id );
			if ( strpos( $mime_type, 'image/' ) !== 0 ) {
				return new WP_Error(
					'invalid_mime_type',
					__( 'Selected file is not an image.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}

			update_post_meta( $post_id, RSFV_POSTER_META_KEY, absint( $poster_id ) );
			$poster_url = wp_get_attachment_url( $poster_id );
		} else {
			delete_post_meta( $post_id, RSFV_POSTER_META_KEY );
			$poster_url = '';
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'post_id'    => $post_id,
				'poster_id'  => $poster_id,
				'poster_url' => $poster_url ? esc_url_raw( $poster_url ) : '',
			),
			200
		);
	}

	/**
	 * Update thumbnail (featured image) for a post.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_thumbnail( WP_REST_Request $request ) {
		$post_id      = absint( $request->get_param( 'post_id' ) );
		$thumbnail_id = absint( $request->get_param( 'thumbnail_id' ) );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		// Validate thumbnail_id is a valid image attachment.
		if ( $thumbnail_id ) {
			$attachment = get_post( $thumbnail_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error(
					'invalid_thumbnail',
					__( 'Invalid thumbnail image.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}

			// Verify it's an image mime type.
			$mime_type = get_post_mime_type( $thumbnail_id );
			if ( strpos( $mime_type, 'image/' ) !== 0 ) {
				return new WP_Error(
					'invalid_mime_type',
					__( 'Selected file is not an image.', 'rsfv' ),
					array( 'status' => 400 )
				);
			}

			set_post_thumbnail( $post_id, $thumbnail_id );
			$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
		} else {
			delete_post_thumbnail( $post_id );
			$thumbnail_url = '';
		}

		return new WP_REST_Response(
			array(
				'success'       => true,
				'post_id'       => $post_id,
				'thumbnail_id'  => $thumbnail_id,
				'thumbnail_url' => $thumbnail_url ? esc_url_raw( $thumbnail_url ) : '',
			),
			200
		);
	}
}
