<?php
namespace RSFV;

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
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woo_get_video' ), 10, 2 );
		add_filter( 'post_thumbnail_html', array( $this, 'get_post_video' ), 10, 5 );
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
	 * Filter method for getting WooCommerce video markup at products.
	 *
	 * @param $html
	 * @param $post_thumbnail_id
	 * @return string
	 */
	public function woo_get_video( $html, $post_thumbnail_id ) {
		global $product;

		$media_id  = get_post_meta( $product->get_id(), RSFV_META_KEY, true );
		$video_url = wp_get_attachment_url( $media_id );
		if ( $video_url && 0 == $this->counter ) {
			$html = '<div class="woocommerce-product-gallery__image rsfv-video__wrapper" data-thumb="' . RSFV_PLUGIN_URL . 'assets/images/video_frame.png"><video class="rsfv-video" id="rsfv_video_' . $product->get_id() . '" controls="" src="' . $video_url . '" style="max-width:100%;display:block;"></video></div>' . $html;
			$this->counter ++;
		}

		return $html;
	}

	/**
	 * Filter method for getting video markup at posts & pages.
	 *
	 * @param $html string Holds markup data.
	 * @param $post_id
	 * @param $post_thumbnail_id
	 * @param $size
	 * @param $attr
	 *
	 * @return string
	 */
	public function get_post_video( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		$video_id = get_post_meta( $post_id, RSFV_META_KEY, true );
		if ( $video_id ) {
			return '<div style="clear:both">' . do_shortcode( '[rsfv]' ) . '</div>';
		}
		return $html;
	}
}
