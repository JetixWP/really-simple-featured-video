<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.1
 * @astra_addon_version 4.6.3
 */

defined( 'ABSPATH' ) || exit;

use RSFV\Compatibility\Plugins\WooCommerce\Compatibility;
use RSFV\Plugin;
use RSFV\Options;
use RSFV\FrontEnd;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

$columns            = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id  = $product->get_image_id();
$wrapper_classes    = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);
$is_vertical_layout = 'vertical-slider' === astra_get_option( 'single-product-gallery-layout' );


$options = Options::get_instance();

$has_order_change       = $options->has( 'global-woo-video-order' );
$video_order            = $options->get( 'global-woo-video-order', 'first' );
$video_slide_attributes = '';
$has_featured_video     = FrontEnd::has_featured_video( $product->get_id() );
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
	<figure class="woocommerce-product-gallery__wrapper">
		<?php
		if ( $post_thumbnail_id ) {
			$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
		} else {
			$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
			$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'astra-addon' ) );
			$html .= '</div>';
		}
		$markup = apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id );
		echo wp_kses( $markup, Plugin::get_instance()->frontend_provider->get_allowed_html() );

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</figure>

	<?php
	$attachment_ids         = $product->get_gallery_image_ids();
	$video_slide_number     = rsfv_get_video_slide_number( $has_featured_video, $has_order_change, $video_order, $attachment_ids );
	$video_slide_attributes = ' data-slide-number=' . absint( $video_slide_number );
	?>
	<!-- Product gallery thumbnail -->
	<?php if ( $is_vertical_layout ) { ?>
		<div id="ast-gallery-thumbnails" class="
		<?php
		if ( $attachment_ids && $product->get_image_id() && rsfv_has_gallery_slider( $has_featured_video, $attachment_ids ) ) {
			?>
			slider-disabled <?php } ?>">
			<div class="ast-vertical-navigation-wrapper">
				<button id="ast-vertical-navigation-prev"></button>
				<button id="ast-vertical-navigation-next"></button>
			</div>
			<div id="ast-vertical-thumbnail-wrapper">
				<div id="ast-vertical-slider-inner" class="woocommerce-product-gallery-thumbnails__wrapper">
					<?php
					if ( $has_featured_video && $video_slide_number < 1 ) {
						rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
					}

					if ( $post_thumbnail_id ) {
						echo wp_kses_post( rsfv_get_gallery_thumbnail( $post_thumbnail_id, $has_featured_video && $video_slide_number < 1 ? 1 : 0 ) );
					}

					if ( $has_featured_video && 1 === $video_slide_number ) {
						rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
					}

					/**
					 *  Implement code inside do_action( 'woocommerce_product_thumbnails' ); without the 'woocommerce_single_product_image_thumbnail_html' filter
					 */
					if ( $attachment_ids && $product->get_image_id() ) {
						if ( $has_featured_video ) {
							$slide_number = $video_slide_number > 1 ? 1 : 2;
						} else {
							$slide_number = 1;
						}

						foreach ( $attachment_ids as $attachment_id ) {
							echo wp_kses_post( rsfv_get_gallery_thumbnail( $attachment_id, $slide_number ) );
							$slide_number++;

							if ( $has_featured_video && $slide_number === $video_slide_number ) {
								rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
							}
						}
					}

					?>
				</div>
			</div>
		</div>
	<?php } else { ?>
		<div class="ast-single-product-thumbnails
		<?php
		if ( $attachment_ids && $product->get_image_id() && rsfv_has_gallery_slider( $has_featured_video, $attachment_ids ) ) {
			?>
			slider-disabled <?php } ?>">
			<div class="woocommerce-product-gallery-thumbnails__wrapper">
				<?php
				if ( $has_featured_video && $video_slide_number < 1 ) {
					rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
				}

				if ( $post_thumbnail_id ) {
					echo wp_kses_post( rsfv_get_gallery_thumbnail( $post_thumbnail_id, $has_featured_video && $video_slide_number < 1 ? 1 : 0 ) );
				}

				if ( $has_featured_video && 1 === $video_slide_number ) {
					rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
				}

				/**
				 *  Implement code inside do_action( 'woocommerce_product_thumbnails' ); without the 'woocommerce_single_product_image_thumbnail_html' filter
				 */

				if ( $attachment_ids && $product->get_image_id() ) {
					if ( $has_featured_video ) {
						$slide_number = $video_slide_number > 1 ? 1 : 2;
					} else {
						$slide_number = 1;
					}

					foreach ( $attachment_ids as $attachment_id ) {
						echo wp_kses_post( rsfv_get_gallery_thumbnail( $attachment_id, $slide_number ) );
						$slide_number++;

						if ( $has_featured_video && $slide_number === $video_slide_number ) {
							rsfv_display_video_markup( $product->get_id(), $video_slide_attributes, true );
						}
					}
				}
				?>
			</div>
		</div>
	<?php } ?>
