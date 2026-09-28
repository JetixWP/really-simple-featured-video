/**
 * Main Bulk Actions App Component
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import ManageFeaturedVideos from './components/ManageFeaturedVideos';
import BulkUpload from './components/BulkUpload';
import ManageFloatingVideos from './components/ManageFloatingVideos';
import AnalyticsReport from './components/AnalyticsReport';
import Sidebar from './components/Sidebar';

const App = () => {
	/**
	 * Filter the Tools tabs. Add-on tabs pass a render() function.
	 *
	 * @param {Array} tabs Tabs: { id, label, render? }.
	 */
	const tabs = applyFilters( 'rsfv.tools.tabs', [
		{
			id: 'manage',
			label: __( 'Manage Featured Videos', 'rsfv' ),
		},
		{
			id: 'bulk-upload',
			label: __( 'Bulk upload', 'rsfv' ),
		},
		{
			id: 'floating-videos',
			label: __( 'Manage Sticky Videos', 'rsfv' ),
		},
		{
			id: 'analytics',
			label: __( 'Analytics', 'rsfv' ),
		},
	] );

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
		return () =>
			window.removeEventListener( 'hashchange', handleHashChange );
	}, [ getTabFromHash ] );

	return (
		<div className="rsfv-tools-app">
			<div className="rsfv-tabs">
				<nav className="rsfv-tabs-nav">
					{ tabs.map( ( tab ) => (
						<button
							key={ tab.id }
							className={ `rsfv-tab-button ${
								activeTab === tab.id ? 'active' : ''
							}` }
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
						{ activeTab === 'bulk-upload' && <BulkUpload /> }
						{ activeTab === 'floating-videos' && (
							<ManageFloatingVideos />
						) }
						{ activeTab === 'analytics' && <AnalyticsReport /> }
						{ tabs
							.filter(
								( tab ) =>
									tab.id === activeTab &&
									'function' === typeof tab.render
							)
							.map( ( tab ) => (
								<div key={ tab.id }>{ tab.render() }</div>
							) ) }
					</div>
					<Sidebar />
				</div>
			</div>
		</div>
	);
};

export default App;
