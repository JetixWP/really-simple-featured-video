<?php
/**
 * Product gallery thumbnails.
 *
 * @package          RSFV
 * @subpackage       Flatsome/WooCommerce/Templates
 * @flatsome-version 3.16.0
 */

use RSFV\Options;
use RSFV\FrontEnd as RSFV_FrontEnd;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as WooCommerceCompatibility;
use function RSFV\Settings\get_post_types;

global $post, $product;

$attachment_ids = $product->get_gallery_image_ids();
$post_thumbnail = has_post_thumbnail();
$thumb_count    = count( $attachment_ids );

if ( $post_thumbnail ) {
	++$thumb_count;
}
$render_without_attachments = apply_filters( 'flatsome_single_product_thumbnails_render_without_attachments', false, $product, array( 'thumb_count' => $thumb_count ) );

// Disable thumbnails if there is only one extra image.
if ( $post_thumbnail && $thumb_count == 1 && ! $render_without_attachments ) {
	return;
}

$rtl              = 'false';
$thumb_cell_align = 'left';

if ( is_rtl() ) {
	$rtl              = 'true';
	$thumb_cell_align = 'right';
}

if ( $attachment_ids || $render_without_attachments ) {
	$loop          = 0;
	$image_size    = 'thumbnail';
	$gallery_class = array( 'product-thumbnails', 'thumbnails' );

	// Check if custom gallery thumbnail size is set and use that.
	$image_check = wc_get_image_size( 'gallery_thumbnail' );
	if ( $image_check['width'] !== 100 ) {
		$image_size = 'gallery_thumbnail';
	}

	$gallery_thumbnail = wc_get_image_size( apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );

	if ( $thumb_count < 5 ) {
		$gallery_class[] = 'slider-no-arrows';
	}

	$gallery_class[] = 'slider row row-small row-slider slider-nav-small small-columns-4';
	$gallery_class   = apply_filters( 'flatsome_single_product_thumbnails_classes', $gallery_class );
	?>
	<div class="<?php echo implode( ' ', $gallery_class ); ?>"
		data-flickity-options='{
			"cellAlign": "<?php echo $thumb_cell_align; ?>",
			"wrapAround": false,
			"autoPlay": false,
			"prevNextButtons": true,
			"asNavFor": ".product-gallery-slider",
			"percentPosition": true,
			"imagesLoaded": true,
			"pageDots": false,
			"rightToLeft": <?php echo $rtl; ?>,
			"contain": true
		}'>
		<?php

		$product_id = $product->get_id();
		$post_type  = get_post_type( $product_id ) ?? '';

		// Get enabled post types.
		$post_types = get_post_types();

		$options       = Options::get_instance();
		$has_thumbnail = RSFV_FrontEnd::has_featured_video( $product_id );
		$video_html    = '';

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post_type, $post_types, true ) ) {
				$video_html    = WooCommerceCompatibility::woo_video_markup( $product->get_id(), 'woocommerce-product-gallery__image', '', true );
                $video_html    = '<div class="col is-nav-selected">' . $video_html . '</div>';
			}
		}

		$total_product_thumbnails = count( $product->get_gallery_image_ids() );
		$display_html             = '';

		if ( $has_thumbnail ) {
            echo $video_html; // phpcs:ignore;
		}

		if ( $post_thumbnail ) :
			?>
			<div class="col is-nav-selected first">
			<a>
				<?php
				$image_id  = get_post_thumbnail_id( $post->ID );
				$image     = wp_get_attachment_image_src( $image_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );
				$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
				$image     = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '" class="attachment-woocommerce_thumbnail" />';

				echo $image;
				?>
			</a>
			</div>
			<?php
		endif;

		foreach ( $attachment_ids as $attachment_id ) {

			$classes     = array( '' );
			$image_class = esc_attr( implode( ' ', $classes ) );
			$image       = wp_get_attachment_image_src( $attachment_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );

			if ( empty( $image ) ) {
				continue;
			}

			$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$image     = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '"  class="attachment-woocommerce_thumbnail" />';

			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', sprintf( '<div class="col"><a>%s</a></div>', $image ), $attachment_id, $post->ID, $image_class );

			++$loop;
		}
		?>
	</div>
	<?php
} ?>
