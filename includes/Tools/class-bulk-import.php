<?php
/**
 * Bulk featured-video import.
 *
 * Rows that fail a check are discarded. A file is stored only after the
 * post, permission, and replace checks pass. If saving the video onto the
 * post fails, the new attachment is deleted.
 *
 * @package RSFV
 * @since   0.90.0
 */

namespace RSFV\Tools;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

/**
 * Bulk_Import class.
 *
 * @since 0.90.0
 */
class Bulk_Import {
	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'rsfv/v1';

	/**
	 * Maximum rows in one preview request.
	 *
	 * @var int
	 */
	const PREVIEW_BATCH = 100;

	/**
	 * Maximum embed rows in one apply request.
	 *
	 * @var int
	 */
	const EMBED_BATCH = 25;

	/**
	 * Maximum characters in one cell.
	 *
	 * @var int
	 */
	const CELL_MAX = 2000;

	/**
	 * Default remote download timeout, in seconds.
	 *
	 * @var int
	 */
	const DOWNLOAD_TIMEOUT = 120;

	/**
	 * Class instance.
	 *
	 * @var Bulk_Import
	 */
	protected static $instance;

	/**
	 * Allowed video extensions for this request.
	 *
	 * @var string[]|null
	 */
	private $video_exts = null;

	/**
	 * Get a class instance.
	 *
	 * @return Bulk_Import
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$args = array(
			'post_type' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'rows'      => array(
				'required' => true,
			),
		);

		register_rest_route(
			self::NAMESPACE,
			'/bulk/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $args,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/bulk/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $args,
			)
		);
	}

	/**
	 * Check that the user can open Video Tools.
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
	 * Check rows and write nothing.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function preview( WP_REST_Request $request ) {
		$post_type = $this->post_type_error( $request->get_param( 'post_type' ) );

		if ( is_wp_error( $post_type ) ) {
			return $post_type;
		}

		$rows = $this->rows_from_request( $request );

		if ( count( $rows ) > $this->preview_limit() ) {
			return new WP_Error(
				'rsfv_bulk_too_many',
				__( 'Send fewer rows in one check.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		$seen    = array();
		$results = array();

		foreach ( $rows as $row ) {
			$checked   = $this->evaluate_row( $row, $post_type, $seen );
			$results[] = $checked['result'];
		}

		return new WP_REST_Response(
			array(
				'results' => $results,
			),
			200
		);
	}

	/**
	 * Check rows again, then save the ones that pass.
	 *
	 * A file row is limited to one per request. Embed rows are limited to
	 * the embed batch size. The file is stored only after the row passes.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply( WP_REST_Request $request ) {
		$post_type = $this->post_type_error( $request->get_param( 'post_type' ) );

		if ( is_wp_error( $post_type ) ) {
			return $post_type;
		}

		$rows     = $this->rows_from_request( $request );
		$has_self = false;

		foreach ( $rows as $row ) {
			if ( is_array( $row ) && isset( $row['source'] ) && 'self' === sanitize_key( (string) $row['source'] ) ) {
				$has_self = true;
				break;
			}
		}

		$limit = $has_self ? 1 : $this->embed_limit();

		if ( count( $rows ) > $limit ) {
			return new WP_Error(
				'rsfv_bulk_too_many',
				__( 'Send fewer rows in one save.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		$seen    = array();
		$results = array();

		foreach ( $rows as $row ) {
			$checked = $this->evaluate_row( $row, $post_type, $seen );

			if ( empty( $checked['can_write'] ) ) {
				$results[] = $checked['result'];
				continue;
			}

			$results[] = $this->write_row( $checked, $request );
		}

		return new WP_REST_Response(
			array(
				'results' => $results,
			),
			200
		);
	}

	/**
	 * Read the rows array from JSON or from a multipart field.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	private function rows_from_request( WP_REST_Request $request ) {
		$rows = $request->get_param( 'rows' );

		if ( is_string( $rows ) ) {
			$decoded = json_decode( $rows, true );

			if ( ! is_array( $decoded ) ) {
				$decoded = json_decode( wp_unslash( $rows ), true );
			}

			$rows = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values( $rows );
	}

	/**
	 * Confirm the post type is enabled for featured videos.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string|WP_Error The slug, or an error.
	 */
	private function post_type_error( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );
		$enabled   = get_post_types();

