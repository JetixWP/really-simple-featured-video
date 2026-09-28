/**
 * Frame capture for Video Studio.
 *
 * Hybrid capture: every <img> in the scene is drawn straight onto the canvas
 * (fast, keeps full photo quality), everything else is rasterised through an
 * SVG foreignObject. Top-level children of the scene can carry data-z to set
 * the paint order; images paint above the DOM of their own group.
 *
 * @package RSFV
 */

const XHTML = 'http://www.w3.org/1999/xhtml';

/**
 * Wait for an image to load. Uses onload instead of decode(), because
 * decode() never settles while the tab is hidden.
 *
 * @param {HTMLImageElement} img Image.
 * @return {Promise<void>} Resolves when loaded.
 */
export function imageLoaded( img ) {
	if ( img.complete && img.naturalWidth ) {
		return Promise.resolve();
	}
	return new Promise( ( resolve, reject ) => {
		img.addEventListener( 'load', () => resolve(), { once: true } );
		img.addEventListener(
			'error',
			() => reject( new Error( 'Image failed to load' ) ),
			{ once: true }
		);
	} );
}

/**
 * Group id of a top-level scene child.
 *
 * @param {Element} el Element.
 * @return {number} Group.
 */
function groupOf( el ) {
	const z = parseInt( el.getAttribute( 'data-z' ) || '0', 10 );
	return Number.isFinite( z ) ? z : 0;
}

/**
 * Parse a CSS length that may be a percentage.
 *
 * @param {string} value CSS value.
 * @param {number} size  Reference size for percentages.
 * @return {number} Pixels.
 */
function toPx( value, size ) {
	if ( ! value ) {
		return 0;
	}
	const first = String( value ).trim().split( /\s+/ )[ 0 ];
	if ( first.endsWith( '%' ) ) {
		return ( parseFloat( first ) / 100 ) * size;
	}
	return parseFloat( first ) || 0;
}

/**
 * Corner radii of an element box.
 *
 * @param {CSSStyleDeclaration} cs Computed style.
 * @param {number}              w  Width.
 * @param {number}              h  Height.
 * @return {number[]} Radii (tl, tr, br, bl).
 */
function radiiOf( cs, w, h ) {
	const ref = Math.min( w, h );
	return [
		toPx( cs.borderTopLeftRadius, ref ),
		toPx( cs.borderTopRightRadius, ref ),
		toPx( cs.borderBottomRightRadius, ref ),
		toPx( cs.borderBottomLeftRadius, ref ),
	].map( ( r ) => Math.min( r, w / 2, h / 2 ) );
}

/**
 * Build the path of a box, rounded when needed.
 *
 * @param {CanvasRenderingContext2D} ctx   Context.
 * @param {number}                   w     Width.
 * @param {number}                   h     Height.
 * @param {number[]}                 radii Radii.
 */
function boxPath( ctx, w, h, radii ) {
	ctx.beginPath();
	if ( radii.some( ( r ) => r > 0 ) && ctx.roundRect ) {
		ctx.roundRect( 0, 0, w, h, radii );
	} else {
		ctx.rect( 0, 0, w, h );
	}
}

/**
 * Walk the offsetParent chain of an element up to the scene and collect the
 * matrix, opacity and overflow clips needed to paint it on the canvas.
 *
 * Template rule: ancestors that move media must be positioned elements.
 *
 * @param {HTMLElement} el   Element.
 * @param {HTMLElement} root Scene root.
 * @return {Object|null} Geometry or null when the element isn't painted.
 */
