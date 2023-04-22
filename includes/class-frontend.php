<?php
/**
 * Frontend handler.
 *
 * @package RSFV
 */

namespace RSFV;

use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

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

		// Get enabled post types.
		$post_types = get_post_types();

		if ( ! empty( $post_types ) ) {
			if ( in_array( $post->post_type, $post_types, true ) ) {
				// Get the meta value of video attachment.
				$video_id = get_post_meta( $post_id, RSFV_META_KEY, true );

				// Get the meta value of video embed url.
				$embed_url = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );

				if ( $video_id || $embed_url ) {
					return '<div style="clear:both">' . do_shortcode( '[rsfv]' ) . '</div>';
				}
			}
		}
		return $html;
	}

	/**
	 * Parses embed data via URL.
	 *
	 * @param string $url Video URL.
	 *
	 * @return array|string
	 */
	public function parse_embed_url( $url ) {

		$parsed = wp_parse_url( esc_url( $url ) );

		switch ( $parsed['host'] ) {
			case 'www.youtube.com':
			case 'youtube.com':
			case 'youtu.be':
				$pattern = '/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|vi|e(?:mbed)?)\/|\S*?[?&]v=|\S*?[?&]vi=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

				$result = preg_match( $pattern, $url, $matches );

				if ( false !== $result ) {
					$id = $matches[1];
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
					$id = $matches[1];
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
					$id = $matches[1];
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
		return array(
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
			),
			'div'    => array(
				'class'      => array(),
				'id'         => array(),
				'data-thumb' => array(),
				'style'      => array(),
			),
			'img'    => array(
				'src'       => array(),
				'alt'       => array(),
				'class'     => array(),
				'draggable' => array(),
			),
			'a'      => array(
				'href'  => array(),
				'class' => array(),
				'style' => array(),
			),
			'p'      => array(),
			'span'   => array(),
			'br'     => array(),
			'i'      => array(),
			'strong' => array(),
		);
	}
}
