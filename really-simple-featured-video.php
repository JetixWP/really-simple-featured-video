<?php
/**
 * Plugin Name: Really Simple Featured Video
 * Plugin URI:  https://jetixwp.com/plugins/really-simple-featured-video
 * Description: Adds support for Featured Video to WordPress posts, pages & WooCommerce products.
 * Version:     0.31.0
 * Author:      JetixWP Plugins
 * Author URI:  https://jetixwp.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rsfv
 * Domain Path: /languages/
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package RSFV
 */

defined( 'ABSPATH' ) || exit;

define( 'RSFV_VERSION', '0.31.0' );
define( 'RSFV_PLUGIN_FILE', __FILE__ );
define( 'RSFV_PLUGIN_URL', plugin_dir_url( RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_DIR', plugin_dir_path( RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_BASE', plugin_basename( RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_PRO_URL', 'https://jetixwp.com/plugins/really-simple-featured-video' );

if ( ! function_exists( 'rsfv_fs' ) ) {
	/**
	 * Create a helper function for easy SDK access.
	 */
	function rsfv_fs() {
		global $rsfv_fs;

		if ( ! isset( $rsfv_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/freemius/start.php';

			$rsfv_fs = fs_dynamic_init(
				array(
					'id'             => '7560',
					'slug'           => 'really-simple-featured-video',
					'type'           => 'plugin',
					'public_key'     => 'pk_6d1ecdde5701fc2158193cf7eab45',
					'is_premium'     => false,
					'has_addons'     => true,
					'has_paid_plans' => false,
					'menu'           => array(
						'slug'       => 'rsfv-settings',
						'first-path' => 'options-general.php?page=rsfv-settings',
						'support'    => false,
						'parent'     => array(
							'slug' => 'options-general.php',
						),
					),
				)
			);
		}

		return $rsfv_fs;
	}

	// Init Freemius.
	rsfv_fs();
	// Signal that SDK was initiated.
	do_action( 'rsfv_fs_loaded' );
}

/**
 * Fire up plugin instance.
 */
add_action(
	'plugins_loaded',
	static function () {

		require_once RSFV_PLUGIN_DIR . 'includes/class-plugin.php';

		// Main instance.
		\RSFV\Plugin::get_instance();
	}
);