function geometryOf( el, root ) {
	const chain = [];
	let node = el;
	while ( node && node !== root ) {
		chain.unshift( node );
		node = node.offsetParent;
	}
	if ( node !== root ) {
		return null;
	}

	let opacity = 1;
	const filters = [];
	for ( let n = el; n && n !== root; n = n.parentElement ) {
		const cs = getComputedStyle( n );
		if ( 'none' === cs.display || n.hasAttribute( 'data-clip-hidden' ) ) {
			return null;
		}
		opacity *= parseFloat( cs.opacity );
		if ( cs.filter && 'none' !== cs.filter ) {
			filters.push( cs.filter );
		}
	}
	if ( opacity <= 0.001 ) {
		return null;
	}

	let matrix = new DOMMatrix();
	let parent = root;
	const clips = [];
	for ( const n of chain ) {
		const cs = getComputedStyle( n );
		matrix = matrix.translate(
			n.offsetLeft + parent.clientLeft,
			n.offsetTop + parent.clientTop
		);
		if ( cs.transform && 'none' !== cs.transform ) {
			const origin = cs.transformOrigin
				.split( ' ' )
				.map( ( v ) => parseFloat( v ) || 0 );
			matrix = matrix
				.translate( origin[ 0 ], origin[ 1 ] )
				.multiply( new DOMMatrix( cs.transform ) )
				.translate( -origin[ 0 ], -origin[ 1 ] );
		}
		if ( n !== el && 'visible' !== cs.overflow ) {
			clips.push( {
				matrix,
				w: n.offsetWidth,
				h: n.offsetHeight,
				radii: radiiOf( cs, n.offsetWidth, n.offsetHeight ),
			} );
		}
		parent = n;
	}

	return {
		matrix,
		opacity,
		clips,
		filter: filters.join( ' ' ),
		cs: getComputedStyle( el ),
	};
}

/**
 * Source and destination rectangles for object-fit.
 *
 * @param {HTMLImageElement}    img Image.
 * @param {number}              w   Box width.
 * @param {number}              h   Box height.
 * @param {CSSStyleDeclaration} cs  Computed style.
 * @return {number[]} dx, dy, dw, dh.
 */
function fitRect( img, w, h, cs ) {
	const iw = img.naturalWidth;
	const ih = img.naturalHeight;
	const fit = cs.objectFit || 'fill';
	let dw = w;
	let dh = h;

	if ( 'cover' === fit || 'contain' === fit || 'scale-down' === fit ) {
		const scale =
			'cover' === fit
				? Math.max( w / iw, h / ih )
				: Math.min( w / iw, h / ih );
		const s = 'scale-down' === fit ? Math.min( 1, scale ) : scale;
		dw = iw * s;
		dh = ih * s;
	} else if ( 'none' === fit ) {
		dw = iw;
		dh = ih;
	}

	const pos = ( cs.objectPosition || '50% 50%' ).split( ' ' );
	const place = ( value, free ) =>
		String( value ).endsWith( '%' )
			? ( parseFloat( value ) / 100 ) * free
			: parseFloat( value ) || 0;

	return [
		place( pos[ 0 ] || '50%', w - dw ),
		place( pos[ 1 ] || '50%', h - dh ),
		dw,
		dh,
	];
}

/**
 * Paint one media element.
 *
 * @param {CanvasRenderingContext2D} ctx  Context.
 * @param {HTMLImageElement}         img  Image.
 * @param {HTMLElement}              root Scene root.
 */
function paintMedia( ctx, img, root ) {
	if ( ! img.complete || ! img.naturalWidth ) {
		return;
	}
	const geo = geometryOf( img, root );
	if ( ! geo || 'hidden' === geo.cs.visibility ) {
		return;
	}
	const w = img.offsetWidth;
	const h = img.offsetHeight;
	if ( ! w || ! h ) {
		return;
	}

	ctx.save();
	for ( const clip of geo.clips ) {
		ctx.setTransform( clip.matrix );
		boxPath( ctx, clip.w, clip.h, clip.radii );
		ctx.clip();
	}
	ctx.setTransform( geo.matrix );
	boxPath( ctx, w, h, radiiOf( geo.cs, w, h ) );
	ctx.clip();
	ctx.globalAlpha = geo.opacity;
	if ( geo.filter ) {
		// Filters of the image and its parents (e.g. a blur-in).
		ctx.filter = geo.filter;
	}
	const [ dx, dy, dw, dh ] = fitRect( img, w, h, geo.cs );
	ctx.drawImage( img, dx, dy, dw, dh );
	ctx.restore();
}

