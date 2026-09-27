/**
 * Analytics report for Video Tools.
 *
 * @package RSFV
 */

import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '../hooks';

/**
 * Format a count for display.
 *
 * @param {number} value Count.
 * @return {string}
 */
const formatCount = ( value ) => Number( value || 0 ).toLocaleString();

/**
 * Short date label, parsed as a local calendar day.
 *
 * @param {string} date Y-m-d.
 * @return {string}
 */
const formatDay = ( date ) => {
	const parsed = new Date( `${ date }T00:00:00` );

	if ( Number.isNaN( parsed.getTime() ) ) {
		return date;
	}

	return parsed.toLocaleDateString( undefined, { month: 'short', day: 'numeric' } );
};

/**
 * Line chart for views and plays.
 *
 * @param {Object}   props        Props.
 * @param {Array}    props.series Daily points.
 * @return {JSX.Element}
 */
const TrendChart = ( { series } ) => {
	const width = 640;
	const height = 180;
	const pad = 16;
	const max = Math.max(
		1,
		...series.map( ( point ) => Math.max( point.views, point.plays ) )
	);

	const xFor = ( index ) => {
		if ( series.length < 2 ) {
			return pad;
		}

		return pad + ( index / ( series.length - 1 ) ) * ( width - pad * 2 );
	};

	const yFor = ( value ) => height - pad - ( value / max ) * ( height - pad * 2 );

	const pathFor = ( key ) => series
		.map( ( point, index ) => `${ index === 0 ? 'M' : 'L' } ${ xFor( index ) } ${ yFor( point[ key ] ) }` )
		.join( ' ' );

	const tickStep = Math.max( 1, Math.ceil( series.length / 6 ) );

	return (
		<figure className="rsfv-analytics-chart">
			<svg viewBox={ `0 0 ${ width } ${ height }` } role="img" aria-label={ __( 'Views and plays over time', 'rsfv' ) }>
				<line x1={ pad } y1={ height - pad } x2={ width - pad } y2={ height - pad } className="rsfv-analytics-axis" />
				<path d={ pathFor( 'views' ) } className="rsfv-analytics-line rsfv-analytics-line-views" />
				<path d={ pathFor( 'plays' ) } className="rsfv-analytics-line rsfv-analytics-line-plays" />
			</svg>
			<div className="rsfv-analytics-ticks">
				{ series.map( ( point, index ) => (
					index % tickStep === 0 ? <span key={ point.date }>{ formatDay( point.date ) }</span> : null
				) ) }
			</div>
			<ul className="rsfv-analytics-legend">
				<li><span className="rsfv-analytics-swatch rsfv-analytics-swatch-views" />{ __( 'Views', 'rsfv' ) }</li>
				<li><span className="rsfv-analytics-swatch rsfv-analytics-swatch-plays" />{ __( 'Plays', 'rsfv' ) }</li>
			</ul>
		</figure>
	);
};

/**
 * Horizontal bars for one breakdown.
 *
 * @param {Object} props       Props.
 * @param {string} props.title Heading.
 * @param {Array}  props.rows  Rows with label and views.
 * @return {JSX.Element}
 */
const Breakdown = ( { title, rows } ) => {
	const max = Math.max( 1, ...rows.map( ( row ) => row.views ) );

	return (
		<section className="rsfv-analytics-breakdown">
			<h3>{ title }</h3>
			{ rows.length === 0 && <p className="rsfv-analytics-muted">{ __( 'Nothing in this period yet.', 'rsfv' ) }</p> }
			<ul>
				{ rows.map( ( row ) => (
					<li key={ row.key }>
						<span className="rsfv-analytics-bar-label">{ row.label }</span>
						<span className="rsfv-analytics-bar-track">
							<span className="rsfv-analytics-bar-fill" style={ { width: `${ ( row.views / max ) * 100 }%` } } />
						</span>
						<span className="rsfv-analytics-bar-value">{ formatCount( row.views ) }</span>
					</li>
				) ) }
			</ul>
		</section>
	);
};

