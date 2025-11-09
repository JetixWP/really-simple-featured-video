<?php
/**
 * Abstract like class Base_Compatibility for Themes.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes;

use RSFV\FrontEnd;

/**
 * Abstract class definition for controllers.
 *
 * @since 0.6.0
 */
abstract class Base_Compatibility {
	/**
	 * Compatibility id.
	 *
	 * @var string $id
	 */
	public $id = '';

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Get instance.
	 *
	 * @return mixed
	 */
	final public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Cloning not allowed.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Update post classes for one or more conditions.
		add_filter( 'post_class', array( $this, 'set_post_classes' ) );

		// Update body classes for one or more conditions.
		add_filter( 'body_class', array( $this, 'set_body_classes' ) );
	}

	/**
	 * Get compatibility id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get compatibility title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the current namespace dir URL.
	 *
	 * @return string
	 */
	public function get_current_dir_url() {
		return RSFV_PLUGIN_URL . 'includes/Compatibility/Themes/';
	}

	/**
	 * Get the current namespace dir.
	 *
	 * @return string
	 */
	public function get_current_dir() {
		return RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/';
	}

	/**
	 * Set post classes.
	 *
	 * @param array $classes Post classes.
	 *
	 * @return array
	 */
	public function set_post_classes( $classes ) {
		$post_id = get_the_ID();

		$has_video = FrontEnd::has_featured_video( $post_id );

		if ( $has_video ) {
			$classes[] = 'rsfv-has-video';
		}

		return $classes;
	}

	/**
	 * Set body classes.
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public function set_body_classes( $classes ) {
		if ( is_singular() ) {
			$post_id = get_the_ID();

			$has_video = FrontEnd::has_featured_video( $post_id );

			if ( $has_video ) {
				$classes[] = 'rsfv-has-video';
			}
		}

		return apply_filters( 'rsfv_body_classes', $classes );
	}
}
