/**
 * Video Studio admin page: pick a post or product first, then make its
 * video. The video is saved to the Media Library and set as that entry's
 * featured video.
 *
 * @package RSFV
 */

import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import { addQueryArgs } from '@wordpress/url';
import {
	Button,
	ExternalLink,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import StudioModal from './StudioModal';
import { proUrl } from '../promo';

/**
 * Keep the open post in the URL so a reload comes back to it.
 *
 * @param {number} postId Post ID, or 0 for the list.
 */
function setUrlPost( postId ) {
	const url = new window.URL( window.location.href );
	if ( postId ) {
		url.searchParams.set( 'post_id', String( postId ) );
	} else {
		url.searchParams.delete( 'post_id' );
	}
	window.history.replaceState( null, '', url.toString() );
}

const StudioPage = ( { base } ) => {
	const postTypes = base.postTypes || [];

	const [ postType, setPostType ] = useState(
		( postTypes[ 0 ] || {} ).value || 'post'
	);
	const [ search, setSearch ] = useState( '' );
	const [ withoutVideo, setWithoutVideo ] = useState( false );
	const [ selected, setSelected ] = useState( {} );
	const [ action, setAction ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ list, setList ] = useState( { items: [], pages: 0 } );
	const [ loading, setLoading ] = useState( true );
	const [ refresh, setRefresh ] = useState( 0 );
	const [ error, setError ] = useState( '' );

	const [ editor, setEditor ] = useState( null );
	const [ opening, setOpening ] = useState( 0 );
	const [ saved, setSaved ] = useState( null );

	const typeLabel = postTypes.find( ( t ) => t.value === postType ) || {};

	// Posts of the chosen type.
	useEffect( () => {
		if ( editor ) {
			return undefined;
		}
		let live = true;
		setLoading( true );
		const timer = setTimeout( () => {
			apiFetch( {
				path: addQueryArgs( '/rsfv/v1/posts', {
					post_type: postType,
					search,
					page,
					per_page: 20,
					without_video: withoutVideo ? 1 : 0,
				} ),
				parse: false,
			} )
				.then( async ( response ) => {
					const items = await response.json();
					if ( live ) {
						setList( {
							items: Array.isArray( items ) ? items : [],
							pages:
								parseInt(
									response.headers.get( 'X-WP-TotalPages' ),
									10
								) || 1,
						} );
					}
				} )
				.catch( async ( e ) => {
					let message = e && e.message;
					if ( e && 'function' === typeof e.json ) {
						const body = await e.json().catch( () => null );
						message = body && body.message;
					}
					if ( live ) {
						setError(
							message ||
								__( 'The list could not be loaded.', 'rsfv' )
						);
					}
				} )
				.finally( () => live && setLoading( false ) );
		}, 250 );
		return () => {
			live = false;
			clearTimeout( timer );
		};
	}, [ postType, search, page, withoutVideo, refresh, editor ] );

	const open = async ( postId ) => {
		setOpening( postId );
		setError( '' );
		setSaved( null );
		try {
			const data = await apiFetch( {
				path: `/rsfv/v1/studio/editor/${ postId }`,
			} );
			setEditor( { ...base, ...data } );
			setUrlPost( postId );
		} catch ( e ) {
			setError(
				( e && e.message ) ||
					__( 'That entry could not be opened.', 'rsfv' )
			);
		}
		setOpening( 0 );
	};

	// Opened from a link with ?post_id=.
	useEffect( () => {
		// wp_localize_script sends numbers as strings ("0").
		const postId = parseInt( base.openPost, 10 ) || 0;
		if ( postId > 0 ) {
			open( postId );
		}
	}, [] );

	// The editor fills the screen below the admin bar.
	useEffect( () => {
		document.body.classList.toggle( 'rsfv-studio-editing', !! editor );
	}, [ editor ] );

	const back = () => {
		setEditor( null );
		setUrlPost( 0 );
		setRefresh( ( n ) => n + 1 );
	};

	if ( editor ) {
		const links = [];
		if ( saved && saved.video ) {
			links.push( {
				href: `${ base.mediaUrl }${ saved.video.id }`,
				label: __( 'View in Media Library', 'rsfv' ),
			} );
		}
		if ( editor.editLink ) {
			links.push( {
				href: editor.editLink,
				label: __( 'Edit this entry', 'rsfv' ),
			} );
		}
		return (
			<div className="rsfv-studio-page">
				<StudioModal
					key={ editor.postId }
					config={ editor }
					inline
					closeLabel={ __( 'Go back', 'rsfv' ) }
					postTitle={ editor.postTitle }
					links={ links }
					onClose={ back }
					onSaved={ ( response ) => {
						setSaved( response );
						setEditor( ( current ) => ( {
							...current,
							composition: response.composition,
							isCurrent: true,
							changed: [],
						} ) );
					} }
				/>
			</div>
		);
	}

	/**
	 * Filter bulk actions for selected entries (PRO adds "Make videos").
	 * Checkboxes only show when there is at least one action.
	 *
	 * @param {Array}  actions Actions: { id, label, render( context ) }.
	 * @param {Object} base    Page config.
	 */
	const bulkActions = applyFilters( 'rsfv.studio.bulkActions', [], base );
	const canSelect = bulkActions.length > 0;
	const selectedItems = Object.values( selected );
	const allOnPage =
		list.items.length > 0 &&
		list.items.every( ( item ) => selected[ item.id ] );
	const columns = canSelect ? 4 : 3;

	const toggleItem = ( item, on ) =>
		setSelected( ( current ) => {
			const next = { ...current };
			if ( on ) {
				next[ item.id ] = item;
			} else {
				delete next[ item.id ];
			}
			return next;
		} );

	const togglePage = ( on ) =>
		setSelected( ( current ) => {
			const next = { ...current };
			list.items.forEach( ( item ) => {
				if ( on ) {
					next[ item.id ] = item;
				} else {
					delete next[ item.id ];
				}
			} );
			return next;
		} );

	const current = bulkActions.find( ( a ) => a.id === action );
	const actionContext = {
		base,
		items: selectedItems,
		clear: () => setSelected( {} ),
		close: () => setAction( '' ),
		refresh: () => setRefresh( ( n ) => n + 1 ),
	};

	return (
		<div className="rsfv-studio-page">
			<p className="rsfv-studio-page__intro">
				{ __(
					'Choose the post, page or product the video is for. Video Studio fills the template from it, makes the video in this browser tab, saves it to the Media Library and sets it as that entry’s featured video.',
					'rsfv'
				) }
			</p>

			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }

			<div className="rsfv-studio-page__filters">
				<SelectControl
					label={ __( 'Type', 'rsfv' ) }
					value={ postType }
					options={ postTypes.map( ( t ) => ( {
						value: t.value,
						label: t.label,
					} ) ) }
					onChange={ ( value ) => {
						setPostType( value );
						setPage( 1 );
					} }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ typeLabel.search || __( 'Search', 'rsfv' ) }
					value={ search }
					onChange={ ( value ) => {
						setSearch( value );
						setPage( 1 );
					} }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<ToggleControl
					label={ __( 'Only without a video', 'rsfv' ) }
					checked={ withoutVideo }
					onChange={ ( value ) => {
						setWithoutVideo( value );
						setPage( 1 );
					} }
					__nextHasNoMarginBottom
				/>
			</div>

			{ ! canSelect && ! base.isPro && (
				<p className="rsfv-studio-page__prohint">
					{ __(
						'Tick many entries and make all their videos in one go.',
						'rsfv'
					) }
					<a
						className="rsfv-pro-tag"
						href={ proUrl( base.upgradeUrl, 'studio-bulk' ) }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'PRO', 'rsfv' ) }
					</a>
				</p>
			) }

			{ canSelect && selectedItems.length > 0 && (
				<div className="rsfv-studio-page__bulkbar">
					<strong>
						{ sprintf(
							/* translators: %d: number of selected entries. */
							__( '%d selected', 'rsfv' ),
							selectedItems.length
						) }
					</strong>
					{ bulkActions.map( ( a ) => (
						<Button
							key={ a.id }
							variant={
								action === a.id ? 'primary' : 'secondary'
							}
							size="compact"
							onClick={ () =>
								setAction( action === a.id ? '' : a.id )
							}
						>
							{ a.label }
						</Button>
					) ) }
					<Button
						variant="tertiary"
						size="compact"
						onClick={ () => {
							setSelected( {} );
							setAction( '' );
						} }
					>
						{ __( 'Clear selection', 'rsfv' ) }
					</Button>
				</div>
			) }

			{ current && (
				<div className="rsfv-studio-page__bulkpanel">
					{ current.render( actionContext ) }
				</div>
			) }

			<table className="widefat striped rsfv-studio-page__table">
				<thead>
					<tr>
						{ canSelect && (
							<td className="check-column">
								<input
									type="checkbox"
									checked={ allOnPage }
									onChange={ ( event ) =>
										togglePage( event.target.checked )
									}
									disabled={ ! list.items.length }
									aria-label={ __(
										'Select all on this page',
										'rsfv'
									) }
								/>
							</td>
						) }
						<th>{ __( 'Title', 'rsfv' ) }</th>
						<th>{ __( 'Featured video', 'rsfv' ) }</th>
						<th className="rsfv-studio-page__action-col">
							<span className="screen-reader-text">
								{ __( 'Action', 'rsfv' ) }
							</span>
						</th>
					</tr>
				</thead>
				<tbody>
					{ loading && (
						<tr>
							<td colSpan={ columns }>
								<Spinner />
							</td>
						</tr>
					) }
					{ ! loading && ! list.items.length && (
						<tr>
							<td colSpan={ columns }>
								{ typeLabel.notFound ||
									__( 'Nothing found.', 'rsfv' ) }
							</td>
						</tr>
					) }
					{ ! loading &&
						list.items.map( ( item ) => (
							<tr key={ item.id }>
								{ canSelect && (
									<td className="check-column">
										<input
											type="checkbox"
											checked={ !! selected[ item.id ] }
											onChange={ ( event ) =>
												toggleItem(
													item,
													event.target.checked
												)
											}
											aria-label={ item.title }
										/>
									</td>
								) }
								<td>
									<div className="rsfv-studio-page__title">
										{ item.thumbnail ? (
											<img
												src={ item.thumbnail }
												alt=""
											/>
										) : (
											<span className="rsfv-studio-page__nothumb" />
										) }
										<button
											type="button"
											className="button-link"
											onClick={ () => open( item.id ) }
										>
											{ item.title ||
												__( '(no title)', 'rsfv' ) }
										</button>
									</div>
								</td>
								<td>
									{ item.has_video &&
										'self' === item.video_source && (
											<ExternalLink
												href={ item.video_url }
											>
												{ __( 'Video file', 'rsfv' ) }
											</ExternalLink>
										) }
									{ item.has_video &&
										'self' !== item.video_source &&
										__( 'Embedded video', 'rsfv' ) }
									{ ! item.has_video && '—' }
								</td>
								<td className="rsfv-studio-page__action-col">
									<Button
										variant={
											item.has_video
												? 'secondary'
												: 'primary'
										}
										size="compact"
										onClick={ () => open( item.id ) }
										isBusy={ opening === item.id }
										disabled={ !! opening }
									>
										{ item.has_video
											? __( 'Make a new video', 'rsfv' )
											: __( 'Make a video', 'rsfv' ) }
									</Button>
								</td>
							</tr>
						) ) }
				</tbody>
			</table>

			{ list.pages > 1 && (
				<div className="rsfv-studio-page__pages">
					<Button
						variant="secondary"
						disabled={ page <= 1 }
						onClick={ () => setPage( page - 1 ) }
					>
						{ __( 'Previous', 'rsfv' ) }
					</Button>
					<span>
						{ sprintf(
							/* translators: 1: page, 2: pages. */
							__( 'Page %1$d of %2$d', 'rsfv' ),
							page,
							list.pages
						) }
					</span>
					<Button
						variant="secondary"
						disabled={ page >= list.pages }
						onClick={ () => setPage( page + 1 ) }
					>
						{ __( 'Next', 'rsfv' ) }
					</Button>
				</div>
			) }
		</div>
	);
};

export default StudioPage;
