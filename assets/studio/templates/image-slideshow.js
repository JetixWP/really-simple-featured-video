/**
 * Video Studio template: Image slideshow.
 *
 * Photos with a slow zoom and pan (Ken Burns), crossfading or sliding into
 * each other, with a title and caption on top.
 *
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const TRANSITION = 0.8;

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	const slideLength = ( ctx ) =>
		Math.min(
			8,
			Math.max( 2, parseFloat( ctx.vars.slide_duration ) || 3 )
		);

	const images = ( ctx ) => {
		const list = ctx.media( 'images' );
		return Array.isArray( list ) ? list : [];
	};

	studio.registerTemplate( {
		id: 'image-slideshow',

		duration: ( ctx ) =>
			Math.max( 1, images( ctx ).length ) * slideLength( ctx ) + 0.4,

		posterTime: ( ctx ) => Math.min( 1.6, slideLength( ctx ) * 0.6 ),

		css: ( ctx ) => {
			const v = ctx.vars;
			const text = color( v.text_color, '#ffffff' );
			const accent = color( v.accent, '#f79009' );
			const shade =
				Math.min( 90, Math.max( 0, parseFloat( v.overlay ) || 0 ) ) /
				100;
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: #0b0b0f; color: ${ text }; font-family: "${ font }", sans-serif; }
.ss-slide { left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; }
.ss-slide img { position: absolute; left: 0; top: 0; width: 100%; height: 100%; object-fit: cover; }
.ss-slide .ss-blur { filter: blur(calc(var(--u) * 4)) brightness(0.75); transform: scale(1.2); }
.ss-slide .ss-whole { object-fit: contain; }
.ss-empty { left: 0; top: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #1f2937, #4b5563); }
.ss-shade { left: 0; top: 0; width: 100%; height: 100%; background: linear-gradient(0deg, rgba(0,0,0,${ shade }) 0%, rgba(0,0,0,${
				shade * 0.35
			}) 45%, rgba(0,0,0,0) 70%); }
.ss-text { left: 7%; right: 7%; bottom: 9%; }
.ss-bar { width: calc(var(--u) * 10); height: calc(var(--u) * 1.1); border-radius: calc(var(--u) * 1); background: ${ accent }; transform-origin: 0 50%; margin-bottom: calc(var(--u) * 3); }
.ss-title { font-size: calc(var(--u) * 8); font-weight: 800; line-height: 1.05; letter-spacing: -0.01em; text-shadow: 0 calc(var(--u) * 0.4) calc(var(--u) * 2) rgba(0,0,0,.35); }
.ss-caption { margin-top: calc(var(--u) * 2); font-size: calc(var(--u) * 4); font-weight: 600; opacity: .9; }
.is-portrait .ss-title { font-size: calc(var(--u) * 10); }
.is-portrait .ss-caption { font-size: calc(var(--u) * 5.2); }
`;
		},

		html: ( ctx ) => {
			const list = images( ctx );
			const length = slideLength( ctx );
			const slides = list.length
				? list
						.map( ( url, i ) => {
							const start = Math.max(
								0,
								i * length - TRANSITION
							);
							const last = i === list.length - 1;
							const clip = last
								? ''
								: ` data-duration="${ (
										i * length -
										start +
										length +
										TRANSITION
								  ).toFixed( 3 ) }"`;
							const photo =
								'contain' === ctx.vars.fit
									? `<img class="ss-blur" src="${ url }" alt=""><img class="ss-photo ss-whole" src="${ url }" alt="">`
									: `<img class="ss-photo" src="${ url }" alt="">`;
							return `<div class="ss-slide ss-slide-${ i }" data-start="${ start.toFixed(
								3
							) }"${ clip }>${ photo }</div>`;
						} )
						.join( '' )
				: '<div class="ss-empty"></div>';

			const v = ctx.vars;
			return `
${ slides }
<div class="ss-shade" data-z="1"></div>
<div class="ss-text" data-z="1">
	<div class="ss-bar"></div>
	${ v.title ? `<div class="ss-title">${ ctx.esc( v.title ) }</div>` : '' }
	${ v.caption ? `<div class="ss-caption">${ ctx.esc( v.caption ) }</div>` : '' }
</div>`;
		},

		timeline: ( tl, ctx ) => {
			const length = slideLength( ctx ) * 1000;
			const move = ( ctx.vars.transition || 'fade' ).toString();
			const end = ctx.duration * 1000;
			const slides = ctx.$$( '.ss-slide' );

			slides.forEach( ( slide, i ) => {
				const img = slide.querySelector( '.ss-photo' );
				const start = i * length;
				const zoomIn = 0 === i % 2;
				const drift = ( i % 3 ) - 1;

				// Ken Burns across the whole time the slide is on screen.
				tl.add(
					img,
					{
						scale: zoomIn ? [ 1, 1.14 ] : [ 1.14, 1 ],
						x: [ -drift * ctx.u * 2, drift * ctx.u * 2 ],
						duration: length + TRANSITION * 2000,
						ease: 'linear',
					},
					Math.max( 0, start - TRANSITION * 1000 )
				);

				if ( 0 === i ) {
					return;
				}

				const from = start - TRANSITION * 1000;
				if ( 'slide' === move ) {
					tl.set( slide, { x: '100%' }, 0 ).add(
						slide,
						{
							x: [ '100%', '0%' ],
							duration: TRANSITION * 1000,
							ease: 'inOutQuart',
						},
						from
					);
				} else if ( 'zoom' === move ) {
					tl.set( slide, { opacity: 0, scale: 1.25 }, 0 ).add(
						slide,
						{
							opacity: [ 0, 1 ],
							scale: [ 1.25, 1 ],
							duration: TRANSITION * 1000,
							ease: 'outQuart',
						},
						from
					);
				} else {
					tl.set( slide, { opacity: 0 }, 0 ).add(
						slide,
						{
							opacity: [ 0, 1 ],
							duration: TRANSITION * 1000,
							ease: 'inOutSine',
						},
						from
					);
				}
			} );

			tl.set( '.ss-bar', { scaleX: 0 }, 0 )
				.set(
					[ '.ss-title', '.ss-caption' ],
					{ opacity: 0, y: ctx.u * 4 },
					0
				)
				.add(
					'.ss-bar',
					{ scaleX: [ 0, 1 ], duration: 700, ease: 'inOutQuart' },
					300
				)
				.add(
					[ '.ss-title', '.ss-caption' ],
					{
						opacity: [ 0, 1 ],
						y: [ ctx.u * 4, 0 ],
						duration: 900,
						delay: ctx.anime.stagger( 180 ),
					},
					500
				)
				.add(
					'.ss-text',
					{ opacity: [ 1, 0 ], duration: 500, ease: 'inQuad' },
					end - 550
				);
		},
	} );
} )( window.RSFVStudio );
