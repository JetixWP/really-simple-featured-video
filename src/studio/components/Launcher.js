/**
 * "Create with Video Studio" button inside the Featured Video box.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import StudioModal from './StudioModal';

const Launcher = ( { config: initialConfig } ) => {
	const [ config, setConfig ] = useState( initialConfig );
	const [ open, setOpen ] = useState( false );

	const hasVideo = config.isCurrent && config.composition;
	const changed = hasVideo && config.changed && config.changed.length > 0;

	const onSaved = ( response ) => {
		setConfig( ( current ) => ( {
			...current,
			composition: response.composition,
			isCurrent: true,
			changed: [],
		} ) );
	};

	return (
		<div className="rsfv-studio-launcher__inner">
			<Button variant="secondary" onClick={ () => setOpen( true ) }>
				{ hasVideo
					? __( 'Edit in Video Studio', 'rsfv' )
					: __( 'Create with Video Studio', 'rsfv' ) }
			</Button>
			{ hasVideo && ! changed && (
				<p className="rsfv-studio-muted">
					{ __( 'This video was made with Video Studio.', 'rsfv' ) }
				</p>
			) }
			{ changed && (
				<p className="rsfv-studio-stale">
					{ __(
						'This post changed after the video was made. Open Video Studio to make it again.',
						'rsfv'
					) }
				</p>
			) }
			{ open && (
				<StudioModal
					config={ config }
					onClose={ () => setOpen( false ) }
					onSaved={ onSaved }
				/>
			) }
		</div>
	);
};

export default Launcher;
