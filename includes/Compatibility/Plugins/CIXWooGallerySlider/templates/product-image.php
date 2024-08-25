<?php
/**
 * Single Product Image
 *
 * @package WooCommerce\Templates
 * @version 10.0.0
 * @CIX-version 2.3.10
 */

use RSFV\Options;
use RSFV\FrontEnd as RSFV_FrontEnd;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as WooCommerceCompatibility;
use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) || ! apply_filters( 'wpgs_default_gallery_hook', true ) ) {
	return;
}

global $product;
$post_thumbnail_id = $product->get_image_id();
$gallery_options   = get_option( 'wpgs_form' );
$html              = '';
$wrapper_classes   = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woo-product-gallery-slider',
		'woocommerce-product-gallery',
		'wpgs--' . ( has_post_thumbnail() ? 'with-images' : 'without-images' ),
		'images',

	)
);

$slider_rtl         = ( is_rtl() ) ? 'true' : 'false';
$lightbox_img_count = ( '1' == $gallery_options['lightbox_img_count'] ) ? 'true' : 'false';

do_action( 'wpgs_before_image_gallery' );

?>

<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" <?php echo esc_attr( 'true' == $slider_rtl ? 'dir=rtl' : '' ); ?> >

	<?php

	echo '<div class="wpgs-for">';
	if ( has_post_thumbnail() ) {

		$image = wp_get_attachment_image(
			$post_thumbnail_id,
			$gallery_options['slider_image_size'],
			true,
			array(
				'class'            => 'attachment-shop_single size-shop_single wp-post-image',
				'data-zoom_src'    => wp_get_attachment_image_src( $post_thumbnail_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
				'data-large_image' => wp_get_attachment_image_src( $post_thumbnail_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
				'alt'              => trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) ),
				'data-o_img'       => wp_get_attachment_image_url( $post_thumbnail_id, apply_filters( 'wpgs_new_main_img_size', wpgs_get_option( 'slider_image_size' ) ) ),
				'data-zoom-image'  => wp_get_attachment_image_url( $post_thumbnail_id, apply_filters( 'gallery_slider_zoom_image_size', 'large' ) ),
			)
		);
	} else {
		$placeholder_id = get_option( 'woocommerce_placeholder_image', 0 );
		$image          = wp_get_attachment_image(
			$placeholder_id,
			$gallery_options['slider_image_size'],
			true,
			array(
				'class'            => 'attachment-shop_single size-shop_single wp-post-image',
				'data-zoom_src'    => wp_get_attachment_image_src( $placeholder_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
				'data-large_image' => wp_get_attachment_image_src( $placeholder_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
				'alt'              => trim( wp_strip_all_tags( get_post_meta( $placeholder_id, '_wp_attachment_image_alt', true ) ) ),
				'data-o_img'       => wp_get_attachment_image_url( $placeholder_id, apply_filters( 'wpgs_new_main_img_size', wpgs_get_option( 'slider_image_size' ) ) ),
				'data-zoom-image'  => wp_get_attachment_image_url( $placeholder_id, apply_filters( 'gallery_slider_zoom_image_size', 'large' ) ),
			)
		);
	}
	$attachment_ids = $product->get_gallery_image_ids();

	$lightbox_src = wc_get_product_attachment_props( $post_thumbnail_id );

	$img_caption = get_the_title( $post_thumbnail_id );
	if ( apply_filters( 'wpgs_show_featured_image_in_gallery', true ) ) {

		if ( '1' === $gallery_options['lightbox_picker'] ) {
			$html .= '<div class="woocommerce-product-gallery__image single-product-main-image">';
			$html .= '<a class="wpgs-lightbox-icon" data-caption="' . $img_caption . '" data-fancybox="wpgs-lightbox"  href="' . $lightbox_src['url'] . '"     data-mobile=["clickContent:close","clickSlide:close"] 	data-click-slide="close" 	data-animation-effect="fade" 	data-loop="true"     data-infobar="' . $lightbox_img_count . '"     data-hash="false" >' . $image . '</a></div>';

		} else {
			$html .= '<div class="woocommerce-product-gallery__image single-product-main-image">' . $image . '</div>';

		}
	}

	echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:ignore.


	if ( $attachment_ids ) {
		foreach ( $attachment_ids as $attachment_id ) {
			$thumbnail_image = wp_get_attachment_image(
				$attachment_id,
				$gallery_options['slider_image_size'],
				true,
				array(
					'class'            => 'attachment-shop_single',
					'data-zoom_src'    => wp_get_attachment_image_src( $attachment_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
					'data-large_image' => wp_get_attachment_image_src( $attachment_id, apply_filters( 'gallery_slider_zoom_image_size', 'full' ) )[0],
					'alt'              => trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
				)
			);
			$lightbox_src    = wc_get_product_attachment_props( $attachment_id );

			$img_caption     = get_the_title( $attachment_id );
			$attachment_html = '';
			if ( '1' === $gallery_options['lightbox_picker'] ) {
				$attachment_html .= '<div><a class="wpgs-lightbox-icon" data-fancybox="wpgs-lightbox"                 data-caption="' . $img_caption . '"                 href="' . $lightbox_src['url'] . '" data-mobile=["clickContent:close","clickSlide:close"] 				data-click-slide="close" 				data-animation-effect="fade" 				data-loop="true"                 data-hash="false"                 data-infobar="' . $lightbox_img_count . '"                 >' . $thumbnail_image . '</a></div>';

			} else {
				$attachment_html .= '<div>' . $thumbnail_image . '</div>';
			}

			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $attachment_html, $post_thumbnail_id ); // phpcs:ignore.

		}
	}
	echo '</div>';


	do_action( 'woocommerce_product_thumbnails' );

	$attachment_ids         = $product->get_gallery_image_ids();
	$gallery_thumbnail_size = $gallery_options['thumbnail_image_size'];

	$product_id = $product->get_id();
	$post_type  = get_post_type( $product_id ) ?? ''; // phpcs:ignore.

	// Get enabled post types.
	$post_types = get_post_types();

	$options             = Options::get_instance();
	$has_video_thumbnail = RSFV_FrontEnd::has_featured_video( $product_id );
	$video_html          = '';

	if ( ! empty( $post_types ) ) {
		if ( in_array( $post_type, $post_types, true ) ) {
			$video_html = WooCommerceCompatibility::woo_video_markup( $product->get_id(), 'woocommerce-product-gallery__image', '', true );
			$video_html = '<div>' . $video_html . '</div>';
		}
	}

	$display_html             = '';

	if ( $attachment_ids ) {
		echo '<div class="wpgs-nav">';
		if ( has_post_thumbnail() ) {

			$image = wp_get_attachment_image(
				$post_thumbnail_id,
				$gallery_thumbnail_size,
				true,
				array(
					'class' => 'wpgs-thumb-main-image',
					'alt'   => trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) ),
				)
			);
		} else {
			$placeholder_id = get_option( 'woocommerce_placeholder_image', 0 );
			$image          = wp_get_attachment_image(
				$placeholder_id,
				$gallery_thumbnail_size,
				true,
				array(
					'class' => 'wpgs-thumb-main-image',
					'alt'   => trim( wp_strip_all_tags( get_post_meta( $placeholder_id, '_wp_attachment_image_alt', true ) ) ),
				)
			);

		}

		if ( $has_video_thumbnail ) {
            echo $video_html; // phpcs:ignore;
		}

		if ( apply_filters( 'wpgs_show_featured_image_in_gallery', true ) ) {
			echo '<div>' . $image . '</div>'; // phpcs:ignore.
		}

		foreach ( $attachment_ids as $attachment_id ) {
			$thumbnail_image = wp_get_attachment_image(
				$attachment_id,
				$gallery_thumbnail_size,
				true,
				array(
					'alt' => trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
				)
			);
			echo '<div>' . $thumbnail_image . '</div>'; // phpcs:ignore.
		}

		echo '</div>';
	} elseif ( $has_video_thumbnail ) {
		$image = '';
		if ( has_post_thumbnail() ) {

			$image = '<div>' . wp_get_attachment_image(
				$post_thumbnail_id,
				$gallery_thumbnail_size,
				true,
				array(
					'class' => 'wpgs-thumb-main-image',
					'alt'   => trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) ),
				)
			) . '</div>';
		}

		?>
		<div class="wpgs-nav">
			<div class="wpgs-thumb-main-image">
            <?php echo $video_html; // phpcs:ignore; ?>
			</div>
			<?php echo $image; // phpcs:ignore; ?>
		</div>
		<?php
	}
	do_action( 'wpgs_after_image_gallery' );

	?>

</div>
