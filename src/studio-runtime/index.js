/**
 * Video Studio runtime.
 *
 * Runs inside the sandboxed preview iframe (opaque origin, no access to the
 * admin). Mounts a template scene, seeks its timeline, and renders it to a
 * video file that is handed back to the editor.
 *
 * Scene format follows HyperFrames where it can: plain HTML/CSS, elements
 * with data-start / data-duration are clips that only show inside their time
 * window, and the animation timeline is seekable to any time.
 *
 * @package RSFV
 */

import * as anime from 'animejs';
import {
	AudioBufferSource,
	canEncodeAudio,
	getFirstEncodableAudioCodec,
	QUALITY_HIGH,
	QUALITY_MEDIUM,
} from 'mediabunny';
import { createCapturer, canCapture, imageLoaded } from './capture';
import { pickCodec, createOutput, fitBitrate } from './encode';

const templates = {};
const extensions = [];

const state = {
	template: null,
	scene: null,
	timeline: null,
	ctx: null,
	width: 0,
	height: 0,
	fps: 30,
	duration: 0,
	posterTime: 0,
	css: '',
	objectUrls: [],
	playing: false,
	loop: false,
	playFrom: 0,
	playStart: 0,
	rendering: false,
	cancelled: false,
};

const fontCache = {};

const BASE_CSS =
	'.rsfv-scene{position:relative;overflow:hidden;box-sizing:border-box;margin:0}' +
	'.rsfv-scene *,.rsfv-scene *::before,.rsfv-scene *::after{box-sizing:border-box}' +
	'.rsfv-scene > *{position:absolute}' +
	'.rsfv-scene img{display:block}' +
	'[data-clip-hidden]{visibility:hidden !important}';

/**
 * Escape text for HTML.
 *
 * @param {*} value Value.
 * @return {string} Escaped string.
 */
