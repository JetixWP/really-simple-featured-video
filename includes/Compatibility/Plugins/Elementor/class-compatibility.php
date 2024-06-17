<?php
/**
 * Elementor's compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\Elementor;

defined( 'ABSPATH' ) || exit;

use RSFV\Compatibility\Plugins\Base_Compatibility;
use RSFV\FrontEnd;
use RSFV\Options;

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

		$this->id = 'elementor';

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
		add_filter( 'rsfv_get_settings_pages', array( $this, 'register_settings' ) );

		$options = Options::get_instance();

		$disable_elementor_support = $options->get( 'disable_elementor_support' );

		if ( ! $options->has( 'disable_elementor_support' ) || ! $disable_elementor_support ) {
			add_filter( 'elementor/image_size/get_attachment_image_html', array( $this, 'update_with_video_html' ), 10, 4 );
		}
	}

	/**
	 * Override Elementor Pro's post widget featured image html.
	 *
	 * @since 0.8.6
	 *
	 * @param string $html ex html markup.
	 * @param array  $settings Settings array of parent widget/element.
	 * @param string $image_size_key Image size key.
	 * @param string $image_key Image key.
	 *
	 * @return string
	 */
	public function update_with_video_html( $html, $settings, $image_size_key, $image_key ) {
		// Exit early if Elementor Pro isn't active.
		if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
			return $html;
		}

		// Exit if the image contains site-logo.
		if ( isset( $settings['__dynamic__'] ) ) {
			$image = $settings['__dynamic__']['image'] ?? '';
			if ( str_contains( $image, 'site-logo' ) ) {
				return $html;
			}
		}

		// If the image markup is from posts/archive/featured image widgets.
		if ( is_array( $settings ) && ( isset( $settings['posts_post_type'] ) || isset( $settings['archive_classic_thumbnail'] ) || isset( $settings['__dynamic__'] ) ) ) {
			global $post;

			// Check if the $post object is not defined.
			if ( 'object' !== gettype( $post ) ) {
				return $html;
			}

			$post_id = $post->ID;

			return FrontEnd::get_featured_video_markup( $post_id, $html );
		}

		return $html;
	}
}
