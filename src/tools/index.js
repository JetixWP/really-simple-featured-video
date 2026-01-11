/**
 * Bulk Actions React App Entry Point
 *
 * @package RSFV
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './style.css';

const container = document.getElementById( 'rsfv-tools-app' );

if ( container ) {
	const root = createRoot( container );
	root.render( <App /> );
}
