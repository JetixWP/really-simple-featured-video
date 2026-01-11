/**
 * Video Action Cell Component
 *
 * Handles video type selection and action button for each post.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const VideoActionCell = ( { post, onUpdate } ) => {
	const [ videoSource, setVideoSource ] = useState( post.video_source || '' );
	const [ saving, setSaving ] = useState( false );

	const handleSourceChange = async ( newSource ) => {
		setSaving( true );
		setVideoSource( newSource );

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
				onUpdate( post.id, { video_source: newSource } );
			}
		} catch ( error ) {
			console.error( 'Error updating video source:', error );
			// Revert on error.
			setVideoSource( post.video_source || '' );
		} finally {
			setSaving( false );
		}
	};

	const getActionButton = () => {
		if ( ! videoSource ) {
			return null;
		}

		const hasVideo =
			( videoSource === 'self' && post.video_id ) ||
			( videoSource === 'embed' && post.embed_url );

		const buttonText = hasVideo
			? __( 'Edit Video', 'rsfv' )
			: __( 'Upload Video', 'rsfv' );

		const buttonClass = hasVideo
			? 'button button-small'
			: 'button button-small button-primary';

		return (
			<button
				className={ buttonClass }
				onClick={ () => openMediaUploader( videoSource ) }
				disabled={ saving }
			>
				{ buttonText }
			</button>
		);
	};

	const openMediaUploader = ( source ) => {
		if ( source === 'self' ) {
			// Open WordPress media uploader for video.
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
		} else if ( source === 'embed' ) {
			// Open embed URL prompt.
			const currentUrl = post.embed_url || '';
			const embedUrl = window.prompt(
				__( 'Enter video embed URL (YouTube, Vimeo, etc.):', 'rsfv' ),
				currentUrl
			);

			if ( embedUrl !== null ) {
				setSaving( true );

				apiFetch( {
					path: '/rsfv/v1/posts/update-video',
					method: 'POST',
					data: {
						post_id: post.id,
						video_source: 'embed',
						embed_url: embedUrl,
					},
				} )
					.then( () => {
						if ( onUpdate ) {
							onUpdate( post.id, {
								video_source: 'embed',
								embed_url: embedUrl,
								has_video: !! embedUrl,
							} );
						}
					} )
					.catch( ( error ) => {
						console.error( 'Error saving embed URL:', error );
					} )
					.finally( () => {
						setSaving( false );
					} );
			}
		}
	};

	return (
		<div className="rsfv-video-action-cell">
			<div className="rsfv-video-type-select">
				<select
					value={ videoSource }
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
			<div className="rsfv-video-action-button">{ getActionButton() }</div>
		</div>
	);
};

export default VideoActionCell;
