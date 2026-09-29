/**
 * Featured Video block registration.
 */

import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import './editor.css';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	// Rendered on the server so it always follows the saved featured video.
	save: () => null,
} );