/**
 * Create a frame capturer for a mounted scene.
 *
 * @param {Object}            options        Options.
 * @param {HTMLElement}       options.scene  Scene root element.
 * @param {string}            options.css    Scene CSS (template + fonts as data URLs).
 * @param {number}            options.width  Width.
 * @param {number}            options.height Height.
 * @param {HTMLCanvasElement} options.canvas Target canvas.
 * @return {Function} Async capture function.
 */
export function createCapturer( { scene, css, width, height, canvas } ) {
	const ctx = canvas.getContext( '2d' );
	const serializer = new XMLSerializer();

	const passCss = ( group, first ) => {
		const visible =
			0 === group
				? '.rsfv-scene > :not([data-z]), .rsfv-scene > [data-z="0"]'
				: `.rsfv-scene > [data-z="${ group }"]`;
		return (
			'.rsfv-scene > * { visibility: hidden; }' +
			`${ visible } { visibility: visible; }` +
			'.rsfv-scene img { visibility: hidden !important; }' +
			'[data-clip-hidden] { visibility: hidden !important; }' +
			( first
				? ''
				: '.rsfv-scene { background: none !important; box-shadow: none !important; }' )
		);
	};

	const rasterise = async ( group, first ) => {
		const html = serializer.serializeToString( scene );
		const svg =
			`<svg xmlns="http://www.w3.org/2000/svg" width="${ width }" height="${ height }">` +
			`<foreignObject x="0" y="0" width="${ width }" height="${ height }">` +
			`<div xmlns="${ XHTML }" style="width:${ width }px;height:${ height }px;position:relative;overflow:hidden">` +
			`<style>${ css }${ passCss(
				group,
				first
			) }</style>${ html }</div>` +
			'</foreignObject></svg>';
		const img = new Image();
		const done = imageLoaded( img );
		img.src =
			'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg );
		await done;
		return img;
	};

	return async function capture() {
		const children = Array.from( scene.children );
		const groups = Array.from(
			new Set( [ 0, ...children.map( groupOf ) ] )
		).sort( ( a, b ) => a - b );

		ctx.setTransform( 1, 0, 0, 1, 0, 0 );
		ctx.clearRect( 0, 0, width, height );

		let first = true;
		for ( const group of groups ) {
			const members = children.filter( ( c ) => groupOf( c ) === group );
			const hasDom = members.some(
				( c ) =>
					'IMG' !== c.tagName &&
					! c.hasAttribute( 'data-clip-hidden' )
			);
			if ( first || hasDom ) {
				const layer = await rasterise( group, first );
				ctx.setTransform( 1, 0, 0, 1, 0, 0 );
				ctx.drawImage( layer, 0, 0, width, height );
			}
			first = false;

			for ( const member of members ) {
				const imgs =
					'IMG' === member.tagName
						? [ member ]
						: Array.from( member.querySelectorAll( 'img' ) );
				imgs.forEach( ( img ) => paintMedia( ctx, img, scene ) );
			}
		}
		ctx.setTransform( 1, 0, 0, 1, 0, 0 );
	};
}

/**
 * Check whether this browser can read back a canvas with an SVG
 * foreignObject drawn on it (Safari can't).
 *
 * @return {Promise<boolean>} True when capture works.
 */
export async function canCapture() {
	try {
		const svg =
			'<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><foreignObject width="4" height="4">' +
			`<div xmlns="${ XHTML }" style="width:4px;height:4px;background:#f00"></div></foreignObject></svg>`;
		const img = new Image();
		const done = imageLoaded( img );
		img.src =
			'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg );
		await done;
		const canvas = document.createElement( 'canvas' );
		canvas.width = canvas.height = 4;
		const ctx = canvas.getContext( '2d' );
		ctx.drawImage( img, 0, 0 );
		const pixel = ctx.getImageData( 2, 2, 1, 1 ).data;
		return pixel[ 0 ] > 200 && pixel[ 3 ] > 200;
	} catch ( e ) {
		return false;
	}
}
