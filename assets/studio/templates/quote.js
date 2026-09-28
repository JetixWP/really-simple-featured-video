/**
 * Video Studio template: Quote.
 *
 * A quote that fades in word by word under a large quotation mark, followed
 * by the author and their role.
 *
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	const wordsOf = ( ctx ) =>
		String( ctx.vars.quote || '' )
			.trim()
			.split( /\s+/ )
			.filter( Boolean );

	studio.registerTemplate( {
		id: 'quote',

		// Longer quotes get more reading time.
		duration: ( ctx ) =>
			Math.min( 14, Math.max( 6, 3.5 + wordsOf( ctx ).length * 0.16 ) ),

		posterTime: ( ctx ) => Math.max( 2, ctx.duration - 1.2 ),

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#1f1b2e' );
			const accent = color( v.accent, '#a78bfa' );
			const text = color( v.text_color, '#ffffff' );
			const font = v.font || 'Vollkorn';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", serif; }
.q-glow { width: calc(var(--u) * 120); height: calc(var(--u) * 120); border-radius: 50%; left: 50%; top: 50%; margin: calc(var(--u) * -60) 0 0 calc(var(--u) * -60); background: radial-gradient(circle, color-mix(in srgb, ${ accent } 20%, transparent) 0%, transparent 65%); }
.q-mark { left: 8%; top: 6%; font-size: calc(var(--u) * 40); line-height: 1; color: ${ accent }; font-weight: 700; transform-origin: 20% 40%; }
.q-body { left: 12%; right: 12%; top: 22%; height: 50%; display: flex; align-items: center; }
.q-text { width: 100%; font-size: calc(var(--u) * 7); line-height: 1.25; font-weight: 500; }
.q-word { display: inline-block; }
.q-meta { left: 12%; right: 12%; bottom: 10%; display: flex; align-items: center; gap: calc(var(--u) * 3); }
.q-line { width: calc(var(--u) * 10); height: calc(var(--u) * .6); background: ${ accent }; transform-origin: 0 50%; }
.q-author { font-size: calc(var(--u) * 4.4); font-weight: 700; }
.q-role { font-size: calc(var(--u) * 3.4); opacity: .7; margin-top: calc(var(--u) * .6); }
.is-portrait .q-body { top: 20%; height: 56%; left: 10%; right: 10%; }
.is-portrait .q-meta { left: 10%; right: 10%; }
`;
		},

		html: ( ctx ) => {
			const v = ctx.vars;
			const words = wordsOf( ctx )
				.map(
					( word ) =>
						`<span class="q-word">${ ctx.esc( word ) }</span>`
				)
				.join( ' ' );

			return `
<div class="q-glow"></div>
<div class="q-mark" data-z="1">&#8220;</div>
<div class="q-body" data-z="1"><div class="q-text">${ words }</div></div>
<div class="q-meta" data-z="1">
	<div class="q-line"></div>
	<div>
		${ v.author ? `<div class="q-author">${ ctx.esc( v.author ) }</div>` : '' }
		${ v.role ? `<div class="q-role">${ ctx.esc( v.role ) }</div>` : '' }
	</div>
</div>`;
		},

		mount: ( ctx ) => {
			const box = ctx.$( '.q-body' );
			const text = ctx.$( '.q-text' );
			let size = ctx.u * 9;
			text.style.fontSize = `${ size }px`;
			while ( size > ctx.u * 3 && text.scrollHeight > box.clientHeight ) {
				size *= 0.94;
				text.style.fontSize = `${ size }px`;
			}
		},

		timeline: ( tl, ctx ) => {
			const words = ctx.$$( '.q-word' );
			const step = Math.min( 120, 2600 / Math.max( 1, words.length ) );
			const wordsEnd = 700 + words.length * step + 700;

			tl.set( '.q-glow', { scale: 0.5, opacity: 0 }, 0 )
				.set( '.q-mark', { scale: 0.3, opacity: 0, rotate: -12 }, 0 )
				.set(
					words,
					{ opacity: 0, y: ctx.u * 2, filter: 'blur(6px)' },
					0
				)
				.set( '.q-line', { scaleX: 0 }, 0 )
				.set(
					[ '.q-author', '.q-role' ],
					{ opacity: 0, x: -ctx.u * 3 },
					0
				)
				.add(
					'.q-glow',
					{
						scale: [ 0.5, 1.1 ],
						opacity: [ 0, 1 ],
						duration: ctx.duration * 1000,
						ease: 'outSine',
					},
					0
				)
				.add(
					'.q-mark',
					{
						scale: [ 0.3, 1 ],
						opacity: [ 0, 1 ],
						rotate: [ -12, 0 ],
						duration: 1200,
						ease: 'outElastic(1, .6)',
					},
					100
				)
				.add(
					words,
					{
						opacity: [ 0, 1 ],
						y: [ ctx.u * 2, 0 ],
						filter: [ 'blur(6px)', 'blur(0px)' ],
						duration: 700,
						ease: 'outQuad',
						delay: ctx.anime.stagger( step ),
					},
					700
				)
				.add(
					'.q-line',
					{ scaleX: [ 0, 1 ], duration: 600, ease: 'inOutQuart' },
					wordsEnd
				)
				.add(
					[ '.q-author', '.q-role' ],
					{
						opacity: [ 0, 1 ],
						x: [ -ctx.u * 3, 0 ],
						duration: 700,
						delay: ctx.anime.stagger( 150 ),
					},
					wordsEnd + 250
				);
		},
	} );
} )( window.RSFVStudio );
