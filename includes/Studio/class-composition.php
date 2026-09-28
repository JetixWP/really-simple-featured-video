<?php
/**
 * Video Studio compositions: what a video was made from.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

/**
 * Class Composition
 */
class Composition {
	/**
	 * Post meta key.
	 *
	 * @var string
	 */
	const META_KEY = 'rsfv_composition';

	/**
	 * Attachment meta key linking a generated video to its post.
	 *
	 * @var string
	 */
	const GENERATED_META_KEY = '_rsfv_generated_from';

	/**
	 * Sanitize one variable value against its definition.
	 *
	 * @param array $def   Variable definition.
	 * @param mixed $value Raw value.
	 *
	 * @return mixed Clean value.
	 */
	public static function sanitize_var( $def, $value ) {
		$default = isset( $def['default'] ) ? $def['default'] : '';
		$max_len = isset( $def['maxLength'] ) ? absint( $def['maxLength'] ) : 500;

		switch ( isset( $def['type'] ) ? $def['type'] : 'string' ) {
			case 'text':
				$clean = sanitize_textarea_field( is_scalar( $value ) ? (string) $value : '' );
				return mb_substr( $clean, 0, $max_len );

			case 'number':
				$number = is_numeric( $value ) ? (float) $value : (float) $default;
				if ( isset( $def['min'] ) ) {
					$number = max( (float) $def['min'], $number );
				}
				if ( isset( $def['max'] ) ) {
					$number = min( (float) $def['max'], $number );
				}
				return $number;

			case 'color':
				$color = is_string( $value ) ? sanitize_hex_color( $value ) : null;
				return $color ? $color : (string) $default;

			case 'boolean':
				return (bool) $value;

			case 'enum':
				$allowed = array();
				foreach ( isset( $def['options'] ) ? (array) $def['options'] : array() as $option ) {
					$allowed[] = is_array( $option ) ? (string) $option['value'] : (string) $option;
				}
				return in_array( (string) $value, $allowed, true ) ? (string) $value : (string) $default;

			case 'font':
				$fonts = Registry::get_fonts();
				return is_string( $value ) && isset( $fonts[ $value ] ) ? $value : (string) $default;

			case 'image':
				$id = absint( $value );
				return $id && wp_attachment_is_image( $id ) ? $id : 0;

			case 'images':
				$max = isset( $def['max'] ) ? absint( $def['max'] ) : 12;
				$ids = array();
				foreach ( is_array( $value ) ? $value : array() as $item ) {
					$id = absint( $item );
					if ( $id && wp_attachment_is_image( $id ) && ! in_array( $id, $ids, true ) ) {
						$ids[] = $id;
					}
					if ( count( $ids ) >= $max ) {
						break;
					}
				}
				return $ids;

			case 'string':
			default:
				$clean = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
				return mb_substr( $clean, 0, $max_len );
		}
	}

	/**
	 * Sanitize a composition sent by the editor.
	 *
	 * @param array $raw     Raw composition.
	 * @param int   $post_id Post the video is for.
	 *
	 * @return array|\WP_Error Clean composition.
	 */
	public static function sanitize( $raw, $post_id ) {
		if ( ! is_array( $raw ) ) {
			return new \WP_Error( 'rsfv_studio_composition', __( 'The video details are missing.', 'rsfv' ), array( 'status' => 400 ) );
		}

		$templates   = Registry::get_templates();
		$template_id = isset( $raw['template'] ) ? sanitize_key( $raw['template'] ) : '';
		if ( ! isset( $templates[ $template_id ] ) ) {
			return new \WP_Error( 'rsfv_studio_template', __( 'That template is not available.', 'rsfv' ), array( 'status' => 400 ) );
		}
		$template = $templates[ $template_id ];

		$presets   = Registry::get_presets();
		$preset_id = isset( $raw['preset'] ) ? sanitize_key( $raw['preset'] ) : '';
		if ( ! isset( $presets[ $preset_id ] ) ) {
			return new \WP_Error( 'rsfv_studio_preset', __( 'That video size is not available.', 'rsfv' ), array( 'status' => 400 ) );
		}
		$preset = $presets[ $preset_id ];

		$raw_vars = isset( $raw['vars'] ) && is_array( $raw['vars'] ) ? $raw['vars'] : array();
		$vars     = array();
		foreach ( $template['vars'] as $def ) {
			if ( empty( $def['id'] ) ) {
				continue;
			}
			$key          = $def['id'];
			$vars[ $key ] = self::sanitize_var( $def, isset( $raw_vars[ $key ] ) ? $raw_vars[ $key ] : ( isset( $def['default'] ) ? $def['default'] : '' ) );
		}

		// Which variables still hold the value filled from the post, so the
		// editor can say when the post changed after the video was made.
		$sources  = array();
		$fields   = Post_Fields::get( $post_id );
		$autofill = isset( $raw['autofill'] ) && is_array( $raw['autofill'] ) ? $raw['autofill'] : array();
		foreach ( $template['vars'] as $def ) {
			if ( empty( $def['autofill'] ) || empty( $autofill[ $def['id'] ] ) ) {
				continue;
			}
			$field = $def['autofill'];
			if ( ! array_key_exists( $field, $fields ) ) {
				continue;
			}
			$sources[ $def['id'] ] = array(
				'field'       => $field,
				'fingerprint' => Post_Fields::fingerprint( $fields[ $field ] ),
			);
		}

		$duration = isset( $raw['duration'] ) ? (float) $raw['duration'] : 0;

		$composition = array(
			'template'         => $template_id,
			'template_version' => $template['version'],
			'preset'           => $preset_id,
			'width'            => $preset['width'],
			'height'           => $preset['height'],
			'fps'              => $preset['fps'],
			'duration'         => round( max( 0, min( 600, $duration ) ), 3 ),
			'vars'             => $vars,
			'sources'          => $sources,
		);

		/**
		 * Filter extra settings saved with a composition (for example PRO
		 * music). Nothing is kept unless an add-on sanitizes it here.
		 *
		 * @since 0.91.0
		 *
		 * @param array $extras  Clean extras.
		 * @param array $raw     Raw extras from the editor.
		 * @param int   $post_id Post ID.
		 */
		$composition['extras'] = (array) apply_filters( 'rsfv_studio_sanitize_extras', array(), isset( $raw['extras'] ) && is_array( $raw['extras'] ) ? $raw['extras'] : array(), $post_id );

		$composition['hash'] = hash( 'sha256', wp_json_encode( array( $template_id, $template['version'], $preset_id, $vars, $composition['extras'] ) ) );

		/**
		 * Filter a sanitized Video Studio composition before it is saved.
		 *
		 * @since 0.91.0
		 *
		 * @param array $composition Composition.
		 * @param array $raw         Raw composition from the editor.
		 * @param int   $post_id     Post ID.
		 */
		return apply_filters( 'rsfv_studio_sanitize_composition', $composition, $raw, $post_id );
	}

