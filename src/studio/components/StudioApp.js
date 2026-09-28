/**
 * Video Studio page with tabs (add-ons add more, e.g. PRO Bulk generate).
 *
 * @package RSFV
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import StudioPage from './StudioPage';

const StudioApp = ( { base } ) => {
	/**
	 * Filter the Video Studio page tabs.
	 *
	 * @param {Array}  tabs Tabs: { id, label, render }.
	 * @param {Object} base Page config.
	 */
	const tabs = applyFilters(
		'rsfv.studio.tabs',
		[
			{
				id: 'make',
				label: __( 'Make a video', 'rsfv' ),
				render: () => <StudioPage base={ base } />,
			},
		],
		base
	);

	const fromHash = () => {
		const hash = window.location.hash.replace( '#', '' );
		return tabs.some( ( tab ) => tab.id === hash ) ? hash : tabs[ 0 ].id;
	};

	const [ active, setActive ] = useState( fromHash );

	useEffect( () => {
		const onHash = () => setActive( fromHash() );
		window.addEventListener( 'hashchange', onHash );
		return () => window.removeEventListener( 'hashchange', onHash );
	}, [] );

	const choose = ( id ) => {
		setActive( id );
		window.history.replaceState(
			null,
			'',
			`${ window.location.pathname }${ window.location.search }#${ id }`
		);
	};

	const current = tabs.find( ( tab ) => tab.id === active ) || tabs[ 0 ];

	return (
		<>
			<div className="rsfv-tabs">
				<nav className="rsfv-tabs-nav">
					{ tabs.map( ( tab ) => (
						<button
							key={ tab.id }
							type="button"
							className={ `rsfv-tab-button ${
								active === tab.id ? 'active' : ''
							}` }
							onClick={ () => choose( tab.id ) }
						>
							{ tab.label }
						</button>
					) ) }
				</nav>
			</div>
			<div className="rsfv-tab-content">
				<div className="rsfv-content-wrapper">
					<div className="rsfv-main-content">
						{ current.render() }
					</div>
				</div>
			</div>
		</>
	);
};

export default StudioApp;
