/**
 * Bulk Actions React App Entry Point
 *
 * @package RSFV
 */

import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import App from './App';
import './style.css';

// Import hooks to make them globally available.
import './hooks';

// Mount on DOM ready so add-ons loaded after this script can add tabs.
domReady( () => {
	const container = document.getElementById( 'rsfv-tools-app' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <App /> );
	}
} );
