<?php
/**
 * Video Studio REST API.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

use function RSFV\Settings\get_post_types;

/**
 * Class REST_API
 */
class REST_API {
	/**
	 * Namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'rsfv/v1';

	/**
	 * Video types Video Studio may save.
	 *
	 * @var string[]
	 */
	const VIDEO_MIMES = array( 'video/mp4', 'video/webm' );

	/**
	 * Poster types Video Studio may save.
	 *
	 * @var string[]
	 */
	const POSTER_MIMES = array( 'image/jpeg', 'image/png', 'image/webp' );

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/studio/upload',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload' ),
				'permission_callback' => array( $this, 'can_edit_post' ),
				'args'                => array(
					'post_id'       => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'composition'   => array(
						'required' => true,
						'type'     => 'string',
					),
					'set_thumbnail' => array(
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/studio/post-fields/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'post_fields' ),
				'permission_callback' => array( $this, 'can_edit_post' ),
				'args'                => array(
					'post_id' => array(
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Whether the current user may add a Video Studio video to a post: they
	 * can edit it, its type has featured videos, and they can upload files.
	 * Add-ons that upload in other ways use this too.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return true|\WP_Error
	 */
	public static function check_post_access( $post_id ) {
		$post_id = absint( $post_id );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post ) {
			return new \WP_Error( 'rsfv_studio_post', __( 'Post not found.', 'rsfv' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to add videos to this post.', 'rsfv' ), array( 'status' => rest_authorization_required_code() ) );
		}
		if ( ! in_array( $post->post_type, (array) get_post_types(), true ) ) {
			return new \WP_Error( 'rsfv_studio_post_type', __( 'Featured videos are not enabled for this post type.', 'rsfv' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Permission callback for routes with a post_id.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return true|\WP_Error
	 */
	public function can_edit_post( $request ) {
		return self::check_post_access( $request['post_id'] );
	}

	/**
	 * Post details for templates.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function post_fields( $request ) {
		return rest_ensure_response( Post_Fields::get( absint( $request['post_id'] ) ) );
	}

	/**
	 * Check an uploaded file's real type.
	 *
	 * @param array    $file    File array (name, tmp_name, error, size).
	 * @param string[] $allowed Allowed MIME types.
	 *
	 * @return true|\WP_Error
	 */
	public static function check_file( $file, $allowed ) {
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ( isset( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) ) {
			return new \WP_Error( 'rsfv_studio_file', __( 'The file did not upload. It may be bigger than this site allows.', 'rsfv' ), array( 'status' => 400 ) );
		}

		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( empty( $check['type'] ) || ! in_array( $check['type'], $allowed, true ) ) {
			return new \WP_Error( 'rsfv_studio_file_type', __( 'That file type is not allowed.', 'rsfv' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Save a rendered video (and poster) as attachments and set it as the
	 * post's featured video. Also used by add-ons that upload in other ways.
	 *
	 * @param int        $post_id       Post ID.
	 * @param array      $raw           Raw composition.
	 * @param array      $video         Video file array (name, tmp_name, ...).
	 * @param array|null $poster        Poster file array or null.
	 * @param bool       $set_thumbnail Also set the poster as featured image.
	 *
	 * @return array|\WP_Error Result.
	 */
	public static function save_generated( $post_id, $raw, $video, $poster = null, $set_thumbnail = false ) {
		$composition = Composition::sanitize( $raw, $post_id );
		if ( is_wp_error( $composition ) ) {
			return $composition;
		}

		$checked = self::check_file( $video, self::VIDEO_MIMES );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}
		if ( $poster ) {
			$checked = self::check_file( $poster, self::POSTER_MIMES );
			if ( is_wp_error( $checked ) ) {
				$poster = null;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$post  = get_post( $post_id );
		$slug  = $post->post_name ? $post->post_name : 'post-' . $post_id;
		$title = get_the_title( $post );

		$video_ext     = pathinfo( $video['name'], PATHINFO_EXTENSION );
		$video['name'] = sanitize_file_name( $slug . '-video.' . ( 'webm' === strtolower( $video_ext ) ? 'webm' : 'mp4' ) );

		$video_id = media_handle_sideload(
			$video,
			$post_id,
			null,
			array(
				/* translators: %s: post title. */
				'post_title' => sprintf( __( '%s (Video Studio)', 'rsfv' ), $title ),
			)
		);
		if ( is_wp_error( $video_id ) ) {
			return $video_id;
		}

		$poster_id = 0;
		if ( $poster ) {
			$poster_ext     = pathinfo( $poster['name'], PATHINFO_EXTENSION );
			$poster['name'] = sanitize_file_name( $slug . '-video-poster.' . strtolower( $poster_ext ? $poster_ext : 'jpg' ) );
			$poster_id      = media_handle_sideload(
				$poster,
				$post_id,
				null,
				array(
					/* translators: %s: post title. */
					'post_title' => sprintf( __( '%s (Video Studio poster)', 'rsfv' ), $title ),
				)
			);
			if ( is_wp_error( $poster_id ) ) {
				$poster_id = 0;
			}
		}

		Composition::attach( $post_id, $composition, $video_id, $poster_id );

		$thumbnail_set = false;
		if ( $set_thumbnail && $poster_id && post_type_supports( $post->post_type, 'thumbnail' ) ) {
			$thumbnail_set = (bool) set_post_thumbnail( $post_id, $poster_id );
		}

		return array(
			'video'         => array(
				'id'  => $video_id,
				'url' => wp_get_attachment_url( $video_id ),
			),
			'poster'        => $poster_id ? array(
				'id'  => $poster_id,
				'url' => wp_get_attachment_image_url( $poster_id, 'medium' ),
			) : null,
			'thumbnail_set' => $thumbnail_set,
			'composition'   => Composition::get( $post_id ),
		);
	}

	/**
	 * Upload handler.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function upload( $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['video'] ) ) {
			return new \WP_Error( 'rsfv_studio_file', __( 'No video was sent.', 'rsfv' ), array( 'status' => 400 ) );
		}

		$raw = json_decode( (string) $request['composition'], true );

		$result = self::save_generated(
			absint( $request['post_id'] ),
			$raw,
			$files['video'],
			isset( $files['poster'] ) ? $files['poster'] : null,
			(bool) $request['set_thumbnail']
		);

		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}
}
