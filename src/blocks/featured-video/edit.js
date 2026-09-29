/**
 * Featured Video block editor component.
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	ComboboxControl,
	ExternalLink,
	PanelBody,
	Placeholder,
	RadioControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useDebounce } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const data = window.rsfvBlock || { controls: {}, hover: false, settingsUrl: '' };

// Post types that are not entries, so there is no featured video to preview.
const NON_ENTRY_TYPES = [ 'wp_template', 'wp_template_part', 'wp_block', 'wp_navigation' ];

const PROVIDERS = {
	self: __( 'Self-hosted', 'rsfv' ),
	youtube: 'YouTube',
	vimeo: 'Vimeo',
	dailymotion: 'Dailymotion',
	unknown: __( 'Embed', 'rsfv' ),
};

const PLAYER_SETTINGS = [
	{ key: 'controls', label: __( 'Controls', 'rsfv' ) },
	{
		key: 'autoplay',
		label: __( 'Autoplay', 'rsfv' ),
		help: __( 'Browsers only autoplay video that is muted.', 'rsfv' ),
	},
	{ key: 'loop', label: __( 'Loop', 'rsfv' ) },
	{ key: 'mute', label: __( 'Mute sound', 'rsfv' ) },
	{ key: 'pip', label: __( 'Picture in Picture', 'rsfv' ) },
	{
		key: 'download',
		label: __( 'Download', 'rsfv' ),
		help: __( 'Self-hosted videos only.', 'rsfv' ),
		selfOnly: true,
	},
];

/**
 * Options for a setting that can follow the global setting or be forced on or off.
 *
 * @param {boolean} globalOn Whether the global setting is currently on.
 * @return {Array} Select options.
 */
function getTriStateOptions( globalOn ) {
	return [
		{
			value: 'inherit',
			label: globalOn
				? __( 'Use global setting (on)', 'rsfv' )
				: __( 'Use global setting (off)', 'rsfv' ),
		},
		{ value: 'on', label: __( 'On', 'rsfv' ) },
		{ value: 'off', label: __( 'Off', 'rsfv' ) },
	];
}

/**
 * Search box that lists posts that have a featured video.
 *
 * @param {Object}   props            Component props.
 * @param {number}   props.value      Selected post ID.
 * @param {string}   props.valueLabel Title of the selected post.
 * @param {Function} props.onChange   Called with the new post ID.
 * @return {Element} Picker.
 */
