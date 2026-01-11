/**
 * Video Type Select Component
 *
 * Handles video type selection for each post.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const VideoTypeSelect = ( { post, onUpdate } ) => {
	const [ saving, setSaving ] = useState( false );

	/**
	 * Calculate has_video based on source type and available data.
	 *
	 * @param {string} source Video source type.
	 * @return {boolean} Whether video exists for the source.
	 */
	const calculateHasVideo = ( source ) => {
		if ( source === 'self' && post.video_id ) {
			return true;
		}
		if ( source === 'embed' && post.embed_url ) {
			return true;
		}
		return false;
	};

	const handleSourceChange = async ( newSource ) => {
		setSaving( true );

		try {
			await apiFetch( {
				path: '/rsfv/v1/posts/update-source',
				method: 'POST',
				data: {
					post_id: post.id,
					video_source: newSource,
				},
			} );

			if ( onUpdate ) {
				onUpdate( post.id, {
					video_source: newSource,
					has_video: calculateHasVideo( newSource ),
				} );
			}
		} catch ( error ) {
			console.error( 'Error updating video source:', error );
		} finally {
			setSaving( false );
		}
	};

	return (
		<div className="rsfv-video-type-select">
			<select
				value={ post.video_source || '' }
				onChange={ ( e ) => handleSourceChange( e.target.value ) }
				disabled={ saving }
				className="rsfv-video-source-select"
			>
				<option value="">{ __( 'Select Type', 'rsfv' ) }</option>
				<option value="self">{ __( 'Self Hosted', 'rsfv' ) }</option>
				<option value="embed">{ __( 'Embed', 'rsfv' ) }</option>
			</select>
			{ saving && (
				<span className="spinner is-active rsfv-inline-spinner"></span>
			) }
		</div>
	);
};

export default VideoTypeSelect;
