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
	 * @return string
	 */
	public function show_video() {
		global $post;

		return $this->get_video_markup( $post->ID, $post->post_type );
	}

	/**
	 * Show video by post id.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function show_video_by_post_id( $atts ) {
		if ( is_array( $atts ) && ! isset( $atts['post_id'] ) ) {
			return esc_html__( 'Please add a post id!', 'rsfv' );
		}

		$post = get_post( $atts['post_id'] );

		return $this->get_video_markup( $post->ID, $post->post_type );
	}

	/**
	 * Creates video markup for showing at frontend.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return string
	 */
	public function get_video_markup( $post_id, $post_type ) {
		// Get enabled post types.
		$post_types = get_post_types();

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );
		$video_source = $video_source ? $video_source : 'self';

		$video_controls = 'self' !== $video_source ? get_video_controls( 'embed' ) : get_video_controls();

		// Get autoplay option.
		$is_autoplay = ( is_array( $video_controls ) && isset( $video_controls['autoplay'] ) ) && $video_controls['autoplay'];

		// Get loop option.
		$is_loop = ( is_array( $video_controls ) && isset( $video_controls['loop'] ) ) && $video_controls['loop'];

		// Get mute option.
		$is_muted = ( is_array( $video_controls ) && isset( $video_controls['mute'] ) ) && $video_controls['mute'];

		// Get PictureInPicture option.
		$is_pip = ( is_array( $video_controls ) && isset( $video_controls['pip'] ) ) && $video_controls['pip'];

		// Get video controls option.
		$has_controls = ( is_array( $video_controls ) && isset( $video_controls['controls'] ) ) && $video_controls['controls'];

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post_type, $post_types, true ) ) {

				if ( 'self' === $video_source ) {
					$video_id  = get_post_meta( $post_id, RSFV_META_KEY, true );
					$video_url = wp_get_attachment_url( $video_id );

					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay playsinline' : '';
					$is_loop      = $is_loop ? 'loop' : '';
					$is_muted     = $is_muted ? 'muted' : '';
					$is_pip       = $is_pip ? 'autopictureinpicture' : '';
					$has_controls = $has_controls ? 'controls' : '';

					if ( $video_url ) {
						return '<video class="rsfv-video" id="rsfv-video-' . esc_attr( $post_id ) . '" src="' . esc_url( $video_url ) . '" style="max-width:100%;display:block;" ' . esc_attr( $has_controls ) . ' ' . esc_attr( $is_autoplay ) . ' ' . esc_attr( $is_loop ) . ' ' . esc_attr( $is_muted ) . ' ' . esc_attr( $is_pip ) . '></video>';
					}
				}

				// Get the meta value for video url.
				$input_url = esc_url( get_post_meta( $post_id, RSFV_EMBED_META_KEY, true ) );

				// Generate video embed URL.
				$embed_url = Plugin::get_instance()->frontend_provider->generate_embed_url( $input_url );

				// Prepare mark up attributes.
				$is_autoplay  = $is_autoplay ? 'autoplay=1&' : 'autoplay=0&';
				$is_loop      = $is_loop ? 'loop=1&' : '';
				$is_muted     = $is_muted ? 'mute=1&muted=1&' : '';
				$is_pip       = $is_pip ? 'picture-in-picture=1&' : '';
				$has_controls = $has_controls ? 'controls=1&' : 'controls=0&';

				if ( $embed_url ) {
					return '<div><iframe class="rsfv-video" width="100%" height="540" src="' . esc_url( $embed_url . '?' . esc_attr( $has_controls ) . esc_attr( $is_autoplay ) . esc_attr( $is_loop ) . esc_attr( $is_muted ) . esc_attr( $is_pip ) ) . '" allow="" frameborder="0"></iframe></div>';
				}
			}
		}
	}

}
