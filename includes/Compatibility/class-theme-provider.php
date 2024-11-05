<?php
/**
 * Theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility;

use RSFV\Compatibility\Themes\Base_Compatibility;
use RSFV\Options;

/**
 * Class Theme_Provider
 *
 * @package RSFV
 */
class Theme_Provider {
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
		add_action( 'after_setup_theme', array( $this, 'load_theme_compat' ) );
	}

	/**
	 * Get a class instance.
	 *
	 * @return Object
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get theme engines.
	 *
	 * @return array
	 */
	public function get_theme_engines() {
		return apply_filters(
			'rsfv_theme_compatibility_engines',
			array(
				'default'           => array(
					'title'       => __( 'Default', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Fallback/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Fallback\Compatibility',
				),

				// Core.
				'twentytwenty'      => array(
					'title'       => __( 'Twenty Twenty', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Twentytwenty/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Twentytwenty\Compatibility',
				),
				'twentytwentyone'   => array(
					'title'       => __( 'Twenty Twenty-One', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Twentytwenty_One/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Twentytwenty_One\Compatibility',
				),
				'twentytwentytwo'   => array(
					'title'       => __( 'Twenty Twenty-Two', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Twentytwenty_Two/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Twentytwenty_Two\Compatibility',
				),
				'twentytwentythree' => array(
					'title'       => __( 'Twenty Twenty-Three', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Twentytwenty_Three/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Twentytwenty_Three\Compatibility',
				),
				'twentytwentyfour'  => array(
					'title'       => __( 'Twenty Twenty-Four', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Twentytwenty_Four/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Twentytwenty_Four\Compatibility',
				),
				'storefront'        => array(
					'title'       => __( 'Storefront', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/Core/Storefront/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\Core\Storefront\Compatibility',
				),

				// Third-Party.
				'neve'              => array(
					'title'       => __( 'Neve', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/Neve/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\Neve\Compatibility',
				),
				'generatepress'     => array(
					'title'       => __( 'GeneratePress', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/GeneratePress/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\GeneratePress\Compatibility',
				),
				'astra'             => array(
					'title'       => __( 'Astra', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/Astra/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\Astra\Compatibility',
				),
				'go'                => array(
					'title'       => __( 'Go', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/Go/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\Go\Compatibility',
				),
				'kadence'           => array(
					'title'       => __( 'Kadence', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/Kadence/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\Kadence\Compatibility',
				),
				'hestia'            => array(
					'title'       => __( 'Hestia', 'rsfv' ),
					'file_source' => RSFV_PLUGIN_DIR . 'includes/Compatibility/Themes/ThirdParty/Hestia/class-compatibility.php',
					'class'       => 'RSFV\Compatibility\Themes\ThirdParty\Hestia\Compatibility',
				),
			)
		);
	}

	/**
	 * Load theme compatibility.
	 *
	 * @return void
	 */
	public function load_theme_compat() {
		$theme      = wp_get_theme();
		$theme_slug = strtolower( $theme->get_stylesheet() );
		$options    = Options::get_instance();

		$compatibility_engine = $options->get( 'theme-compatibility-engine' );

		// For when there is an engine set.
		if ( 'disabled' === $compatibility_engine ) {
			// Exits early.
			$options->set( 'active-theme-engine', $compatibility_engine );
			$options->delete( 'automatic-theme-engine' );
			return;
		} elseif ( $compatibility_engine && 'auto' !== $compatibility_engine ) {
			$theme_slug = $compatibility_engine;
		}

		$theme_compat = null;

		$theme_engines = $this->get_theme_engines();

		if ( ! in_array( $theme_slug, array_keys( $theme_engines ), true ) ) {
			$theme_slug = 'default';
		}

		require_once $theme_engines[ $theme_slug ]['file_source'];
		$theme_compat = $theme_engines[ $theme_slug ]['class']::get_instance();

		if ( ! $theme_compat instanceof Base_Compatibility ) {
			$options->set( 'theme-engine-error', __( 'Failed at registration', 'rsfv' ) );
			$options->set( 'active-theme-engine', __( 'Unregistered', 'rsfv' ) );
			return;
		}

		// For when it defaults to auto.
		if ( ! $compatibility_engine || 'auto' === $compatibility_engine ) {
			$options->set( 'automatic-theme-engine', $theme_compat->get_id() );
		}

		// Stores the final engine active.
		$options->set( 'active-theme-engine', $theme_compat->get_id() );
	}

	/**
	 * Get registered engines id and title.
	 *
	 * @return array
	 */
	public function get_available_engines() {
		$registered_engines = array();
		$theme_engines      = $this->get_theme_engines();

		foreach ( $theme_engines as $engine_id => $engine_data ) {
			$registered_engines[ $engine_id ] = $engine_data['title'];
		}

		return $registered_engines;
	}

	/**
	 * Get selectable engines for user settings.
	 *
	 * @return array
	 */
	public function get_selectable_engine_options() {
		$selectable_engines = array(
			'disabled' => __( 'Disabled (Legacy)', 'rsfv' ),
			'auto'     => __( 'Auto (Do it for me)', 'rsfv' ),
		);

		$theme_engines = $this->get_theme_engines();

		foreach ( $theme_engines as $engine_id => $engine_data ) {
			$selectable_engines[ $engine_id ] = $engine_data['title'];
		}

		// Pro theme Engines for promo.
		$pro_selectable_engines = $this->get_selectable_pro_engine_options_promo();

		// Include promo engines.
		foreach ( $pro_selectable_engines as $engine_id => $engine_label ) {
			if ( ! array_key_exists( $engine_id, $selectable_engines ) ) {
				$selectable_engines[ $engine_id ] = $engine_label;
			}
		}

		return $selectable_engines;
	}

	/**
	 * Returns the list of theme engines available in PRO plugin.
	 *
	 * @return array
	 */
	public function get_selectable_pro_engine_options_promo() {
		return array(
			'oceanwp'  => __( 'OceanWP (PRO)', 'rsfv' ),
			'jupiterx' => __( 'Jupiter X (PRO)', 'rsfv' ),
			'flatsome' => __( 'Flatsome (PRO)', 'rsfv' ),
			'wellco'   => __( 'Wellco (PRO)', 'rsfv' ),
			'avanam'   => __( 'Avanam (PRO)', 'rsfv' ),
			'divi'     => __( 'Divi Builder (PRO)', 'rsfv' ),
			'avada'    => __( 'Avada (PRO)', 'rsfv' ),
			'konte'    => __( 'Konte (PRO)', 'rsfv' ),
			'lay'      => __( 'Lay (PRO)', 'rsfv' ),
		);
	}
}
