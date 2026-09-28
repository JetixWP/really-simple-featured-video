/**
 * Template thumbnails: this entry drawn in each template, made one by one
 * in a second, hidden sandbox so the preview keeps playing.
 *
 * @package RSFV
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Sandbox from './sandbox';
import { buildLoadPayload, initialVars } from './payload';

// Thumbnails stay for this page view, so reopening the editor is instant.
const cache = new Map();

const THUMB_WIDTH = 320;

const even = ( value ) => Math.max( 2, Math.round( value / 2 ) * 2 );

/**
 * Thumbnails for every template at the chosen size.
 *
 * @param {Object}  options          Options.
 * @param {Object}  options.config   Editor config.
 * @param {Object}  options.fields   Post fields.
 * @param {string}  options.presetId Size.
 * @param {boolean} options.enabled  Start making them.
 * @return {Object} { thumbs: { templateId: url }, hostRef }.
 */
export default function useTemplateThumbs( {
	config,
	fields,
	presetId,
	enabled,
} ) {
	const [ thumbs, setThumbs ] = useState( {} );
	const hostRef = useRef( null );
	const sandboxRef = useRef( null );
	const queue = useRef( Promise.resolve() );

	useEffect(
		() => () => {
			if ( sandboxRef.current ) {
				sandboxRef.current.destroy();
			}
		},
		[]
	);

	useEffect( () => {
		const preset = config.presets[ presetId ];
		if ( ! enabled || ! preset ) {
			return undefined;
		}

		let stopped = false;
		const scale = Math.min( 1, THUMB_WIDTH / preset.width );
		const small = {
			...preset,
			width: even( preset.width * scale ),
			height: even( preset.height * scale ),
		};
		const key = ( id ) => `${ config.postId || 0 }:${ presetId }:${ id }`;

		const known = {};
		config.templates.forEach( ( template ) => {
			if ( cache.has( key( template.id ) ) ) {
				known[ template.id ] = cache.get( key( template.id ) );
			}
		} );
		setThumbs( known );

		const sandbox = async () => {
			if ( ! sandboxRef.current ) {
				const instance = new Sandbox( {
					runtimeUrl: config.runtimeUrl,
					scripts: [
						...( config.extensions || [] ),
						...config.templates.map( ( t ) => t.script ),
					],
					title: __( 'Template thumbnails', 'rsfv' ),
				} );
				await instance.mount( hostRef.current );
				sandboxRef.current = instance;
			}
			return sandboxRef.current;
		};

		const make = async ( template ) => {
			if ( stopped || cache.has( key( template.id ) ) ) {
				return;
			}
			const instance = await sandbox();
			const saved =
				config.composition &&
				config.composition.template === template.id
					? config.composition.vars
					: null;
			const { vars } = initialVars( template, fields, saved );
			const payload = await buildLoadPayload( {
				template,
				vars,
				preset: small,
				fonts: config.fonts,
			} );
			if ( stopped ) {
				return;
			}
			const loaded = await instance.call( 'load', payload );
			const { image } = await instance.call( 'snapshot', {
				time: loaded.posterTime,
				width: small.width,
			} );
			const url = window.URL.createObjectURL(
				new window.Blob( [ image ], { type: 'image/jpeg' } )
			);
			cache.set( key( template.id ), url );
			if ( ! stopped ) {
				setThumbs( ( current ) => ( {
					...current,
					[ template.id ]: url,
				} ) );
			}
		};

		// One at a time, also across size changes.
		config.templates.forEach( ( template ) => {
			queue.current = queue.current
				.then( () => make( template ) )
				.catch( () => {} );
		} );

		return () => {
			stopped = true;
		};
	}, [ enabled, presetId ] );

	return { thumbs, hostRef };
}
