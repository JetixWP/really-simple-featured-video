/**
 * Upload a rendered video and apply it to the editor.
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Default uploader: one multipart request to rsfv/v1/studio/upload.
 *
 * @param {Object}   options              Options.
 * @param {Object}   options.config       Editor config (restUrl, nonce).
 * @param {number}   options.postId       Post ID.
 * @param {Object}   options.composition  Composition to save.
 * @param {Object}   options.result       Runtime render result.
 * @param {boolean}  options.setThumbnail Also set the poster as featured image.
 * @param {Function} options.onProgress   Progress callback (0-1).
 * @return {Promise<Object>} Server response.
 */
export function defaultUploader( {
	config,
	postId,
	composition,
	result,
	setThumbnail,
	onProgress,
} ) {
	const form = new window.FormData();
	form.append( 'post_id', String( postId ) );
	form.append( 'composition', JSON.stringify( composition ) );
	form.append( 'set_thumbnail', setThumbnail ? '1' : '0' );
	form.append(
		'video',
		new window.Blob( [ result.video ], { type: result.mime } ),
		`video.${ result.extension.replace( /^\./, '' ) }`
	);
	if ( result.poster ) {
		form.append(
			'poster',
			new window.Blob( [ result.poster ], { type: 'image/jpeg' } ),
			'poster.jpg'
		);
	}

	return new Promise( ( resolve, reject ) => {
		const xhr = new window.XMLHttpRequest();
		xhr.open( 'POST', `${ config.restUrl }upload` );
		xhr.setRequestHeader( 'X-WP-Nonce', config.nonce );
		xhr.upload.onprogress = ( event ) => {
			if ( event.lengthComputable && onProgress ) {
				onProgress( event.loaded / event.total );
			}
		};
		xhr.onload = () => {
			let body = null;
			try {
				body = JSON.parse( xhr.responseText );
			} catch ( e ) {
				body = null;
			}
			if ( xhr.status >= 200 && xhr.status < 300 && body ) {
				resolve( body );
			} else {
				reject(
					new Error(
						( body && body.message ) ||
							__( 'The upload failed.', 'rsfv' )
					)
				);
			}
		};
		xhr.onerror = () =>
			reject( new Error( __( 'The upload failed.', 'rsfv' ) ) );
		xhr.send( form );
	} );
}

/**
 * Upload with the active uploader (PRO can swap in a chunked one).
 *
 * @param {Object} options See defaultUploader().
 * @return {Promise<Object>} Server response.
 */
export function uploadRender( options ) {
	/**
	 * Filter the function that uploads a rendered video.
	 *
	 * @param {Function} uploader Uploader.
	 * @param {Object}   options  Upload options.
	 */
	const uploader = applyFilters(
		'rsfv.studio.uploader',
		defaultUploader,
		options
	);
	return uploader( options );
}

/**
 * Update the Featured Video box (and featured image) so saving the post
 * keeps the new video.
 *
 * @param {Object} response Upload response.
 */
export function applyToEditor( response ) {
	const $ = window.jQuery;
	const box = document.getElementById( 'featured-video' );
	if ( ! box || ! response || ! response.video ) {
		return;
	}

	const selfRadio = box.querySelector(
		'input[type=radio][name=rsfv_source][value=self]'
	);
	if ( selfRadio ) {
		selfRadio.checked = true;
		if ( $ ) {
			$( selfRadio ).trigger( 'change' );
		}
	}

	const button = box.querySelector( '.rsfv-upload-video-btn' );
	if ( button ) {
		const video = document.createElement( 'video' );
		video.controls = true;
		video.src = response.video.url;
		button.classList.remove( 'button' );
		button.replaceChildren( video );
		const input = document.getElementById( 'rsfv_featured_video' );
		if ( input ) {
			input.value = String( response.video.id );
		}
		const remove = box.querySelector( '.remove-video' );
		if ( remove ) {
			remove.style.display = 'inline-block';
		}
	}

	if ( response.poster ) {
		const posterInput = document.getElementById( 'rsfv_featured_poster' );
		if ( posterInput ) {
			posterInput.value = String( response.poster.id );
		}
		const preview = document.getElementById( 'rsfv-poster-preview' );
		if ( preview ) {
			preview.src = response.poster.url;
			preview.style.display = 'block';
		}
		const removePoster = box.querySelector( '.rsfv-remove-poster' );
		if ( removePoster ) {
			removePoster.style.display = 'inline-block';
		}
	}

	if ( response.thumbnail_set && response.poster ) {
		const data = window.wp && window.wp.data;
		const isBlockEditor =
			document.body.classList.contains( 'block-editor-page' ) &&
			data &&
			data.select( 'core/editor' );
		if ( isBlockEditor ) {
			data.dispatch( 'core/editor' ).editPost( {
				featured_media: response.poster.id,
			} );
		} else if (
			window.wp &&
			window.wp.media &&
			window.wp.media.featuredImage
		) {
			window.wp.media.featuredImage.set( response.poster.id );
		}
	}
}
