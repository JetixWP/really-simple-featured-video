<?php
/**
 * Plugin Name: Really Simple Featured Video
 * Plugin URI:  https://github.com/lushkant/really-simple-featured-video
 * Description: Upload featured videos for WordPress posts, pages & WooCommerce products.
 * Version:     0.0.2
 * Author:      Krishna Kant Chourasiya
 * Author URI:  https://lushkant.blog/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rsfv
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || exit;

define( 'RSFV_VERSION', '0.0.2' );
define( 'RSFV_PLUGIN_FILE', __FILE__ );
define( 'RSFV_PLUGIN_URL', plugin_dir_url( RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_DIR', plugin_dir_path( RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_BASE', plugin_basename( RSFV_PLUGIN_FILE ) );

/**
 * Fire up plugin instance.
 */
add_action(
	'plugins_loaded',
	static function() {

		require_once RSFV_PLUGIN_DIR . 'includes/Plugin.php';

		// Main instance.
		\RSFV\Plugin::get_instance();
	}
);
