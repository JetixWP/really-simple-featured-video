/**
 * Sidebar Component
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const Sidebar = () => {
	return (
		<div className="rsfv-sidebar">
			<div className="rsfv-sidebar-panel">
				<h3 className="rsfv-sidebar-title">
					{ __( 'Quick Tips', 'rsfv' ) }
				</h3>
				<ul className="rsfv-sidebar-tips">
					<li>{ __( 'Click on a thumbnail to set or change the featured image.', 'rsfv' ) }</li>
					<li>{ __( 'Use the video type dropdown to switch between self-hosted and embed videos.', 'rsfv' ) }</li>
					<li>{ __( 'Set a poster image for self-hosted videos to display before playback.', 'rsfv' ) }</li>
					<li>{ __( 'Search for posts by title using the search field above.', 'rsfv' ) }</li>
				</ul>
			</div>

			<div className="rsfv-sidebar-panel">
				<h3 className="rsfv-sidebar-title">
					{ __( 'Keyboard Shortcuts', 'rsfv' ) }
				</h3>
				<ul className="rsfv-sidebar-shortcuts">
					<li>
						<kbd>Enter</kbd>
						<span>{ __( 'Submit search', 'rsfv' ) }</span>
					</li>
					<li>
						<kbd>Esc</kbd>
						<span>{ __( 'Close media modal', 'rsfv' ) }</span>
					</li>
				</ul>
			</div>
		</div>
	);
};

export default Sidebar;
