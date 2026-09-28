/**
 * Video Studio template: Product card.
 *
 * Product photo in a rounded frame with name, price (and old price when on
 * sale), a badge and a call to action. Fills itself from WooCommerce.
 *
 * @param studio
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	studio.registerTemplate( {
		id: 'product-card',

		duration: () => 8,

		posterTime: () => 4.2,

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#f4ede4' );
			const accent = color( v.accent, '#e04f39' );
			const text = color( v.text_color, '#1d1a17' );
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", sans-serif; }
.pc-circle { border-radius: 50%; background: ${ accent }; opacity: .12; width: calc(var(--u) * 88); height: calc(var(--u) * 88); right: calc(var(--u) * -6); top: 50%; margin-top: calc(var(--u) * -44); }
.pc-frame { background: #fff; border-radius: calc(var(--u) * 4); overflow: hidden; box-shadow: 0 calc(var(--u) * 3) calc(var(--u) * 8) rgba(0,0,0,.18); width: calc(var(--u) * 66); height: calc(var(--u) * 66); right: 8%; top: 50%; margin-top: calc(var(--u) * -33); }
.pc-frame img { position: absolute; left: 0; top: 0; width: 100%; height: 100%; object-fit: cover; }
.pc-frame-empty { position: absolute; inset: 0; background: linear-gradient(135deg, #e5e7eb, #cbd5e1); }
.pc-text { left: 8%; width: 44%; top: 0; bottom: 0; display: flex; flex-direction: column; justify-content: center; }
.pc-kicker { font-size: calc(var(--u) * 3.2); font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: ${ accent }; margin-bottom: calc(var(--u) * 2); }
.pc-title { font-size: calc(var(--u) * 8); font-weight: 800; line-height: 1.05; letter-spacing: -0.02em; }
.pc-prices { display: flex; align-items: baseline; gap: calc(var(--u) * 3); margin-top: calc(var(--u) * 4); }
.pc-price { font-size: calc(var(--u) * 9); font-weight: 800; color: ${ accent }; transform-origin: 0 70%; }
.pc-old { position: relative; font-size: calc(var(--u) * 4.6); font-weight: 600; opacity: .55; }
.pc-strike { position: absolute; left: -4%; top: 52%; height: calc(var(--u) * .5); width: 108%; background: ${ text }; transform-origin: 0 50%; }
.pc-cta { margin-top: calc(var(--u) * 5); align-self: flex-start; background: ${ text }; color: ${ bg }; font-size: calc(var(--u) * 3.6); font-weight: 700; padding: calc(var(--u) * 2.2) calc(var(--u) * 5); border-radius: calc(var(--u) * 10); }
.pc-badge { width: calc(var(--u) * 20); height: calc(var(--u) * 20); border-radius: 50%; background: ${ accent }; color: #fff; display: flex; align-items: center; justify-content: center; text-align: center; font-size: calc(var(--u) * 4.4); font-weight: 800; line-height: 1; right: calc(8% - var(--u) * 10); top: calc(50% - var(--u) * 43); box-shadow: 0 calc(var(--u) * 1.5) calc(var(--u) * 4) rgba(0,0,0,.2); }
.is-portrait .pc-circle { right: auto; left: 50%; top: 4%; margin: 0 0 0 calc(var(--u) * -44); }
.is-portrait .pc-frame { width: calc(var(--u) * 80); height: calc(var(--u) * 80); left: 50%; right: auto; top: 6%; margin: 0 0 0 calc(var(--u) * -40); }
.is-portrait .pc-text { left: 10%; width: 80%; top: 56%; bottom: 4%; justify-content: flex-start; align-items: center; text-align: center; }
.is-portrait .pc-title { font-size: calc(var(--u) * 9.5); }
.is-portrait .pc-cta { align-self: center; font-size: calc(var(--u) * 4.4); }
.is-portrait .pc-price { transform-origin: 50% 70%; font-size: calc(var(--u) * 11); }
.is-portrait .pc-badge { right: calc(50% - var(--u) * 50); left: auto; top: calc(6% + var(--u) * 64); }
.is-square .pc-frame { width: calc(var(--u) * 46); height: calc(var(--u) * 46); margin-top: calc(var(--u) * -23); right: 5%; }
.is-square .pc-circle { width: calc(var(--u) * 70); height: calc(var(--u) * 70); margin-top: calc(var(--u) * -35); }
.is-square .pc-text { width: 38%; left: 6%; }
.is-square .pc-title { font-size: calc(var(--u) * 6.4); }
.is-square .pc-price { font-size: calc(var(--u) * 7.5); }
.is-square .pc-badge { right: calc(5% - var(--u) * 4); top: calc(50% - var(--u) * 30.5); width: calc(var(--u) * 15); height: calc(var(--u) * 15); font-size: calc(var(--u) * 3.4); }
`;
		},

		html: ( ctx ) => {
			const v = ctx.vars;
			const image = ctx.media( 'image' );
			const words = String( v.title || '' )
				.trim()
				.split( /\s+/ )
				.filter( Boolean )
				.map(
					( word ) => `<span class="pc-w">${ ctx.esc( word ) }</span>`
				)
				.join( ' ' );

			return `
<div class="pc-circle"></div>
<div class="pc-frame" data-z="1">${
				image
					? `<img src="${ image }" alt="">`
					: '<div class="pc-frame-empty"></div>'
			}</div>
<div class="pc-text" data-z="2">
	${ v.kicker ? `<div class="pc-kicker">${ ctx.esc( v.kicker ) }</div>` : '' }
	<div class="pc-title">${ words }</div>
	<div class="pc-prices">
		${ v.price ? `<div class="pc-price">${ ctx.esc( v.price ) }</div>` : '' }
		${
			v.old_price
				? `<div class="pc-old">${ ctx.esc(
						v.old_price
				  ) }<div class="pc-strike"></div></div>`
				: ''
		}
	</div>
	${ v.cta ? `<div class="pc-cta">${ ctx.esc( v.cta ) }</div>` : '' }
</div>
${
	v.badge
		? `<div class="pc-badge" data-z="2">${ ctx.esc( v.badge ) }</div>`
		: ''
}`;
		},

		mount: ( ctx ) => {
			// Long names shrink until they fit in a few lines.
			const title = ctx.$( '.pc-title' );
			const lines = 'landscape' === ctx.orientation ? 3 : 4;
			let size = parseFloat( getComputedStyle( title ).fontSize );
			while (
				size > ctx.u * 3.5 &&
				title.offsetHeight > size * 1.05 * lines + size * 0.5
			) {
				size *= 0.94;
				title.style.fontSize = `${ size }px`;
			}
		},

		timeline: ( tl, ctx ) => {
			const { stagger } = ctx.anime;
			const end = ctx.duration * 1000;
			const portrait = 'portrait' === ctx.orientation;
			const words = ctx.$$( '.pc-w' );

			tl.set( '.pc-circle', { scale: 0 }, 0 )
				.set(
					'.pc-frame',
					portrait
						? { y: ctx.u * 30, opacity: 0 }
						: { x: ctx.u * 40, rotate: 6, opacity: 0 },
					0
				)
				.set( '.pc-frame img', { scale: 1.25 }, 0 )
				.set(
					[ '.pc-kicker', ...words ],
					{ opacity: 0, y: ctx.u * 4 },
					0
				)
				.set( '.pc-price', { scale: 0.4, opacity: 0 }, 0 )
				.set( '.pc-old', { opacity: 0 }, 0 )
				.set( '.pc-strike', { scaleX: 0 }, 0 )
				.set( '.pc-cta', { opacity: 0, y: ctx.u * 4 }, 0 )
				.set( '.pc-badge', { scale: 0, rotate: -40 }, 0 )
				.add(
					'.pc-circle',
					{ scale: [ 0, 1 ], duration: 1400, ease: 'outQuart' },
					0
				)
				.add(
					'.pc-frame',
					portrait
						? {
								y: [ ctx.u * 30, 0 ],
								opacity: [ 0, 1 ],
								duration: 1100,
						  }
						: {
								x: [ ctx.u * 40, 0 ],
								rotate: [ 6, 0 ],
								opacity: [ 0, 1 ],
								duration: 1100,
						  },
					200
				)
				.add(
					'.pc-frame img',
					{
						scale: [ 1.25, 1.02 ],
						duration: end - 200,
						ease: 'outSine',
					},
					200
				)
				.add(
					'.pc-kicker',
					{ opacity: [ 0, 1 ], y: [ ctx.u * 4, 0 ], duration: 700 },
					700
				)
				.add(
					words,
					{
						opacity: [ 0, 1 ],
						y: [ ctx.u * 4, 0 ],
						duration: 800,
						delay: stagger( 70 ),
					},
					850
				)
				.add(
					'.pc-price',
					{
						scale: [ 0.4, 1 ],
						opacity: [ 0, 1 ],
						duration: 1100,
						ease: 'outElastic(1, .55)',
					},
					1700
				)
				.add( '.pc-old', { opacity: [ 0, 0.55 ], duration: 500 }, 2000 )
				.add(
					'.pc-strike',
					{ scaleX: [ 0, 1 ], duration: 500, ease: 'inOutQuart' },
					2300
				)
				.add(
					'.pc-badge',
					{
						scale: [ 0, 1 ],
						rotate: [ -40, 0 ],
						duration: 1200,
						ease: 'outElastic(1, .5)',
					},
					2500
				)
				.add(
					'.pc-cta',
					{ opacity: [ 0, 1 ], y: [ ctx.u * 4, 0 ], duration: 700 },
					2900
				)
				.add(
					'.pc-cta',
					{ scale: [ 1, 1.07, 1 ], duration: 900, ease: 'inOutSine' },
					4400
				)
				.add(
					'.pc-cta',
					{ scale: [ 1, 1.07, 1 ], duration: 900, ease: 'inOutSine' },
					5800
				);
		},
	} );
} )( window.RSFVStudio );
