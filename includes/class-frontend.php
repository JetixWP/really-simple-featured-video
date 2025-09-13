<?php
/**
 * Frontend handler.
 *
 * @package RSFV
 */

namespace RSFV;

use function RSFV\Settings\get_post_types;

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
	 * Front_End constructor.
	 */
	public function __construct() {
		$this->get_posts_hooks();
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
		add_filter( 'wp_kses_allowed_html', array( $this, 'update_wp_kses_allowed_html' ), 10, 2 );
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

		$options                  = Options::get_instance();
		$blog_archives_visibility = $options->get( 'blog_archives_visibility' );
		$blog_single_visibility   = $options->get( 'blog_single_visibility' );

		if ( ( ( is_home() || is_archive() ) && ( $options->has( 'blog_archives_visibility' ) && ! $blog_archives_visibility ) ) ) {
			return $html;
		}

		if ( ( ( is_single() ) && ( $options->has( 'blog_single_visibility' ) && ! $blog_single_visibility ) ) ) {
			return $html;
		}

		if ( 'object' !== gettype( $post ) ) {
			return $html;
		}

		return self::get_featured_video_markup( $post->ID, $html );
	}

	/**
	 * Get featured video markup.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $markup Holds markup data.
	 *
	 * @return string
	 */
	public static function get_featured_video_markup( $post_id, $markup = '' ) {

		// Exit early if no post id is provided.
		if ( ! $post_id ) {
			return $markup;
		}

		$post = get_post( $post_id );

		if ( 'object' !== gettype( $post ) ) {
			return $markup;
		}

		// Get enabled post types.
		$post_types = get_post_types();

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {
				// Get the meta value of video embed url.
				$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
				$video_source = $video_source ? $video_source : 'self';

				if ( 'self' === $video_source ) {
					// Get the meta value of video attachment.
					$video_id = get_post_meta( $post->ID, RSFV_META_KEY, true );

					if ( $video_id ) {
						$shortcode_output = do_shortcode( '[rsfv]' );

						return '<div class="rsfv-shortcode-wrapper" data-rsfv-video="true" data-rsfv-source="self" style="clear:both">' . $shortcode_output . '</div>';
					}
				} else {
					// Get the meta value of video embed url.
					$embed_url = get_post_meta( $post_id, RSFV_EMBED_META_KEY, true );

					if ( $embed_url ) {
						$embed_data = self::get_instance()->parse_embed_url( $embed_url );
						$video_type = is_array( $embed_data ) ? $embed_data['host'] : 'unknown';

						$video_data = array(
							'post_id' => $post_id,
							'embed_url' => $embed_url,
							'type' => $video_type,
							'source' => 'embed',
						);

						$shortcode_output = do_shortcode( '[rsfv]' );
						return '<div class="rsfv-shortcode-wrapper" data-rsfv-video="true" data-rsfv-source="embed" data-rsfv-type="' . esc_attr( $video_type ) . '" style="clear:both">' . $shortcode_output . '</div>';
					}
				}
			}
		}

		return $markup;
	}

	/**
	 * Method for checking if the post has a featured video set.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function has_featured_video( $post_id ) {

		// Exit early if no post id is provided.
		if ( empty( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( 'object' !== gettype( $post ) ) {
			return false;
		}

		// Get enabled post types.
		$post_types = get_post_types();

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {

				// Get the meta value of video embed url.
				$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
				$video_source = $video_source ? $video_source : 'self';

				if ( 'self' === $video_source ) {
					// Get the meta value of video attachment.
					$video_id = get_post_meta( $post->ID, RSFV_META_KEY, true );

					if ( $video_id ) {
						return true;
					}
				} else {
					// Get the meta value of video embed url.
					$embed_url = get_post_meta( $post_id, RSFV_EMBED_META_KEY, true );

					if ( $embed_url ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Parses embed data via URL.
	 *
	 * @param string $url Video URL.
	 *
	 * @return array|string
	 */
	public function parse_embed_url( $url ) {

		if ( empty( $url ) ) {
			return $url;
		}

		$parsed = wp_parse_url( esc_url( $url ) );

		switch ( $parsed['host'] ) {
			case 'www.youtube.com':
			case 'youtube.com':
			case 'youtu.be':
				$pattern = '/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|vi|e(?:mbed)?)\/|\S*?[?&]v=|\S*?[?&]vi=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

				$result = preg_match( $pattern, $url, $matches );

				if ( false !== $result ) {
					$id = $matches[1] ?? false;
				} else {
					$id = false;
				}

				return array(
					'host' => 'youtube',
					'id'   => $id,
				);

			case 'vimeo.com':
			case 'player.vimeo.com':
				$pattern = '/\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i';

				$result = preg_match(
					$pattern,
					$url,
					$matches
				);

				if ( false !== $result ) {
					$id = $matches[1] ?? false;
				} else {
					$id = false;
				}

				return array(
					'host' => 'vimeo',
					'id'   => $id,
				);

			case 'dailymotion.com':
			case 'www.dailymotion.com':
			case 'dai.ly':
				$pattern = '/^(?:(?:https?):)?(?:\/\/)?(?:www\.)?(?:(?:dailymotion\.com(?:\/embed|\/hub)?\/video)|dai\.ly)\/([a-zA-Z0-9]+)(?:_[\w_-]+)?$/';

				$result = preg_match(
					$pattern,
					$url,
					$matches
				);

				if ( $result ) {
					$id = $matches[1] ?? false;
				} else {
					$id = false;
				}

				return array(
					'host' => 'dailymotion',
					'id'   => $id,
				);

			default:
				return $url;
		}
	}

	/**
	 * Generate an embed URL.
	 *
	 * @param string $url Video URL.
	 *
	 * @return string
	 */
	public function generate_embed_url( $url ) {
		$embed_data = $this->parse_embed_url( $url );

		if ( is_array( $embed_data ) && isset( $embed_data['host'] ) && 'youtube' === $embed_data['host'] ) {
			$embed_url = 'https://www.youtube.com/embed/' . $embed_data['id'];
		} elseif ( is_array( $embed_data ) && isset( $embed_data['host'] ) && 'vimeo' === $embed_data['host'] ) {
			$embed_url = 'https://player.vimeo.com/video/' . $embed_data['id'];
		} elseif ( is_array( $embed_data ) && isset( $embed_data['host'] ) && 'dailymotion' === $embed_data['host'] ) {
			$embed_url = 'https://www.dailymotion.com/embed/video/' . $embed_data['id'];
		} else {
			$embed_url = $url;
		}

		return $embed_url;
	}

	/**
	 * Get allowed HTML elements.
	 *
	 * @return array List of elements.
	 */
	public function get_allowed_html() {
		return apply_filters(
			'rsfv_allowed_html',
			array(
				'video'  => array(
					'id'                   => array(),
					'class'                => array(),
					'src'                  => array(),
					'style'                => array(),
					'loop'                 => array(),
					'muted'                => array(),
					'controls'             => array(),
					'autopictureinpicture' => array(),
					'autoplay'             => array(),
					'playsinline'          => array(),
					'preload'              => array(),
					'poster'               => array(),
					'tabindex'             => array(),
					'role'                 => array(),
					'aria-label'           => array(),
					'aria-hidden'          => array(),
					'data-rsfv-video'      => array(),
					'data-rsfv-type'       => array(),
					'data-rsfv-source'     => array(),
					'data-rsfv-hover-enabled' => array(),
					'data-state'           => array(),
				),
				'iframe' => array(
					'id'              => array(),
					'class'           => array(),
					'src'             => array(),
					'width'           => array(),
					'style'           => array(),
					'height'          => array(),
					'frameborder'     => array(),
					'allowfullscreen' => array(),
					'allow'           => array(),
					'loading'         => array(),
					'tabindex'        => array(),
					'role'            => array(),
					'aria-label'      => array(),
					'aria-hidden'     => array(),
					'data-rsfv-video' => array(),
					'data-rsfv-type'  => array(),
					'data-rsfv-source' => array(),
					'data-rsfv-hover-enabled' => array(),
					'data-rsfv-embed-type' => array(),
				),
				'div'    => array(
					'class'             => array(),
					'id'                => array(),
					'data-thumb'        => array(),
					'style'             => array(),
					'data-slide-number' => array(),
					'data-rsfv-video'   => array(),
					'data-rsfv-type'    => array(),
					'data-rsfv-source'  => array(),
					'data-rsfv-hover-enabled' => array(),
					'data-rsfv-context' => array(),
					'data-rsfv-archives' => array(),
					'data-rsfv-lazy'    => array(),
					'data-rsfv-threshold' => array(),
					'data-aspect-ratio' => array(),
					'data-state'        => array(),
					'role'              => array(),
					'aria-hidden'       => array(),
					'aria-label'        => array(),
					'tabindex'          => array(),
					// WooCommerce specific.
					'data-rsfv-woo-product-id' => array(),
					'data-rsfv-embed-type' => array(),
				),
				'img'    => array(
					'src'       => array(),
					'alt'       => array(),
					'class'     => array(),
					'draggable' => array(),
					'width'     => array(),
					'height'    => array(),
					'loading'   => array(),
					'style'     => array(),
					'role'      => array(),
					'aria-hidden' => array(),
					'tabindex'  => array(),
				),
				'a'      => array(
					'href'  => array(),
					'class' => array(),
					'style' => array(),
					'role'  => array(),
					'aria-label' => array(),
					'tabindex' => array(),
					'data-rsfv-product' => array(),
				),
				'p'      => array(
					'class' => array(),
					'style' => array(),
				),
				'span'   => array(
					'class' => array(),
					'style' => array(),
					'role'  => array(),
					'aria-hidden' => array(),
				),
				'br'     => array(),
				'i'      => array(
					'class' => array(),
					'aria-hidden' => array(),
				),
				'strong' => array(
					'class' => array(),
				),
			)
		);
	}
	/**
	 * Generate dynamic CSS.
	 *
	 * @return string
	 */
	public function generate_dynamic_css() {
		$css = '';

		return apply_filters( 'rsfv_generated_dynamic_css', $css );
	}

	/**
	 * Override wp_kses_post allowed html array to make way for videos.
	 *
	 * @param array  $allowed_html List of html tags.
	 * @param string $context Key of current context.
	 *
	 * @return array
	 */
	public function update_wp_kses_allowed_html( $allowed_html, $context ) {

		// Keep only for 'post' context.
		if ( 'post' === $context ) {
			$additional_html = $this->get_allowed_html();

			$updated_tags = $allowed_html;

			// This fixes overriding existing html tag attributes data.
			foreach ( $additional_html as $tag => $attrs ) {
				if ( in_array( $tag, array_keys( $allowed_html ), true ) ) {
					if ( ! empty( $attrs ) ) {
						$ex_attrs = $allowed_html[ $tag ];

						if ( ! empty( $ex_attrs ) ) {
							foreach ( $attrs as $attr => $value ) {
								if ( ! empty( $ex_attrs[ $attr ] ) ) {
									$updated_tags[ $tag ][ $attr ] = $ex_attrs[ $attr ];
								} else {
									$updated_tags[ $tag ][ $attr ] = $value;
								}
							}
						} else {
							$updated_tags[ $tag ] = $attrs;
						}
					} else {
						$updated_tags[ $tag ] = $allowed_html[ $tag ];
					}

					continue;
				}

				$updated_tags[ $tag ] = $attrs;
			}

			return $updated_tags;
		}

		return $allowed_html;
	}
}
