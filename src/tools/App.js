/**
 * Main Bulk Actions App Component
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ManageFeaturedVideos from './components/ManageFeaturedVideos';

const App = () => {
	const [ activeTab, setActiveTab ] = useState( 'manage' );

	const tabs = [
		{
			id: 'manage',
			label: __( 'Manage Featured Videos', 'rsfv' ),
		},
	];

	return (
		<div className="rsfv-tools-app">
			<div className="rsfv-tabs">
				<nav className="rsfv-tabs-nav">
					{ tabs.map( ( tab ) => (
						<button
							key={ tab.id }
							className={ `rsfv-tab-button ${ activeTab === tab.id ? 'active' : '' }` }
							onClick={ () => setActiveTab( tab.id ) }
						>
							{ tab.label }
						</button>
					) ) }
				</nav>
			</div>

			<div className="rsfv-tab-content">
				{ activeTab === 'manage' && <ManageFeaturedVideos /> }
			</div>
		</div>
	);
};

export default App;
