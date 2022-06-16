<?php
/**
 * Shortcode handler.
 *
 * @package RSFV
 */

namespace RSFV;

use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

/**
 * Class Shortcode
 */
class Shortcode {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Shortcode constructor.
	 */
	public function __construct() {
		// Shortcode to display the video on pages, or posts.
		add_shortcode( 'rsfv', array( $this, 'show_video' ) );

		// Shortcode to display using post id.
		add_shortcode( 'rsfv_by_postid', array( $this, 'show_video_by_post_id' ) );
	}

	/**
	 * Get an instance of class.
	 *
	 * @return Shortcode
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Show video on posts & pages.
	 *
	 * @return string|void
	 */
	public function show_video() {
		global $post;

		// Get enabled post types.
		$post_types = get_post_types();

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
		$video_source = $video_source ? $video_source : 'self';

		$video_controls = 'self' !== $video_source ? get_video_controls( 'embed' ) : get_video_controls();

		// Get autoplay option.
		$is_autoplay = is_array( $video_controls ) && isset( $video_controls['autoplay'] );

		// Get loop option.
		$is_loop = is_array( $video_controls ) && isset( $video_controls['loop'] );

		// Get mute option.
		$is_muted = is_array( $video_controls ) && isset( $video_controls['mute'] );

		// Get PictureInPicture option.
		$is_pip = is_array( $video_controls ) && isset( $video_controls['pip'] );

		// Get video controls option.
		$has_controls = is_array( $video_controls ) && isset( $video_controls['controls'] );

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {

				if ( 'self' === $video_source ) {
					$video_id  = get_post_meta( $post->ID, RSFV_META_KEY, true );
					$video_url = wp_get_attachment_url( $video_id );

					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay' : '';
					$is_loop      = $is_loop ? 'loop' : '';
					$is_muted     = $is_muted ? 'muted' : '';
					$is_pip       = $is_pip ? 'autopictureinpicture' : '';
					$has_controls = $has_controls ? 'controls' : '';

					if ( $video_url ) {
						return '<video class="rsfv-video" id="rsfv-video-' . $post->ID . '" src="' . $video_url . '" style="max-width:100%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video>';
					}
				}

				// Get the meta value of video embed url.
				$embed_url = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );

				// Prepare mark up attributes.
				$is_autoplay = $is_autoplay ? 'autoplay=1&' : '';
				$is_loop     = $is_loop ? 'loop=1&' : '';
				$is_muted    = $is_muted ? 'mute=1&muted=1&' : '';
				$is_pip      = $is_pip ? 'picture-in-picture=1&' : '';

				if ( $embed_url ) {
					return '<div><iframe width="100%" height="540" src="' . $embed_url . "?{$is_autoplay}{$is_loop}{$is_muted}{$is_pip}" . '" allow="" frameborder="0"></iframe></div>';
				}
			}
		}

	}

	/**
	 * Show video by post id.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string|void
	 */
	public function show_video_by_post_id( $atts ) {
		if ( is_array( $atts ) && ! isset( $atts['post_id'] ) ) {
			return esc_html__( 'Please add a post id!', 'rsfv' );
		}

		$post = get_post( $atts['post_id'] );

		// Get enabled post types.
		$post_types = get_post_types();

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $atts['post_id'], RSFV_SOURCE_META_KEY, true );
		$video_source = $video_source ? $video_source : 'self';

		$video_controls = 'self' !== $video_source ? get_video_controls( 'embed' ) : get_video_controls();

		// Get autoplay option.
		$is_autoplay = is_array( $video_controls ) && isset( $video_controls['autoplay'] );

		// Get loop option.
		$is_loop = is_array( $video_controls ) && isset( $video_controls['loop'] );

		// Get mute option.
		$is_muted = is_array( $video_controls ) && isset( $video_controls['mute'] );

		// Get PictureInPicture option.
		$is_pip = is_array( $video_controls ) && isset( $video_controls['pip'] );

		// Get video controls option.
		$has_controls = is_array( $video_controls ) && isset( $video_controls['controls'] );

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {

				if ( 'self' === $video_source ) {
					$video_id  = get_post_meta( $atts['post_id'], RSFV_META_KEY, true );
					$video_url = wp_get_attachment_url( $video_id );

					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay' : '';
					$is_loop      = $is_loop ? 'loop' : '';
					$is_muted     = $is_muted ? 'muted' : '';
					$is_pip       = $is_pip ? 'autopictureinpicture' : '';
					$has_controls = $has_controls ? 'controls' : '';

					if ( $video_url ) {
						return '<video class="rsfv-video" id="rsfv-video-' . $atts['post_id'] . '" src="' . $video_url . '" style="max-width:100%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video>';
					}
				}

				// Get the meta value of video embed url.
				$embed_url = get_post_meta( $atts['post_id'], RSFV_EMBED_META_KEY, true );

				// Prepare mark up attributes.
				$is_autoplay = $is_autoplay ? 'autoplay=1&' : '';
				$is_loop     = $is_loop ? 'loop=1&' : '';
				$is_muted    = $is_muted ? 'mute=1&muted=1&' : '';
				$is_pip      = $is_pip ? 'picture-in-picture=1&' : '';

				if ( $embed_url ) {
					return '<div><iframe width="100%" height="540" src="' . $embed_url . "?{$is_autoplay}{$is_loop}{$is_muted}{$is_pip}" . '" allow="" frameborder="0"></iframe></div>';
				}
			}
		}
	}

}
