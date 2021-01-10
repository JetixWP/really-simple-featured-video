<?php
namespace RSFV;

use function RSFV\Settings\get_post_types;

/**
 * Class Shortcode
 */
class Shortcode {
	/**
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

		$video_id        = get_post_meta( $post->ID, RSFV_META_KEY, true );
		$video_url       = wp_get_attachment_url( $video_id );

		// Get enabled post types.
		$post_types = get_post_types();

		// Get autoplay option.
		$is_autoplay = Options::get_instance()->get( 'video_autoplay' );
		$is_autoplay = $is_autoplay ? 'autoplay' : '';

		// Get loop option.
		$is_loop    = Options::get_instance()->get( 'video_loop' );
		$is_loop    = $is_loop ? 'loop' : '';

		// Get mute option.
		$is_muted    = Options::get_instance()->get( 'mute_video' );
		$is_muted    = $is_muted ? 'muted' : '';

		// Get PictureInPicture option.
		$is_pip    = Options::get_instance()->get( 'picture_in_picture' );
		$is_pip    = $is_pip ? 'autopictureinpicture' : '';

		// Get video controls option.
		$has_controls = Options::get_instance()->get( 'video_controls' );
		$has_controls = $has_controls ? 'controls' : '';

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types ) ) {
				if ( $video_url ) {
					return '<video class="rsfv-video" id="rsfv-video-' . $post->ID . '" src="' . $video_url . '" style="max-width:100%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video>';
				}
			}
		}

	}

	/**
	 *
	 * Show video by post id.
	 *
	 * @param $atts array Shortcode attributes
	 * @return string|void
	 */
	public function show_video_by_post_id( $atts ) {
		global $post;

		$video_id        = get_post_meta( $atts['post_id'], RSFV_META_KEY, true );
		$video_url       = wp_get_attachment_url( $video_id );

		// Get enabled post types.
		$post_types = get_post_types();

		// Get autoplay option.
		$is_autoplay = Options::get_instance()->get( 'video_autoplay' );
		$is_autoplay = $is_autoplay ? 'autoplay' : '';

		// Get loop option.
		$is_loop    = Options::get_instance()->get( 'video_loop' );
		$is_loop    = $is_loop ? 'loop' : '';

		// Get mute option.
		$is_muted    = Options::get_instance()->get( 'mute_video' );
		$is_muted    = $is_muted ? 'muted' : '';

		// Get PictureInPicture option.
		$is_pip    = Options::get_instance()->get( 'picture_in_picture' );
		$is_pip    = $is_pip ? 'autopictureinpicture' : '';

		// Get video controls option.
		$has_controls = Options::get_instance()->get( 'video_controls' );
		$has_controls = $has_controls ? 'controls' : '';

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types ) ) {
				if ( $video_url ) {
					return '<video class="rsfv-video" id="rsfv-video-' . $atts['post_id'] . '" controls="" src="' . $video_url . '" style="max-width:100%%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video>';
				}
			}
		}
	}

}
