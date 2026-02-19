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
use RSFV\Shortcode;
use RSFV\Compatibility\Plugins\Base_Compatibility;
use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay;
use RSFV\Featuresets\Hover_Autoplay\Utils as Hover_Utils;
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
	 */
	public function __construct() {
		parent::__construct();

		$this->id = 'woocommerce';

		$this->counter = 0;
		$this->setup();
	}

	/**
	 * Register Settings.
	 *
	 * @param array $settings Active settings file array.
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		// Settings.
		$settings[] = include 'class-settings.php';

		return $settings;
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		$options = Options::get_instance();

		// Registers WooCommerce related settings tab.
		add_filter( 'rsfv_get_settings_pages', array( $this, 'register_settings' ) );

		// Include post type.
		add_filter( 'rsfv_post_types_support', array( $this, 'update_post_types' ) );

		// Update default settings for Enabled Post Types.
		add_filter( 'rsfv_default_enabled_post_types', array( $this, 'update_default_enabled_post_types' ) );

		// Enable product post type by default.
		add_filter( 'rsfv_get_enabled_post_types', array( $this, 'update_enabled_post_types' ) );

		// Custom styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Adds support for single product.
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woo_get_video' ), 10, 2 );

		// Update body classes for Woo.
		add_filter( 'rsfv_body_classes', array( $this, 'modify_body_classes' ) );

		// Initialize hover support for WooCommerce.
		$this->init_hover_support();

		$product_archives_visibility = $options->get( 'product_archives_visibility' );

		if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
			// Adds support for product archives.
			remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'get_woo_archives_video' ), 10 );
		}

		add_action( 'rsfv_woo_archives_product_thumbnails', 'woocommerce_template_loop_product_thumbnail', 10 );

		$product_video_external_url = $options->get( 'product_video_external_url' );

		if ( $options->has( 'product_video_external_url' ) && $product_video_external_url ) {
			add_filter( 'rsfv_get_video_source', array( $this, 'set_external_product_url' ), 10, 2 );
			add_filter( 'rsfv_get_woo_video_source', array( $this, 'set_external_product_url' ), 10, 2 );
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
	 * Include Product post type at default enabled post types.
	 *
	 * @param array $post_types Default post types.
	 *
	 * @return array Supported post types
	 */
	public function update_default_enabled_post_types( $post_types ) {
		$post_types['product'] = true;

		return $post_types;
	}

	/**
	 * Initialize hover support for WooCommerce videos
	 */
	private function init_hover_support() {
		// Only initialize if hover autoplay is available.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			// Add WooCommerce-specific hover filters.
			add_filter( 'rsfv_woo_video_container_attributes', array( $this, 'add_woo_hover_attributes' ), 10, 3 );
			add_filter( 'rsfv_woo_video_html', array( $this, 'enhance_woo_video_html' ), 10, 3 );
		}
	}

	/**
	 * Enable Product post type by default.
	 *
	 * @param array $enabled_post_types Existing post types.
	 *
	 * @return array Supported post types
	 */
	public function update_enabled_post_types( $enabled_post_types ) {
		$post_types = Options::get_instance()->get( 'post_types' );
		$post_types = is_array( $post_types ) ? array_keys( $post_types ) : '';

		if ( ! is_array( $post_types ) && empty( $post_types ) ) {
			$enabled_post_types[] = 'product';
		}

		return $enabled_post_types;
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
		$styles = '';

		// Set product videos to 16/9 aspect ratio.
		$styles .= '.woocommerce ul.products li.product .woocommerce-product-gallery__image video.rsfv-video,
				    .woocommerce ul.products li.product .woocommerce-product-gallery__image iframe.rsfv-video,
					.woocommerce div.product div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image video.rsfv-video,
				 .woocommerce div.product div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image iframe.rsfv-video,
				 .woocommerce.product.rsfv-has-video div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image video.rsfv-video,
				 .woocommerce.product.rsfv-has-video div.woocommerce-product-gallery figure.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image iframe.rsfv-video,
				 { height: auto; width: 100% !important; aspect-ratio: 16/9; }';

		$styles .= '.woocommerce-loop-product__title { margin-top: 20px; }';

		$styles .= '.woocommerce.product.rsfv-has-video .woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image + .woocommerce-product-gallery__image--placeholder
					{ display: none; }';

		// Add hover-specific styles if enabled.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$hover_settings = Hover_Autoplay::get_settings();

			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$styles .= $this->get_hover_styles();
			}
		}

		return apply_filters( 'rsfv_woo_generated_dynamic_css', $styles );
	}

	/**
	 * Get hover-specific CSS styles for WooCommerce
	 *
	 * @return string
	 */
	private function get_hover_styles() {
		return '
		/* WooCommerce Hover Styles */
		.woocommerce-product-gallery__image[data-rsfv-hover-enabled="true"] {
			position: relative;
			overflow: hidden;
			transition: transform 0.2s ease;
		}
		
		@media (min-width: 769px) {
			.woocommerce-product-gallery__image[data-rsfv-hover-enabled="true"]:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
			}
		}
		
		.woocommerce-product-gallery__image .rsfv-play-overlay {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: 50px;
			height: 50px;
			background: rgba(0, 0, 0, 0.7);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			transition: opacity 0.3s ease;
			pointer-events: none;
			z-index: 5;
		}
		
		.woocommerce-product-gallery__image:hover .rsfv-play-overlay,
		.woocommerce-product-gallery__image:focus-within .rsfv-play-overlay {
			opacity: 1;
		}
		
		.woocommerce-product-gallery__image .rsfv-play-overlay::before {
			content: "";
			width: 0;
			height: 0;
			border-left: 15px solid #ffffff;
			border-top: 9px solid transparent;
			border-bottom: 9px solid transparent;
			margin-left: 2px;
		}
		
		/* Shop/Archive page hover styles */
		.woocommerce ul.products li.product .woocommerce-product-gallery__image[data-rsfv-hover-enabled="true"] {
			border-radius: 4px;
		}
		
		@media (min-width: 769px) {
			.woocommerce ul.products li.product .woocommerce-product-gallery__image[data-rsfv-hover-enabled="true"]:hover {
				transform: scale(1.02);
				box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
			}
		}
		';
	}

	/**
	 * Get counter.
	 *
	 * @return int
	 */
	public function get_counter() {
		return $this->counter;
	}

	/**
	 * Set counter.
	 *
	 * @param int $counter Counter.
	 */
	public function set_counter( $counter ) {
		$this->counter = $counter;
	}

	/**
	 * Sets the video source for external products if supported video url in external product url.
	 *
	 * @param string $video_source Video source type.
	 * @param int    $product_id Product ID.
	 *
	 * @return string
	 */
	public function set_external_product_url( $video_source, $product_id ) {
		// Only modify for product post type.
		if ( 'product' !== get_post_type( $product_id ) ) {
			return $video_source;
		}

		$product      = wc_get_product( $product_id );
		$product_type = $product ? $product->get_type() : '';

		$video_url = '';
		if ( 'self' === $video_source ) {
			$video_url = get_post_meta( $product_id, RSFV_META_KEY, true );
		} elseif ( 'embed' === $video_source ) {
			$video_url = get_post_meta( $product_id, RSFV_EMBED_META_KEY, true );
		}

		if ( '' === $video_url && 'external' === $product_type ) {
			$options              = Options::get_instance();
			$external_url_enabled = $options->get( 'product_video_external_url', false );
			$external_url         = get_post_meta( $product_id, '_product_url', true );

			if ( $external_url_enabled ) {
				$frontend   = Plugin::get_instance()->frontend_provider;
				$embed_data = $frontend->parse_embed_url( $external_url );

				if ( is_array( $embed_data ) && isset( $embed_data['host'] ) && in_array( $embed_data['host'], array( 'youtube', 'vimeo', 'dailymotion' ), true ) ) {
					$video_source = 'embed';

					add_filter(
						'rsfv_get_embed_woo_video_url',
						function () use ( $external_url ) {
							return esc_url( $external_url );
						}
					);

					add_filter(
						'rsfv_get_embed_video_url',
						function () use ( $external_url ) {
							return esc_url( $external_url );
						}
					);
				}
			}
		}

		return $video_source;
	}

	/**
	 * Get external product URL if available.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return string
	 */
	public function get_external_product_url( $product_id ) {
		$product      = wc_get_product( $product_id );
		$product_type = $product ? $product->get_type() : '';

		$video_url = '';

		if ( 'external' === $product_type ) {
			$external_url = get_post_meta( $product_id, '_product_url', true );
			$video_url    = esc_url( $external_url );
		}

		return $video_url;
	}

	/**
	 * Product Video Markup.
	 *
	 * @param int    $id Product ID.
	 * @param string $wrapper_class Wrapper markup classes.
	 * @param string $wrapper_attributes Wrapper markup attributes.
	 * @param bool   $thumbnail_only Whether only thumbnail should be returned.
	 * @param bool   $is_archives Whether this is for archives/shop pages.
	 *
	 * @return string
	 */
	public static function woo_video_markup( $id, $wrapper_class = 'woocommerce-product-gallery__image', $wrapper_attributes = '', $thumbnail_only = false, $is_archives = false ) {
		$post_type = get_post_type( $id ) ?? 'product';

		// Get enabled post types.
		$post_types = get_post_types();

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $id, RSFV_SOURCE_META_KEY, true );
		$video_source = $video_source ? $video_source : 'self';

		// Catalyst for external products support.
		$video_source = apply_filters( 'rsfv_get_woo_video_source', $video_source, $id );

		$video_controls = 'self' !== $video_source ? get_video_controls( 'embed' ) : get_video_controls();

		// Prepare video data for hover functionality.
		$video_data = array(
			'product_id'    => $id,
			'post_type'     => $post_type,
			'source'        => $video_source,
			'controls'      => $video_controls,
			'is_archives'   => $is_archives,
			'wrapper_class' => $wrapper_class,
		);

		$video_html = '';

		if ( ! empty( $post_types ) && in_array( $post_type, $post_types, true ) ) {
			$img_url           = RSFV_PLUGIN_URL . 'assets/images/video_frame.png';
			$thumbnail         = apply_filters( 'rsfv_default_woo_gallery_video_thumb', $img_url );
			$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );

			// Return early if thumbnail is only required.
			if ( $thumbnail_only ) {
				$thumbnail_attributes = self::get_woo_container_attributes( $wrapper_attributes, $video_data, true );
				return '<div class="' . esc_attr( $wrapper_class ) . '" data-thumb="' . esc_url( $thumbnail ) . '"' . $thumbnail_attributes . '><img width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '" src="' . esc_url( $thumbnail ) . '" alt /></div>';
			}

			// Get enhanced container attributes for hover functionality.
			$enhanced_wrapper_attributes = self::get_woo_container_attributes( $wrapper_attributes, $video_data );

			if ( 'self' === $video_source ) {
				$video_html = self::get_self_hosted_woo_video( $id, $wrapper_class, $enhanced_wrapper_attributes, $thumbnail, $video_controls, $video_data );
			} else {
				$video_html = self::get_embed_woo_video( $id, $wrapper_class, $enhanced_wrapper_attributes, $thumbnail, $video_controls, $video_data );
			}

			// Apply WooCommerce-specific hover enhancements.
			if ( ! empty( $video_html ) ) {
				$video_html = apply_filters( 'rsfv_woo_video_html', $video_html, $video_data, $id );
			}
		}

		return $video_html;
	}

	/**
	 * Get enhanced container attributes for WooCommerce videos
	 *
	 * @param string $existing_attributes Existing wrapper attributes.
	 * @param array  $video_data Video data.
	 * @param bool   $thumbnail_only Whether this is thumbnail only.
	 * @return string
	 */
	private static function get_woo_container_attributes( $existing_attributes, $video_data, $thumbnail_only = false ) {
		$attributes = array();

		// Add hover attributes if hover autoplay is enabled.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) && ! $thumbnail_only ) {
			$hover_settings = Hover_Autoplay::get_settings();

			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$attributes['data-rsfv-video']         = 'true';
				$attributes['data-rsfv-hover-enabled'] = 'true';
				$attributes['data-rsfv-source']        = $video_data['source'];
				$attributes['data-rsfv-context']       = 'woocommerce';

				if ( $video_data['is_archives'] ) {
					$attributes['data-rsfv-archives'] = 'true';
				}
			}
		}

		// Apply WooCommerce-specific filter.
		$attributes = apply_filters( 'rsfv_woo_video_container_attributes', $attributes, $video_data, $existing_attributes );

		// Build attributes string.
		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) && $value ) {
				$attr_string .= ' ' . esc_attr( $key );
			} elseif ( ! is_bool( $value ) ) {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $existing_attributes . $attr_string;
	}

	/**
	 * Get self-hosted video HTML for WooCommerce
	 *
	 * @param int    $id Product ID.
	 * @param string $wrapper_class Wrapper class.
	 * @param string $wrapper_attributes Wrapper attributes.
	 * @param string $thumbnail Thumbnail URL.
	 * @param array  $video_controls Video controls.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	private static function get_self_hosted_woo_video( $id, $wrapper_class, $wrapper_attributes, $thumbnail, $video_controls, $video_data ) {
		$media_id  = get_post_meta( $id, RSFV_META_KEY, true );
		$video_url = esc_url( wp_get_attachment_url( $media_id ) );

		if ( ! $video_url ) {
			return '';
		}

		// Get video attributes with hover enhancements.
		$video_attrs = self::get_enhanced_video_attributes( $video_controls );

		// Get poster image.
		$poster_id  = get_post_meta( $id, RSFV_POSTER_META_KEY, true );
		$poster_url = $poster_id ? wp_get_attachment_url( $poster_id ) : '';

		// Build attributes string.
		$attr_string = '';
		foreach ( $video_attrs as $key => $value ) {
			if ( is_bool( $value ) && $value ) {
				$attr_string .= ' ' . esc_attr( $key );
			} elseif ( ! is_bool( $value ) ) {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		$video_element = sprintf(
			'<video class="rsfv-video" id="rsfv_video_%d" src="%s"%s%s></video>',
			$id,
			$video_url,
			$poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : '',
			$attr_string
		);

		// Add play overlay if hover is enabled.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$hover_settings = Hover_Autoplay::get_settings();

			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$video_element .= '<div class="rsfv-play-overlay" aria-hidden="true"></div>';

				// For Woo Compatibility, wrap video in additional container.
				$video_element = sprintf(
					'<div class="rsfv-video-wrapper" %s>%s</div>',
					$wrapper_attributes,
					$video_element
				);
			}
		}

		return sprintf(
			'<div class="%s" data-thumb="%s"%s>%s</div>',
			esc_attr( $wrapper_class ),
			esc_url( $thumbnail ),
			$wrapper_attributes,
			$video_element
		);
	}

	/**
	 * Get embed video HTML for WooCommerce
	 *
	 * @param int    $id Product ID.
	 * @param string $wrapper_class Wrapper class.
	 * @param string $wrapper_attributes Wrapper attributes.
	 * @param string $thumbnail Thumbnail URL.
	 * @param array  $video_controls Video controls.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	private static function get_embed_woo_video( $id, $wrapper_class, $wrapper_attributes, $thumbnail, $video_controls, $video_data ) {
		$input_url = apply_filters( 'rsfv_get_embed_woo_video_url', esc_url( get_post_meta( $id, RSFV_EMBED_META_KEY, true ) ), $id );

		if ( ! $input_url ) {
			return '';
		}

		$frontend   = Plugin::get_instance()->frontend_provider;
		$embed_data = $frontend->parse_embed_url( $input_url );
		$video_type = is_array( $embed_data ) ? $embed_data['host'] : 'unknown';

		// Enhanced embed URL with mobile support.
		$embed_url = $frontend->generate_embed_url( $input_url );

		// Use enhanced mobile iframe src method.
		if ( method_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Utils', 'enhance_iframe_src' ) ) {
			$embed_url = Hover_Utils::enhance_iframe_src( $embed_url, $video_type );
		}

		// Get URL parameters.
		$url_params = self::get_woo_embed_url_parameters( $video_controls, $video_data );

		// Determine the correct URL concatenation method.
		$final_embed_url_with_params = false !== strpos( $embed_url, '?' )
		? $embed_url . '&' . $url_params
		: $embed_url . '?' . $url_params;

		$final_embed_url_with_params = apply_filters( 'rsfv_final_embed_url_with_params', $final_embed_url_with_params );

		// Build iframe with mobile enhancements.
		$iframe_attrs = array(
			'class'           => 'rsfv-video',
			'width'           => '100%',
			'height'          => '540',
			'src'             => esc_url( $final_embed_url_with_params ),
			'frameborder'     => '0',
			'allowfullscreen' => true,
			'allow'           => 'autoplay; fullscreen; picture-in-picture',
			'loading'         => 'lazy',
		);

		// Add mobile-specific attributes.
		if ( wp_is_mobile() ) {
			$iframe_attrs['playsinline']        = true;
			$iframe_attrs['webkit-playsinline'] = true;
		}

		// Add accessibility attributes if hover is enabled.
		if ( class_exists( 'RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$hover_settings = Hover_Autoplay::get_settings();
			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$iframe_attrs['role']       = 'presentation';
				$iframe_attrs['aria-label'] = __( 'Product video - tap to play', 'rsfv' );
			}
		}

		$iframe_attr_string = '';
		foreach ( $iframe_attrs as $key => $value ) {
			if ( is_bool( $value ) && $value ) {
				$iframe_attr_string .= ' ' . esc_attr( $key );
			} elseif ( ! is_bool( $value ) ) {
				$iframe_attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		$iframe_html = '<iframe' . $iframe_attr_string . '></iframe>';

		// Wrap iframe in responsive container.
		if ( class_exists( 'RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$hover_settings = Hover_Autoplay::get_settings();
			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$iframe_html = sprintf(
					'<div class="rsfv-iframe-wrapper" %s>%s</div>',
					$wrapper_attributes,
					$iframe_html
				);

				// Add play overlay for mobile.
				if ( wp_is_mobile() ) {
						$iframe_html .= '<div class="rsfv-play-overlay" aria-hidden="true"></div>';
				}
			}
		}

		return sprintf(
			'<div class="%s" data-thumb="%s"%s>%s</div>',
			esc_attr( $wrapper_class ),
			esc_url( $thumbnail ),
			$wrapper_attributes,
			$iframe_html
		);
	}

	/**
	 * Get enhanced video attributes with hover support
	 *
	 * @param array $video_controls Video controls.
	 * @return array
	 */
	private static function get_enhanced_video_attributes( $video_controls ) {
		$attributes = Shortcode::get_html5_video_attributes( $video_controls );

		// Hover enhancements.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Utils' ) ) {
			$attributes = Hover_Utils::get_html5_attributes( $attributes );
		}

		return $attributes;
	}

	/**
	 * Get embed URL parameters with hover enhancements
	 *
	 * @param array $video_controls Video controls.
	 * @param array $video_data Video data.
	 * @return string
	 */
	private static function get_woo_embed_url_parameters( $video_controls, $video_data ) {
		// Standard parameters.
		$is_autoplay  = ! empty( $video_controls['autoplay'] ) ? 'autoplay=1&' : 'autoplay=0&';
		$is_loop      = ! empty( $video_controls['loop'] ) ? 'loop=1&' : '';
		$is_muted     = ! empty( $video_controls['mute'] ) ? 'mute=1&muted=1&' : '';
		$is_pip       = ! empty( $video_controls['pip'] ) ? 'picture-in-picture=1&' : '';
		$has_controls = ! empty( $video_controls['controls'] ) ? 'controls=1&' : 'controls=0&';

		$base_params = $has_controls . $is_autoplay . $is_loop . $is_muted . $is_pip;

		// Add hover-specific API parameters.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) && isset( $video_data['embed_type'] ) ) {
			$hover_settings = Hover_Autoplay::get_settings();

			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				switch ( $video_data['embed_type'] ) {
					case 'youtube':
						$base_params .= 'enablejsapi=1&modestbranding=1&';
						break;
					case 'vimeo':
						$base_params .= 'api=1&background=1&';
						break;
					case 'dailymotion':
						$base_params .= 'api=postMessage&';
						break;
				}
			}
		}

		return rtrim( $base_params, '&' );
	}

	/**
	 * Filter method for getting WooCommerce video markup at products
	 *
	 * @param string $html Thumbnail markup for products.
	 * @param int    $post_thumbnail_id Thumbnail ID.
	 * @param bool   $is_archives Whether to run at archives.
	 * @return string
	 */
	public function woo_get_video( $html, $post_thumbnail_id, $is_archives = false ) {
		global $product;

		if ( 'object' !== gettype( $product ) ) {
			return $html;
		}

		$product_id = $product->get_id();
		$post_type  = get_post_type( $product_id ) ?? '';
		$post_types = get_post_types();

		// Enhanced video markup with hover support.
		$video_html = self::woo_video_markup( $product->get_id(), 'woocommerce-product-gallery__image', '', false, $is_archives );

		if ( ! empty( $post_types ) ) {
			$updated_html = $video_html . $html;

			if ( ! $is_archives && apply_filters( 'rsfv_has_modified_video_thumbnail_html', false, $this->counter, $product ) ) {
				$html = apply_filters( 'rsfv_video_thumbnail_html', $html, $video_html, $this->counter, $product );
			} elseif ( 0 === $this->counter || $is_archives ) {
				$html = $updated_html;
			}

			if ( in_array( $post_type, $post_types, true ) && ! $is_archives ) {
				++$this->counter;
			}
		}

		return $html;
	}

	/**
	 * Get video for woo archives
	 *
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public function get_woo_archives_video( $post_id = '' ) {
		$video_markup = $this->woo_get_video( '', 0, true );

		if ( $video_markup ) {
			echo wp_kses( $video_markup, Plugin::get_instance()->frontend_provider->get_allowed_html() );
		} else {
			do_action( 'rsfv_woo_archives_product_thumbnails', $post_id );
		}
	}

	/**
	 * Add WooCommerce-specific hover attributes
	 *
	 * @param array  $attributes Current attributes.
	 * @param array  $video_data Video data.
	 * @param string $existing_attributes Existing wrapper attributes.
	 * @return array
	 */
	public function add_woo_hover_attributes( $attributes, $video_data, $existing_attributes ) {
		$hover_settings = Hover_Autoplay::get_settings();

		if ( empty( $hover_settings['enable_hover_autoplay'] ) ) {
			return $attributes;
		}

		// Add WooCommerce-specific attributes.
		$attributes['data-rsfv-woo-product-id'] = $video_data['product_id'];

		if ( ! empty( $video_data['embed_type'] ) ) {
			$attributes['data-rsfv-embed-type'] = $video_data['embed_type'];
		}

		return $attributes;
	}

	/**
	 * Enhance WooCommerce video HTML with hover features
	 *
	 * @param string $video_html Video HTML.
	 * @param array  $video_data Video data.
	 * @param int    $product_id Product ID.
	 * @return string
	 */
	public function enhance_woo_video_html( $video_html, $video_data, $product_id ) {
		$hover_settings = Hover_Autoplay::get_settings();

		if ( empty( $hover_settings['enable_hover_autoplay'] ) ) {
			return $video_html;
		}

		// Add accessibility attributes.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Utils' ) ) {
			$video_html = Hover_Utils::add_accessibility_attributes( $video_html );
		}

		return $video_html;
	}

	/**
	 * Modify page body classes.
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public function modify_body_classes( $classes ) {
		$options = Options::get_instance();

		// Default is enabled.
		$product_archives_visibility = $options->has( 'product_archives_visibility' ) ? $options->get( 'product_archives_visibility' ) : true;

		if ( $product_archives_visibility && ( is_shop() || is_product_category() || is_product_tag() ) ) {
			$classes[] = 'rsfv-archives-support';
		}

		// Add hover support class if enabled.
		if ( class_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$hover_settings = Hover_Autoplay::get_settings();
			if ( ! empty( $hover_settings['enable_hover_autoplay'] ) ) {
				$classes[] = 'rsfv-hover-enabled';

				if ( is_product() ) {
					$classes[] = 'rsfv-product-hover';
				} elseif ( is_shop() || is_product_category() || is_product_tag() ) {
					$classes[] = 'rsfv-archive-hover';
				}
			}
		}

		return $classes;
	}
}
