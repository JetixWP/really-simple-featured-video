<?php
/**
 * Post and product details templates can fill themselves from.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

/**
 * Class Post_Fields
 */
class Post_Fields {
	/**
	 * Image details for an attachment.
	 *
	 * @param int $id Attachment ID.
	 *
	 * @return array|null
	 */
	public static function image( $id ) {
		$id = absint( $id );
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return null;
		}

		$thumb = wp_get_attachment_image_url( $id, 'medium' );

		return array(
			'id'    => $id,
			'url'   => wp_get_attachment_url( $id ),
			'thumb' => $thumb ? $thumb : wp_get_attachment_url( $id ),
		);
	}

	/**
	 * Plain-text price.
	 *
	 * @param string|float $amount Amount.
	 *
	 * @return string
	 */
	protected static function price_text( $amount ) {
		if ( '' === $amount || null === $amount || ! function_exists( 'wc_price' ) ) {
			return '';
		}
		return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
	}

	/**
	 * Fields for a post.
	 *
	 * @param int|\WP_Post $post Post.
	 *
	 * @return array
	 */
	public static function get( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return array();
		}

		$excerpt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '...' );

		$featured = self::image( get_post_thumbnail_id( $post ) );
		$gallery  = $featured ? array( $featured ) : array();

		$fields = array(
			'title'          => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'excerpt'        => html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' ),
			'author'         => get_the_author_meta( 'display_name', $post->post_author ),
			'site_name'      => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'featured_image' => $featured,
			'gallery'        => $gallery,
			'price'          => '',
			'regular_price'  => '',
			'sale_badge'     => '',
			'category'       => '',
		);

		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$fields['category'] = html_entity_decode( $categories[0]->name, ENT_QUOTES, 'UTF-8' );
		}

		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$fields['price'] = self::price_text( $product->get_price() );

				if ( $product->is_on_sale() ) {
					$regular = $product->is_type( 'variable' ) ? $product->get_variation_regular_price( 'min' ) : $product->get_regular_price();
					$sale    = $product->get_price();

					$fields['regular_price'] = self::price_text( $regular );

					if ( (float) $regular > 0 && (float) $sale < (float) $regular ) {
						$fields['sale_badge'] = '-' . round( ( 1 - (float) $sale / (float) $regular ) * 100 ) . '%';
					} else {
						$fields['sale_badge'] = __( 'Sale', 'rsfv' );
					}
				}

				$seen = $featured ? array( $featured['id'] ) : array();
				foreach ( $product->get_gallery_image_ids() as $image_id ) {
					$image = in_array( absint( $image_id ), $seen, true ) ? null : self::image( $image_id );
					if ( $image ) {
						$fields['gallery'][] = $image;
						$seen[]              = $image['id'];
					}
				}

				$terms = get_the_terms( $post->ID, 'product_cat' );
				if ( is_array( $terms ) && ! empty( $terms ) ) {
					$fields['category'] = html_entity_decode( $terms[0]->name, ENT_QUOTES, 'UTF-8' );
				}
			}
		}

		/**
		 * Filter the details Video Studio templates can fill themselves from.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $fields Fields.
		 * @param \WP_Post $post   Post.
		 */
		return apply_filters( 'rsfv_studio_post_fields', $fields, $post );
	}

	/**
	 * Compare a field value with what the video was made from, for the
	 * "out of date" notice. Images compare by attachment id.
	 *
	 * @param mixed $value Field value.
	 *
	 * @return string
	 */
	public static function fingerprint( $value ) {
		if ( is_array( $value ) && isset( $value['id'] ) ) {
			return (string) absint( $value['id'] );
		}
		if ( is_array( $value ) ) {
			return implode(
				',',
				array_map(
					function ( $item ) {
						return is_array( $item ) && isset( $item['id'] ) ? absint( $item['id'] ) : (string) $item;
					},
					$value
				)
			);
		}
		return trim( (string) $value );
	}
}