</div>

<?php

/**
 * Get HTML for gallery thumbnail.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $slide_number Slide Number.
 * @return string
 */
function rsfv_get_gallery_thumbnail( $attachment_id, $slide_number ) {
	$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
	$thumbnail_size    = apply_filters( 'ast_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
	$full_size         = apply_filters( 'ast_gallery_full_size', apply_filters( 'ast_product_thumbnails_large_size', 'full' ) );
	$thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
	$alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
	$full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
	$image             = wp_get_attachment_image( $attachment_id, $thumbnail_size );
	$is_first_slide    = 0 === $slide_number ? 'flex-active-slide' : '';

	return '<div data-slide-number="' . esc_attr( $slide_number ) . '" data-thumb="' . esc_url( isset( $thumbnail_src[0] ) ? $thumbnail_src[0] : '' ) . '" data-thumb-alt="' . esc_attr( $alt_text ) . '" class="ast-woocommerce-product-gallery__image ' . esc_attr( $is_first_slide ) . '">' . $image . '</div>';
}

/**
 * Get thumbnail slider number for gallery.
 *
 * @param bool   $has_featured_video If featured video is set.
 * @param bool   $has_order_change If video has order change set.
 * @param string $video_order Order of video.
 * @param array  $attachment_ids All Attachment IDs.
 *
 * @return int
 */
function rsfv_get_video_slide_number( $has_featured_video, $has_order_change, $video_order, $attachment_ids ) {
	if ( ! $has_featured_video ) {
		return -1;
	}

	$video_slide_number = 0;
	$has_pro            = Plugin::get_instance()->has_pro_active();

    if ( ! $has_pro ) {
        return $video_slide_number;
    }

	if ( ! $has_order_change ) {
		return $video_slide_number;
	}

	$attachment_count = count( $attachment_ids );

	if ( 'second' === $video_order ) {
		$video_slide_number = 1;
	} elseif ( 'last' === $video_order ) {
		$video_slide_number = $attachment_count + 1;
	}

	return $video_slide_number;
}

/**
 * Display featured video markup.
 *
 * @param int    $product_id Product ID.
 * @param string $video_slide_attributes HTML attributes for video markup.
 * @param bool   $thumbnail_only If only thumbnail markup required.
 *
 * @return void
 */
function rsfv_display_video_markup( $product_id, $video_slide_attributes, $thumbnail_only = false ) {
	echo wp_kses( Compatibility::woo_video_markup( $product_id, 'ast-woocommerce-product-gallery__image', $video_slide_attributes, $thumbnail_only ), Plugin::get_instance()->frontend_provider->get_allowed_html() );
}

/**
 * Checks whether gallery slider nav should be active.
 *
 * @param bool  $has_featured_video If featured video is set.
 * @param array $attachment_ids All Attachment IDs.
 *
 * @return bool
 */
function rsfv_has_gallery_slider( $has_featured_video, $attachment_ids ) {
	$attachment_count = count( $attachment_ids ) + 1;

	if ( $has_featured_video ) {
		return $attachment_count + 1 <= 4;
	}

	return $attachment_count <= 4;
}
?>
