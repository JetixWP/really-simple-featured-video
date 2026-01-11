/**
 * Video Preview Component
 *
 * Displays video preview for posts with featured videos.
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const VideoPreview = ( { post } ) => {
	const videoSource = post.video_source || '';

	// No video type selected.
	if ( ! videoSource ) {
		return <span className="rsfv-no-video">—</span>;
	}

	// Self-hosted video preview.
	if ( videoSource === 'self' && post.video_id ) {
		const videoUrl = post.video_url || '';
		const posterUrl = post.poster_url || '';
		if ( videoUrl ) {
			return (
				<div className="rsfv-video-preview">
					<video
						src={ videoUrl }
						poster={ posterUrl || undefined }
						controls
						muted
						preload="metadata"
					/>
				</div>
			);
		}
		return <span className="rsfv-no-video">—</span>;
	}

	// Embed video preview.
	if ( videoSource === 'embed' && post.embed_url ) {
		return (
			<div className="rsfv-video-preview rsfv-embed-preview">
				<a
					href={ post.embed_url }
					target="_blank"
					rel="noopener noreferrer"
					className="rsfv-embed-link"
				>
					<span className="dashicons dashicons-video-alt3"></span>
					{ __( 'View Video', 'rsfv' ) }
				</a>
			</div>
		);
	}

	return <span className="rsfv-no-video">—</span>;
};

export default VideoPreview;