function PostPicker( { value, valueLabel, onChange } ) {
	const [ options, setOptions ] = useState( [] );

	const fetchOptions = ( term ) => {
		apiFetch( {
			path: addQueryArgs( '/rsfv/v1/block/search', { search: term } ),
		} )
			.then( ( rows ) => {
				setOptions(
					rows.map( ( row ) => ( {
						value: String( row.id ),
						label: `${ row.title } (${ row.postType }${
							'publish' === row.status ? '' : `, ${ row.status }`
						})`,
					} ) )
				);
			} )
			.catch( () => setOptions( [] ) );
	};

	const debouncedFetch = useDebounce( fetchOptions, 300 );

	useEffect( () => {
		fetchOptions( '' );
		// Load the first page once.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const allOptions = [ ...options ];

	if ( value && ! allOptions.some( ( o ) => o.value === String( value ) ) ) {
		allOptions.unshift( {
			value: String( value ),
			label: valueLabel || `#${ value }`,
		} );
	}

	return (
		<div className="rsfv-block-picker">
			<ComboboxControl
				label={ __( 'Post with a featured video', 'rsfv' ) }
				value={ value ? String( value ) : null }
				options={ allOptions }
				onChange={ ( next ) => onChange( next ? parseInt( next, 10 ) : 0 ) }
				onFilterValueChange={ debouncedFetch }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</div>
	);
}

/**
 * Static stand-in for the player. Nothing plays and nothing is counted in the editor.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.info Preview data from the server.
 * @return {Element} Preview.
 */
function Preview( { info } ) {
	const provider = PROVIDERS[ info.provider ] || PROVIDERS.unknown;

	return (
		<div className="rsfv-block-preview">
			{ 'self' === info.source && info.url && (
				<video
					src={ info.url }
					poster={ info.poster || undefined }
					preload="metadata"
					muted
					playsInline
				/>
			) }
			<span className="rsfv-block-preview__provider">{ provider }</span>
			<span className="rsfv-block-preview__play" aria-hidden="true">
				<svg width="28" height="28" viewBox="0 0 24 24">
					<path d="M8 5v14l11-7z" />
				</svg>
			</span>
			{ info.title && (
				<span className="rsfv-block-preview__label">{ info.title }</span>
			) }
		</div>
	);
}

/**
 * Message shown instead of the preview.
 *
 * @param {Object} props Component props.
 * @return {Element} Placeholder.
 */
function EmptyState( { instructions, children } ) {
	return (
		<Placeholder
			icon="video-alt3"
			label={ __( 'Featured Video', 'rsfv' ) }
			instructions={ instructions }
		>
			{ children }
		</Placeholder>
	);
}

export default function Edit( { attributes, setAttributes, context } ) {
	const { source, postId, fallback, hover } = attributes;
	const blockProps = useBlockProps();

	const editorPostId = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( ! editor || ! editor.getCurrentPostType ) {
			return 0;
		}

		const type = editor.getCurrentPostType();

		if ( ! type || NON_ENTRY_TYPES.includes( type ) ) {
			return 0;
		}

		return editor.getCurrentPostId() || 0;
	}, [] );

	const isSaving = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor && editor.isSavingPost ? editor.isSavingPost() : false;
	}, [] );

	const previewId =
		'post' === source ? postId : context?.postId || editorPostId;

	const [ info, setInfo ] = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ savedTick, setSavedTick ] = useState( 0 );
	const wasSaving = useRef( false );

	// The Featured Video box saves with the post, so look again once saving ends.
	useEffect( () => {
		if ( wasSaving.current && ! isSaving ) {
			setSavedTick( ( tick ) => tick + 1 );
		}
		wasSaving.current = isSaving;
	}, [ isSaving ] );

	useEffect( () => {
		if ( ! previewId ) {
			setInfo( null );
			return undefined;
		}

		let cancelled = false;
		setLoading( true );

		apiFetch( {
			path: addQueryArgs( '/rsfv/v1/block/preview', {
				post_id: previewId,
			} ),
		} )
			.then( ( result ) => {
				if ( ! cancelled ) {
					setInfo( result );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setInfo( { exists: false } );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ previewId, savedTick ] );

	const isPostSource = 'post' === source;
	const picker = isPostSource ? (
		<PostPicker
			value={ postId }
			valueLabel={ info && info.title }
			onChange={ ( next ) => setAttributes( { postId: next } ) }
		/>
	) : null;

	let body;

	if ( ! previewId ) {
		body = (
			<EmptyState
				instructions={
					isPostSource
						? __( 'Pick a post to show its featured video.', 'rsfv' )
						: __(
								'Shows the featured video of the entry this block is placed in. Nothing to preview here, it appears on the site.',
								'rsfv'
						  )
				}
			>
				{ picker }
			</EmptyState>
		);
	} else if ( loading && ! info ) {
		body = (
			<EmptyState>
				<Spinner />
			</EmptyState>
		);
	} else if ( info && ! info.exists ) {
		body = (
			<EmptyState
				instructions={ __(
					'This post can not be found, or you can not see it.',
					'rsfv'
				) }
			>
				{ picker }
			</EmptyState>
		);
	} else if ( info && ! info.typeEnabled ) {
		body = (
			<EmptyState
				instructions={ __(
					'Featured video is not turned on for this type of content.',
					'rsfv'
				) }
			>
				{ data.settingsUrl && (
					<ExternalLink href={ data.settingsUrl }>
						{ __( 'Open Featured Video settings', 'rsfv' ) }
					</ExternalLink>
				) }
				{ picker }
			</EmptyState>
		);
	} else if ( info && ! info.hasVideo ) {
		body = (
			<EmptyState
				instructions={
					isPostSource
						? __( 'The selected post has no featured video.', 'rsfv' )
						: __(
								'This entry has no featured video yet. Add one in the Featured Video box.',
								'rsfv'
						  )
				}
			>
				{ picker }
			</EmptyState>
		);
	} else if ( info ) {
		body = <Preview info={ info } />;
	}

	const provider = info && 'embed' === info.source ? 'embed' : 'self';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Source', 'rsfv' ) }>
					<RadioControl
						label={ __( 'Show the featured video of', 'rsfv' ) }
						selected={ source }
						options={ [
							{
								value: 'current',
								label: __( 'This entry', 'rsfv' ),
							},
							{
								value: 'post',
								label: __( 'Another post', 'rsfv' ),
							},
						] }
						onChange={ ( next ) => setAttributes( { source: next } ) }
					/>
					{ isPostSource && (
						<PostPicker
							value={ postId }
							valueLabel={ info && info.title }
							onChange={ ( next ) =>
								setAttributes( { postId: next } )
							}
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Player', 'rsfv' ) } initialOpen={ false }>
					{ PLAYER_SETTINGS.filter(
						( item ) => ! item.selfOnly || 'embed' !== provider
					).map( ( item ) => (
						<SelectControl
							key={ item.key }
							label={ item.label }
							help={ item.help }
							value={ attributes[ item.key ] }
							options={ getTriStateOptions(
								!! data.controls?.[ item.key ]?.[ provider ]
							) }
							onChange={ ( next ) =>
								setAttributes( { [ item.key ]: next } )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					) ) }
					{ data.settingsUrl && (
						<ExternalLink href={ data.settingsUrl }>
							{ __( 'Global player settings', 'rsfv' ) }
						</ExternalLink>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Autoplay on hover', 'rsfv' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Play when hovered', 'rsfv' ) }
						value={ hover }
						options={ getTriStateOptions( !! data.hover ) }
						onChange={ ( next ) => setAttributes( { hover: next } ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'When there is no video', 'rsfv' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Show', 'rsfv' ) }
						value={ fallback }
						options={ [
							{ value: 'none', label: __( 'Nothing', 'rsfv' ) },
							{
								value: 'image',
								label: __( 'The featured image', 'rsfv' ),
							},
						] }
						onChange={ ( next ) => setAttributes( { fallback: next } ) }
						help={ __(
							'Shown when the post has no featured video.',
							'rsfv'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ body }</div>
		</>
	);
}
