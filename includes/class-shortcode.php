<?php
/**
 * Shortcode handler.
 *
 * @package RSFV
 */

namespace RSFV;

use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay;
use RSFV\Featuresets\Hover_Autoplay\Utils as Hover_Utils;
use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

/**
 * Class Shortcode
 */
class Shortcode {
	/**
	 * Class instance.
	 *
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

		// Initialize hover functionality.
		$this->init_hover_support();
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
	 * @return string
	 */
	public function show_video( $atts = array() ) {
		global $post;

		$video_markup = $this->get_video_markup( $post->ID, $post->post_type );

		// Apply hover enhancements if enabled.
		if ( class_exists( 'RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$video_data = array(
				'post_id' => $post->ID,
				'post_type' => $post->post_type,
				'source' => get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true ) ? get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true ) : 'self',
				'shortcode_atts' => $atts,
			);

			$video_markup = apply_filters( 'rsfv_shortcode_video_output', $video_markup, $video_data );
		}

		return $video_markup;
	}

	/**
	 * Show video by post id.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function show_video_by_post_id( $atts ) {
		if ( is_array( $atts ) && ! isset( $atts['post_id'] ) ) {
			return esc_html__( 'Please add a post id!', 'rsfv' );
		}

		$post = get_post( $atts['post_id'] );

		if ( ! $post ) {
			return esc_html__( 'Post not found!', 'rsfv' );
		}

		$video_markup = $this->get_video_markup( $post->ID, $post->post_type );

		// Apply hover enhancements if enabled.
		if ( class_exists( 'RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			$video_data = array(
				'post_id' => $post->ID,
				'post_type' => $post->post_type,
				'source' => get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true ) ? get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true ) : 'self',
				'shortcode_atts' => $atts,
			);

			$video_markup = apply_filters( 'rsfv_shortcode_video_output', $video_markup, $video_data );
		}

		return $video_markup;
	}

	/**
	 * Initialize hover autoplay support
	 */
	private function init_hover_support() {
		// Ensure hover autoplay class exists.
		if ( class_exists( 'RSFV\\Featuresets\\Hover_Autoplay\\Init' ) ) {
			add_filter( 'rsfv_video_html5_attributes', array( $this, 'enhance_html5_attributes' ), 10, 2 );
			add_filter( 'rsfv_video_iframe_src', array( $this, 'enhance_iframe_src' ), 10, 3 );
			add_filter( 'rsfv_video_container_class', array( $this, 'add_hover_container_class' ), 10, 2 );
			add_filter( 'rsfv_shortcode_video_output', array( $this, 'handle_shortcode_output_filter' ), 10, 2 );
			add_filter( 'rsfv_video_container_attributes', array( $this, 'add_container_attributes' ), 10, 2 );
		}
	}

	/**
	 * Creates video markup for showing at frontend.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @return string
	 */
	public function get_video_markup( $post_id, $post_type ) {
		// Get enabled post types.
		$post_types = get_post_types();

		if ( empty( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {
			return '';
		}

		// Get video source.
		$video_source = get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true ) ? get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true ) : 'self';

		// Get video controls.
		$video_controls = 'self' !== $video_source ? get_video_controls( 'embed' ) : get_video_controls();

		// Prepare video data for hover functionality.
		$video_data = array(
			'post_id' => $post_id,
			'post_type' => $post_type,
			'source' => $video_source,
			'controls' => $video_controls,
		);

		if ( 'self' === $video_source ) {
			return $this->get_self_hosted_video( $post_id, $video_controls, $video_data );
		} else {
			return $this->get_embed_video( $post_id, $video_controls, $video_data );
		}
	}

	/**
	 * Generate self-hosted video markup
	 *
	 * @param int   $post_id Post ID.
	 * @param array $video_controls Video control settings.
	 * @param array $video_data Video data for hover functionality.
	 * @return string
	 */
	private function get_self_hosted_video( $post_id, $video_controls, $video_data ) {
		$video_id = get_post_meta( $post_id, RSFV_META_KEY, true );

		if ( ! $video_id ) {
			return '';
		}

		$video_url = wp_get_attachment_url( $video_id );

		if ( ! $video_url ) {
			return '';
		}

		// Get poster image.
		$poster_id = get_post_meta( $post_id, RSFV_POSTER_META_KEY, true );
		$poster_url = $poster_id ? wp_get_attachment_url( $poster_id ) : '';

		// Prepare video attributes.
		$attributes = $this->get_html5_video_attributes( $video_controls, $video_data );

		// Apply hover enhancements to attributes.
		$attributes = apply_filters( 'rsfv_video_html5_attributes', $attributes, $video_data );

		// Build video element.
		$video_html = sprintf(
			'<video id="rsfv-video-%d" class="rsfv-video" src="%s"%s%s></video>',
			esc_attr( $post_id ),
			esc_url( $video_url ),
			$poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : '',
			$this->build_attributes_string( $attributes )
		);

		// Wrap in container with hover support.
		$container_class = apply_filters( 'rsfv_video_container_class', 'rsfv-video-wrapper', $video_data );
		$container_attributes = apply_filters( 'rsfv_video_container_attributes', array(), $video_data );

		$container_attrs_string = $this->build_attributes_string( $container_attributes );

		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $container_class ),
			$container_attrs_string,
			$video_html
		);
	}

	/**
	 * Generate embed video markup
	 *
	 * @param int   $post_id Post ID.
	 * @param array $video_controls Video control settings.
	 * @param array $video_data Video data for hover functionality.
	 * @return string
	 */
	private function get_embed_video( $post_id, $video_controls, $video_data ) {
		$input_url = esc_url( get_post_meta( $post_id, RSFV_EMBED_META_KEY, true ) );

		if ( ! $input_url ) {
			return '';
		}

		// Parse embed data to get video type.
		$frontend = Plugin::get_instance()->frontend_provider;
		$embed_data = $frontend->parse_embed_url( $input_url );
		$video_type = is_array( $embed_data ) ? $embed_data['host'] : 'unknown';

		// Add video type to video data.
		$video_data['embed_type'] = $video_type;
		$video_data['embed_data'] = $embed_data;

		// Generate base embed URL.
		$embed_url = $frontend->generate_embed_url( $input_url );

		// Build URL parameters.
		$url_params = $this->get_embed_url_parameters( $video_controls );

		// Apply hover enhancements to iframe src.
		$final_embed_url = apply_filters( 'rsfv_video_iframe_src', $embed_url, $url_params, $video_data );

		// Build iframe.
		$iframe_html = sprintf(
			'<iframe class="rsfv-video" width="100%%" height="540" src="%s" frameborder="0" allowfullscreen></iframe>',
			esc_url( $final_embed_url . '?' . $url_params )
		);

		// Wrap in responsive container.
		$iframe_html = $this->wrap_in_responsive_container( $iframe_html, $video_data );

		// Wrap in container with hover support.
		$container_class = apply_filters( 'rsfv_video_container_class', 'rsfv-video-wrapper', $video_data );
		$container_attributes = apply_filters( 'rsfv_video_container_attributes', array(), $video_data );

		$container_attrs_string = $this->build_attributes_string( $container_attributes );

		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $container_class ),
			$container_attrs_string,
			$iframe_html
		);
	}

	/**
	 * Get HTML5 video attributes
	 *
	 * @param array $video_controls Video control settings.
	 * @param array $video_data Video data.
	 * @return array
	 */
	private function get_html5_video_attributes( $video_controls, $video_data ) {
		$attributes = array(
			'style' => 'max-width:100%;display:block;',
		);

		// Add control attributes based on settings.
		if ( ! empty( $video_controls['controls'] ) ) {
			$attributes['controls'] = true;
		}

		if ( ! empty( $video_controls['autoplay'] ) ) {
			$attributes['autoplay'] = true;
			$attributes['playsinline'] = true;
		}

		if ( ! empty( $video_controls['loop'] ) ) {
			$attributes['loop'] = true;
		}

		if ( ! empty( $video_controls['mute'] ) ) {
			$attributes['muted'] = true;
		}

		if ( ! empty( $video_controls['pip'] ) ) {
			$attributes['autopictureinpicture'] = true;
		}

		return $attributes;
	}

	/**
	 * Get embed URL parameters
	 *
	 * @param array $video_controls Video control settings.
	 * @return string
	 */
	private function get_embed_url_parameters( $video_controls ) {
		$params = array();

		$params[] = ! empty( $video_controls['autoplay'] ) ? 'autoplay=1' : 'autoplay=0';
		$params[] = ! empty( $video_controls['controls'] ) ? 'controls=1' : 'controls=0';

		if ( ! empty( $video_controls['loop'] ) ) {
			$params[] = 'loop=1';
		}

		if ( ! empty( $video_controls['mute'] ) ) {
			$params[] = 'mute=1&muted=1';
		}

		if ( ! empty( $video_controls['pip'] ) ) {
			$params[] = 'picture-in-picture=1';
		}

		return implode( '&', array_filter( $params ) );
	}

	/**
	 * Wrap iframe in responsive container
	 *
	 * @param string $iframe_html Iframe HTML.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	private function wrap_in_responsive_container( $iframe_html, $video_data ) {
		// Default aspect ratio.
		$aspect_ratio = '16:9';

		$aspect_ratio = apply_filters( 'rsfv_video_aspect_ratio', $aspect_ratio, $video_data );

		return sprintf(
			'<div class="rsfv-iframe-wrapper" data-aspect-ratio="%s">%s</div>',
			esc_attr( $aspect_ratio ),
			$iframe_html
		);
	}

	/**
	 * Build attributes string from array
	 *
	 * @param array $attributes Attributes array.
	 * @return string
	 */
	private function build_attributes_string( $attributes ) {
		if ( empty( $attributes ) ) {
			return '';
		}

		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$attr_string .= ' ' . esc_attr( $key );
				}
			} else {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $attr_string;
	}

	/**
	 * Enhance HTML5 video attributes for hover functionality
	 *
	 * @param array $attributes Current attributes.
	 * @param array $video_data Video data.
	 * @return array
	 */
	public function enhance_html5_attributes( $attributes, $video_data ) {
		$hover_settings = Hover_Autoplay::get_settings();

		if ( ! $hover_settings['enable_hover_autoplay'] ) {
			return $attributes;
		}

		// Add hover-specific attributes.
		$attributes['preload'] = 'metadata';
		$attributes['playsinline'] = true;

		// Ensure muted for autoplay compatibility.
		if ( ! isset( $attributes['muted'] ) ) {
			$attributes['muted'] = true;
		}

		return $attributes;
	}

	/**
	 * Enhance iframe src for hover functionality
	 *
	 * @param string $embed_url Base embed URL.
	 * @param string $url_params URL parameters.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	public function enhance_iframe_src( $embed_url, $url_params, $video_data ) {
		if ( ! isset( $video_data['embed_type'] ) ) {
			return $embed_url;
		}

		$video_type = $video_data['embed_type'];

		// Use enhanced mobile iframe src method.
		if ( method_exists( '\\RSFV\\Featuresets\\Hover_Autoplay\\Utils', 'enhance_iframe_src' ) ) {
			$embed_url = Hover_Utils::enhance_iframe_src( $embed_url, $video_type );
		}

		return $embed_url;
	}

	/**
	 * Add hover container class
	 *
	 * @param string $class Current container class.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	public function add_hover_container_class( $class, $video_data ) {
		$hover_settings = Hover_Autoplay::get_settings();

		if ( ! $hover_settings['enable_hover_autoplay'] ) {
			return $class;
		}

		return $class . ' rsfv-hover-enabled';
	}

	/**
	 * Handle shortcode video output filter
	 *
	 * @param string $video_markup Video HTML markup.
	 * @param array  $video_data Video data.
	 * @return string
	 */
	public function handle_shortcode_output_filter( $video_markup, $video_data ) {
		// Add hover attributes to container.
		$video_markup = Hover_Utils::add_hover_attributes( $video_markup, $video_data );

		// Add play overlay.
		$video_markup = Hover_Utils::add_play_overlay( $video_markup );

		// Add accessibility attributes.
		$video_markup = Hover_Utils::add_accessibility_attributes( $video_markup );

		return $video_markup;
	}

	/**
	 * Add container attributes for hover functionality
	 *
	 * @param array $attributes Current attributes.
	 * @param array $video_data Video data.
	 * @return array
	 */
	public function add_container_attributes( $attributes, $video_data ) {
		$hover_settings = Hover_Autoplay::get_settings();

		if ( ! $hover_settings['enable_hover_autoplay'] ) {
			return $attributes;
		}

		$attributes['data-rsfv-video'] = 'true';
		$attributes['data-rsfv-hover-enabled'] = 'true';

		if ( isset( $video_data['source'] ) ) {
			$attributes['data-rsfv-source'] = $video_data['source'];
		}

		if ( isset( $video_data['embed_type'] ) ) {
			$attributes['data-rsfv-type'] = $video_data['embed_type'];
		}

		return $attributes;
	}
}
