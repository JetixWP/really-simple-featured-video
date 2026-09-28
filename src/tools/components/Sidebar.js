/**
 * Sidebar Component
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const Sidebar = () => {
	const isPro = window.rsfvTools?.isPro || false;
	const upgradeUrl =
		window.rsfvTools?.upgradeUrl ||
		'https://jetixwp.com/plugins/really-simple-featured-video/#pricing';
	const compareUrl = upgradeUrl.replace( /#.*$/, '' ) + '#compare';

	return (
		<div className="rsfv-sidebar">
			<div className="rsfv-sidebar-panel">
				<h3 className="rsfv-sidebar-title">
					<span
						className="dashicons dashicons-info-outline rsfv-promo-icon"
						aria-hidden="true"
					/>
					{ __( 'Important Note', 'rsfv' ) }
				</h3>
				<p>
					{ __(
						'If Featured Videos are not working with your theme, try selecting a supported',
						'rsfv'
					) }{ ' ' }
					<a href={ `${ window.rsfvTools?.settingsUrl || '#' }` }>
						{ __( 'Theme Compatibility Engine', 'rsfv' ) }
					</a>{ ' ' }
					{ __( 'in Settings.', 'rsfv' ) }
				</p>

				<p>
					{ __(
						'If your theme is not listed and the issue persists, submit a request on our GitHub repository. Please note that PRO subscribers receive priority support over GitHub requests, which supports the continuous development of the plugin.',
						'rsfv'
					) }
				</p>

				<div className="rsfv-sidebar-actions">
					<a
						className="button button-primary"
						href={ `${ window.rsfvTools?.settingsUrl || '#' }` }
					>
						{ __( 'Go to Settings', 'rsfv' ) }
					</a>
					<a
						className="button button-secondary"
						href="https://github.com/JetixWP/really-simple-featured-video/issues"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'File a Request', 'rsfv' ) }
					</a>
				</div>
			</div>

			{ ! isPro && (
				<div className="rsfv-sidebar-panel rsfv-upgrade-banner">
					<h3 className="rsfv-upgrade-title">
						<span
							className="dashicons dashicons-star-filled rsfv-promo-icon"
							aria-hidden="true"
						/>
						{ __( 'Ready to go beyond?', 'rsfv' ) }
					</h3>
					<p className="rsfv-upgrade-description">
						{ __(
							'RSFV PRO adds more Video Studio templates and tools, deeper WooCommerce control, full video analytics, wider theme support, and direct help from the developer.',
							'rsfv'
						) }
					</p>
					<ul className="rsfv-upgrade-features">
						<li>
							<strong>
								{ __( 'Video Studio PRO', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'20 more templates, vertical and 4K sizes, music, brand kit, every Google Font, videos in bulk',
									'rsfv'
								) }
							</span>
						</li>
						<li>
							<strong>
								{ __( 'Full video analytics', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'History beyond 14 days, watch time, completion, CSV export',
									'rsfv'
								) }
							</span>
						</li>
						<li>
							<strong>
								{ __( 'WooCommerce controls', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'Gallery order, thumbnails, aspect ratios',
									'rsfv'
								) }
							</span>
						</li>
						<li>
							<strong>
								{ __( 'Autoplay on hover', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'Extended controls for listings and shops',
									'rsfv'
								) }
							</span>
						</li>
						<li>
							<strong>
								{ __( 'Premium and custom themes', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'More supported, compatibility on request',
									'rsfv'
								) }
							</span>
						</li>
						<li>
							<strong>
								{ __( 'Priority support', 'rsfv' ) }
							</strong>
							<span>
								{ __(
									'Direct help from the developer',
									'rsfv'
								) }
							</span>
						</li>
					</ul>
					<a
						href={ upgradeUrl }
						className="button button-primary rsfv-upgrade-button"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'See PRO plans', 'rsfv' ) }
					</a>
					<p className="rsfv-upgrade-trust">
						{ __(
							'One-time payment · lifetime updates · 14-day money-back guarantee',
							'rsfv'
						) }
					</p>
					<p className="rsfv-upgrade-compare">
						<a
							href={ compareUrl }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Compare free vs PRO →', 'rsfv' ) }
						</a>
					</p>
				</div>
			) }

			<div className="rsfv-sidebar-panel">
				<h3 className="rsfv-sidebar-title">
					{ __( 'Quick Tips', 'rsfv' ) }
				</h3>
				<ul className="rsfv-sidebar-tips">
					<li>
						{ __(
							'Click on a thumbnail to set or change the featured image.',
							'rsfv'
						) }
					</li>
					<li>
						{ __(
							'Use the video type dropdown to switch between self-hosted and embed videos.',
							'rsfv'
						) }
					</li>
					<li>
						{ __(
							'Set a poster image for self-hosted videos to display before playback.',
							'rsfv'
						) }
					</li>
					<li>
						{ __(
							'Search for posts by title using the search field above.',
							'rsfv'
						) }
					</li>
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
