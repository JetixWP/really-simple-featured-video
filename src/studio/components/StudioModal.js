/**
 * Video Studio editor: pick a template, fill it in, preview and render.
 *
 * @package RSFV
 */

import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters, doAction } from '@wordpress/hooks';
import {
	Button,
	Modal,
	Notice,
	ProgressBar,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import Sandbox from '../sandbox';
import { autofillValue, buildLoadPayload, initialVars } from '../payload';
import { applyToEditor, uploadRender } from '../upload';
import FieldControl from './FieldControl';
import TemplatePicker from './TemplatePicker';
import { proPresetOptions, proUrl } from '../promo';

/**
 * Post fields with the title/excerpt as currently typed in the editor.
 *
 * @param {Object} fields Saved post fields.
 * @return {Object} Fields.
 */
function liveFields( fields ) {
	const live = { ...fields };
	const data = window.wp && window.wp.data;
	if (
		document.body.classList.contains( 'block-editor-page' ) &&
		data &&
		data.select( 'core/editor' )
	) {
		const editor = data.select( 'core/editor' );
		const title = editor.getEditedPostAttribute( 'title' );
		const excerpt = editor.getEditedPostAttribute( 'excerpt' );
		if ( title ) {
			live.title = title;
		}
		if ( excerpt ) {
			live.excerpt = excerpt;
		}
	} else {
		const title = document.getElementById( 'title' );
		if ( title && title.value ) {
			live.title = title.value;
		}
	}
	return live;
}

const formatSize = ( bytes ) => `${ ( bytes / 1048576 ).toFixed( 1 ) } MB`;
const formatTime = ( seconds ) => `${ ( seconds || 0 ).toFixed( 1 ) }s`;

const StudioModal = ( {
	config,
	onClose,
	onSaved,
	inline = false,
	closeLabel = '',
} ) => {
	const { templates, presets, fonts } = config;
	const saved = config.composition;
	const fields = useMemo( () => liveFields( config.fields || {} ), [] );

	const firstTemplate =
		( saved && templates.find( ( t ) => t.id === saved.template ) ) ||
		templates[ 0 ];

	const [ templateId, setTemplateId ] = useState(
		firstTemplate ? firstTemplate.id : ''
	);
	const template = templates.find( ( t ) => t.id === templateId );

	const [ presetId, setPresetId ] = useState( () => {
		if ( saved && presets[ saved.preset ] ) {
			return saved.preset;
		}
		return presets[ config.defaultPreset ]
			? config.defaultPreset
			: Object.keys( presets )[ 0 ];
	} );
	const preset = presets[ presetId ];

	const [ values, setValues ] = useState( () =>
		firstTemplate
			? initialVars(
					firstTemplate,
					fields,
					saved && saved.template === firstTemplate.id
						? saved.vars
						: null
			  )
			: { vars: {}, autofill: {} }
	);
	const [ extras, setExtras ] = useState( ( saved && saved.extras ) || {} );

	const [ booted, setBooted ] = useState( false );
	const [ capability, setCapability ] = useState( null );
	const [ status, setStatus ] = useState( 'booting' );
	const [ error, setError ] = useState( '' );
	const [ info, setInfo ] = useState( { duration: 0, posterTime: 0 } );
	const [ time, setTime ] = useState( 0 );
	const [ playing, setPlaying ] = useState( false );
	const [ progress, setProgress ] = useState( 0 );
	const [ result, setResult ] = useState( null );
	const [ setThumbnail, setSetThumbnail ] = useState( false );

	const frameRef = useRef( null );
	const sandboxRef = useRef( null );
	const loadSeq = useRef( 0 );

	// Start the sandbox once.
	useEffect( () => {
		const scripts = [
			...( config.extensions || [] ),
			...templates.map( ( t ) => t.script ),
		];
		const sandbox = new Sandbox( {
			runtimeUrl: config.runtimeUrl,
			scripts,
			title: __( 'Video preview', 'rsfv' ),
		} );
		sandboxRef.current = sandbox;
		sandbox.on( 'time', ( data ) => setTime( data.time ) );
		sandbox.on( 'progress', ( data ) =>
			setProgress( data.frame / data.frames )
		);

		sandbox
			.mount( frameRef.current )
			.then( () => sandbox.call( 'capability' ) )
			.then( ( caps ) => {
				setCapability( caps );
				setBooted( true );
			} )
			.catch( () => {
				setStatus( 'failed' );
				setError( __( 'Video Studio could not start.', 'rsfv' ) );
			} );

		return () => sandbox.destroy();
	}, [] );

	// Load the scene whenever the template, size or values change.
	useEffect( () => {
		if ( ! booted || ! template || ! preset ) {
			return undefined;
		}
		const seq = ++loadSeq.current;
		const timer = setTimeout( async () => {
			setStatus( 'loading' );
			try {
				const payload = await buildLoadPayload( {
					template,
					vars: values.vars,
					preset,
					fonts,
				} );
				if ( seq !== loadSeq.current ) {
					return;
				}
				const loaded = await sandboxRef.current.call( 'load', payload );
				if ( seq !== loadSeq.current ) {
					return;
				}
				setInfo( loaded );
				setTime( 0 );
				setStatus( 'ready' );
				await sandboxRef.current.call( 'play' );
				setPlaying( true );
			} catch ( e ) {
				if ( seq === loadSeq.current ) {
					setStatus( 'ready' );
					setError( e.message );
				}
			}
		}, 350 );
		return () => clearTimeout( timer );
	}, [ booted, templateId, presetId, JSON.stringify( values.vars ) ] );

	const chooseTemplate = ( id ) => {
		const next = templates.find( ( t ) => t.id === id );
		if ( ! next || id === templateId ) {
			return;
		}
		setTemplateId( id );
		setValues(
			initialVars(
				next,
				fields,
				saved && saved.template === id ? saved.vars : null
			)
		);
		setResult( null );
	};

	const setVar = useCallback( ( id, value ) => {
		setValues( ( current ) => ( {
			vars: { ...current.vars, [ id ]: value },
			autofill: { ...current.autofill, [ id ]: false },
		} ) );
		setResult( null );
	}, [] );

	const refill = ( def ) => {
		const value = autofillValue( def, fields );
		if ( undefined === value ) {
			return;
		}
		setValues( ( current ) => ( {
			vars: { ...current.vars, [ def.id ]: value },
			autofill: { ...current.autofill, [ def.id ]: true },
		} ) );
	};

	const togglePlay = async () => {
		const sandbox = sandboxRef.current;
		if ( playing ) {
			await sandbox.call( 'pause' );
			setPlaying( false );
		} else {
			await sandbox.call( 'play' );
			setPlaying( true );
		}
	};

	const scrub = async ( value ) => {
		setPlaying( false );
		setTime( value );
		await sandboxRef.current.call( 'seek', { time: value } );
	};

	const render = async () => {
		const sandbox = sandboxRef.current;
		setError( '' );
		setResult( null );
		setProgress( 0 );
		setPlaying( false );
		setStatus( 'rendering' );

		try {
			const context = { template, vars: values.vars, preset, extras };

			/**
			 * Filter the largest file the uploader can send, in bytes
			 * (0 for no limit). The video is encoded to fit it.
			 *
			 * @param {number} size Site upload limit.
			 */
			const maxSize = applyFilters(
				'rsfv.studio.maxUploadSize',
				Number( config.maxUploadSize ) || 0,
				context
			);

			/**
			 * Filter settings passed to runtime extensions. Values may be
			 * promises (for example a music file being fetched).
			 *
			 * @param {Object} extensions Settings keyed by extension id.
			 * @param {Object} context    Editor context.
			 */
			const pending = applyFilters(
				'rsfv.studio.renderExtensions',
				{},
				context
			);
			const extensions = {};
			await Promise.all(
				Object.keys( pending || {} ).map( async ( key ) => {
					extensions[ key ] = await pending[ key ];
				} )
			);
			let budget = maxSize;
			let rendered = null;
			// Encoders can overshoot the target a little; try once more
			// with a smaller budget before giving up.
			for ( let attempt = 0; attempt < 2; attempt++ ) {
				rendered = await sandbox.call( 'render', {
					poster: true,
					maxBytes: budget,
					extensions,
				} );
				if ( ! maxSize || rendered.video.byteLength <= maxSize ) {
					break;
				}
				budget = Math.floor(
					( budget * maxSize * 0.9 ) / rendered.video.byteLength
				);
				setProgress( 0 );
			}
			if ( maxSize && rendered.video.byteLength > maxSize ) {
				throw new Error(
					sprintf(
						/* translators: 1: video size, 2: upload limit. */
						__(
							'The video is %1$s, but this site accepts uploads up to %2$s. Try a smaller size.',
							'rsfv'
						),
						formatSize( rendered.video.byteLength ),
						formatSize( maxSize )
					)
				);
			}

			setStatus( 'uploading' );
			setProgress( 0 );

			const composition = applyFilters(
				'rsfv.studio.composition',
				{
					template: template.id,
					template_version: template.version,
					preset: presetId,
					duration: rendered.duration,
					vars: values.vars,
					autofill: values.autofill,
					extras,
				},
				context
			);

			const response = await uploadRender( {
				config,
				postId: config.postId,
				composition,
				result: rendered,
				setThumbnail,
				onProgress: setProgress,
			} );

			applyToEditor( response );
			doAction( 'rsfv.studio.saved', response, context );
			setResult( response );
			setStatus( 'ready' );
			onSaved( response );
		} catch ( e ) {
			setStatus( 'ready' );
			if ( 'cancelled' !== e.message ) {
				setError( e.message );
			}
		}
	};

	const cancel = () => sandboxRef.current.call( 'cancel' );

	const busy = 'rendering' === status || 'uploading' === status;

	const requestClose = () => {
		if (
			busy &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'The video is still being made. Close anyway?', 'rsfv' )
			)
		) {
			return;
		}
		if ( 'rendering' === status ) {
			cancel();
		}
		onClose();
	};

	let blocked = '';
	if ( capability && ! capability.canRender ) {
		if ( ! capability.secure ) {
			blocked = __(
				'Rendering needs a secure (HTTPS) connection to this site. Preview works here.',
				'rsfv'
			);
		} else if ( ! capability.capture || ! capability.webcodecs ) {
			blocked = __(
				'Rendering needs Chrome, Edge or Firefox. Preview works here.',
				'rsfv'
			);
		} else {
			blocked = __(
				'This browser cannot encode video. Try Chrome, Edge or Firefox.',
				'rsfv'
			);
		}
	}

	const panelContext = {
		config,
		template,
		vars: values.vars,
		setVar,
		preset,
		presetId,
		extras,
		setExtras,
		busy,
	};

	/**
	 * Filter extra sidebar panels (PRO adds music and brand kit).
	 *
	 * @param {Array}  panels  Panels: { id, title, render( context ) }.
	 * @param {Object} context Editor context.
	 */
	const panels = applyFilters( 'rsfv.studio.panels', [], panelContext );

	const contentVars = template
		? template.vars.filter( ( def ) => 'style' !== def.group )
		: [];
	const styleVars = template
		? template.vars.filter( ( def ) => 'style' === def.group )
		: [];

	const renderField = ( def ) => {
		const filled = autofillValue( def, fields );
		return (
			<div className="rsfv-studio-field" key={ def.id }>
				<FieldControl
					def={ def }
					value={ values.vars[ def.id ] }
					fonts={ fonts }
					isPro={ !! config.isPro }
					fromPost={ !! values.autofill[ def.id ] }
					canRefill={ undefined !== filled }
					onRefill={ () => refill( def ) }
					onChange={ ( value ) => setVar( def.id, value ) }
				/>
			</div>
		);
	};

	const body = (
		<div className="rsfv-studio">
			<aside className="rsfv-studio__sidebar">
				<section className="rsfv-studio__section">
					<h3>{ __( 'Template', 'rsfv' ) }</h3>
					<TemplatePicker
						templates={ templates }
						value={ templateId }
						onChange={ chooseTemplate }
						disabled={ busy }
						isPro={ config.isPro }
						upgradeUrl={ config.upgradeUrl }
					/>
				</section>

				{ contentVars.length > 0 && (
					<section className="rsfv-studio__section">
						<h3>{ __( 'Content', 'rsfv' ) }</h3>
						{ contentVars.map( renderField ) }
					</section>
				) }

				{ styleVars.length > 0 && (
					<section className="rsfv-studio__section">
						<h3>{ __( 'Style', 'rsfv' ) }</h3>
						{ styleVars.map( renderField ) }
					</section>
				) }

				{ panels.map( ( panel ) => (
					<section className="rsfv-studio__section" key={ panel.id }>
						<h3>{ panel.title }</h3>
						{ panel.render( panelContext ) }
					</section>
				) ) }

				{ ! config.isPro && (
					<section className="rsfv-studio__section">
						<h3>
							{ __( 'Music & brand kit', 'rsfv' ) }
							<a
								className="rsfv-pro-tag"
								href={ proUrl(
									config.upgradeUrl,
									'studio-editor'
								) }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'PRO', 'rsfv' ) }
							</a>
						</h3>
						<div className="rsfv-studio-promo">
							<p>
								{ __(
									'Add a soundtrack with fades, save your colors, font and logo as a brand kit, and use any Google Font.',
									'rsfv'
								) }
							</p>
						</div>
					</section>
				) }

				<section className="rsfv-studio__section">
					<h3>{ __( 'Video', 'rsfv' ) }</h3>
					<SelectControl
						label={ __( 'Size', 'rsfv' ) }
						value={ presetId }
						options={ [
							...Object.keys( presets ).map( ( id ) => ( {
								value: id,
								label: presets[ id ].label,
							} ) ),
							...( config.isPro ? [] : proPresetOptions() ),
						] }
						onChange={ ( id ) => {
							setPresetId( id );
							setResult( null );
						} }
						disabled={ busy }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ config.supportsThumb && (
						<ToggleControl
							label={ __(
								'Also use the poster as featured image',
								'rsfv'
							) }
							checked={ setThumbnail }
							onChange={ setSetThumbnail }
							disabled={ busy }
							__nextHasNoMarginBottom
						/>
					) }
				</section>
			</aside>

			<main className="rsfv-studio__main">
				<div className="rsfv-studio__stage" ref={ frameRef }>
					{ ( 'booting' === status || 'loading' === status ) && (
						<div className="rsfv-studio__loading">
							<Spinner />
						</div>
					) }
				</div>

				<div className="rsfv-studio__controls">
					<Button
						icon={ playing ? 'controls-pause' : 'controls-play' }
						label={
							playing
								? __( 'Pause', 'rsfv' )
								: __( 'Play', 'rsfv' )
						}
						onClick={ togglePlay }
						disabled={ busy || 'ready' !== status }
					/>
					<div className="rsfv-studio__scrub">
						<RangeControl
							label={ __( 'Time', 'rsfv' ) }
							hideLabelFromVision
							value={ Math.min( time, info.duration ) }
							min={ 0 }
							max={ info.duration || 1 }
							step={ 0.01 }
							withInputField={ false }
							showTooltip={ false }
							onChange={ scrub }
							disabled={ busy || 'ready' !== status }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
					<span className="rsfv-studio__time">
						{ formatTime( time ) } / { formatTime( info.duration ) }
					</span>
				</div>

				<div className="rsfv-studio__footer">
					{ blocked && (
						<Notice status="warning" isDismissible={ false }>
							{ blocked }
						</Notice>
					) }
					{ error && (
						<Notice
							status="error"
							onRemove={ () => setError( '' ) }
						>
							{ error }
						</Notice>
					) }
					{ result && (
						<Notice status="success" isDismissible={ false }>
							{ inline
								? __(
										'Saved to the Media Library and set as the featured video.',
										'rsfv'
								  )
								: __(
										'Saved as this post’s featured video. Update the post to keep other changes.',
										'rsfv'
								  ) }
						</Notice>
					) }

					{ busy && (
						<div className="rsfv-studio__progress">
							<ProgressBar
								className="rsfv-studio__bar"
								value={ Math.round( progress * 100 ) }
							/>
							<span>
								{ 'rendering' === status
									? sprintf(
											/* translators: %d: percent. */
											__(
												'Making the video… %d%%',
												'rsfv'
											),
											Math.round( progress * 100 )
									  )
									: sprintf(
											/* translators: %d: percent. */
											__( 'Uploading… %d%%', 'rsfv' ),
											Math.round( progress * 100 )
									  ) }
							</span>
							{ 'rendering' === status && (
								<Button variant="tertiary" onClick={ cancel }>
									{ __( 'Cancel', 'rsfv' ) }
								</Button>
							) }
						</div>
					) }

					<div className="rsfv-studio__actions">
						{ 'rendering' === status && (
							<span className="rsfv-studio-muted">
								{ __(
									'Keep this tab open until it finishes.',
									'rsfv'
								) }
							</span>
						) }
						<Button variant="tertiary" onClick={ requestClose }>
							{ closeLabel ||
								( result
									? __( 'Done', 'rsfv' )
									: __( 'Close', 'rsfv' ) ) }
						</Button>
						<Button
							variant="primary"
							onClick={ render }
							disabled={
								busy || 'ready' !== status || !! blocked
							}
							isBusy={ busy }
						>
							{ result
								? __( 'Make again', 'rsfv' )
								: __( 'Make video', 'rsfv' ) }
						</Button>
					</div>
				</div>
			</main>
		</div>
	);

	if ( inline ) {
		return <div className="rsfv-studio-inline">{ body }</div>;
	}

	return (
		<Modal
			title={ __( 'Video Studio', 'rsfv' ) }
			onRequestClose={ requestClose }
			shouldCloseOnClickOutside={ false }
			isFullScreen
			className="rsfv-studio-modal"
		>
			{ body }
		</Modal>
	);
};

export default StudioModal;
