<?php
/**
* Plugin Name: Really Simple Featured Video
* Plugin URI:  https://xcessthemes.com/plugins/really-simple-featured-video
* Description: Upload featured videos for wordpress posts, pages & woocommerce products.
* Version:     1.0.0
* Author:      xcessthemes
* Author URI:  https://xcessthemes.com/
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: rsfv
* Domain Path: /languages/
* WC requires at least: 4.5
* WC tested up to: 4.5.1
*/

defined( 'ABSPATH' ) || exit;

define( 'RSFV_VERSION', '1.0.0' );
define( 'RSFV_PLUGIN_FILE', __FILE__ );
define( 'RSFV_PLUGIN_URL', plugin_dir_url(RSFV_PLUGIN_FILE ) );
define( 'RSFV_PLUGIN_DIR', plugin_dir_path( RSFV_PLUGIN_FILE ) );

/**
 * Fire up plugin instance.
 *
 */
add_action(
    'plugins_loaded',
    static function() {

        require_once RSFV_PLUGIN_DIR . 'includes/Plugin.php';

       // Main instance.
        \RSFV\Plugin::get_instance();
    } );