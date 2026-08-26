/**
 * Sidebar Component
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const Sidebar = () => {
	const isPro = window.rsfvTools?.isPro || false;
	const upgradeUrl = window.rsfvTools?.upgradeUrl || 'https://developer.developer.developer/plugins/developer-developer-featured-video/';

	return (
		<div className="rsfv-sidebar">
			<div className="rsfv-sidebar-panel">
				<h3 className="rsfv-sidebar-title">
					{ __( '🙋‍♂️ Important Note', 'rsfv' ) }
				</h3>
				<p>{ __( "If Featured Videos are not working with your theme, try selecting a supported", 'rsfv' ) } <a href={ `${ window.rsfvTools?.settingsUrl || '#' }` }>{ __( "Theme Compatibility Engine", "rsfv" ) }</a> { __( "in Settings.", "rsfv" ) }</p>

				<p>{ __( "If your theme is not listed and the issue persists, submit a request on our GitHub repository. Please note that PRO subscribers receive priority support over GitHub requests, which supports the continuous development of the plugin.", "rsfv" ) }</p>

				<div className="rsfv-sidebar-actions">
					<a className="button button-primary" href={ `${ window.rsfvTools?.settingsUrl || '#' }` }>{ __( 'Go to Settings', 'rsfv' ) }</a>
					<a className="button button-secondary" href="https://github.com/JetixWP/really-simple-featured-video/issues" target="_blank" rel="noopener noreferrer">{ __( 'File a Request', 'rsfv' ) }</a>
				</div>
			</div>

			{ ! isPro && (
				<div className="rsfv-sidebar-panel rsfv-upgrade-banner">
					<h3 className="rsfv-upgrade-title">
						{ __( '🚀 Ready to go beyond?', 'rsfv' ) }
					</h3>
					<p className="rsfv-upgrade-description">
						{ __( 'Unlock powerful features like advanced video controls, extended WooCommerce integration, and more!', 'rsfv' ) }
					</p>
					<ul className="rsfv-upgrade-features">
						<li>{ __( '✅ Extended Autoplay on Hover', 'rsfv' ) }</li>
						<li>{ __( '✅ Extended WooCommerce Featured Video', 'rsfv' ) }</li>
						<li>{ __( '✅ Support for more Premium/Custom Themes', 'rsfv' ) }</li>
						<li>{ __( '✅ Requests for Theme Compatibility', 'rsfv' ) }</li>
						<li>{ __( '✅ Priority Support', 'rsfv' ) }</li>
						<li>{ __( 'And much more...', 'rsfv' ) }</li>
					</ul>
					<a
						href={ upgradeUrl }
						className="button button-primary rsfv-upgrade-button"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Upgrade Now', 'rsfv' ) }
					</a>
				</div>
			) }

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