		if ( ! in_array( $post_type, $enabled, true ) || ! post_type_exists( $post_type ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'The specified post type is not enabled for featured videos.', 'rsfv' ),
				array( 'status' => 400 )
			);
		}

		return $post_type;
	}

	/**
	 * Check one row. Nothing is written here.
	 *
	 * @param mixed  $row       Raw row.
	 * @param string $post_type Enabled post type.
	 * @param array  $seen      Post IDs already accepted in this request.
	 *
	 * @return array
	 */
	private function evaluate_row( $row, $post_type, array &$seen ) {
		$row     = is_array( $row ) ? $row : array();
		$id      = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
		$post    = isset( $row['post'] ) ? sanitize_text_field( (string) $row['post'] ) : '';
		$source  = isset( $row['source'] ) ? sanitize_key( (string) $row['source'] ) : '';
		$value   = isset( $row['value'] ) ? (string) $row['value'] : '';
		$value   = trim( str_replace( array( "\r", "\n", "\t" ), '', wp_strip_all_tags( $value ) ) );
		$replace = $this->is_replace( $row['replace'] ?? false );

		if ( '' === $post ) {
			return $this->rejected( $id, 'missing_post' );
		}

		if ( ! in_array( $source, array( 'self', 'embed' ), true ) ) {
			return $this->rejected( $id, 'invalid_source' );
		}

		if ( '' === $value ) {
			return $this->rejected( $id, 'missing_value' );
		}

		if ( mb_strlen( $post, 'UTF-8' ) > self::CELL_MAX || mb_strlen( $value, 'UTF-8' ) > self::CELL_MAX ) {
			return $this->rejected( $id, 'value_too_long' );
		}

		$mode = 'local';

		if ( 'embed' === $source ) {
			$url = esc_url_raw( $value );

			if ( ! wp_http_validate_url( $url ) ) {
				return $this->rejected( $id, 'invalid_embed' );
			}

			if ( ! $this->embed_is_supported( $url ) ) {
				return $this->rejected( $id, 'unsupported_embed' );
			}

			$mode  = 'embed';
			$value = $url;
		} elseif ( preg_match( '#^https?://#i', $value ) ) {
			$url = esc_url_raw( $value );

			if ( ! wp_http_validate_url( $url ) ) {
				return $this->rejected( $id, 'invalid_url' );
			}

			if ( ! $this->has_video_extension( $url ) ) {
				return $this->rejected( $id, 'invalid_mime' );
			}

			$mode  = 'remote';
			$value = $url;
		} elseif ( ! $this->has_video_extension( $value ) ) {
			return $this->rejected( $id, 'invalid_mime' );
		}

		if ( 'self' === $source && ! current_user_can( 'upload_files' ) ) {
			return $this->rejected( $id, 'upload_forbidden' );
		}

		$found = $this->resolve_post( $post, $post_type );

		if ( ! $found instanceof WP_Post ) {
			return $this->rejected( $id, 'invalid_post' );
		}

		if ( ! in_array( $found->post_status, $this->allowed_statuses(), true ) ) {
			return $this->rejected( $id, 'invalid_status' );
		}

		if ( ! current_user_can( 'edit_post', $found->ID ) ) {
			return $this->rejected( $id, 'forbidden' );
		}

		if ( isset( $seen[ $found->ID ] ) ) {
			return $this->rejected( $id, 'duplicate_post' );
		}

		$seen[ $found->ID ] = true;

		$has_video = $this->post_has_video( $found->ID );
		$title     = html_entity_decode( wp_strip_all_tags( get_the_title( $found ) ), ENT_QUOTES, 'UTF-8' );

		if ( $has_video && ! $replace ) {
			return $this->rejected( $id, 'has_video', $found->ID, $title, true );
		}

		$status = $has_video ? 'replace' : 'ready';
		$reason = $has_video ? 'will_replace' : 'ok';

		return array(
			'can_write' => true,
			'result'    => $this->result( $id, $status, $reason, $found->ID, $title, $has_video ),
			'post_id'   => (int) $found->ID,
			'mode'      => $mode,
			'value'     => $value,
			'id'        => $id,
			'title'     => $title,
		);
	}

	/**
	 * Save one row that already passed the check.
	 *
	 * @param array           $checked Checked row context.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	private function write_row( array $checked, WP_REST_Request $request ) {
		$post_id = (int) $checked['post_id'];
		$id      = (int) $checked['id'];
		$title   = (string) $checked['title'];
		$mode    = (string) $checked['mode'];
		$value   = (string) $checked['value'];

		if ( 'embed' === $mode ) {
			if ( ! $this->assign_embed( $post_id, $value ) ) {
				return $this->result( $id, 'discarded', 'save_failed', $post_id, $title, false );
			}

			return $this->result( $id, 'saved', 'ok', $post_id, $title, true );
		}

		if ( 'remote' === $mode ) {
			$attachment_id = $this->sideload_remote( $value, $post_id );

			if ( is_wp_error( $attachment_id ) ) {
				return $this->result( $id, 'discarded', $attachment_id->get_error_code(), $post_id, $title, false );
			}

			if ( ! $this->assign_self( $post_id, $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
				return $this->result( $id, 'discarded', 'save_failed', $post_id, $title, false );
			}

			return $this->result( $id, 'saved', 'ok', $post_id, $title, true );
		}

		$file = $this->uploaded_file( $request );

		if ( is_wp_error( $file ) ) {
			return $this->result( $id, 'discarded', $file->get_error_code(), $post_id, $title, false );
		}

		$actual = isset( $file['name'] ) ? (string) wp_unslash( $file['name'] ) : '';

		if ( ! $this->names_match( $value, $actual ) ) {
			return $this->result( $id, 'discarded', 'file_mismatch', $post_id, $title, false );
		}

		$checked_type = wp_check_filetype_and_ext( $file['tmp_name'], $actual );

		if ( empty( $checked_type['type'] ) || 0 !== strpos( (string) $checked_type['type'], 'video/' ) ) {
			return $this->result( $id, 'discarded', 'invalid_mime', $post_id, $title, false );
		}

		$this->load_media_api();

		$attachment_id = media_handle_upload( 'file', $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $this->result( $id, 'discarded', 'upload_failed', $post_id, $title, false );
		}

		$attachment_id = (int) $attachment_id;

		if ( ! $this->assign_self( $post_id, $attachment_id ) ) {
			wp_delete_attachment( $attachment_id, true );
			return $this->result( $id, 'discarded', 'save_failed', $post_id, $title, false );
		}

		return $this->result( $id, 'saved', 'ok', $post_id, $title, true );
	}

	/**
	 * Download a remote video and store it on the post.
	 *
	 * The URL must pass wp_http_validate_url. download_url uses the safe
	 * HTTP API, which checks the URL again, including redirects.
	 *
	 * @param string $url     Public video URL.
	 * @param int    $post_id Post ID that will own the attachment.
	 *
	 * @return int|WP_Error Attachment ID, or an error whose code is a row reason.
	 */
	private function sideload_remote( $url, $post_id ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid_url' );
		}

		$max  = (int) wp_max_upload_size();
		$head = wp_safe_remote_head(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
			)
		);

		if ( ! is_wp_error( $head ) ) {
			$length = wp_remote_retrieve_header( $head, 'content-length' );

			if ( is_numeric( $length ) && (int) $length > $max ) {
				return new WP_Error( 'too_large' );
			}
		}

		$this->load_media_api();

		$tmp = download_url( $url, $this->download_timeout() );

		if ( is_wp_error( $tmp ) ) {
			return new WP_Error( 'download_failed' );
		}

		$size = file_exists( $tmp ) ? (int) filesize( $tmp ) : 0;

		if ( $size < 1 || $size > $max ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'too_large' );
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$name = sanitize_file_name( rawurldecode( wp_basename( $path ) ) );
		$mime = wp_check_filetype_and_ext( $tmp, $name );

		if ( empty( $mime['type'] ) || 0 !== strpos( (string) $mime['type'], 'video/' ) ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'invalid_mime' );
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => $name,
				'tmp_name' => $tmp,
			),
			$post_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}

			return new WP_Error( 'download_failed' );
		}

		return (int) $attachment_id;
	}

	/**
	 * Read the uploaded video from this request.
	 *
	 * The file stays in PHP's temp directory until media_handle_upload
	 * runs. A discarded row never reaches that call.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error
	 */
	private function uploaded_file( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return new WP_Error( 'missing_file' );
		}

		$file  = $files['file'];
		$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

		if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
			return new WP_Error( 'too_large' );
		}

		$tmp = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

		if ( UPLOAD_ERR_OK !== $error || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			return new WP_Error( 'missing_file' );
		}

		$size = isset( $file['size'] ) ? (int) $file['size'] : 0;

		if ( $size < 1 || $size > (int) wp_max_upload_size() ) {
			return new WP_Error( 'too_large' );
		}

		return $file;
	}

	/**
	 * Compare a row file name with the uploaded file name.
	 *
	 * @param string $expected Name from the row.
	 * @param string $actual   Name from the upload.
	 *
	 * @return bool
	 */
	private function names_match( $expected, $actual ) {
		$expected = strtolower( sanitize_file_name( wp_basename( (string) $expected ) ) );
		$actual   = strtolower( sanitize_file_name( wp_basename( (string) $actual ) ) );

		return '' !== $expected && $expected === $actual;
	}

	/**
	 * Save an embed link on the post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Embed URL.
	 *
	 * @return bool
	 */
	private function assign_embed( $post_id, $url ) {
		$post_id = absint( $post_id );
		$url     = esc_url_raw( $url );

		if ( ! $post_id || '' === $url ) {
			return false;
		}

		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, 'embed' );
		update_post_meta( $post_id, RSFV_EMBED_META_KEY, $url );
		delete_post_meta( $post_id, RSFV_META_KEY );

		$saved  = (string) get_post_meta( $post_id, RSFV_EMBED_META_KEY, true );
		$source = (string) get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );

		return 'embed' === $source && $saved === $url;
	}

	/**
	 * Save a media library video on the post.
	 *
	 * @param int $post_id       Post ID.
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	private function assign_self( $post_id, $attachment_id ) {
		$post_id       = absint( $post_id );
		$attachment_id = absint( $attachment_id );

		if ( ! $post_id || ! $attachment_id ) {
			return false;
		}

		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, 'self' );
		update_post_meta( $post_id, RSFV_META_KEY, $attachment_id );
		delete_post_meta( $post_id, RSFV_EMBED_META_KEY );

		$saved  = absint( get_post_meta( $post_id, RSFV_META_KEY, true ) );
		$source = (string) get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );

		return 'self' === $source && $saved === $attachment_id;
	}

	/**
	 * Whether the post already has a featured video.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	private function post_has_video( $post_id ) {
		$source = (string) get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );

		if ( 'self' === $source && absint( get_post_meta( $post_id, RSFV_META_KEY, true ) ) ) {
			return true;
		}

		if ( 'embed' === $source && '' !== (string) get_post_meta( $post_id, RSFV_EMBED_META_KEY, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Find a post by ID or by slug inside one post type.
	 *
	 * @param string $raw       ID or slug.
	 * @param string $post_type Post type slug.
	 *
	 * @return WP_Post|null
	 */
	private function resolve_post( $raw, $post_type ) {
		$raw = trim( $raw );

		if ( ctype_digit( $raw ) ) {
			if ( strlen( $raw ) > 10 ) {
				return null;
			}

			$post = get_post( absint( $raw ) );

			if ( ! $post instanceof WP_Post || $post->post_type !== $post_type ) {
				return null;
			}

			return $post;
		}

		$slug = sanitize_title( $raw );

		if ( '' === $slug ) {
			return null;
		}

		$found = get_posts(
			array(
				'name'                   => $slug,
				'post_type'              => $post_type,
				'post_status'            => $this->allowed_statuses(),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( empty( $found ) || ! $found[0] instanceof WP_Post ) {
			return null;
		}

		return $found[0];
	}

	/**
	 * Post statuses a row may target.
	 *
	 * @return string[]
	 */
	private function allowed_statuses() {
		return array( 'publish', 'draft', 'pending', 'private', 'future' );
	}

	/**
	 * Whether an embed URL is YouTube, Vimeo, or Dailymotion.
	 *
	 * @param string $url Embed URL.
	 *
	 * @return bool
	 */
	private function embed_is_supported( $url ) {
		$plugin = \RSFV\Plugin::get_instance();

		if ( ! $plugin || empty( $plugin->frontend_provider ) ) {
			return false;
		}

		$data = $plugin->frontend_provider->parse_embed_url( $url );

		return is_array( $data ) && ! empty( $data['id'] );
	}

	/**
	 * Whether the path ends in a video extension this site allows.
	 *
	 * @param string $value File name or URL.
	 *
	 * @return bool
	 */
	private function has_video_extension( $value ) {
		$path = (string) $value;

		if ( preg_match( '#^https?://#i', $path ) ) {
			$path = (string) wp_parse_url( $path, PHP_URL_PATH );
		}

		$ext = strtolower( pathinfo( wp_basename( $path ), PATHINFO_EXTENSION ) );

		return '' !== $ext && in_array( $ext, $this->video_extensions(), true );
	}

	/**
	 * Video extensions from the site's allowed mime types.
	 *
	 * @return string[]
	 */
	private function video_extensions() {
		if ( null !== $this->video_exts ) {
			return $this->video_exts;
		}

		$exts = array();

		foreach ( get_allowed_mime_types() as $list => $mime ) {
			if ( 0 !== strpos( (string) $mime, 'video/' ) ) {
				continue;
			}

			foreach ( explode( '|', (string) $list ) as $ext ) {
				$exts[] = strtolower( $ext );
			}
		}

		$this->video_exts = $exts;

		return $this->video_exts;
	}

	/**
	 * Read replace as yes or no.
	 *
	 * @param mixed $value Raw replace flag.
	 *
	 * @return bool
	 */
	private function is_replace( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return 1 === (int) $value;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( 'yes', 'true', '1' ), true );
	}

	/**
	 * Load the media helpers used to store a video.
	 *
	 * @return void
	 */
	private function load_media_api() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * Remote download timeout in seconds.
	 *
	 * @return int
	 */
	private function download_timeout() {
		/**
		 * Filter the remote video download timeout in seconds.
		 *
		 * @since 0.90.0
		 *
		 * @param int $timeout Timeout in seconds.
		 */
		$timeout = (int) apply_filters( 'rsfv_bulk_download_timeout', self::DOWNLOAD_TIMEOUT );

		if ( $timeout < 5 ) {
			return 5;
		}

		if ( $timeout > 300 ) {
			return 300;
		}

		return $timeout;
	}

	/**
	 * How many rows one preview request may hold.
	 *
	 * @return int
	 */
	private function preview_limit() {
		/**
		 * Filter how many rows one bulk preview request may hold.
		 *
		 * The value is capped at 100.
		 *
		 * @since 0.90.0
		 *
		 * @param int $limit Row limit.
		 */
		$limit = (int) apply_filters( 'rsfv_bulk_preview_batch', self::PREVIEW_BATCH );

		if ( $limit < 1 ) {
			return 1;
		}

		if ( $limit > self::PREVIEW_BATCH ) {
			return self::PREVIEW_BATCH;
		}

		return $limit;
	}

	/**
	 * How many embed rows one apply request may hold.
	 *
	 * @return int
	 */
	private function embed_limit() {
		/**
		 * Filter how many embed rows one bulk apply request may hold.
		 *
		 * The value is capped at 25. File rows are always one per request.
		 *
		 * @since 0.90.0
		 *
		 * @param int $limit Row limit.
		 */
		$limit = (int) apply_filters( 'rsfv_bulk_embed_batch', self::EMBED_BATCH );

		if ( $limit < 1 ) {
			return 1;
		}

		if ( $limit > self::EMBED_BATCH ) {
			return self::EMBED_BATCH;
		}

		return $limit;
	}

	/**
	 * Build a discarded row result.
	 *
	 * @param int    $id        Client row id.
	 * @param string $reason    Reason code.
	 * @param int    $post_id   Post ID, when known.
	 * @param string $title     Post title, when known.
	 * @param bool   $has_video Whether the post already has a video.
	 *
	 * @return array
	 */
	private function rejected( $id, $reason, $post_id = 0, $title = '', $has_video = false ) {
		return array(
			'can_write' => false,
			'result'    => $this->result( $id, 'discarded', $reason, $post_id, $title, $has_video ),
		);
	}

	/**
	 * Build one row result.
	 *
	 * @param int    $id        Client row id.
	 * @param string $status    ready, replace, discarded, or saved.
	 * @param string $reason    Reason code.
	 * @param int    $post_id   Post ID.
	 * @param string $title     Post title.
	 * @param bool   $has_video Whether the post has a video.
	 *
	 * @return array
	 */
	private function result( $id, $status, $reason, $post_id = 0, $title = '', $has_video = false ) {
		return array(
			'id'         => absint( $id ),
			'status'     => $status,
			'reason'     => $reason,
			'post_id'    => absint( $post_id ),
			'post_title' => $title,
			'has_video'  => (bool) $has_video,
			'message'    => $this->message( $reason ),
		);
	}

	/**
	 * Human text for a reason code.
	 *
	 * @param string $reason Reason code.
	 *
	 * @return string
	 */
	private function message( $reason ) {
		switch ( $reason ) {
			case 'ok':
				return __( 'Ready.', 'rsfv' );
			case 'will_replace':
				return __( 'This will replace the current featured video.', 'rsfv' );
			case 'missing_post':
				return __( 'No post was given.', 'rsfv' );
			case 'invalid_post':
				return __( 'No matching post was found.', 'rsfv' );
			case 'invalid_status':
				return __( 'That post cannot take a featured video.', 'rsfv' );
			case 'invalid_source':
				return __( 'Source must be self or embed.', 'rsfv' );
			case 'missing_value':
				return __( 'The video value is empty.', 'rsfv' );
			case 'value_too_long':
				return __( 'A cell is too long.', 'rsfv' );
			case 'invalid_embed':
				return __( 'The embed link is not a valid URL.', 'rsfv' );
			case 'unsupported_embed':
				return __( 'Embed links must be YouTube, Vimeo, or Dailymotion.', 'rsfv' );
			case 'invalid_url':
				return __( 'The video link is not allowed.', 'rsfv' );
			case 'invalid_mime':
				return __( 'The file is not a video this site allows.', 'rsfv' );
			case 'missing_file':
				return __( 'The video file was not included.', 'rsfv' );
			case 'file_mismatch':
				return __( 'The video file name does not match the row.', 'rsfv' );
			case 'duplicate_post':
				return __( 'This post is already used by an earlier row.', 'rsfv' );
			case 'has_video':
				return __( 'This post already has a featured video. Set replace to yes to change it.', 'rsfv' );
			case 'forbidden':
				return __( 'You cannot edit that post.', 'rsfv' );
			case 'upload_forbidden':
				return __( 'You cannot upload files.', 'rsfv' );
			case 'too_large':
				return __( 'The video is larger than this site allows.', 'rsfv' );
			case 'download_failed':
				return __( 'The video could not be downloaded.', 'rsfv' );
			case 'upload_failed':
				return __( 'The video could not be saved to the library.', 'rsfv' );
			case 'save_failed':
				return __( 'The video could not be set on the post.', 'rsfv' );
			default:
				return __( 'This row was skipped.', 'rsfv' );
		}
	}
}
