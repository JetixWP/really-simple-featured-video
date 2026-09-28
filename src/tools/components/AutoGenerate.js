/**
 * Auto Generate Video: pick a template, preview it filled from the entry and
 * make the video in one step.
 *
 * @package RSFV
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import {
	Button,
	Modal,
	Notice,
	ProgressBar,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';

const proSizes = [
	{
		value: 'pro-portrait',
		label: __( '9:16, Stories, Reels, Shorts (PRO)', 'rsfv' ),
		disabled: true,
	},
	{ value: 'pro-4k', label: __( '16:9, 4K (PRO)', 'rsfv' ), disabled: true },
];

const AutoGenerate = ( { post, onClose, onDone } ) => {
	const api = window.rsfvStudioApi;
	const config = window.rsfvStudio || {};
	const templates = config.templates || [];
	const presets = config.presets || {};
	const studioUrl = ( window.rsfvTools && window.rsfvTools.studioUrl ) || '';

	const [ fields, setFields ] = useState( null );
	const [ templateId, setTemplateId ] = useState( '' );
	const [ presetId, setPresetId ] = useState(
		presets[ config.defaultPreset ]
			? config.defaultPreset
			: Object.keys( presets )[ 0 ]
	);
	const [ setThumbnail, setSetThumbnail ] = useState( false );
	const [ booted, setBooted ] = useState( false );
	const [ blocked, setBlocked ] = useState( '' );
	const [ stage, setStage ] = useState( 'loading' );
	const [ progress, setProgress ] = useState( 0 );
	const [ error, setError ] = useState( '' );

	const frameRef = useRef( null );
	const sandboxRef = useRef( null );
	const loadedRef = useRef( null );
	const seq = useRef( 0 );

	const template = templates.find( ( t ) => t.id === templateId );
	const preset = presets[ presetId ];
	const busy = 'rendering' === stage || 'uploading' === stage;

	// Entry details, then a sensible first template.
	useEffect( () => {
		apiFetch( { path: `/rsfv/v1/studio/post-fields/${ post.id }` } )
			.then( ( data ) => {
				setFields( data );
				const preferred =
					data && data.price ? 'product-card' : 'text-reveal';
				setTemplateId(
					(
						templates.find( ( t ) => t.id === preferred ) ||
						templates[ 0 ] ||
						{}
					).id || ''
				);
			} )
			.catch( ( e ) => {
				setError( e.message );
				setStage( 'ready' );
			} );
	}, [] );

	// Sandbox for preview and rendering.
	useEffect( () => {
		const sandbox = new api.Sandbox( {
			runtimeUrl: config.runtimeUrl,
			scripts: [
				...( config.extensions || [] ),
				...templates.map( ( t ) => t.script ),
			],
			title: __( 'Video preview', 'rsfv' ),
		} );
		sandboxRef.current = sandbox;
		sandbox.on( 'progress', ( data ) =>
			setProgress( data.frame / data.frames )
		);
		sandbox
			.mount( frameRef.current )
			.then( () => sandbox.call( 'capability' ) )
			.then( ( caps ) => {
				if ( ! caps.canRender ) {
					setBlocked(
						caps.secure
							? __(
									'Making videos needs Chrome, Edge or Firefox. Preview works here.',
									'rsfv'
							  )
							: __(
									'Making videos needs a secure (HTTPS) connection. Preview works here.',
									'rsfv'
							  )
					);
				}
				setBooted( true );
			} )
			.catch( () =>
				setError( __( 'Video Studio could not start.', 'rsfv' ) )
			);
		return () => sandbox.destroy();
	}, [] );

	// Preview the chosen template filled from this entry.
	useEffect( () => {
		if ( ! booted || ! fields || ! template || ! preset ) {
			return;
		}
		const current = ++seq.current;
		setStage( 'loading' );
		( async () => {
			try {
				const { vars, autofill } = api.initialVars(
					template,
					fields,
					null
				);
				const payload = await api.buildLoadPayload( {
					template,
					vars,
					preset,
					fonts: config.fonts,
				} );
				if ( current !== seq.current ) {
					return;
				}
				await sandboxRef.current.call( 'load', payload );
				loadedRef.current = { vars, autofill };
				await sandboxRef.current.call( 'play' );
				setStage( 'ready' );
			} catch ( e ) {
				setError( e.message );
				setStage( 'ready' );
			}
		} )();
	}, [ booted, fields, templateId, presetId ] );

	const generate = async () => {
		const sandbox = sandboxRef.current;
		const { vars, autofill } = loadedRef.current || {};
		if ( ! vars ) {
			return;
		}
		setError( '' );
		setProgress( 0 );
		setStage( 'rendering' );
		try {
			const context = { template, vars, preset, extras: {} };
			const maxSize = applyFilters(
				'rsfv.studio.maxUploadSize',
				Number( config.maxUploadSize ) || 0,
				context
			);
			let budget = maxSize;
			let result = null;
			for ( let attempt = 0; attempt < 2; attempt++ ) {
				result = await sandbox.call( 'render', {
					poster: true,
					maxBytes: budget,
				} );
				if ( ! maxSize || result.video.byteLength <= maxSize ) {
					break;
				}
				budget = Math.floor(
					( budget * maxSize * 0.9 ) / result.video.byteLength
				);
			}
			if ( maxSize && result.video.byteLength > maxSize ) {
				throw new Error(
					__(
						'The video is bigger than this site accepts. Try a smaller size.',
						'rsfv'
					)
				);
			}

			setStage( 'uploading' );
			setProgress( 0 );
			const response = await api.uploadRender( {
				config,
				postId: post.id,
				composition: {
					template: template.id,
					template_version: template.version,
					preset: presetId,
					duration: result.duration,
					vars,
					autofill,
					extras: {},
				},
				result,
				setThumbnail,
				onProgress: setProgress,
			} );
			setStage( 'done' );
			onDone( response );
		} catch ( e ) {
			setStage( 'ready' );
			if ( 'cancelled' !== e.message ) {
				setError( e.message );
			}
		}
	};

	const close = () => {
		if ( busy ) {
			sandboxRef.current.call( 'cancel' ).catch( () => {} );
		}
		onClose();
	};

	return (
		<Modal
			title={ sprintf(
				/* translators: %s: post title. */
				__( 'Auto Generate Video: %s', 'rsfv' ),
				post.title
			) }
			onRequestClose={ close }
			shouldCloseOnClickOutside={ false }
			className="rsfv-autogen"
		>
			<div className="rsfv-autogen__layout">
				<div className="rsfv-autogen__preview" ref={ frameRef }>
					{ 'loading' === stage && (
						<div className="rsfv-autogen__spinner">
							<Spinner />
						</div>
					) }
				</div>

				<div className="rsfv-autogen__controls">
					<SelectControl
						label={ __( 'Template', 'rsfv' ) }
						value={ templateId }
						options={ templates.map( ( t ) => ( {
							value: t.id,
							label: t.title,
						} ) ) }
						onChange={ setTemplateId }
						help={ template ? template.description : '' }
						disabled={ busy || 'done' === stage }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Size', 'rsfv' ) }
						value={ presetId }
						options={ [
							...Object.keys( presets ).map( ( id ) => ( {
								value: id,
								label: presets[ id ].label,
							} ) ),
							...( config.isPro ? [] : proSizes ),
						] }
						onChange={ setPresetId }
						disabled={ busy || 'done' === stage }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<ToggleControl
						label={ __(
							'Also use the poster as featured image',
							'rsfv'
						) }
						checked={ setThumbnail }
						onChange={ setSetThumbnail }
						disabled={ busy || 'done' === stage }
						__nextHasNoMarginBottom
					/>
					<p className="rsfv-autogen__hint">
						{ __(
							'The text, prices and photos come from this entry. To change them first, open it in Video Studio.',
							'rsfv'
						) }
					</p>
				</div>
			</div>

			{ blocked && (
				<Notice status="warning" isDismissible={ false }>
					{ blocked }
				</Notice>
			) }
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ 'done' === stage && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Done. The video is in the Media Library and set as the featured video.',
						'rsfv'
					) }
				</Notice>
			) }
			{ busy && (
				<div className="rsfv-autogen__progress">
					<ProgressBar
						className="rsfv-autogen__bar"
						value={ Math.round( progress * 100 ) }
					/>
					<span>
						{ 'rendering' === stage
							? __( 'Making the video…', 'rsfv' )
							: __( 'Uploading…', 'rsfv' ) }
					</span>
				</div>
			) }

			<div className="rsfv-autogen__actions">
				{ studioUrl && (
					<a
						className="rsfv-autogen__studio"
						href={ `${ studioUrl }&post_id=${ post.id }` }
					>
						{ 'done' === stage
							? __( 'Edit in Video Studio', 'rsfv' )
							: __( 'Customize in Video Studio', 'rsfv' ) }
					</a>
				) }
				<Button variant="tertiary" onClick={ close }>
					{ 'done' === stage
						? __( 'Close', 'rsfv' )
						: __( 'Cancel', 'rsfv' ) }
				</Button>
				{ 'done' !== stage && (
					<Button
						variant="primary"
						onClick={ generate }
						isBusy={ busy }
						disabled={
							busy ||
							'ready' !== stage ||
							!! blocked ||
							! loadedRef.current
						}
					>
						{ __( 'Generate video', 'rsfv' ) }
					</Button>
				) }
			</div>
		</Modal>
	);
};

export default AutoGenerate;
