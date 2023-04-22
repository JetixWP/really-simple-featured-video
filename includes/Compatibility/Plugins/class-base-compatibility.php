<?php
/**
 * Abstract class Base_Compatibility for Plugins.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins;

/**
 * Abstract class definition for plugin compat.
 *
 * @since 0.6.0
 */
abstract class Base_Compatibility {
	/**
	 * Compatibility id.
	 *
	 * @var string $id
	 */
	protected $id;

	/**
	 * Compatibility title.
	 *
	 * @var string $title
	 */
	protected $title;

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Settings instance.
	 *
	 * @var $settings
	 */
	protected $settings;

	/**
	 * Get instance.
	 *
	 * @param string $id Compat ID.
	 * @param string $title Compat title.
	 *
	 * @return mixed
	 */
	final public static function get_instance( $id, $title ) {
		if ( null === static::$instance ) {
			static::$instance = new static( $id, $title );
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
	 *
	 * @param string $id Compat ID.
	 * @param string $title Compat title.
	 */
	public function __construct( $id, $title ) {
		$this->id    = $id;
		$this->title = $title;
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
		return RSFV_PLUGIN_URL . 'includes/Compatibility/Plugins/';
	}

	/**
	 * Get the current namespace dir.
	 *
	 * @return string
	 */
	public function get_current_dir() {
		return RSFV_PLUGIN_DIR . 'includes/Compatibility/Plugins/';
	}
}
