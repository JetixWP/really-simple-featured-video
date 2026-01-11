<?php
/**
 * Admin Bulk Actions Class
 *
 * @package  RSFV
 */

namespace RSFV\BulkActions;

use RSFV\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Bulk_Actions Class.
 */
class Admin_Bulk_Actions {
	/**
	 * Handles the output of the Bulk Actions page.
	 */
	public static function output() {
		include RSFV_PLUGIN_DIR . 'includes/BulkActions/Views/html-admin-bulk-actions.php';
	}
}
