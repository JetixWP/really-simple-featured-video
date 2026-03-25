<?php
/**
 * Woostify theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\Woostify;

use RSFV\Compatibility\Themes\Base_Compatibility;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as BaseWooCompatibility;
use RSFV\Plugin;
use RSFV\Options;
use RSFV\FrontEnd as RSFV_FrontEnd;

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
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id = 'woostify';

		$this->override_woo_templates();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-woostify', $this->get_current_dir_url() . 'ThirdParty/Woostify/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/Woostify/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-woostify' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-woostify', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}

	/**
	 * Overrides theme Woo templates.
	 *
	 * @return void
	 */
	public function override_woo_templates() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$options                     = Options::get_instance();
		$product_archives_visibility = $options->get( 'product_archives_visibility' );

		$base_woo_compat_instance = BaseWooCompatibility::get_instance();

		if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
			remove_action( 'woocommerce_before_shop_loop_item_title', array( $base_woo_compat_instance, 'get_woo_archives_video' ), 10 );

			remove_action( 'woocommerce_before_shop_loop_item_title', 'woostify_loop_product_image', 50 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_video_in_loop' ), 50 );
		}

		if ( ! function_exists( 'woostify_options' ) ) {
			return;
		}

		$options = woostify_options( false );
		$gallery = $options['shop_single_product_gallery_layout_select'];

		if ( 'theme' === $gallery ) {
			// PRODUCT PAGE.
			// Product images box.
			remove_action( 'woocommerce_before_single_product_summary', 'woostify_single_product_gallery_image_slide', 30 );
			remove_action( 'woocommerce_before_single_product_summary', 'woostify_single_product_gallery_thumb_slide', 40 );

			add_action( 'woocommerce_before_single_product_summary', array( $this, 'woostify_single_product_gallery_image_slide' ), 30 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'woostify_single_product_gallery_thumb_slide' ), 40 );
		}
	}

	/**
	 * Renders video in loop.
	 *
	 * @return void
	 */
	public function render_video_in_loop() {
		global $product;

		if ( ! $product ) {
			return '';
		}

		$base_woo_compat_instance = BaseWooCompatibility::get_instance();
		$video_markup             = $base_woo_compat_instance->woo_get_video( '', 0, true );

		if ( $video_markup ) {
			echo wp_kses( $video_markup, Plugin::get_instance()->frontend_provider->get_allowed_html() );
		} elseif ( function_exists( 'woostify_loop_product_image' ) ) {
			woostify_loop_product_image();
			return;
		}
	}

	/**
	 * Renders product gallery on single product page.
	 *
	 * @version 2.4.4
	 * @return void
	 */
	public function woostify_single_product_gallery_image_slide() {
		$product_id = woostify_is_elementor_editor() ? woostify_get_last_product_id() : woostify_get_page_id();
		$product    = wc_get_product( $product_id );

		if ( empty( $product ) ) {
			return;
		}

		$image_id            = $product->get_image_id();
		$image_alt           = woostify_image_alt( $image_id, esc_attr__( 'Product image', 'woostify' ) );
		$get_size            = wc_get_image_size( 'shop_catalog' );
		$image_size          = $get_size['width'] . 'x' . ( ! empty( $get_size['height'] ) ? $get_size['height'] : $get_size['width'] );
		$image_medium_src[0] = wc_placeholder_img_src();
		$image_full_src[0]   = wc_placeholder_img_src();
		$image_srcset        = '';

		if ( $image_id ) {
			$image_medium_src = wp_get_attachment_image_src( $image_id, 'woocommerce_single' );
			$image_full_src   = wp_get_attachment_image_src( $image_id, 'full' );
			$image_size       = ( isset( $image_full_src[1] ) ? $image_full_src[1] : 800 ) . 'x' . ( isset( $image_full_src[2] ) ? $image_full_src[2] : 800 );
			$image_srcset     = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' ) : '';
		}

		if ( ! $image_id ) {
			$image_full_src[1] = '800';
			$image_full_src[2] = '800';
		}

		// Gallery.
		$gallery_id = $product->get_gallery_image_ids();

		// Support <img> srcset attr.
		$html_allowed                  = wp_kses_allowed_html( 'post' );
		$html_allowed['img']['srcset'] = true;

		// RSFV video support.
		$prod_post_type = get_post_type( $product_id ) ?? '';

		// Get enabled post types.
		$post_types = get_post_types();

		$has_video_thumbnail = RSFV_FrontEnd::has_featured_video( $product_id );
		?>

		<div class="product-images">
			<div class="product-images-container">

				<?php
				$video_html = '';

				if ( ! empty( $post_types ) ) {
					if ( in_array( $prod_post_type, $post_types, true ) ) {
						$video_html = BaseWooCompatibility::woo_video_markup( $product->get_id(), 'woocommerce-product-gallery__image', '', false );
						$video_html = '<div class="image-item is-selected">' . $video_html . '</div>';
					}
				}

				if ( $has_video_thumbnail ) {
					echo $video_html; // phpcs:ignore;
				}
				?>
				<figure class="image-item ez-zoom">
					<a href="<?php echo esc_url( isset( $image_full_src[0] ) ? $image_full_src[0] : '#' ); ?>" data-size="<?php echo esc_attr( $image_size ); ?>" data-elementor-open-lightbox="no">
						<?php echo wp_kses( $product->get_image( 'woocommerce_single', array(), true ), $html_allowed ); ?>
					</a>
				</figure>
				<?php

				if ( ! empty( $gallery_id ) ) {
					foreach ( $gallery_id as $key ) {
						$g_full_img_src = wp_get_attachment_image_src( $key, 'full' );
						if ( empty( $g_full_img_src ) ) {
							continue;
						}
						$g_medium_img_src = wp_get_attachment_image_src( $key, 'woocommerce_single' );
						$g_image_size     = $g_full_img_src[1] . 'x' . $g_full_img_src[2];
						$g_img_alt        = woostify_image_alt( $key, esc_attr__( 'Product image', 'woostify' ) );
						$g_img_srcset     = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $key, 'woocommerce_single' ) : '';
						?>
						<figure class="image-item ez-zoom">
							<a href="<?php echo esc_url( $g_full_img_src[0] ); ?>" data-size="<?php echo esc_attr( $g_image_size ); ?>" data-elementor-open-lightbox="no">
								<img width="<?php echo esc_attr( $g_medium_img_src[1] ); ?>" height="<?php echo esc_attr( $g_medium_img_src[2] ); ?>"  src="<?php echo esc_url( $g_medium_img_src[0] ); ?>" alt="<?php echo esc_attr( $g_img_alt ); ?>" srcset="<?php echo wp_kses_post( $g_img_srcset ); ?>">
							</a>
						</figure>
						<?php
					}
				}
				?>
			</div>

			<?php do_action( 'woostify_product_images_box_end' ); ?>
		</div>

		<?php
	}

	/**
	 * Renders product gallery on single product page.
	 *
	 * @version 2.4.4
	 * @return void
	 */
	public function woostify_single_product_gallery_thumb_slide() {
		$options = woostify_options( false );
		if ( ! in_array( $options['shop_single_gallery_layout'], array( 'vertical', 'horizontal' ), true ) ) {
			return;
		}

		$product_id = woostify_is_elementor_editor() ? woostify_get_last_product_id() : woostify_get_page_id();
		$product    = wc_get_product( $product_id );

		if ( empty( $product ) ) {
			return;
		}

		$image_id        = $product->get_image_id();
		$image_alt       = woostify_image_alt( $image_id, esc_attr__( 'Product image', 'woostify' ) );
		$image_small_src = $image_id ? wp_get_attachment_image_src( $image_id, 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img_src();
		$gallery_id      = $product->get_gallery_image_ids();

		// RSFV video support.
		$prod_post_type = get_post_type( $product_id ) ?? '';

		// Get enabled post types.
		$post_types = get_post_types();

		$has_video_thumbnail = RSFV_FrontEnd::has_featured_video( $product_id );
		?>

		<div class="product-thumbnail-images">
			<?php if ( ! empty( $gallery_id ) ) { ?>
			<div class="product-thumbnail-images-container">
				<?php
				$video_html = '';

				if ( ! empty( $post_types ) ) {
					if ( in_array( $prod_post_type, $post_types, true ) ) {
						$video_html = BaseWooCompatibility::woo_video_markup( $product->get_id(), 'woocommerce-product-gallery__image', '', true );
						$video_html = '<div class="thumbnail-item">' . $video_html . '</div>';
					}
				}

				if ( $has_video_thumbnail ) {
					echo $video_html; // phpcs:ignore;
				}
				?>

				<?php if ( ! empty( $image_small_src ) ) { ?>
					<div class="thumbnail-item">
						<img src="<?php echo esc_url( $image_small_src[0] ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>">
					</div>
				<?php } ?>

				<?php
				foreach ( $gallery_id as $key ) {
					$g_thumb_src = wp_get_attachment_image_src( $key, 'woocommerce_gallery_thumbnail' );
					$g_thumb_alt = woostify_image_alt( $key, esc_attr__( 'Product image', 'woostify' ) );

					if ( ! empty( $g_thumb_src ) ) {
						?>
						<div class="thumbnail-item">
							<img src="<?php echo esc_url( $g_thumb_src[0] ); ?>" alt="<?php echo esc_attr( $g_thumb_alt ); ?>">
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php } ?>
		</div>
		<?php
	}
}
