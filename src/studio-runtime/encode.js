/**
 * Video encoding for Video Studio (WebCodecs through Mediabunny).
 *
 * @package RSFV
 */

import {
	Output,
	Mp4OutputFormat,
	WebMOutputFormat,
	BufferTarget,
	CanvasSource,
	QUALITY_HIGH,
	getFirstEncodableVideoCodec,
} from 'mediabunny';

/**
 * Pick the best codec this browser can encode at the given size.
 * H.264 in MP4 first (plays everywhere), then VP9/AV1 in WebM.
 *
 * @param {number} width  Width.
 * @param {number} height Height.
 * @return {Promise<string|null>} Codec or null.
 */
export function pickCodec( width, height ) {
	return getFirstEncodableVideoCodec( [ 'avc', 'vp9', 'av1' ], {
		width,
		height,
	} );
}

/**
 * Create an output for the canvas.
 *
 * @param {Object}            options         Options.
 * @param {HTMLCanvasElement} options.canvas  Canvas the frames are drawn on.
 * @param {number}            options.fps     Frame rate.
 * @param {string}            options.codec   Codec from pickCodec().
 * @param {number}            options.bitrate Bits per second (default: high quality).
 * @return {Object} { output, source, format }.
 */
export function createOutput( { canvas, fps, codec, bitrate } ) {
	const format =
		'avc' === codec
			? new Mp4OutputFormat( { fastStart: 'in-memory' } )
			: new WebMOutputFormat();
	const output = new Output( { format, target: new BufferTarget() } );
	const source = new CanvasSource( canvas, {
		codec,
		...( bitrate ? { bitrate } : { quality: QUALITY_HIGH } ),
		keyFrameInterval: 2,
	} );
	output.addVideoTrack( source, { frameRate: fps } );

	return { output, source, format };
}

/**
 * Bitrate that keeps the file under a size limit (for hosts with small
 * upload limits). Returns 0 when high quality already fits.
 *
 * @param {Object} options          Options.
 * @param {number} options.maxBytes Largest file allowed.
 * @param {number} options.duration Seconds.
 * @param {number} options.width    Width.
 * @param {number} options.height   Height.
 * @param {number} options.fps      Frame rate.
 * @return {number} Bits per second, or 0 for the default.
 */
export function fitBitrate( { maxBytes, duration, width, height, fps } ) {
	if ( ! maxBytes || ! duration ) {
		return 0;
	}
	// Leave room for the container and the poster upload.
	const budget = Math.floor( ( maxBytes * 8 * 0.8 ) / duration );
	// Roughly what "high quality" asks for at this size.
	const high = width * height * fps * 0.1;
	if ( budget >= high ) {
		return 0;
	}
	return Math.max( 300000, budget );
}
