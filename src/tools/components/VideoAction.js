/**
 * Video Action Component
 *
 * Handles video upload/embed action for each post.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const VideoAction = ( { post, onUpdate } ) => {
	const [ embedUrl, setEmbedUrl ] = useState( post.embed_url || '' );
	const [ saving, setSaving ] = useState( false );

	const videoSource = post.video_source || '';

	// No video type selected.
	if ( ! videoSource ) {
		return <span className="rsfv-no-action">—</span>;
	}

	const openMediaUploader = () => {
		const frame = wp.media( {
			title: __( 'Select or Upload Video', 'rsfv' ),
			button: {
				text: __( 'Use this video', 'rsfv' ),
			},
			library: {
				type: 'video',
			},
			multiple: false,
		} );

		frame.on( 'select', async () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();

			setSaving( true );

			try {
				await apiFetch( {
					path: '/rsfv/v1/posts/update-video',
					method: 'POST',
					data: {
						post_id: post.id,
						video_source: 'self',
						video_id: attachment.id,
					},
				} );

				if ( onUpdate ) {
					onUpdate( post.id, {
						video_source: 'self',
						video_id: attachment.id,
						has_video: true,
					} );
				}
			} catch ( error ) {
				console.error( 'Error saving video:', error );
			} finally {
				setSaving( false );
			}
		} );

		frame.open();
	};

	const handleEmbedSave = async () => {
		setSaving( true );

		try {
			await apiFetch( {
				path: '/rsfv/v1/posts/update-video',
				method: 'POST',
				data: {
					post_id: post.id,
					video_source: 'embed',
					embed_url: embedUrl,
				},
			} );

			if ( onUpdate ) {
				onUpdate( post.id, {
					video_source: 'embed',
					embed_url: embedUrl,
					has_video: !! embedUrl,
				} );
			}
		} catch ( error ) {
			console.error( 'Error saving embed URL:', error );
		} finally {
			setSaving( false );
		}
	};

	// Self-hosted video action.
	if ( videoSource === 'self' ) {
		const hasVideo = !! post.video_id;
		const buttonText = hasVideo
			? __( 'Edit Video', 'rsfv' )
			: __( 'Upload Video', 'rsfv' );
		const buttonClass = hasVideo
			? 'button button-small'
			: 'button button-small button-primary';

		return (
			<div className="rsfv-video-action">
				<button
					className={ buttonClass }
					onClick={ openMediaUploader }
					disabled={ saving }
				>
					{ saving ? __( 'Saving...', 'rsfv' ) : buttonText }
				</button>
			</div>
		);
	}

	// Embed video action.
	if ( videoSource === 'embed' ) {
		return (
			<div className="rsfv-video-action rsfv-embed-action">
				<input
					type="url"
					className="rsfv-embed-input"
					value={ embedUrl }
					onChange={ ( e ) => setEmbedUrl( e.target.value ) }
					placeholder={ __( 'Enter video URL...', 'rsfv' ) }
					disabled={ saving }
				/>
				<button
					className="button button-small button-primary"
					onClick={ handleEmbedSave }
					disabled={ saving || embedUrl === ( post.embed_url || '' ) }
				>
					{ saving ? __( 'Saving...', 'rsfv' ) : __( 'Save', 'rsfv' ) }
				</button>
			</div>
		);
	}

	return null;
};

export default VideoAction;
