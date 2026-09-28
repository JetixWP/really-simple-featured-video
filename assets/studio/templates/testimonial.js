/**
 * Video Studio template: Testimonial.
 *
 * @package RSFV
 */

( function ( studio ) {
	if ( ! studio ) {
		return;
	}

	const color = ( value, fallback ) =>
		/^#[0-9a-f]{3,8}$/i.test( value || '' ) ? value : fallback;

	const words = ( ctx ) =>
		String( ctx.vars.review || '' )
			.trim()
			.split( /\s+/ )
			.filter( Boolean );

	const STAR =
		'<svg viewBox="0 0 24 24" width="100%" height="100%"><path fill="currentColor" d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z"/></svg>';

	studio.registerTemplate( {
		id: 'testimonial',

		duration: ( ctx ) =>
			Math.min( 14, Math.max( 6, 4 + words( ctx ).length * 0.14 ) ),

		posterTime: ( ctx ) => Math.max( 2, ctx.duration - 1.2 ),

		css: ( ctx ) => {
			const v = ctx.vars;
			const bg = color( v.background, '#fdf2f8' );
			const accent = color( v.accent, '#db2777' );
			const text = color( v.text_color, '#1f2937' );
			const font = v.font || 'Manrope';

			return `
.rsfv-scene { background: ${ bg }; color: ${ text }; font-family: "${ font }", sans-serif; }
.tm-blob { width: calc(var(--u) * 60); height: calc(var(--u) * 60); border-radius: 50%; background: ${ accent }; opacity: .1; left: -8%; bottom: -30%; }
.tm-card { left: 10%; right: 10%; top: 12%; bottom: 12%; background: #fff; border-radius: calc(var(--u) * 4); box-shadow: 0 calc(var(--u) * 2) calc(var(--u) * 8) rgba(0,0,0,.08); }
.tm-inner { left: 16%; right: 16%; top: 18%; bottom: 18%; display: flex; flex-direction: column; justify-content: center; }
.tm-stars { display: flex; gap: calc(var(--u) * 1); color: #f59e0b; margin-bottom: calc(var(--u) * 3); }
.tm-star { width: calc(var(--u) * 5); height: calc(var(--u) * 5); }
.tm-review-box { flex: 1; min-height: 0; display: flex; align-items: center; }
.tm-review { font-size: calc(var(--u) * 6); font-weight: 600; line-height: 1.3; }
.tm-word { display: inline-block; }
.tm-person { display: flex; align-items: center; gap: calc(var(--u) * 3); margin-top: calc(var(--u) * 3); }
.tm-photo { position: relative; width: calc(var(--u) * 11); height: calc(var(--u) * 11); border-radius: 50%; overflow: hidden; background: ${ accent }; flex-shrink: 0; }
.tm-photo img { position: absolute; left: 0; top: 0; width: 100%; height: 100%; object-fit: cover; }
.tm-name { font-size: calc(var(--u) * 3.8); font-weight: 800; }
.tm-detail { font-size: calc(var(--u) * 2.8); opacity: .65; margin-top: calc(var(--u) * .5); }
.tm-detail b { color: ${ accent }; font-weight: 700; }
.tm-logo { right: 12%; top: 15%; width: calc(var(--u) * 12); height: calc(var(--u) * 6); object-fit: contain; }
.is-portrait .tm-card { left: 7%; right: 7%; top: 14%; bottom: 14%; }
.is-portrait .tm-inner { left: 13%; right: 13%; top: 19%; bottom: 19%; }
`;
		},

		html: ( ctx ) => {
			const v = ctx.vars;
			const photo = ctx.media( 'photo' );
			const logo = ctx.media( 'logo' );
			const rating = Math.max(
				0,
				Math.min( 5, parseInt( v.rating, 10 ) || 0 )
			);
			const stars = Array.from( { length: rating } )
				.map( () => `<span class="tm-star">${ STAR }</span>` )
				.join( '' );
			const list = words( ctx );
			const review = list
				.map( ( word, i ) => {
					const open = 0 === i ? '&ldquo;' : '';
					const close = i === list.length - 1 ? '&rdquo;' : '';
					return `<span class="tm-word">${ open }${ ctx.esc(
						word
					) }${ close }</span>`;
				} )
				.join( ' ' );
			const detail = [
				v.detail ? ctx.esc( v.detail ) : '',
				v.product ? `<b>${ ctx.esc( v.product ) }</b>` : '',
			]
				.filter( Boolean )
				.join( ' &middot; ' );

			return `
<div class="tm-blob"></div>
<div class="tm-card" data-z="1"></div>
<div class="tm-inner" data-z="2">
	${ stars ? `<div class="tm-stars">${ stars }</div>` : '' }
	<div class="tm-review-box"><div class="tm-review">${ review }</div></div>
	<div class="tm-person">
		<div class="tm-photo">${ photo ? `<img src="${ photo }" alt="">` : '' }</div>
		<div>
			${ v.name ? `<div class="tm-name">${ ctx.esc( v.name ) }</div>` : '' }
			${ detail ? `<div class="tm-detail">${ detail }</div>` : '' }
		</div>
	</div>
</div>
${ logo ? `<img class="tm-logo" data-z="3" src="${ logo }" alt="">` : '' }`;
		},

		mount: ( ctx ) => {
			const box = ctx.$( '.tm-review-box' );
			const review = ctx.$( '.tm-review' );
			let size = ctx.u * 6;
			while (
				size > ctx.u * 2.4 &&
				review.scrollHeight > box.clientHeight
			) {
				size *= 0.94;
				review.style.fontSize = `${ size }px`;
			}
		},

		timeline: ( tl, ctx ) => {
			const { stagger } = ctx.anime;
			const list = ctx.$$( '.tm-word' );
			const step = Math.min( 90, 2400 / Math.max( 1, list.length ) );
			const after = 1400 + list.length * step;

			tl.set( '.tm-blob', { scale: 0.5, opacity: 0 }, 0 )
				.set( '.tm-card', { y: ctx.u * 8, opacity: 0 }, 0 )
				.set( '.tm-star', { scale: 0, rotate: -60 }, 0 )
				.set( list, { opacity: 0, y: ctx.u * 1.5 }, 0 )
				.set( '.tm-person', { opacity: 0, x: -ctx.u * 4 }, 0 )
				.set( '.tm-logo', { opacity: 0 }, 0 )
				.add(
					'.tm-blob',
					{
						scale: [ 0.5, 1 ],
						opacity: [ 0, 0.1 ],
						duration: 1400,
						ease: 'outQuad',
					},
					0
				)
				.add(
					'.tm-card',
					{ y: [ ctx.u * 8, 0 ], opacity: [ 0, 1 ], duration: 900 },
					100
				)
				.add(
					'.tm-star',
					{
						scale: [ 0, 1 ],
						rotate: [ -60, 0 ],
						duration: 700,
						ease: 'outElastic(1, .5)',
						delay: stagger( 110 ),
					},
					600
				)
				.add(
					list,
					{
						opacity: [ 0, 1 ],
						y: [ ctx.u * 1.5, 0 ],
						duration: 500,
						ease: 'outQuad',
						delay: stagger( step ),
					},
					1200
				)
				.add(
					'.tm-person',
					{ opacity: [ 0, 1 ], x: [ -ctx.u * 4, 0 ], duration: 700 },
					after
				)
				.add(
					'.tm-logo',
					{ opacity: [ 0, 1 ], duration: 600 },
					after + 200
				);
		},
	} );
} )( window.RSFVStudio );
