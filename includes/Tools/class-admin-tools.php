<?php
/**
 * Admin Tools Class
 *
 * @package  RSFV
 */

namespace RSFV\Tools;

use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Tools Class.
 */
class Admin_Tools {
	/**
	 * Handles the output of the Tools page.
	 */
	public static function output() {
		// Enqueue necessary assets.
		self::enqueue_assets();

		include RSFV_PLUGIN_DIR . 'includes/Tools/Views/html-admin-tools.php';
	}

	/**
	 * Enqueue assets for the Bulk Actions page.
	 */
	public static function enqueue_assets() {
		$asset_file = RSFV_PLUGIN_DIR . 'assets/js/tools/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Enqueue media scripts for video upload.
		wp_enqueue_media();

		wp_enqueue_script(
			'rsfv-tools',
			RSFV_PLUGIN_URL . 'assets/js/tools/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'rsfv-tools',
			RSFV_PLUGIN_URL . 'assets/js/tools/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_localize_script(
			'rsfv-tools',
			'rsfvTools',
			array(
				'postTypes' => self::get_enabled_post_types_options(),
				'perPage'   => 20,
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Get enabled post types as options array.
	 *
	 * @return array
	 */
	public static function get_enabled_post_types_options() {
		$enabled_types = get_post_types();
		$options       = array();

		foreach ( $enabled_types as $post_type ) {
			$post_type_obj = get_post_type_object( $post_type );

			if ( $post_type_obj ) {
				$options[] = array(
					'value' => $post_type,
					'label' => $post_type_obj->labels->name,
				);
			}
		}

		return $options;
	}
}
