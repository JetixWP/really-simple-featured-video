/**
 * Template strip under the preview: a row of cards with a thumbnail of
 * this entry in each template.
 *
 * @package RSFV
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Dashicon, ExternalLink } from '@wordpress/components';
import { proTemplates, proUrl } from '../promo';

const ICONS = {
	text: 'editor-textcolor',
	images: 'format-gallery',
	product: 'cart',
	brand: 'star-filled',
	blog: 'admin-post',
	food: 'carrot',
	business: 'building',
};

const categoryLabel = ( category ) => {
	const labels = {
		text: __( 'Text', 'rsfv' ),
		images: __( 'Photos', 'rsfv' ),
		product: __( 'Products', 'rsfv' ),
		brand: __( 'Brand', 'rsfv' ),
		blog: __( 'Blog & news', 'rsfv' ),
		food: __( 'Food', 'rsfv' ),
		business: __( 'Business', 'rsfv' ),
	};
	return (
		labels[ category ] ||
		category.charAt( 0 ).toUpperCase() + category.slice( 1 )
	);
};

const TemplateStrip = ( {
	templates,
	value,
	onChange,
	disabled,
	isPro,
	proNeedsUpdate = false,
	pluginsUrl = '',
	upgradeUrl,
	thumbs = {},
	ratio = 16 / 9,
} ) => {
	const [ filter, setFilter ] = useState( 'all' );
	const listRef = useRef( null );

	const promo = isPro ? [] : proTemplates();

	// Free also lists PRO's categories; they show the PRO card only.
	const categories = [
		...new Set(
			[ ...templates, ...promo ]
				.map( ( t ) => t.category )
				.filter( Boolean )
		),
	];
	const shown =
		'all' === filter
			? templates
			: templates.filter( ( t ) => t.category === filter );

	// Keep the chosen template in view.
	useEffect( () => {
		const list = listRef.current;
		const card = list && list.querySelector( '.is-selected' );
		if ( card ) {
			list.scrollLeft = Math.max(
				0,
				card.offsetLeft - ( list.clientWidth - card.offsetWidth ) / 2
			);
		}
	}, [ filter ] );

	// A mouse wheel scrolls the row sideways.
	const onWheel = ( event ) => {
		if ( Math.abs( event.deltaY ) > Math.abs( event.deltaX ) ) {
			listRef.current.scrollLeft += event.deltaY;
		}
	};

	const promoShown =
		'all' === filter
			? promo
			: promo.filter( ( t ) => t.category === filter );
	const promoNames = promoShown.map( ( t ) => t.name );

	return (
		<section className="rsfv-studio-strip">
			<div className="rsfv-studio-strip__head">
				<h3>{ __( 'Templates', 'rsfv' ) }</h3>
				{ categories.length > 1 && (
					<div
						className="rsfv-studio-strip__filters"
						role="group"
						aria-label={ __( 'Show templates', 'rsfv' ) }
					>
						{ [ 'all', ...categories ].map( ( category ) => (
							<button
								type="button"
								key={ category }
								className="rsfv-studio-chip"
								aria-pressed={ filter === category }
								onClick={ () => setFilter( category ) }
							>
								{ 'all' === category
									? sprintf(
											/* translators: %d: number of templates. */
											__( 'All %d', 'rsfv' ),
											templates.length
									  )
									: categoryLabel( category ) }
							</button>
						) ) }
					</div>
				) }
			</div>

			<div
				className="rsfv-studio-strip__list"
				role="radiogroup"
				aria-label={ __( 'Templates', 'rsfv' ) }
				ref={ listRef }
				onWheel={ onWheel }
				style={ { '--rsfv-thumb-ratio': ratio } }
			>
				{ shown.map( ( template ) => (
					<button
						type="button"
						role="radio"
						aria-checked={ value === template.id }
						key={ template.id }
						className={ `rsfv-studio-card${
							value === template.id ? ' is-selected' : ''
						}` }
						onClick={ () => onChange( template.id ) }
						disabled={ disabled }
						title={ template.description }
					>
						<span className="rsfv-studio-card__thumb">
							{ thumbs[ template.id ] ? (
								<img src={ thumbs[ template.id ] } alt="" />
							) : (
								<Dashicon
									icon={
										ICONS[ template.category ] ||
										'video-alt3'
									}
								/>
							) }
						</span>
						<span className="rsfv-studio-card__title">
							{ template.title }
						</span>
					</button>
				) ) }

				{ proNeedsUpdate && 'all' === filter && (
					<div className="rsfv-studio-card rsfv-studio-card--pro">
						<span className="rsfv-studio-card__promo">
							<strong>{ __( 'Update RSFV PRO', 'rsfv' ) }</strong>
						</span>
						<span className="rsfv-studio-card__names">
							{ __(
								'Version 1.40.0 or newer adds its templates, sizes, music and brand kit here.',
								'rsfv'
							) }
						</span>
						{ pluginsUrl && (
							<a href={ pluginsUrl }>
								{ __( 'Go to Plugins', 'rsfv' ) }
							</a>
						) }
					</div>
				) }

				{ promoShown.length > 0 && (
					<div className="rsfv-studio-card rsfv-studio-card--pro">
						<span className="rsfv-studio-card__promo">
							<strong>
								{ sprintf(
									/* translators: %d: number of PRO templates. */
									_n(
										'%d more template',
										'%d more templates',
										promoShown.length,
										'rsfv'
									),
									promoShown.length
								) }
							</strong>
							<span className="rsfv-pro-tag">
								{ __( 'PRO', 'rsfv' ) }
							</span>
						</span>
						<span className="rsfv-studio-card__names">
							{ promoNames.length > 5
								? sprintf(
										/* translators: 1: a few template names, 2: how many more. */
										__( '%1$s and %2$d more', 'rsfv' ),
										promoNames.slice( 0, 4 ).join( ', ' ),
										promoNames.length - 4
								  )
								: promoNames.join( ', ' ) }
						</span>
						<ExternalLink
							href={ proUrl( upgradeUrl, 'studio-templates' ) }
						>
							{ __( 'Get PRO', 'rsfv' ) }
						</ExternalLink>
					</div>
				) }
			</div>
		</section>
	);
};

export default TemplateStrip;
