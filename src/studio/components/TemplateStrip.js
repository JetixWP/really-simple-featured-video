/**
 * Template strip under the preview: a row of cards with a thumbnail of
 * this entry in each template.
 *
 * @package RSFV
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Dashicon, ExternalLink } from '@wordpress/components';
import { proTemplateNames, proUrl } from '../promo';

const ICONS = {
	text: 'editor-textcolor',
	images: 'format-gallery',
	product: 'cart',
	brand: 'star-filled',
};

const categoryLabel = ( category ) => {
	const labels = {
		text: __( 'Text', 'rsfv' ),
		images: __( 'Photos', 'rsfv' ),
		product: __( 'Products', 'rsfv' ),
		brand: __( 'Brand', 'rsfv' ),
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
	upgradeUrl,
	thumbs = {},
	ratio = 16 / 9,
} ) => {
	const [ filter, setFilter ] = useState( 'all' );
	const listRef = useRef( null );

	const categories = [
		...new Set( templates.map( ( t ) => t.category ).filter( Boolean ) ),
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

	const proNames = proTemplateNames();

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

				{ ! isPro && 'all' === filter && (
					<div className="rsfv-studio-card rsfv-studio-card--pro">
						<span className="rsfv-studio-card__promo">
							<strong>
								{ sprintf(
									/* translators: %d: number of PRO templates. */
									__( '%d more templates', 'rsfv' ),
									proNames.length
								) }
							</strong>
							<span className="rsfv-pro-tag">
								{ __( 'PRO', 'rsfv' ) }
							</span>
						</span>
						<span className="rsfv-studio-card__names">
							{ proNames.join( ', ' ) }
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
