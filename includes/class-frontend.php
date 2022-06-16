<?php
/**
 * Frontend handler.
 *
 * @package RSFV
 */

namespace RSFV;

use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

/**
 * Class FrontEnd
 *
 * @package RSFV
 */
class FrontEnd {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * A counter variable.
	 *
	 * @var int $counter
	 */
	protected $counter;

	/**
	 * Front_End constructor.
	 */
	public function __construct() {
		$this->counter = 0;

		$this->get_posts_hooks();

		if ( Plugin::is_woo_activated() ) {
			$this->get_woo_hooks();
		}
	}

	/**
	 * Get a class instance.
	 *
	 * @return FrontEnd
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get posts hooks.
	 *
	 * @return void
	 */
	public function get_posts_hooks() {
		add_filter( 'post_thumbnail_html', array( $this, 'get_post_video' ), 10, 5 );
	}

	/**
	 * Get Woo hooks.
	 *
	 * @retun void
	 */
	public function get_woo_hooks() {
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woo_get_video' ), 10, 2 );
	}

	/**
	 * Filter method for getting WooCommerce video markup at products.
	 *
	 * @param string $html Thumbnail markup for products.
	 * @param int    $post_thumbnail_id Thumbnail ID.
	 * @return string
	 */
	public function woo_get_video( $html, $post_thumbnail_id ) {
		global $product;

		// Get enabled post types.
		$post_types = get_post_types();

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $product->get_id(), RSFV_SOURCE_META_KEY, true );
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
			if ( in_array( $product->post_type, $post_types, true ) && 0 === $this->counter ) {

				if ( 'self' === $video_source ) {
					$media_id  = get_post_meta( $product->get_id(), RSFV_META_KEY, true );
					$video_url = wp_get_attachment_url( $media_id );

					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay' : '';
					$is_loop      = $is_loop ? 'loop' : '';
					$is_muted     = $is_muted ? 'muted' : '';
					$is_pip       = $is_pip ? 'autopictureinpicture' : '';
					$has_controls = $has_controls ? 'controls' : '';

					if ( $video_url ) {
						$html = '<div class="woocommerce-product-gallery__image rsfv-video__wrapper" data-thumb="' . RSFV_PLUGIN_URL . 'assets/images/video_frame.png"><video class="rsfv-video" id="rsfv_video_' . $product->get_id() . '" src="' . $video_url . '" style="max-width:100%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video></div>' . $html;
					}
				} else {
					// Get the meta value of video embed url.
					$embed_url = get_post_meta( $product->get_id(), RSFV_EMBED_META_KEY, true );
					// Prepare mark up attributes.
					$is_autoplay = $is_autoplay ? 'autoplay=1&' : '';
					$is_loop     = $is_loop ? 'loop=1&' : '';
					$is_muted    = $is_muted ? 'mute=1&muted=1&' : '';
					$is_pip      = $is_pip ? 'picture-in-picture=1&' : '';

					if ( $embed_url ) {
						$html = '<div class="woocommerce-product-gallery__image rsfv-video__wrapper" data-thumb="' . RSFV_PLUGIN_URL . 'assets/images/video_frame.png"><iframe width="100%" height="540" src="' . $embed_url . "?{$is_autoplay}{$is_loop}{$is_muted}{$is_pip}" . '" allow="" frameborder="0"></iframe></div>' . $html;
					}
				}

				$this->counter++;
			}
		}
		return $html;
	}

	/**
	 * Filter method for getting video markup at posts & pages.
	 *
	 * @param string $html Holds markup data.
	 * @param int    $post_id Post ID.
	 * @param int    $post_thumbnail_id Thumbnail ID.
	 * @param int    $size Requested image size.
	 * @param string $attr Query string or array of attributes.
	 *
	 * @return string
	 */
	public function get_post_video( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		global $post;

		// Get enabled post types.
		$post_types = get_post_types();

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {
				// Get the meta value of video attachment.
				$video_id = get_post_meta( $post_id, RSFV_META_KEY, true );

				// Get the meta value of video embed url.
				$embed_url = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );

				if ( $video_id || $embed_url ) {
					return '<div style="clear:both">' . do_shortcode( '[rsfv]' ) . '</div>';
				}
			}
		}
		return $html;
	}
}
