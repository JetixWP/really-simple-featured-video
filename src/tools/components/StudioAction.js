/**
 * Video Studio actions for a row: Auto Generate Video when there is no
 * video, Edit in Video Studio when Video Studio made it.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import AutoGenerate from './AutoGenerate';

const StudioAction = ( { post, onUpdate } ) => {
	const [ open, setOpen ] = useState( false );
	const studioUrl = ( window.rsfvTools && window.rsfvTools.studioUrl ) || '';

	if ( ! window.rsfvStudioApi || ! window.rsfvStudio || ! studioUrl ) {
		return null;
	}

	const done = ( response ) => {
		onUpdate( post.id, {
			has_video: true,
			from_studio: true,
			video_source: 'self',
			video_id: response.video.id,
			video_url: response.video.url,
			poster_id: response.poster ? response.poster.id : 0,
			poster_url: response.poster ? response.poster.url : '',
		} );
	};

	let action = null;
	if ( post.from_studio ) {
		action = (
			<a
				className="button button-small"
				href={ `${ studioUrl }&post_id=${ post.id }` }
			>
				{ __( 'Edit in Video Studio', 'rsfv' ) }
			</a>
		);
	} else if ( ! post.has_video ) {
		action = (
			<button
				type="button"
				className="button button-small button-primary"
				onClick={ () => setOpen( true ) }
			>
				{ __( 'Auto Generate Video', 'rsfv' ) }
			</button>
		);
	}

	if ( ! action && ! open ) {
		return null;
	}

	// The dialog stays open after the row updates, to show the result.
	return (
		<div className="rsfv-action-row rsfv-studio-action">
			{ action }
			{ open && (
				<AutoGenerate
					post={ post }
					onClose={ () => setOpen( false ) }
					onDone={ done }
				/>
			) }
		</div>
	);
};

export default StudioAction;
