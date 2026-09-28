/**
 * Video Studio template: News headline.
 *
 * The post photo fills the frame with a slow zoom under a dark gradient,
 * then a category tag, the headline (line by line), a short summary and a
 * byline come in.
 *
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	/**
	 * Shorten text at a word break.
	 *
	 * @param {string} raw Text.
	 * @param {number} max Max characters.
	 * @return {string} Text.
	 */
	const clip = ( raw, max ) => {
		const text = String( raw || '' )
			.replace( /\s+/g, ' ' )
			.trim();
		if ( text.length <= max ) {
			return text;
		}
		const cut = text.slice( 0, max );
		const space = cut.lastIndexOf( ' ' );
		return (
			( space > max * 0.6 ? cut.slice( 0, space ) : cut ).replace(
				/[\s.,;:!?-]+$/,
				''
			) + '…'
		);
	};

	studio.registerTemplate( {
		id: 'news-headline',

		duration: () => 8,

		posterTime: () => 5,

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#111111' );
			const accent = color( v.accent, '#e11d48' );
			const text = color( v.text_color, '#ffffff' );
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", sans-serif; }
.nw-photo { left: 0; top: 0; width: 100%; height: 100%; object-fit: cover; transform-origin: 65% 40%; }
.nw-empty { left: 0; top: 0; width: 100%; height: 100%; background: radial-gradient(circle at 75% 25%, color-mix(in srgb, ${ accent } 45%, ${ bg }) 0%, ${ bg } 70%); }
.nw-shade { left: 0; top: 0; width: 100%; height: 100%; }
.nw-text { left: 7%; bottom: 10%; width: 62%; }
.nw-chip { display: inline-block; background: ${ accent }; color: #fff; font-size: calc(var(--u) * 2.6); font-weight: 800; letter-spacing: .12em; text-transform: uppercase; padding: calc(var(--u) * 1) calc(var(--u) * 2); border-radius: calc(var(--u) * .8); margin-bottom: calc(var(--u) * 2.6); transform-origin: 0 50%; }
.nw-title { font-size: calc(var(--u) * 7.2); font-weight: 800; line-height: 1.08; letter-spacing: -0.02em; text-shadow: 0 calc(var(--u) * .3) calc(var(--u) * 2) rgba(0,0,0,.3); }
.nw-line { display: block; overflow: hidden; padding-bottom: .1em; margin-bottom: -.1em; }
.nw-line-in { display: inline-block; white-space: nowrap; }
.nw-excerpt { font-size: calc(var(--u) * 3.1); font-weight: 500; line-height: 1.4; opacity: .85; margin-top: calc(var(--u) * 2.6); max-width: 92%; }
.nw-byline { display: flex; align-items: center; gap: calc(var(--u) * 1.6); margin-top: calc(var(--u) * 3); font-size: calc(var(--u) * 2.7); font-weight: 700; }
.nw-rule { width: calc(var(--u) * 5); height: calc(var(--u) * .5); border-radius: calc(var(--u) * .5); background: ${ accent }; transform-origin: 0 50%; flex-shrink: 0; }
.nw-logo { left: 7%; top: 7%; width: calc(var(--u) * 14); height: calc(var(--u) * 8); object-fit: contain; object-position: 0 50%; }
.is-portrait .nw-text, .is-square .nw-text { width: auto; right: 7%; bottom: 7%; }
.is-portrait .nw-chip { font-size: calc(var(--u) * 3.2); }
.is-portrait .nw-title { font-size: calc(var(--u) * 8.4); }
.is-portrait .nw-excerpt { font-size: calc(var(--u) * 3.8); max-width: none; }
.is-portrait .nw-byline { font-size: calc(var(--u) * 3.4); }
.is-square .nw-text { bottom: 8%; }
.is-square .nw-chip { font-size: calc(var(--u) * 2.8); }
.is-square .nw-title { font-size: calc(var(--u) * 6.8); }
.is-square .nw-excerpt { font-size: calc(var(--u) * 3.3); max-width: none; }
.is-square .nw-byline { font-size: calc(var(--u) * 2.9); }
`;
		},

		html: ( ctx ) => {
			const v = ctx.vars;
			const image = ctx.media( 'image' );
			const logo = ctx.media( 'logo' );
			const words = String( v.title || '' )
				.trim()
				.split( /\s+/ )
				.filter( Boolean )
				.map(
					( word ) => `<span class="nw-w">${ ctx.esc( word ) }</span>`
				)
				.join( ' ' );
			const excerpt = clip(
				v.excerpt,
				'landscape' === ctx.orientation ? 150 : 130
			);
			const byline = [ v.author, v.site_name ]
				.map( ( part ) => String( part || '' ).trim() )
				.filter( Boolean )
				.map( ( part ) => ctx.esc( part ) )
				.join( ' &middot; ' );

			return `
${
	image
		? `<img class="nw-photo" src="${ image }" alt="">`
		: '<div class="nw-empty"></div>'
}
<div class="nw-shade" data-z="1"></div>
<div class="nw-text" data-z="1">
	${ v.category ? `<div class="nw-chip">${ ctx.esc( v.category ) }</div>` : '' }
	<div class="nw-title">${ words }</div>
	${ excerpt ? `<div class="nw-excerpt">${ ctx.esc( excerpt ) }</div>` : '' }
	${
		byline
			? `<div class="nw-byline"><span class="nw-rule"></span><span>${ byline }</span></div>`
			: ''
	}
</div>
${ logo ? `<img class="nw-logo" data-z="2" src="${ logo }" alt="">` : '' }`;
		},

		mount: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#111111' );
			const landscape = 'landscape' === ctx.orientation;
			const box = ctx.$( '.nw-text' );
			const title = ctx.$( '.nw-title' );
			// Leave room for the logo at the top.
			const top = ctx.media( 'logo' ) ? ctx.u * 18 : ctx.u * 7;

			// Long headlines shrink until they fit in a few lines.
			const lines = landscape ? 3 : 4;
			let size = parseFloat( getComputedStyle( title ).fontSize );
			while (
				size > ctx.u * 3.5 &&
				( title.offsetHeight > size * 1.08 * lines + size * 0.5 ||
					box.offsetTop < top )
			) {
				size *= 0.94;
				title.style.fontSize = `${ size }px`;
			}

			// Wrap each line so it can slide up from behind a mask.
			const rows = [];
			let last = null;
			ctx.$$( '.nw-w' ).forEach( ( word ) => {
				if ( null === last || Math.abs( word.offsetTop - last ) > 2 ) {
					rows.push( [] );
					last = word.offsetTop;
				}
				rows[ rows.length - 1 ].push( word.textContent );
			} );
			title.innerHTML = rows
				.map(
					( row ) =>
						`<span class="nw-line"><span class="nw-line-in">${ ctx.esc(
							row.join( ' ' )
						) }</span></span>`
				)
				.join( '' );

			// The gradient reaches just above the text.
			const reach = Math.min(
				100,
				( ( ctx.height - box.offsetTop ) / ctx.height ) * 100 + 12
			);
			const mix = ( amount ) =>
				`color-mix(in srgb, ${ bg } ${ amount }%, transparent)`;
			const bottom = `linear-gradient(0deg, ${ mix( 94 ) } 0%, ${ mix(
				80
			) } ${ ( reach * 0.6 ).toFixed( 1 ) }%, ${ mix( 45 ) } ${ (
				reach * 0.9
			).toFixed( 1 ) }%, transparent ${ Math.min(
				100,
				reach + 20
			).toFixed( 1 ) }%)`;
			ctx.$( '.nw-shade' ).style.background = landscape
				? `${ bottom }, linear-gradient(90deg, ${ mix( 70 ) } 0%, ${ mix(
						30
				  ) } 55%, transparent 80%)`
				: bottom;
		},

		timeline: ( tl, ctx ) => {
			const { stagger } = ctx.anime;
			const end = ctx.duration * 1000;
			const lines = ctx.$$( '.nw-line-in' );
			const after = 650 + lines.length * 140 + 350;

			tl.set( '.nw-photo', { scale: 1.04, x: 0 }, 0 )
				.set( '.nw-shade', { opacity: 0 }, 0 )
				.set( '.nw-chip', { opacity: 0, x: -ctx.u * 3 }, 0 )
				.set( lines, { y: '110%' }, 0 )
				.set(
					[ '.nw-excerpt', '.nw-byline' ],
					{ opacity: 0, y: ctx.u * 3 },
					0
				)
				.set( '.nw-rule', { scaleX: 0 }, 0 )
				.set( '.nw-logo', { opacity: 0 }, 0 )
				.add(
					'.nw-photo',
					{
						scale: [ 1.04, 1.16 ],
						x: [ 0, -ctx.u * 2 ],
						duration: end,
						ease: 'inOutSine',
					},
					0
				)
				.add(
					'.nw-shade',
					{ opacity: [ 0, 1 ], duration: 1200, ease: 'outQuad' },
					0
				)
				.add(
					'.nw-chip',
					{ opacity: [ 0, 1 ], x: [ -ctx.u * 3, 0 ], duration: 700 },
					400
				)
				.add(
					lines,
					{
						y: [ '110%', '0%' ],
						duration: 1000,
						delay: stagger( 140 ),
					},
					650
				)
				.add(
					'.nw-excerpt',
					{ opacity: [ 0, 1 ], y: [ ctx.u * 3, 0 ], duration: 800 },
					after
				)
				.add(
					'.nw-byline',
					{ opacity: [ 0, 1 ], y: [ ctx.u * 3, 0 ], duration: 800 },
					after + 250
				)
				.add(
					'.nw-rule',
					{ scaleX: [ 0, 1 ], duration: 600, ease: 'inOutQuart' },
					after + 400
				)
				.add( '.nw-logo', { opacity: [ 0, 1 ], duration: 600 }, 500 );
		},
	} );
} )( window.RSFVStudio );