	/**
	 * Get the saved composition of a post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array|null
	 */
	public static function get( $post_id ) {
		$composition = get_post_meta( $post_id, self::META_KEY, true );
		return is_array( $composition ) && ! empty( $composition['template'] ) ? $composition : null;
	}

	/**
	 * Whether the post's current featured video is the one Video Studio made.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function is_current( $post_id ) {
		$composition = self::get( $post_id );
		if ( ! $composition || empty( $composition['video_id'] ) ) {
			return false;
		}
		$source = get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );
		$video  = absint( get_post_meta( $post_id, RSFV_META_KEY, true ) );

		return ( '' === $source || 'self' === $source ) && absint( $composition['video_id'] ) === $video;
	}

	/**
	 * Post details that changed since the video was made.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string[] Variable ids whose source changed.
	 */
	public static function changed_sources( $post_id ) {
		$composition = self::get( $post_id );
		if ( ! $composition || empty( $composition['sources'] ) ) {
			return array();
		}

		$fields  = Post_Fields::get( $post_id );
		$changed = array();
		foreach ( $composition['sources'] as $var_id => $source ) {
			if ( ! isset( $source['field'] ) || ! array_key_exists( $source['field'], $fields ) ) {
				continue;
			}
			if ( Post_Fields::fingerprint( $fields[ $source['field'] ] ) !== $source['fingerprint'] ) {
				$changed[] = $var_id;
			}
		}

		return $changed;
	}

	/**
	 * Save a finished video and its composition on a post.
	 *
	 * @param int   $post_id     Post ID.
	 * @param array $composition Sanitized composition.
	 * @param int   $video_id    Video attachment ID.
	 * @param int   $poster_id   Poster attachment ID (0 for none).
	 *
	 * @return void
	 */
	public static function attach( $post_id, $composition, $video_id, $poster_id ) {
		$composition['video_id']  = absint( $video_id );
		$composition['poster_id'] = absint( $poster_id );
		$composition['created']   = time();

		update_post_meta( $post_id, self::META_KEY, $composition );
		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, 'self' );
		update_post_meta( $post_id, RSFV_META_KEY, absint( $video_id ) );
		if ( $poster_id ) {
			update_post_meta( $post_id, RSFV_POSTER_META_KEY, absint( $poster_id ) );
		}

		$link = array(
			'post_id' => absint( $post_id ),
			'hash'    => $composition['hash'],
		);
		update_post_meta( $video_id, self::GENERATED_META_KEY, $link );
		if ( $poster_id ) {
			update_post_meta( $poster_id, self::GENERATED_META_KEY, $link );
		}

		/**
		 * Fires after Video Studio saved a video on a post.
		 *
		 * @since 0.91.0
		 *
		 * @param int   $post_id     Post ID.
		 * @param int   $video_id    Video attachment ID.
		 * @param int   $poster_id   Poster attachment ID.
		 * @param array $composition Composition.
		 */
		do_action( 'rsfv_studio_video_created', $post_id, $video_id, $poster_id, $composition );
	}
}
