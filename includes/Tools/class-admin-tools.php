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
			self::get_localized_data()
		);
	}

	/**
	 * Get localized data for the Tools app.
	 *
	 * @return array
	 */
	public static function get_localized_data() {
		$data = array(
			'postTypes' => self::get_enabled_post_types_options(),
			'perPage'   => 20,
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'columns'   => self::get_table_columns(),
		);

		/**
		 * Filter the localized data for the Tools app.
		 *
		 * @since 0.59.0
		 *
		 * @param array $data Localized data.
		 */
		return apply_filters( 'rsfv_tools_localized_data', $data );
	}

	/**
	 * Get table columns configuration.
	 *
	 * @return array
	 */
	public static function get_table_columns() {
		$columns = array(
			'thumbnail'     => array(
				'label'    => __( 'Thumbnail', 'rsfv' ),
				'class'    => 'column-thumbnail',
				'sortable' => false,
			),
			'title'         => array(
				'label'    => __( 'Title', 'rsfv' ),
				'class'    => 'column-title',
				'sortable' => false,
			),
			'status'        => array(
				'label'    => __( 'Video Status', 'rsfv' ),
				'class'    => 'column-status',
				'sortable' => false,
			),
			'video_type'    => array(
				'label'    => __( 'Video Type', 'rsfv' ),
				'class'    => 'column-video-type',
				'sortable' => false,
			),
			'video_action'  => array(
				'label'    => __( 'Action', 'rsfv' ),
				'class'    => 'column-video-action',
				'sortable' => false,
			),
			'video_preview' => array(
				'label'    => __( 'Video', 'rsfv' ),
				'class'    => 'column-video-preview',
				'sortable' => false,
			),
		);

		/**
		 * Filter the table columns for the Tools page.
		 *
		 * @since 0.59.0
		 *
		 * @param array $columns Table columns configuration.
		 */
		return apply_filters( 'rsfv_tools_table_columns', $columns );
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
					'value' => sanitize_key( $post_type ),
					'label' => esc_html( $post_type_obj->labels->name ),
				);
			}
		}

		return $options;
	}
}
