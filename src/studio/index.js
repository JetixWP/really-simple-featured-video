/**
 * Video Studio editor entry.
 *
 * @package RSFV
 */

import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import Sandbox from './sandbox';
import {
	autofillValue,
	buildLoadPayload,
	fetchBlob,
	getMedia,
	initialVars,
	rememberMedia,
} from './payload';
import { applyToEditor, defaultUploader, uploadRender } from './upload';
import Launcher from './components/Launcher';
import StudioModal from './components/StudioModal';
import AutoGenerate from './components/AutoGenerate';
import Progress from './components/Progress';
import StudioPage from './components/StudioPage';
import './style.scss';

// Building blocks for add-ons (PRO bulk generate uses these).
window.rsfvStudioApi = {
	version: 1,
	Sandbox,
	autofillValue,
	buildLoadPayload,
	fetchBlob,
	getMedia,
	initialVars,
	rememberMedia,
	applyToEditor,
	defaultUploader,
	uploadRender,
	StudioModal,
	AutoGenerate,
	Progress,
};

domReady( () => {
	const root = document.getElementById( 'rsfv-studio-launcher' );
	if ( root && window.rsfvStudio ) {
		createRoot( root ).render( <Launcher config={ window.rsfvStudio } /> );
	}

	const page = document.getElementById( 'rsfv-studio-page' );
	if ( page && window.rsfvStudio ) {
		createRoot( page ).render(
			<div className="rsfv-tab-content">
				<StudioPage base={ window.rsfvStudio } />
			</div>
		);
	}
} );