/**
 * Analytics tab.
 *
 * @return {JSX.Element}
 */
const AnalyticsReport = () => {
	const [ report, setReport ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ range, setRange ] = useState( { from: '', to: '' } );
	const [ compare, setCompare ] = useState( null );
	const [ hookTick, setHookTick ] = useState( 0 );

	useEffect( () => {
		const bump = () => setHookTick( ( value ) => value + 1 );

		window.addEventListener( 'rsfv-tools-hooks-ready', bump );

		return () => window.removeEventListener( 'rsfv-tools-hooks-ready', bump );
	}, [] );

	const load = useCallback( ( nextRange ) => {
		let path = '/rsfv/v1/analytics/report';

		if ( nextRange && nextRange.from && nextRange.to ) {
			path += `?from=${ encodeURIComponent( nextRange.from ) }&to=${ encodeURIComponent( nextRange.to ) }`;
		}

		apiFetch( { path } )
			.then( ( data ) => {
				setReport( data );
				setError( '' );
			} )
			.catch( () => {
				setError( __( 'The report could not be loaded.', 'rsfv' ) );
			} );
	}, [] );

	useEffect( () => {
		load( range );
	}, [ range, load ] );

	if ( error ) {
		return <p className="rsfv-analytics-error">{ error }</p>;
	}

	if ( ! report ) {
		return (
			<div className="rsfv-loading">
				<span className="spinner is-active" />
				<span>{ __( 'Loading report…', 'rsfv' ) }</span>
			</div>
		);
	}

	const summary = report.summary || {};
	const days = Number.isFinite( Number( report.retentionDays ) ) ? Number( report.retentionDays ) : 14;
	const period = 0 === days
		? __( 'All time', 'rsfv' )
		: sprintf(
			/* translators: %d: number of days. */
			__( 'Last %d days', 'rsfv' ),
			days
		);

	const baseCards = [
		{ key: 'videos', label: __( 'Videos tracked', 'rsfv' ), value: formatCount( summary.videos ) },
		{ key: 'views', label: __( 'Views', 'rsfv' ), value: formatCount( summary.views ) },
		{ key: 'plays', label: __( 'Plays', 'rsfv' ), value: formatCount( summary.plays ) },
		{ key: 'playRate', label: __( 'Play rate', 'rsfv' ), value: `${ summary.playRate || 0 }%` },
	];
	const cards = applyFilters( 'rsfv_analytics_cards', baseCards, { report, compare } );
	const rangeControl = applyFilters( 'rsfv_analytics_range_control', null, { report, setRange, setCompare } );
	const columns = applyFilters( 'rsfv_analytics_table_columns', [
		{ key: 'title', label: __( 'Video', 'rsfv' ) },
		{ key: 'kind', label: __( 'Kind', 'rsfv' ) },
		{ key: 'provider', label: __( 'Source', 'rsfv' ) },
		{ key: 'views', label: __( 'Views', 'rsfv' ) },
		{ key: 'plays', label: __( 'Plays', 'rsfv' ) },
		{ key: 'playRate', label: __( 'Play rate', 'rsfv' ) },
	], report );
	const proUrl = window.rsfvTools?.upgradeUrl || '#';
	const showPromo = ! report.isPro;
	const promoColumns = showPromo ? [
		{ key: 'promo-watch', label: __( 'Watch time', 'rsfv' ), promo: true },
		{ key: 'promo-completion', label: __( 'Completion', 'rsfv' ), promo: true },
	] : [];
	const tableColumns = columns.concat( promoColumns );

	void hookTick;

	const proBadge = (
		<a className="rsfv-analytics-pro-tag" href={ proUrl } target="_blank" rel="noopener noreferrer">
			{ __( 'Pro', 'rsfv' ) }
		</a>
	);

	return (
		<div className="rsfv-analytics">
			<header className="rsfv-analytics-header">
				<div className="rsfv-analytics-heading-row">
					<h2>{ __( 'Video Analytics', 'rsfv' ) }</h2>
					{ rangeControl }
					{ showPromo && (
						<div className="rsfv-analytics-range is-promo">
							{ proBadge }
							<select disabled aria-label={ __( 'Date range', 'rsfv' ) }>
								<option>{ __( 'Range', 'rsfv' ) }</option>
							</select>
							<input type="date" disabled aria-label={ __( 'From', 'rsfv' ) } />
							<input type="date" disabled aria-label={ __( 'To', 'rsfv' ) } />
							<label>
								<input type="checkbox" disabled />
								{ ' ' }
								{ __( 'Compare', 'rsfv' ) }
							</label>
							<button type="button" className="button" disabled>{ __( 'Export CSV', 'rsfv' ) }</button>
						</div>
					) }
				</div>
					<p className="rsfv-analytics-muted">{ __( 'A view is a player actually on screen. A play is someone starting the video. Hover preview does not count.', 'rsfv' ) }</p>
					{ ! report.isPro && (
						<p className="rsfv-analytics-muted">
							{ __( 'Recording only the last 14 days of history.', 'rsfv' ) }{ ' ' }
							<a href={ report.settingsUrl }>{ __( 'Analytics settings', 'rsfv' ) }</a>
						</p>
					) }
					{ report.isPro && ! rangeControl && (
						<p className="rsfv-analytics-muted">{ period }</p>
					) }
					{ ! report.enabled && (
						<p className="rsfv-analytics-notice">
							{ __( 'Analytics is turned off. New visits are not being counted.', 'rsfv' ) }{ ' ' }
							<a href={ report.settingsUrl }>{ __( 'Turn it on', 'rsfv' ) }</a>
						</p>
					) }
			</header>

			<ul className="rsfv-analytics-cards">
				{ cards.map( ( card ) => (
					<li key={ card.key || card.label }>
						<span className="rsfv-analytics-card-value">{ card.value }</span>
						<span className="rsfv-analytics-card-label">{ card.label }</span>
					</li>
				) ) }
				{ showPromo && (
					<>
						<li className="is-promo">
							<span className="rsfv-analytics-card-value">—</span>
							<span className="rsfv-analytics-card-label">{ __( 'Average watch', 'rsfv' ) } { proBadge }</span>
						</li>
						<li className="is-promo">
							<span className="rsfv-analytics-card-value">—</span>
							<span className="rsfv-analytics-card-label">{ __( 'Completion', 'rsfv' ) } { proBadge }</span>
						</li>
					</>
				) }
			</ul>

			<TrendChart series={ report.series || [] } />

			<div className="rsfv-analytics-splits">
				<Breakdown title={ __( 'Where videos were shown', 'rsfv' ) } rows={ report.surfaces || [] } />
				<Breakdown title={ __( 'Video source', 'rsfv' ) } rows={ report.providers || [] } />
			</div>

			<section className="rsfv-analytics-table-wrap">
				<h3>{ __( 'Videos', 'rsfv' ) }</h3>
				<table className="rsfv-analytics-table">
					<thead>
						<tr>
							{ tableColumns.map( ( column ) => (
								<th key={ column.key }>
									{ column.label }
									{ column.promo && <> { proBadge }</> }
								</th>
							) ) }
						</tr>
					</thead>
					<tbody>
						{ ( report.videos || [] ).length === 0 && (
							<tr>
								<td colSpan={ tableColumns.length }>{ __( 'No videos yet. Add a featured or sticky video and this list will fill in.', 'rsfv' ) }</td>
							</tr>
						) }
						{ ( report.videos || [] ).map( ( video ) => (
							<tr key={ video.id } className={ video.status === 'inactive' ? 'is-inactive' : '' }>
								{ tableColumns.map( ( column ) => (
									<td key={ column.key } className={ column.promo ? 'is-promo' : undefined }>
										{ column.promo && '—' }
										{ ! column.promo && column.key === 'title' && (
											<>
												{ video.editUrl ? <a href={ video.editUrl }>{ video.title }</a> : video.title }
												{ video.status === 'inactive' && <span className="rsfv-analytics-pill">{ __( 'Removed', 'rsfv' ) }</span> }
											</>
										) }
										{ ! column.promo && column.key === 'views' && formatCount( video.views ) }
										{ ! column.promo && column.key === 'plays' && formatCount( video.plays ) }
										{ ! column.promo && column.key === 'playRate' && `${ video.playRate || 0 }%` }
										{ ! column.promo && column.key !== 'title' && column.key !== 'views' && column.key !== 'plays' && column.key !== 'playRate' && (
											column.render ? column.render( video ) : video[ column.key ]
										) }
									</td>
								) ) }
							</tr>
						) ) }
					</tbody>
				</table>
			</section>

			{ showPromo && (
				<>
					<section className="rsfv-analytics-funnel is-promo">
						<h3>{ __( 'How far people watched', 'rsfv' ) } { proBadge }</h3>
						<p className="rsfv-analytics-muted">{ __( 'Share of plays that reached each point.', 'rsfv' ) }</p>
						<ol>
							{ [ __( 'Played', 'rsfv' ), __( 'Reached 25%', 'rsfv' ), __( 'Reached 50%', 'rsfv' ), __( 'Reached 75%', 'rsfv' ), __( 'Completed', 'rsfv' ) ].map( ( label ) => (
								<li key={ label }>
									<span className="rsfv-analytics-funnel-label">{ label }</span>
									<span className="rsfv-analytics-funnel-track"><span style={ { width: '0%' } } /></span>
									<span className="rsfv-analytics-funnel-stat"><strong>—</strong></span>
								</li>
							) ) }
						</ol>
					</section>
					<div className="rsfv-analytics-splits rsfv-analytics-promo-splits">
						{ [ __( 'Countries', 'rsfv' ), __( 'Devices', 'rsfv' ), __( 'Referring sites', 'rsfv' ) ].map( ( title ) => (
							<section className="rsfv-analytics-breakdown is-promo" key={ title }>
								<h3>{ title } { proBadge }</h3>
								<p className="rsfv-analytics-muted">{ __( 'Available in Pro.', 'rsfv' ) }</p>
							</section>
						) ) }
					</div>
				</>
			) }

			{ ( report.sections || [] ).map( ( section, index ) => {
				if ( ! section || ! section.title ) {
					return null;
				}

				if ( section.type === 'funnel' ) {
					const steps = Array.isArray( section.rows ) ? section.rows : [];

					return (
						<section className="rsfv-analytics-funnel" key={ `${ section.title }-${ index }` }>
							<h3>{ section.title }</h3>
							{ section.text ? <p className="rsfv-analytics-muted">{ section.text }</p> : null }
							{ steps.length === 0 && <p className="rsfv-analytics-muted">{ __( 'Nothing in this period yet.', 'rsfv' ) }</p> }
							<ol>
								{ steps.map( ( step ) => (
									<li key={ step.label }>
										<span className="rsfv-analytics-funnel-label">{ step.label }</span>
										<span className="rsfv-analytics-funnel-track">
											<span style={ { width: `${ Math.max( 0, Math.min( 100, Number( step.share ) || 0 ) ) }%` } } />
										</span>
										<span className="rsfv-analytics-funnel-stat">
											<strong>{ formatCount( step.count ) }</strong>
											<span>{ `${ Number( step.share ) || 0 }%` }</span>
										</span>
									</li>
								) ) }
							</ol>
						</section>
					);
				}

				return (
					<section className="rsfv-analytics-breakdown" key={ `${ section.title }-${ index }` }>
						<h3>{ section.title }</h3>
						{ section.text ? <p>{ section.text }</p> : null }
						{ Array.isArray( section.rows ) && section.rows.length > 0 && (
							<ul>
								{ section.rows.map( ( row ) => (
									<li key={ row.label }>
										<span className="rsfv-analytics-bar-label">{ row.label }</span>
										<span className="rsfv-analytics-bar-value">{ row.value }</span>
									</li>
								) ) }
							</ul>
						) }
					</section>
				);
			} ) }
		</div>
	);
};

export default AnalyticsReport;
