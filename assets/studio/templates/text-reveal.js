/**
 * Video Studio template: Text reveal.
 *
 * A big headline that rises in word by word, an accent bar and a short line
 * under it, over soft drifting shapes.
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
		id: 'text-reveal',

		duration: () => 7,

		posterTime: () => 3.4,

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#101828' );
			const accent = color( v.accent, '#f79009' );
			const text = color( v.text_color, '#ffffff' );
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", sans-serif; }
.tr-blob { border-radius: 50%; background: ${ accent }; opacity: 0.18; }
.tr-blob-1 { width: calc(var(--u) * 70); height: calc(var(--u) * 70); right: calc(var(--u) * -18); top: calc(var(--u) * -22); }
.tr-blob-2 { width: calc(var(--u) * 42); height: calc(var(--u) * 42); left: calc(var(--u) * -12); bottom: calc(var(--u) * -16); opacity: 0.12; }
.tr-content { left: 9%; right: 9%; top: 0; bottom: 0; display: flex; flex-direction: column; justify-content: center; }
.tr-bar { width: calc(var(--u) * 14); height: calc(var(--u) * 1.4); background: ${ accent }; border-radius: calc(var(--u) * 1); transform-origin: 0 50%; margin-bottom: calc(var(--u) * 4); }
.tr-headline { height: 46%; font-weight: 800; line-height: 1.04; letter-spacing: -0.02em; overflow: hidden; display: flex; align-items: flex-end; }
.tr-headline-inner { width: 100%; }
.tr-word { display: inline-block; overflow: hidden; vertical-align: top; padding-bottom: 0.08em; }
.tr-word-inner { display: inline-block; }
.tr-sub { margin-top: calc(var(--u) * 4); font-size: calc(var(--u) * 4.6); font-weight: 600; opacity: 0.85; line-height: 1.3; max-width: 90%; }
.is-portrait .tr-headline { height: 40%; }
.is-portrait .tr-sub { font-size: calc(var(--u) * 5.6); }
`;
		},

		html: ( ctx ) => {
			const words = String( ctx.vars.headline || '' )
				.trim()
				.split( /\s+/ )
				.filter( Boolean )
				.map(
					( word ) =>
						`<span class="tr-word"><span class="tr-word-inner">${ ctx.esc(
							word
						) }</span></span>`
				)
				.join( ' ' );

			return `
<div class="tr-blob tr-blob-1"></div>
<div class="tr-blob tr-blob-2"></div>
<div class="tr-content" data-z="1">
	<div class="tr-bar"></div>
	<div class="tr-headline"><div class="tr-headline-inner">${ words }</div></div>
	${
		ctx.vars.subline
			? `<div class="tr-sub">${ ctx.esc( ctx.vars.subline ) }</div>`
			: ''
	}
</div>`;
		},

		mount: ( ctx ) => {
			const box = ctx.$( '.tr-headline' );
			const inner = ctx.$( '.tr-headline-inner' );
			// Fit on the inner block so the flex box keeps its height.
			let size = ctx.u * 16;
			inner.style.fontSize = `${ size }px`;
			while (
				size > ctx.u * 4 &&
				inner.scrollHeight > box.clientHeight
			) {
				size *= 0.94;
				inner.style.fontSize = `${ size }px`;
			}
		},

		timeline: ( tl, ctx ) => {
			const { stagger } = ctx.anime;
			const words = ctx.$$( '.tr-word-inner' );
			const end = ctx.duration * 1000;

			tl.set( '.tr-blob', { scale: 0.6, opacity: 0 }, 0 )
				.set( '.tr-bar', { scaleX: 0 }, 0 )
				.set( words, { y: '110%' }, 0 )
				.set( '.tr-sub', { opacity: 0, y: ctx.u * 3 }, 0 )
				.add(
					'.tr-blob',
					{
						scale: [ 0.6, 1 ],
						opacity: [ 0, 0.18 ],
						duration: 1600,
						ease: 'outQuad',
						delay: stagger( 250 ),
					},
					0
				)
				.add(
					'.tr-blob-1',
					{
						x: -ctx.u * 8,
						y: ctx.u * 5,
						duration: end,
						ease: 'inOutSine',
					},
					0
				)
				.add(
					'.tr-blob-2',
					{
						x: ctx.u * 6,
						y: -ctx.u * 4,
						duration: end,
						ease: 'inOutSine',
					},
					0
				)
				.add(
					'.tr-bar',
					{ scaleX: [ 0, 1 ], duration: 700, ease: 'inOutQuart' },
					300
				)
				.add(
					words,
					{
						y: [ '110%', '0%' ],
						duration: 900,
						delay: stagger( 90 ),
					},
					550
				)
				.add(
					'.tr-sub',
					{
						opacity: [ 0, 0.85 ],
						y: [ ctx.u * 3, 0 ],
						duration: 900,
					},
					900 + words.length * 90
				)
				.add(
					'.tr-content',
					{
						opacity: [ 1, 0 ],
						y: [ 0, -ctx.u * 2 ],
						duration: 600,
						ease: 'inQuad',
					},
					end - 650
				);
		},
	} );
} )( window.RSFVStudio );
