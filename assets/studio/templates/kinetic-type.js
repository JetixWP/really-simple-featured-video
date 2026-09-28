/**
 * Video Studio template: Kinetic type.
 *
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	studio.registerTemplate( {
		id: 'kinetic-type',

		duration: () => 6,

		posterTime: () => 5,

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#fafafa' );
			const accent = color( v.accent, '#ef4444' );
			const text = color( v.text_color, '#0a0a0a' );
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", sans-serif; }
.kt-flash { left: 0; top: 0; width: 100%; height: 100%; background: ${ accent }; transform-origin: 0 50%; }
.kt-stack { left: 7%; right: 7%; top: 0; bottom: 0; display: flex; flex-direction: column; justify-content: center; }
.kt-line { font-weight: 800; line-height: .92; letter-spacing: -0.045em; white-space: nowrap; }
.kt-1 { font-size: calc(var(--u) * 16); }
.kt-2 { font-size: calc(var(--u) * 30); color: ${ accent }; transform-origin: 0 60%; }
.kt-3 { font-size: calc(var(--u) * 6.4); font-weight: 700; letter-spacing: 0; margin-top: calc(var(--u) * 3); }
.kt-char { display: inline-block; white-space: pre; }
.kt-underline { height: calc(var(--u) * 1.2); background: ${ text }; transform-origin: 0 50%; margin-top: calc(var(--u) * 2); width: 40%; }
`;
		},

		html: ( ctx ) => {
			const v = ctx.vars;
			const chars = Array.from( String( v.line3 || '' ) )
				.map(
					( ch ) => `<span class="kt-char">${ ctx.esc( ch ) }</span>`
				)
				.join( '' );
			return `
<div class="kt-flash"></div>
<div class="kt-stack" data-z="1">
	<div class="kt-line kt-1">${ ctx.esc( v.line1 || '' ) }</div>
	<div class="kt-line kt-2">${ ctx.esc( v.line2 || '' ) }</div>
	<div class="kt-underline"></div>
	<div class="kt-line kt-3">${ chars }</div>
</div>`;
		},

		mount: ( ctx ) => {
			// Shrink lines that are wider than the frame.
			const stack = ctx.$( '.kt-stack' );
			ctx.$$( '.kt-1, .kt-2' ).forEach( ( line ) => {
				let size = parseFloat( getComputedStyle( line ).fontSize );
				while (
					size > ctx.u * 5 &&
					line.scrollWidth > stack.clientWidth
				) {
					size *= 0.94;
					line.style.fontSize = `${ size }px`;
				}
			} );
		},

		// Line 3 types on one letter every 55 ms from 2.7 s. Set from the
		// time on every frame: 1 ms tweens can be skipped when seeking,
		// which left letters hidden.
		update: ( t, ctx ) => {
			const shown = Math.floor( ( t - 2.7 ) / 0.055 ) + 1;
			ctx.$$( '.kt-char' ).forEach( ( el, i ) => {
				el.style.opacity = i < shown ? '1' : '0';
			} );
		},

		timeline: ( tl, ctx ) => {
			const w = ctx.width;

			tl.set( '.kt-flash', { scaleX: 0 }, 0 )
				.set( '.kt-1', { x: -w, skewX: 20 }, 0 )
				.set( '.kt-2', { scale: 4, opacity: 0 }, 0 )
				.set( '.kt-underline', { scaleX: 0 }, 0 )
				.add(
					'.kt-1',
					{
						x: [ -w, 0 ],
						skewX: [ 20, 0 ],
						duration: 650,
						ease: 'outExpo',
					},
					150
				)
				.add(
					'.kt-flash',
					{ scaleX: [ 0, 1 ], duration: 350, ease: 'inQuart' },
					900
				)
				.add(
					'.kt-flash',
					{ x: [ 0, w ], duration: 400, ease: 'outQuart' },
					1250
				)
				.add(
					'.kt-2',
					{
						scale: [ 4, 1 ],
						opacity: [ 0, 1 ],
						duration: 700,
						ease: 'outExpo',
					},
					1300
				)
				.add(
					'.kt-2',
					{ rotate: [ 0, -2, 0 ], duration: 600, ease: 'inOutSine' },
					2000
				)
				.add(
					'.kt-underline',
					{ scaleX: [ 0, 1 ], duration: 500, ease: 'inOutQuart' },
					2300
				)
				.add(
					'.kt-stack',
					{
						scale: [ 1, 1.04 ],
						duration: ctx.duration * 1000 - 3000,
						ease: 'linear',
					},
					3000
				);
		},
	} );
} )( window.RSFVStudio );