function esc( value ) {
	return String( null === value || undefined === value ? '' : value )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

/**
 * Seeded random numbers, so renders are repeatable.
 *
 * @param {string} seedText Seed.
 * @return {Function} Random function.
 */
/* eslint-disable no-bitwise */
function seededRandom( seedText ) {
	let seed = 0;
	for ( let i = 0; i < seedText.length; i++ ) {
		seed = ( Math.imul( 31, seed ) + seedText.charCodeAt( i ) ) | 0;
	}
	return function random() {
		seed = ( seed + 0x6d2b79f5 ) | 0;
		let t = Math.imul( seed ^ ( seed >>> 15 ), 1 | seed );
		t = ( t + Math.imul( t ^ ( t >>> 7 ), 61 | t ) ) ^ t;
		return ( ( t ^ ( t >>> 14 ) ) >>> 0 ) / 4294967296;
	};
}
/* eslint-enable no-bitwise */

/**
 * Read a Blob as a data URL.
 *
 * @param {Blob} blob Blob.
 * @return {Promise<string>} Data URL.
 */
function toDataUrl( blob ) {
	return new Promise( ( resolve, reject ) => {
		const reader = new FileReader();
		reader.onload = () => resolve( reader.result );
		reader.onerror = () => reject( reader.error );
		reader.readAsDataURL( blob );
	} );
}

/**
 * Load fonts into the document and build @font-face rules for capture.
 *
 * @param {Array} fonts Fonts: { family, blob, weight, style }.
 * @return {Promise<string>} CSS.
 */
async function loadFonts( fonts ) {
	let css = '';
	for ( const font of fonts || [] ) {
		if ( ! font || ! font.family || ! font.blob ) {
			continue;
		}
		const key = `${ font.family }|${ font.weight }|${ font.style }`;
		if ( ! fontCache[ key ] ) {
			const descriptors = {
				weight: font.weight || '400',
				style: font.style || 'normal',
			};
			const face = new FontFace(
				font.family,
				await font.blob.arrayBuffer(),
				descriptors
			);
			await face.load();
			document.fonts.add( face );
			const url = await toDataUrl(
				new Blob( [ font.blob ], { type: 'font/woff2' } )
			);
			fontCache[ key ] =
				`@font-face{font-family:"${ font.family }";src:url(${ url }) format("woff2");` +
				`font-weight:${ descriptors.weight };font-style:${ descriptors.style };font-display:block}`;
		}
		css += fontCache[ key ];
	}
	return css;
}

/**
 * Fit the text of an element inside its box by changing its font size.
 *
 * @param {HTMLElement} el          Element with a fixed box.
 * @param {Object}      options     { min, max } in px.
 * @return {number} Chosen font size.
 */
function fitText( el, { min = 12, max = 200 } = {} ) {
	if ( ! el ) {
		return 0;
	}
	let lo = min;
	let hi = max;
	let best = min;
	for ( let i = 0; i < 12; i++ ) {
		const mid = ( lo + hi ) / 2;
		el.style.fontSize = `${ mid }px`;
		const fits =
			el.scrollWidth <= el.clientWidth + 1 &&
			el.scrollHeight <= el.clientHeight + 1;
		if ( fits ) {
			best = mid;
			lo = mid;
		} else {
			hi = mid;
		}
	}
	el.style.fontSize = `${ best }px`;
	return best;
}

/**
 * Show or hide clips for a time.
 *
 * @param {number} time Seconds.
 */
function applyClips( time ) {
	if ( ! state.scene ) {
		return;
	}
	state.scene.querySelectorAll( '[data-start]' ).forEach( ( el ) => {
		const start = parseFloat( el.getAttribute( 'data-start' ) ) || 0;
		const length = parseFloat( el.getAttribute( 'data-duration' ) );
		const end = Number.isFinite( length ) ? start + length : Infinity;
		if ( time >= start && time < end ) {
			el.removeAttribute( 'data-clip-hidden' );
		} else {
			el.setAttribute( 'data-clip-hidden', '' );
		}
	} );
}

/**
 * Seek the scene to a time.
 *
 * @param {number} time Seconds.
 */
function seek( time ) {
	const t = Math.max( 0, Math.min( time, state.duration ) );
	if ( state.timeline ) {
		state.timeline.seek( t * 1000 );
	}
	applyClips( t );
	if ( state.template && 'function' === typeof state.template.update ) {
		state.template.update( t, state.ctx );
	}
}

/**
 * Scale the stage so the whole scene fits the preview.
 */
function fitStage() {
	const viewport = document.getElementById( 'rsfv-viewport' );
	const stage = document.getElementById( 'rsfv-stage' );
	if ( ! viewport || ! stage || ! state.width ) {
		return;
	}
	const scale = Math.min(
		viewport.clientWidth / state.width,
		viewport.clientHeight / state.height
	);
	stage.style.width = `${ state.width }px`;
	stage.style.height = `${ state.height }px`;
	stage.style.transform = `translate(${ ( -state.width * scale ) / 2 }px, ${
		( -state.height * scale ) / 2
	}px) scale(${ scale })`;
}

/**
 * Post an event to the editor.
 *
 * @param {string} event Event name.
 * @param {Object} data  Data.
 */
function emit( event, data = {} ) {
	window.parent.postMessage( { event, ...data }, '*' );
}

/**
 * Preview loop.
 */
function tick() {
	if ( ! state.playing ) {
		return;
	}
	let time = state.playFrom + ( performance.now() - state.playStart ) / 1000;
	if ( time >= state.duration ) {
		if ( ! state.loop ) {
			// Play once: stop on the last frame.
			state.playing = false;
			state.playFrom = state.duration;
			seek( state.duration );
			emit( 'time', { time: state.duration } );
			emit( 'ended' );
			return;
		}
		time = 0;
		state.playFrom = 0;
		state.playStart = performance.now();
	}
	seek( time );
	emit( 'time', { time } );
	window.requestAnimationFrame( tick );
}

/**
 * Pause the preview.
 */
function pause() {
	if ( state.playing ) {
		state.playFrom += ( performance.now() - state.playStart ) / 1000;
	}
	state.playing = false;
}

const handlers = {
	/**
	 * What this browser can do.
	 *
	 * @return {Promise<Object>} Capabilities.
	 */
	async capability() {
		const secure = !! window.isSecureContext;
		const webcodecs =
			'undefined' !== typeof window.VideoEncoder &&
			'undefined' !== typeof window.VideoFrame;
		const capture = await canCapture();
		let codec = null;
		if ( secure && webcodecs ) {
			try {
				codec = await pickCodec( 1280, 720 );
			} catch ( e ) {
				codec = null;
			}
		}
		return {
			secure,
			webcodecs,
			capture,
			codec,
			canRender: secure && webcodecs && capture && !! codec,
		};
	},

	/**
	 * Mount a template.
	 *
	 * @param {Object} payload Template id, vars, size, assets and fonts.
	 * @return {Promise<Object>} Duration and poster time.
	 */
	async load( payload ) {
		pause();
		const template = templates[ payload.template ];
		if ( ! template ) {
			throw new Error( `Unknown template: ${ payload.template }` );
		}

		state.objectUrls.forEach( ( url ) => URL.revokeObjectURL( url ) );
		state.objectUrls = [];

		const media = {};
		Object.keys( payload.assets || {} ).forEach( ( key ) => {
			const value = payload.assets[ key ];
			const toUrl = ( blob ) => {
				if ( ! blob ) {
					return '';
				}
				const url = URL.createObjectURL( blob );
				state.objectUrls.push( url );
				return url;
			};
			media[ key ] = Array.isArray( value )
				? value.map( toUrl ).filter( Boolean )
				: toUrl( value );
		} );

		const width = payload.width;
		const height = payload.height;
		const ratio = width / height;
		let orientation = 'square';
		if ( ratio > 1.1 ) {
			orientation = 'landscape';
		} else if ( ratio < 0.9 ) {
			orientation = 'portrait';
		}

		const vars = payload.vars || {};
		const ctx = {
			vars,
			width,
			height,
			fps: payload.fps,
			u: Math.min( width, height ) / 100,
			orientation,
			esc,
			anime,
			random: seededRandom( payload.template ),
			media: ( key ) => media[ key ] || '',
			fitText,
			$: ( selector ) => state.scene.querySelector( selector ),
			$$: ( selector ) =>
				Array.from( state.scene.querySelectorAll( selector ) ),
		};

		const duration =
			'function' === typeof template.duration
				? template.duration( ctx )
				: template.duration || 8;
		ctx.duration = duration;

		const fontCss = await loadFonts( payload.fonts );
		const templateCss =
			'function' === typeof template.css
				? template.css( ctx )
				: template.css || '';

		const stage = document.getElementById( 'rsfv-stage' );
		stage.innerHTML = '';
		const style = document.createElement( 'style' );
		style.textContent = BASE_CSS + templateCss;
		stage.appendChild( style );

		const scene = document.createElement( 'div' );
		scene.className = `rsfv-scene is-${ orientation }`;
		scene.style.width = `${ width }px`;
		scene.style.height = `${ height }px`;
		scene.style.setProperty( '--u', `${ ctx.u }px` );
		scene.style.setProperty( '--w', `${ width }px` );
		scene.style.setProperty( '--h', `${ height }px` );
		scene.innerHTML = template.html( ctx );
		stage.appendChild( scene );

		state.template = template;
		state.scene = scene;
		state.ctx = ctx;
		state.width = width;
		state.height = height;
		state.fps = payload.fps || 30;
		state.duration = duration;
		state.css = fontCss + BASE_CSS + templateCss;
		state.playFrom = 0;

		await Promise.all(
			Array.from( scene.querySelectorAll( 'img' ) ).map( ( img ) =>
				imageLoaded( img ).catch( () => null )
			)
		);
		await document.fonts.ready;

		if ( 'function' === typeof template.mount ) {
			template.mount( ctx );
		}

		const timeline = anime.createTimeline( {
			autoplay: false,
			defaults: { ease: 'outExpo' },
		} );
		template.timeline( timeline, ctx );
		state.timeline = timeline;

		state.posterTime =
			'function' === typeof template.posterTime
				? template.posterTime( ctx )
				: Math.min( duration, template.posterTime || duration / 2 );

		fitStage();
		seek( 0 );

		return { duration, posterTime: state.posterTime };
	},

	async seek( { time } ) {
		pause();
		seek( time );
		// Play continues from here.
		state.playFrom = Math.max( 0, Math.min( time, state.duration ) );
		return { time };
	},

	/**
	 * Play the preview.
	 *
	 * @param {Object}  payload      Options.
	 * @param {boolean} payload.loop Start again at the end.
	 * @return {Promise<Object>} Nothing.
	 */
	async play( { loop } = {} ) {
		if ( state.rendering || ! state.scene ) {
			return {};
		}
		if ( undefined !== loop ) {
			state.loop = !! loop;
		}
		if ( state.playing ) {
			return {};
		}
		// At the end, play from the start again.
		if ( state.playFrom >= state.duration - 0.01 ) {
			state.playFrom = 0;
		}
		state.playing = true;
		state.playStart = performance.now();
		window.requestAnimationFrame( tick );
		return {};
	},

	async loop( { loop } ) {
		state.loop = !! loop;
		return {};
	},

	async pause() {
		pause();
		return { time: state.playFrom };
	},

	async cancel() {
		state.cancelled = true;
		return {};
	},

	/**
	 * Capture one frame as a JPEG, exactly as the render would draw it.
	 *
	 * @param {Object} payload       { time, width } (width scales the image down).
	 * @param          payload.time
	 * @param          payload.width
	 * @return {Promise<Object>} { image: ArrayBuffer }.
	 */
	async snapshot( { time = 0, width } = {} ) {
		if ( ! state.scene || state.rendering ) {
			throw new Error( 'Nothing to capture.' );
		}
		pause();
		const canvas = document.createElement( 'canvas' );
		canvas.width = state.width;
		canvas.height = state.height;
		const capture = createCapturer( {
			scene: state.scene,
			css: state.css,
			width: state.width,
			height: state.height,
			canvas,
		} );
		seek( time );
		await capture();

		let out = canvas;
		if ( width && width < state.width ) {
			out = document.createElement( 'canvas' );
			out.width = Math.round( width );
			out.height = Math.round( ( width / state.width ) * state.height );
			out.getContext( '2d' ).drawImage(
				canvas,
				0,
				0,
				out.width,
				out.height
			);
		}
		const blob = await new Promise( ( resolve ) =>
			out.toBlob( resolve, 'image/jpeg', 0.88 )
		);
		return { image: await blob.arrayBuffer() };
	},

	/**
	 * Render the mounted scene to a video.
	 *
	 * @param {Object} payload { poster: bool, posterTime, maxBytes, extensions: {} }.
	 * @return {Promise<Object>} Video (and poster) as ArrayBuffers.
	 */
	async render( payload = {} ) {
		if ( ! state.scene ) {
			throw new Error( 'Nothing to render.' );
		}
		pause();
		state.rendering = true;
		state.cancelled = false;

		const { width, height, fps, duration } = state;
		const canvas = document.createElement( 'canvas' );
		canvas.width = width;
		canvas.height = height;
		const capture = createCapturer( {
			scene: state.scene,
			css: state.css,
			width,
			height,
			canvas,
		} );

		try {
			const codec = await pickCodec( width, height );
			if ( ! codec ) {
				throw new Error(
					'This browser cannot encode video at this size.'
				);
			}
			const bitrate = fitBitrate( {
				maxBytes: Number( payload.maxBytes ) || 0,
				duration,
				width,
				height,
				fps,
			} );
			const { output, source, format } = createOutput( {
				canvas,
				fps,
				codec,
				bitrate,
			} );

			const extensionContext = {
				output,
				format,
				duration,
				fps,
				width,
				height,
				lib: { mediabunny: runtimeApi.lib.mediabunny },
			};
			for ( const extension of extensions ) {
				if ( 'function' === typeof extension.beforeRender ) {
					await extension.beforeRender( {
						...extensionContext,
						config: ( payload.extensions || {} )[ extension.id ],
					} );
				}
			}

			await output.start();

			for ( const extension of extensions ) {
				if ( 'function' === typeof extension.afterStart ) {
					await extension.afterStart( {
						...extensionContext,
						config: ( payload.extensions || {} )[ extension.id ],
					} );
				}
			}

			const frames = Math.max( 1, Math.round( duration * fps ) );
			for ( let i = 0; i < frames; i++ ) {
				if ( state.cancelled ) {
					await output.cancel();
					throw new Error( 'cancelled' );
				}
				seek( i / fps );
				await capture();
				await source.add( i / fps, 1 / fps );
				if ( 0 === i % 5 || i === frames - 1 ) {
					emit( 'progress', { frame: i + 1, frames } );
				}
			}

			for ( const extension of extensions ) {
				if ( 'function' === typeof extension.afterFrames ) {
					await extension.afterFrames( {
						...extensionContext,
						config: ( payload.extensions || {} )[ extension.id ],
					} );
				}
			}

			await output.finalize();
			const video = output.target.buffer;

			let poster = null;
			if ( false !== payload.poster ) {
				seek(
					undefined !== payload.posterTime
						? Number( payload.posterTime )
						: state.posterTime
				);
				await capture();
				const blob = await new Promise( ( resolve ) =>
					canvas.toBlob( resolve, 'image/jpeg', 0.9 )
				);
				poster = blob ? await blob.arrayBuffer() : null;
			}

			seek( 0 );

			return {
				video,
				poster,
				mime: format.mimeType,
				extension: format.fileExtension,
				codec,
				width,
				height,
				fps,
				duration,
				frames,
			};
		} finally {
			state.rendering = false;
		}
	},
};

window.addEventListener( 'message', async ( event ) => {
	if ( event.source !== window.parent ) {
		return;
	}
	const { id, type, payload } = event.data || {};
	if ( ! id || ! handlers[ type ] ) {
		return;
	}
	try {
		const result = await handlers[ type ]( payload || {} );
		const transfer = [];
		if ( result && result.video instanceof ArrayBuffer ) {
			transfer.push( result.video );
		}
		if ( result && result.poster instanceof ArrayBuffer ) {
			transfer.push( result.poster );
		}
		if ( result && result.image instanceof ArrayBuffer ) {
			transfer.push( result.image );
		}
		window.parent.postMessage( { id, ok: true, result }, '*', transfer );
	} catch ( error ) {
		window.parent.postMessage(
			{
				id,
				ok: false,
				error: String( ( error && error.message ) || error ),
			},
			'*'
		);
	}
} );

window.addEventListener( 'resize', fitStage );
if ( 'function' === typeof window.ResizeObserver ) {
	window.addEventListener( 'DOMContentLoaded', () => {
		const viewport = document.getElementById( 'rsfv-viewport' );
		if ( viewport ) {
			new window.ResizeObserver( fitStage ).observe( viewport );
		}
	} );
}

const runtimeApi = {
	version: 1,
	anime,
	/**
	 * Register a template.
	 *
	 * @param {Object} definition { id, duration, posterTime, css, html, mount, timeline, update }.
	 */
	registerTemplate( definition ) {
		if ( definition && definition.id ) {
			templates[ definition.id ] = definition;
		}
	},
	/**
	 * Register a render extension (e.g. an audio track). Hooks, in order:
	 * beforeRender (add tracks), afterStart (add samples), afterFrames.
	 *
	 * @param {Object} extension { id, beforeRender, afterStart, afterFrames }.
	 */
	registerExtension( extension ) {
		if ( extension && extension.id ) {
			extensions.push( extension );
		}
	},
	lib: {
		mediabunny: {
			AudioBufferSource,
			canEncodeAudio,
			getFirstEncodableAudioCodec,
			QUALITY_HIGH,
			QUALITY_MEDIUM,
		},
	},
	/**
	 * Called after all template scripts ran.
	 */
	boot() {
		emit( 'boot', { templates: Object.keys( templates ) } );
	},
};

window.RSFVStudio = runtimeApi;
