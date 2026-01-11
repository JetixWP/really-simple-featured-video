/**
 * Thumbnail Cell Component
 *
 * Handles thumbnail display and set/remove actions on hover.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const ThumbnailCell = ( { post, onUpdate } ) => {
	const [ saving, setSaving ] = useState( false );
	const hasThumbnail = !! post.thumbnail;

	const openMediaUploader = () => {
		const frame = wp.media( {
			title: __( 'Select Featured Image', 'rsfv' ),
			button: {
				text: __( 'Set featured image', 'rsfv' ),
			},
			library: {
				type: 'image',
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
					path: '/rsfv/v1/posts/update-thumbnail',
					method: 'POST',
					data: {
						post_id: post.id,
						thumbnail_id: attachment.id,
					},
				} );

				if ( onUpdate ) {
					onUpdate( post.id, {
						thumbnail: attachment.sizes?.thumbnail?.url || attachment.url,
					} );
				}
			} catch ( error ) {
				console.error( 'Error setting thumbnail:', error );
			} finally {
				setSaving( false );
			}
		} );

		frame.open();
	};

	const handleRemoveThumbnail = async ( e ) => {
		e.stopPropagation();

		setSaving( true );

		try {
			await apiFetch( {
				path: '/rsfv/v1/posts/update-thumbnail',
				method: 'POST',
				data: {
					post_id: post.id,
					thumbnail_id: 0,
				},
			} );

			if ( onUpdate ) {
				onUpdate( post.id, {
					thumbnail: '',
				} );
			}
		} catch ( error ) {
			console.error( 'Error removing thumbnail:', error );
		} finally {
			setSaving( false );
		}
	};

	if ( saving ) {
		return (
			<div className="rsfv-thumbnail-cell rsfv-thumbnail-saving">
				<span className="spinner is-active"></span>
			</div>
		);
	}

	if ( hasThumbnail ) {
		return (
			<div
				className="rsfv-thumbnail-cell rsfv-has-thumbnail"
				onClick={ openMediaUploader }
			>
				<img
					src={ post.thumbnail }
					alt={ post.title }
					className="rsfv-thumbnail"
				/>
				<div className="rsfv-thumbnail-overlay rsfv-thumbnail-remove">
					<button
						className="rsfv-thumbnail-action"
						onClick={ handleRemoveThumbnail }
						title={ __( 'Remove featured image', 'rsfv' ) }
					>
						<span className="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>
		);
	}

	return (
		<div
			className="rsfv-thumbnail-cell rsfv-no-thumbnail"
			onClick={ openMediaUploader }
		>
			<span className="dashicons dashicons-format-image"></span>
			<div className="rsfv-thumbnail-overlay rsfv-thumbnail-add">
				<button
					className="rsfv-thumbnail-action"
					title={ __( 'Set featured image', 'rsfv' ) }
				>
					<span className="dashicons dashicons-plus-alt2"></span>
				</button>
			</div>
		</div>
	);
};

export default ThumbnailCell;
