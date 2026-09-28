/**
 * Video Studio editor: pick a template, fill it in, preview and render.
 *
 * Top bar (size, make video), preview with a template strip under it, and
 * an inspector with tabs for the template's fields and add-on panels.
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
	Dropdown,
	ExternalLink,
	Modal,
	Notice,
	ProgressBar,
	RangeControl,
	SelectControl,
	Spinner,
	TabPanel,
	ToggleControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import Sandbox from '../sandbox';
import { autofillValue, buildLoadPayload, initialVars } from '../payload';
import { applyToEditor, uploadRender } from '../upload';
import FieldControl from './FieldControl';
import TemplateStrip from './TemplateStrip';
import { proPresetOptions, proUrl } from '../promo';
import useTemplateThumbs from '../thumbs';

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

// The loop choice is remembered in this browser.
const LOOP_KEY = 'rsfvStudioLoop';

const savedLoop = () => {
	try {
		return '1' === window.localStorage.getItem( LOOP_KEY );
	} catch ( e ) {
		return false;
	}
};

const saveLoop = ( on ) => {
	try {
		window.localStorage.setItem( LOOP_KEY, on ? '1' : '0' );
	} catch ( e ) {
		// Private mode: only for this visit.
	}
};

const StudioModal = ( {
	config,
	onClose,
	onSaved,
	inline = false,
	closeLabel = '',
	postTitle = '',
	links = [],
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
	const [ lastTab, setLastTab ] = useState( 'content' );
	const [ edited, setEdited ] = useState( false );
	const [ loop, setLoop ] = useState( savedLoop );
	const loopRef = useRef( loop );
	const [ pendingTemplate, setPendingTemplate ] = useState( '' );
	const [ seen, setSeen ] = useState( false );
	const headingId = `rsfv-studio-heading-${ useInstanceId( StudioModal ) }`;

	const { thumbs, hostRef: thumbHost } = useTemplateThumbs( {
		config,
		fields,
		presetId,
		enabled: seen,
	} );

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
		sandbox.on( 'ended', () => setPlaying( false ) );
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
				// Thumbnails wait for the first preview.
				setSeen( true );
				// Play once after every change (or keep going with loop on).
				await sandboxRef.current.call( 'play', {
					loop: loopRef.current,
				} );
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

	const switchTemplate = ( id ) => {
		const next = templates.find( ( t ) => t.id === id );
		if ( ! next ) {
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
		setEdited( false );
		setResult( null );
	};

	// Changes are lost on a switch, so ask first when there are any.
	const chooseTemplate = ( id ) => {
		if ( id === templateId ) {
			return;
		}
		if ( edited ) {
			setPendingTemplate( id );
			return;
		}
		switchTemplate( id );
	};

	const setVar = useCallback( ( id, value ) => {
		setValues( ( current ) => ( {
			vars: { ...current.vars, [ id ]: value },
			autofill: { ...current.autofill, [ id ]: false },
		} ) );
		setEdited( true );
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
		setEdited( true );
	};

	const togglePlay = async () => {
		const sandbox = sandboxRef.current;
		if ( playing ) {
			await sandbox.call( 'pause' );
			setPlaying( false );
		} else {
			await sandbox.call( 'play', { loop } );
			setPlaying( true );
		}
	};

	const toggleLoop = () => {
		const next = ! loop;
		setLoop( next );
		loopRef.current = next;
		saveLoop( next );
		sandboxRef.current.call( 'loop', { loop: next } ).catch( () => {} );
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

			// Show the poster frame while it uploads.
			await sandbox.call( 'seek', { time: info.posterTime || 0 } );
			setTime( info.posterTime || 0 );

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
			// Saved with the video, so switching loses nothing now.
			setEdited( false );
			setStatus( 'ready' );
			onSaved( response );
		} catch ( e ) {
			setStatus( 'ready' );
			setTime( 0 );
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
	 * Filter extra inspector tabs (PRO adds music and brand kit).
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

	const tabs = [
		...( contentVars.length
			? [ { name: 'content', title: __( 'Content', 'rsfv' ) } ]
			: [] ),
		...( styleVars.length
			? [ { name: 'style', title: __( 'Style', 'rsfv' ) } ]
			: [] ),
		...panels.map( ( panel ) => ( {
			name: panel.id,
			title: panel.title,
		} ) ),
		...( config.isPro
			? []
			: [
					{
						name: 'pro',
						title: (
							<>
								{ __( 'Music & Brand Kit', 'rsfv' ) }
								<span className="rsfv-pro-tag">
									{ __( 'PRO', 'rsfv' ) }
								</span>
							</>
						),
					},
			  ] ),
	];

	const renderTab = ( tab ) => {
		if ( 'content' === tab.name ) {
			return contentVars.map( renderField );
		}
		if ( 'style' === tab.name ) {
			return styleVars.map( renderField );
		}
		if ( 'pro' === tab.name ) {
			return (
				<div className="rsfv-studio-promo">
					<p>
						<strong>{ __( 'Video Studio PRO', 'rsfv' ) }</strong>
						<span className="rsfv-pro-tag">
							{ __( 'PRO', 'rsfv' ) }
						</span>
					</p>
					<ul>
						<li>
							{ __(
								'Auto generate videos from 20 more templates',
								'rsfv'
							) }
						</li>
						<li>
							{ __(
								'A soundtrack with fade in and out',
								'rsfv'
							) }
						</li>
						<li>
							{ __(
								'A brand kit with your colors, font and logo',
								'rsfv'
							) }
						</li>
						<li>{ __( 'Every Google Font', 'rsfv' ) }</li>
						<li>
							{ __(
								'Vertical, 4:5, 60 fps and 4K sizes',
								'rsfv'
							) }
						</li>
					</ul>
					<ExternalLink
						href={ proUrl( config.upgradeUrl, 'studio-editor' ) }
					>
						{ __( 'Get PRO', 'rsfv' ) }
					</ExternalLink>
				</div>
			);
		}
		const panel = panels.find( ( item ) => item.id === tab.name );
		return panel ? panel.render( panelContext ) : null;
	};

	const percent = Math.round( progress * 100 );
	const ratio = preset ? preset.width / preset.height : 16 / 9;
	const heading = postTitle || fields.title || '';

	const body = (
		<div className="rsfv-studio">
			<header className="rsfv-studio__bar">
				<div className="rsfv-studio__bar-start">
					<Button
						className="rsfv-studio__back"
						icon="arrow-left-alt2"
						onClick={ requestClose }
					>
						{ closeLabel ||
							( result
								? __( 'Done', 'rsfv' )
								: __( 'Close', 'rsfv' ) ) }
					</Button>
					<div className="rsfv-studio__heading">
						<span id={ headingId }>
							{ __( 'Video Studio', 'rsfv' ) }
						</span>
						{ heading && <strong>{ heading }</strong> }
					</div>
					{ links.length > 0 && (
						<div className="rsfv-studio__links">
							{ links.map( ( link ) => (
								<a key={ link.href } href={ link.href }>
									{ link.label }
								</a>
							) ) }
						</div>
					) }
				</div>

				<div className="rsfv-studio__bar-end">
					<SelectControl
						className="rsfv-studio__size"
						label={ __( 'Frame size', 'rsfv' ) }
						labelPosition="side"
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
						<Dropdown
							popoverProps={ { placement: 'bottom-end' } }
							renderToggle={ ( { isOpen, onToggle } ) => (
								<Button
									icon="admin-generic"
									label={ __( 'Video options', 'rsfv' ) }
									onClick={ onToggle }
									aria-expanded={ isOpen }
									__next40pxDefaultSize
								/>
							) }
							renderContent={ () => (
								<div className="rsfv-studio__options">
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
								</div>
							) }
						/>
					) }
					<Button
						variant="primary"
						onClick={ render }
						disabled={ busy || 'ready' !== status || !! blocked }
						isBusy={ busy }
						__next40pxDefaultSize
					>
						{ result
							? __( 'Make again', 'rsfv' )
							: __( 'Make video', 'rsfv' ) }
					</Button>
				</div>
			</header>

			<div className="rsfv-studio__body">
				<main className="rsfv-studio__main">
					<div className="rsfv-studio__canvas">
						<div className="rsfv-studio__notices">
							{ blocked && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
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
								<Notice
									status="success"
									onRemove={ () => setResult( null ) }
								>
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
						</div>

						<div className="rsfv-studio__fit">
							<div
								className="rsfv-studio__frame"
								style={ { '--rsfv-ratio': ratio } }
								ref={ frameRef }
							>
								{ ( 'booting' === status ||
									'loading' === status ) && (
									<div className="rsfv-studio__loading">
										<Spinner />
									</div>
								) }
							</div>
						</div>

						{ busy && (
							<div
								className="rsfv-studio__progress"
								role="status"
							>
								<div className="rsfv-studio__progress-row">
									<strong>
										{ 'rendering' === status
											? __( 'Making the video', 'rsfv' )
											: __( 'Uploading', 'rsfv' ) }
									</strong>
									<span>{ percent }%</span>
								</div>
								<ProgressBar
									className="rsfv-studio__bar-progress"
									value={ percent }
								/>
								<div className="rsfv-studio__progress-row">
									<span className="rsfv-studio-muted">
										{ __(
											'Keep this tab open until it finishes.',
											'rsfv'
										) }
									</span>
									{ 'rendering' === status && (
										<Button
											variant="tertiary"
											size="small"
											onClick={ cancel }
										>
											{ __( 'Cancel', 'rsfv' ) }
										</Button>
									) }
								</div>
							</div>
						) }
					</div>

					<div className="rsfv-studio__transport">
						<Button
							icon={
								playing ? 'controls-pause' : 'controls-play'
							}
							label={
								playing
									? __( 'Pause', 'rsfv' )
									: __( 'Play', 'rsfv' )
							}
							onClick={ togglePlay }
							disabled={ busy || 'ready' !== status }
						/>
						<span className="rsfv-studio__time">
							{ formatTime( time ) }
						</span>
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
							{ formatTime( info.duration ) }
						</span>
						<Button
							className="rsfv-studio__loop"
							icon="controls-repeat"
							label={
								loop
									? __( 'Loop is on', 'rsfv' )
									: __( 'Loop is off', 'rsfv' )
							}
							showTooltip
							isPressed={ loop }
							onClick={ toggleLoop }
						/>
					</div>

					<TemplateStrip
						templates={ templates }
						value={ templateId }
						onChange={ chooseTemplate }
						disabled={ busy }
						isPro={ config.isPro }
						proNeedsUpdate={ !! config.proNeedsUpdate }
						pluginsUrl={ config.pluginsUrl }
						upgradeUrl={ config.upgradeUrl }
						thumbs={ thumbs }
						ratio={ ratio }
					/>
				</main>

				<aside className="rsfv-studio__inspector">
					{ template && (
						<div className="rsfv-studio__template">
							<strong>{ template.title }</strong>
							<span>{ template.description }</span>
						</div>
					) }
					{ tabs.length > 0 && (
						<TabPanel
							key={ templateId }
							className="rsfv-studio__tabs"
							tabs={ tabs }
							initialTabName={
								tabs.find( ( tab ) => tab.name === lastTab )
									? lastTab
									: tabs[ 0 ].name
							}
							onSelect={ setLastTab }
						>
							{ ( tab ) => (
								<div className="rsfv-studio__panel">
									{ renderTab( tab ) }
								</div>
							) }
						</TabPanel>
					) }
				</aside>
			</div>

			{ pendingTemplate && (
				<Modal
					title={ __( 'Switch template?', 'rsfv' ) }
					onRequestClose={ () => setPendingTemplate( '' ) }
					size="small"
					className="rsfv-studio-confirm"
				>
					<p>
						{ sprintf(
							/* translators: %s: template name. */
							__(
								'Your changes to %s will be lost. The new template starts again from this entry.',
								'rsfv'
							),
							template ? template.title : ''
						) }
					</p>
					<div className="rsfv-studio-confirm__actions">
						<Button
							variant="tertiary"
							onClick={ () => setPendingTemplate( '' ) }
						>
							{ __( 'Keep editing', 'rsfv' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => {
								switchTemplate( pendingTemplate );
								setPendingTemplate( '' );
							} }
						>
							{ __( 'Switch template', 'rsfv' ) }
						</Button>
					</div>
				</Modal>
			) }

			<div
				className="rsfv-studio__offstage"
				ref={ thumbHost }
				aria-hidden="true"
			/>
		</div>
	);

	if ( inline ) {
		return <div className="rsfv-studio-inline">{ body }</div>;
	}

	return (
		<Modal
			title={ __( 'Video Studio', 'rsfv' ) }
			__experimentalHideHeader
			aria={ { labelledby: headingId } }
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
