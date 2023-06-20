<?php
/**
 * Migrations handler.
 *
 * @package RSFV
 */

namespace RSFV;

defined( 'ABSPATH' ) || exit;

/**
 * Class Updater
 */
class Updater {

	/**
	 * The slug of db option.
	 *
	 * @var string
	 */
	const OPTION = 'rsfv_db_version';

	/**
	 * The slug of db option.
	 *
	 * @var string
	 */
	const PREVIOUS_OPTION = 'rsfv_previous_db_version';

	/**
	 * Hooked into admin_init and walks through an array of upgrade methods.
	 *
	 * @return void
	 */
	public function init() {
		$routines = array(
			'0.5.0' => 'upgrade_0_5',
			'0.5.1' => 'upgrade_0_5',
		);

		$version = get_option( self::OPTION, '0.0.3' );

		if ( version_compare( RSFV_VERSION, $version, '=' ) ) {
			return;
		}

		array_walk( $routines, array( $this, 'run_upgrade_routine' ), $version );
		$this->finish_up( $version );
	}

	/**
	 * Runs the upgrade routine.
	 *
	 * @param string $routine The method to call.
	 * @param string $version The new version.
	 * @param string $current_version The current set version.
	 *
	 * @return void
	 */
	protected function run_upgrade_routine( $routine, $version, $current_version ) {
		if ( version_compare( $current_version, $version, '<' ) ) {
			$this->$routine( $current_version );
		}
	}

	/**
	 * Runs the needed cleanup after an update, setting the DB version to latest version, flushing caches etc.
	 *
	 * @param string $previous_version The previous version.
	 *
	 * @return void
	 */
	protected function finish_up( $previous_version ) {
		update_option( self::PREVIOUS_OPTION, $previous_version );
		update_option( self::OPTION, RSFV_VERSION );
	}

	/**
	 * Upgrade to 0.5.0 & 0.5.1
	 *
	 * @return void
	 */
	protected function upgrade_0_5() {
		$set_options = array(
			'autoplay' => Options::get_instance()->get( 'video_autoplay' ),
			'loop'     => Options::get_instance()->get( 'video_loop' ),
			'mute'     => Options::get_instance()->get( 'mute_video' ),
			'pip'      => Options::get_instance()->get( 'picture_in_picture' ),
			'controls' => Options::get_instance()->get( 'video_controls', true ),
		);

		$updated_values = array();

		foreach ( $set_options as $option => $value ) {
			$updated_values[ $option ] = $value ? $value : 0;
		}

		Options::get_instance()->set( 'self_video_controls', $updated_values );
	}
}
