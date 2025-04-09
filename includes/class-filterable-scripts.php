<?php
/**
 * WP Script Localize handler.
 *
 * @package RSFV
 */

namespace RSFV;

use WP_Scripts;

/**
 * Class Filterable_Scripts
 *
 * @package RSFV
 */
class Filterable_Scripts extends WP_Scripts {

	/**
	 * Override localize method to include a filter.
	 *
	 * @param string $handle Script handle the data will be attached to.
	 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
	 *                             Example: '/[a-zA-Z0-9_]+/'.
	 * @param array  $l10n The data itself. The data can be either a single or multi-dimensional array.
	 * @return bool True on success, false on failure.
	 */
	public function localize( $handle, $object_name, $l10n ) {
		$l10n = apply_filters( 'rsfv_script_l10n', $l10n, $handle, $object_name );
		return parent::localize( $handle, $object_name, $l10n );
	}
}
