/**
 * Turn a template, its variables and the post into what the runtime needs.
 *
 * @package RSFV
 */

import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';

const blobCache = new Map();
const mediaCache = new Map();

/**
 * Remember an attachment's URLs (from the media picker or post fields).
 *
 * @param {Object} media { id, url, thumb }.
 */
export function rememberMedia( media ) {
	if ( media && media.id && media.url ) {
		mediaCache.set( Number( media.id ), media );
	}
}

/**
 * Attachment URLs by id, fetched when not known yet.
 *
 * @param {number} id Attachment id.
 * @return {Promise<Object|null>} { id, url, thumb }.
 */
export async function getMedia( id ) {
	const key = Number( id );
	if ( ! key ) {
		return null;
	}
	if ( mediaCache.has( key ) ) {
		return mediaCache.get( key );
	}
	try {
		const item = await apiFetch( {
			path: `/wp/v2/media/${ key }?context=edit`,
		} );
		const sizes = ( item.media_details && item.media_details.sizes ) || {};
		const media = {
			id: key,
			url: item.source_url,
			thumb:
				( sizes.medium && sizes.medium.source_url ) || item.source_url,
		};
		mediaCache.set( key, media );
		return media;
	} catch ( e ) {
		return null;
	}
}

/**
 * Fetch a same-site file as a Blob once.
 *
 * @param {string} url URL.
 * @return {Promise<Blob|null>} Blob.
 */
export function fetchBlob( url ) {
	if ( ! url ) {
		return Promise.resolve( null );
	}
	if ( ! blobCache.has( url ) ) {
		blobCache.set(
			url,
			window
				.fetch( url, { credentials: 'same-origin' } )
				.then( ( r ) => ( r.ok ? r.blob() : null ) )
				.catch( () => null )
		);
	}
	return blobCache.get( url );
}

/**
 * Value a variable gets from the post, or undefined when the post has none.
 *
 * @param {Object} def    Variable definition.
 * @param {Object} fields Post fields.
 * @return {*} Value.
 */
export function autofillValue( def, fields ) {
	if ( ! def.autofill || ! fields ) {
		return undefined;
	}
	const value = fields[ def.autofill ];
	if ( 'image' === def.type ) {
		if ( value && value.id ) {
			rememberMedia( value );
			return value.id;
		}
		return undefined;
	}
	if ( 'images' === def.type ) {
		const list = Array.isArray( value ) ? value : [];
		list.forEach( rememberMedia );
		const ids = list.map( ( item ) => item.id ).slice( 0, def.max || 12 );
		return ids.length ? ids : undefined;
	}
	if ( 'string' === typeof value && '' !== value.trim() ) {
		return value;
	}
	return undefined;
}

/**
 * Starting values for a template: saved values, else post details, else
 * brand defaults (PRO), else the template defaults.
 *
 * @param {Object} template Template.
 * @param {Object} fields   Post fields.
 * @param {Object} saved    Saved variables for this template, if any.
 * @return {Object} { vars, autofill }.
 */
export function initialVars( template, fields, saved = null ) {
	const vars = {};
	const autofill = {};

	template.vars.forEach( ( def ) => {
		if ( saved && Object.prototype.hasOwnProperty.call( saved, def.id ) ) {
			vars[ def.id ] = saved[ def.id ];
			const filled = autofillValue( def, fields );
			autofill[ def.id ] =
				undefined !== filled &&
				JSON.stringify( filled ) === JSON.stringify( saved[ def.id ] );
			return;
		}
		const filled = autofillValue( def, fields );
		if ( undefined !== filled ) {
			vars[ def.id ] = filled;
			autofill[ def.id ] = true;
			return;
		}
		vars[ def.id ] = def.default;
	} );

	/**
	 * Filter the starting values of a template (PRO brand kit uses this).
	 *
	 * @param {Object} vars     Values.
	 * @param {Object} template Template.
	 * @param {Object} context  { fields, saved }.
	 */
	return {
		vars: applyFilters( 'rsfv.studio.initialVars', vars, template, {
			fields,
			saved,
		} ),
		autofill,
	};
}

/**
 * Build the runtime "load" payload.
 *
 * @param {Object} options          Options.
 * @param {Object} options.template Template.
 * @param {Object} options.vars     Values.
 * @param {Object} options.preset   Preset { width, height, fps }.
 * @param {Object} options.fonts    Fonts keyed by family.
 * @return {Promise<Object>} Payload.
 */
export async function buildLoadPayload( { template, vars, preset, fonts } ) {
	const assets = {};
	const families = new Set();

	await Promise.all(
		template.vars.map( async ( def ) => {
			const value = vars[ def.id ];
			if ( 'image' === def.type ) {
				const media = await getMedia( value );
				assets[ def.id ] = media ? await fetchBlob( media.url ) : null;
			} else if ( 'images' === def.type ) {
				const list = Array.isArray( value ) ? value : [];
				const blobs = await Promise.all(
					list.map( async ( id ) => {
						const media = await getMedia( id );
						return media ? fetchBlob( media.url ) : null;
					} )
				);
				assets[ def.id ] = blobs.filter( Boolean );
			} else if ( 'font' === def.type ) {
				families.add( value || def.default );
			}
		} )
	);

	const fontList = [];
	for ( const family of Array.from( families ) ) {
		const font = family && fonts[ family ];
		if ( ! font ) {
			continue;
		}
		const defaults = font.files || [
			{
				url: font.url,
				weight: font.weight || '400',
				style: font.style || 'normal',
			},
		];

		/**
		 * Filter the files of a font (PRO fetches Google Fonts here). May
		 * return a promise.
		 *
		 * @param {Array}  files  Files: { url, weight, style }.
		 * @param {string} family Family.
		 * @param {Object} font   Font entry.
		 */
		const files = await Promise.resolve(
			applyFilters( 'rsfv.studio.fontFiles', defaults, family, font )
		);
		for ( const file of files || [] ) {
			const blob = file && file.url ? await fetchBlob( file.url ) : null;
			if ( blob ) {
				fontList.push( {
					family,
					blob,
					weight: file.weight || '400',
					style: file.style || 'normal',
				} );
			}
		}
	}

	return {
		template: template.id,
		vars,
		width: preset.width,
		height: preset.height,
		fps: preset.fps,
		assets,
		fonts: fontList,
	};
}
