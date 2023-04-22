<?php
/**
 * WooCommerce compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\WooCommerce;

defined( 'ABSPATH' ) || exit;

use RSFV\Options;
use RSFV\Plugin;
use RSFV\Compatibility\Plugins\Base_Compatibility;
use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

/**
 * Class Compatibility
 *
 * @package RSFV
 */
class Compatibility extends Base_Compatibility {
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
	 * Constructor.
	 *
	 * @param string $id Compat ID.
	 * @param string $title Compat title.
	 */
	public function __construct( $id, $title ) {
		parent::__construct( $id, $title );
		$this->includes();

		$this->counter  = 0;
		$this->settings = new Settings();
		$this->setup();
	}

	/**
	 * Include required files.
	 *
	 * @return void
	 */
	public function includes() {
		// Settings.
		require_once RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/WooCommerce/class-settings.php';
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		$options = Options::get_instance();

		// Include post type.
		add_filter( 'rsfv_post_types_support', array( $this, 'update_post_types' ) );

		// Custom styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Adds support for single product.
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woo_get_video' ), 10, 2 );

		$product_archives_visibility = $options->get( 'product_archives_visibility' );

		if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
			// Adds support for product archives.
			remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'get_woo_archives_video' ), 10 );
		}
	}

	/**
	 * Include post types.
	 *
	 * @param array $post_types Existing post types.
	 *
	 * @return array Supported post types
	 */
	public function update_post_types( $post_types ) {
		$post_types['product'] = __( 'Products', 'rsfv' );

		return $post_types;
	}

	/**
	 * Conditionally enqueue scripts & styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Dummy style for inline styles.
		wp_register_style( 'rsfv-woocommerce', false, array(), time() );

		if ( is_woocommerce() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
			wp_enqueue_style( 'rsfv-woocommerce' );
			wp_add_inline_style( 'rsfv-woocommerce', $this->generate_inline_styles() );
		}
	}

	/**
	 * Generate inline styles.
	 *
	 * @return string Inline styles.
	 */
	public function generate_inline_styles() {
		// Set product videos to 1/1 aspect ratio.
		$styles = '.woocommerce ul.products li.product .woocommerce-product-gallery__image video.rsfv-video,
					.woocommerce div.product div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image video.rsfv-video,
				 .woocommerce ul.products li.product .woocommerce-product-gallery__image iframe.rsfv-video,
				 .woocommerce div.product div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image iframe.rsfv-video
				 { height: auto; width: 100% !important; aspect-ratio: 1/1; }';

		return $styles;
	}

	/**
	 * Filter method for getting WooCommerce video markup at products.
	 *
	 * @param string $html Thumbnail markup for products.
	 * @param int    $post_thumbnail_id Thumbnail ID.
	 * @param bool   $is_loop Whether to run at archives.
	 * @return string
	 */
	public function woo_get_video( $html, $post_thumbnail_id, $is_loop = false ) {
		global $product;

		$post_type = get_post_type( $product->get_id() ) ?? 'product';

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
			if ( in_array( $post_type, $post_types, true ) && ( 0 === $this->counter || $is_loop ) ) {

				if ( 'self' === $video_source ) {
					$media_id  = get_post_meta( $product->get_id(), RSFV_META_KEY, true );
					$video_url = wp_get_attachment_url( $media_id );

					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay playsinline' : '';
					$is_loop      = $is_loop ? 'loop' : '';
					$is_muted     = $is_muted ? 'muted' : '';
					$is_pip       = $is_pip ? 'autopictureinpicture' : '';
					$has_controls = $has_controls ? 'controls' : '';

					if ( $video_url ) {
						$html = '<div class="woocommerce-product-gallery__image rsfv-video__wrapper" data-thumb="' . RSFV_PLUGIN_URL . 'assets/images/video_frame.png"><video class="rsfv-video" id="rsfv_video_' . $product->get_id() . '" src="' . $video_url . '" style="max-width:100%;display:block;" ' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video></div>' . $html;
					}
				} else {
					// Get the meta value of video embed url.
					$embed_url = get_post_meta( $product->get_id(), RSFV_EMBED_META_KEY, true );
					// Prepare mark up attributes.
					$is_autoplay  = $is_autoplay ? 'autoplay=1&' : 'autoplay=0&';
					$is_loop      = $is_loop ? 'loop=1&' : '';
					$is_muted     = $is_muted ? 'mute=1&muted=1&' : '';
					$is_pip       = $is_pip ? 'picture-in-picture=1&' : '';
					$has_controls = $has_controls ? 'controls=1&' : 'controls=0&';

					if ( $embed_url ) {
						$html = '<div class="woocommerce-product-gallery__image rsfv-video__wrapper" data-thumb="' . RSFV_PLUGIN_URL . 'assets/images/video_frame.png"><iframe width="100%" height="540" src="' . $embed_url . "?{$has_controls}{$is_autoplay}{$is_loop}{$is_muted}{$is_pip}" . '" allow="" frameborder="0"></iframe></div>' . $html;
					}
				}

				$this->counter++;
			}
		}
		return $html;
	}

	/**
	 * Get video for woo archives.
	 *
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public function get_woo_archives_video( $post_id ) {
		$video_markup = $this->woo_get_video( '', 0, true );

		if ( $video_markup ) {
			echo wp_kses( $video_markup, Plugin::get_instance()->frontend_provider->get_allowed_html() );
		} else {
			woocommerce_template_loop_product_thumbnail();
		}
	}
}
