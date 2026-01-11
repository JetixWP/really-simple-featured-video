/**
 * Main Bulk Actions App Component
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ManageFeaturedVideos from './components/ManageFeaturedVideos';
import Sidebar from './components/Sidebar';

const App = () => {
	const tabs = [
		{
			id: 'manage',
			label: __( 'Manage Featured Videos', 'rsfv' ),
		},
	];

	/**
	 * Get the initial tab from URL hash or default to first tab.
	 *
	 * @return {string} The tab ID.
	 */
	const getTabFromHash = useCallback( () => {
		const hash = window.location.hash.replace( '#', '' );
		const validTabIds = tabs.map( ( tab ) => tab.id );
		return validTabIds.includes( hash ) ? hash : tabs[ 0 ].id;
	}, [] );

	const [ activeTab, setActiveTab ] = useState( getTabFromHash );

	/**
	 * Update URL hash when tab changes.
	 *
	 * @param {string} tabId The tab ID to set.
	 */
	const handleTabChange = ( tabId ) => {
		setActiveTab( tabId );
		window.history.replaceState( null, '', `#${ tabId }` );
	};

	// Listen for hash changes (browser back/forward).
	useEffect( () => {
		const handleHashChange = () => {
			setActiveTab( getTabFromHash() );
		};

		window.addEventListener( 'hashchange', handleHashChange );
		return () => window.removeEventListener( 'hashchange', handleHashChange );
	}, [ getTabFromHash ] );

	return (
		<div className="rsfv-tools-app">
			<div className="rsfv-tabs">
				<nav className="rsfv-tabs-nav">
					{ tabs.map( ( tab ) => (
						<button
							key={ tab.id }
							className={ `rsfv-tab-button ${ activeTab === tab.id ? 'active' : '' }` }
							onClick={ () => handleTabChange( tab.id ) }
						>
							{ tab.label }
						</button>
					) ) }
				</nav>
			</div>

			<div className="rsfv-tab-content">
				<div className="rsfv-content-wrapper">
					<div className="rsfv-main-content">
						{ activeTab === 'manage' && <ManageFeaturedVideos /> }
					</div>
					<Sidebar />
				</div>
			</div>
		</div>
	);
};

export default App;
