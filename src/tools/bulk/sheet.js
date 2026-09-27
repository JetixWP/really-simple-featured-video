/**
 * CSV and TXT parser for bulk video import.
 *
 * @package RSFV
 */

export const MAX_ROWS = 10000;
export const MAX_SHEET_BYTES = 2 * 1024 * 1024;
export const MAX_LOCAL_FILES = 200;
export const MAX_PASTE = 200;
export const CELL_MAX = 2000;

const HEADER = [ 'post', 'source', 'value', 'replace' ];

/**
 * File name without a folder, lower case.
 *
 * @param {string} value File name or path.
 * @return {string} Lower case file name.
 */
export function baseName( value ) {
	const clean = String( value || '' )
		.trim()
		.replace( /\\/g, '/' );
	const parts = clean.split( '/' );
	return ( parts[ parts.length - 1 ] || '' ).toLowerCase();
}

/**
 * Whether a cell looks like an http(s) URL.
 *
 * @param {string} value Cell value.
 * @return {boolean} True when the value starts with http:// or https://.
 */
export function isRemoteUrl( value ) {
	return /^https?:\/\//i.test( String( value || '' ).trim() );
}

/**
 * Parse a CSV or TXT sheet.
 *
 * The first useful line must be post,source,value,replace.
 * A TXT file may use tabs. Blank lines and lines that start with # are skipped.
 *
 * @param {string} text File text.
 * @return {{ rows: Array, error: string }} Parsed rows, or an error code.
 */
export function parseSheet( text ) {
	const input = String( text || '' ).replace( /^\uFEFF/, '' );
	const delimiter = detectDelimiter( input );
	const records = parseRecords( input, delimiter );
	const useful = records.filter( ( record ) => ! isSkippable( record ) );

	if ( ! useful.length ) {
		return { rows: [], error: 'empty' };
	}

	const header = useful[ 0 ].map( ( cell ) => cell.trim().toLowerCase() );
	const matchesHeader = HEADER.every(
		( name, index ) => header[ index ] === name
	);

	if ( ! matchesHeader ) {
		return { rows: [], error: 'bad_header' };
	}

	const rows = [];

	for ( let index = 1; index < useful.length; index++ ) {
		const record = useful[ index ];
		const post = ( record[ 0 ] || '' ).trim();
		const source = ( record[ 1 ] || '' ).trim().toLowerCase();
		const value = ( record[ 2 ] || '' ).trim();
		const replaceCell = ( record[ 3 ] || '' ).trim().toLowerCase();

		if ( rows.length >= MAX_ROWS ) {
			return { rows: [], error: 'too_many' };
		}

		rows.push( {
			post,
			source,
			value,
			replace:
				replaceCell === 'yes' ||
				replaceCell === 'true' ||
				replaceCell === '1',
		} );
	}

	if ( ! rows.length ) {
		return { rows: [], error: 'empty' };
	}

	return { rows, error: '' };
}

/**
 * Pick a comma or a tab from the header line.
 *
 * @param {string} text File text.
 * @return {string} Comma or tab.
 */
function detectDelimiter( text ) {
	const line = firstUsefulLine( text );
	let commas = 0;
	let tabs = 0;
	let inQuotes = false;

	for ( let i = 0; i < line.length; i++ ) {
		const char = line[ i ];

		if ( char === '"' ) {
			if ( inQuotes && line[ i + 1 ] === '"' ) {
				i++;
			} else {
				inQuotes = ! inQuotes;
			}
			continue;
		}

		if ( inQuotes ) {
			continue;
		}

		if ( char === ',' ) {
			commas++;
		} else if ( char === '\t' ) {
			tabs++;
		}
	}

	return tabs > commas ? '\t' : ',';
}

/**
 * First line that is not blank and not a comment.
 *
 * @param {string} text File text.
 * @return {string} Header line, or an empty string.
 */
function firstUsefulLine( text ) {
	const lines = text.split( /\r?\n/ );

	for ( let i = 0; i < lines.length; i++ ) {
		const line = lines[ i ].trim();

		if ( line && line[ 0 ] !== '#' ) {
			return lines[ i ];
		}
	}

	return '';
}

/**
 * Split text into rows and cells. Quotes may hold commas and line breaks.
 *
 * @param {string} text      File text.
 * @param {string} delimiter Comma or tab.
 * @return {string[][]} Rows of cells.
 */
function parseRecords( text, delimiter ) {
	const records = [];
	let row = [];
	let field = '';
	let inQuotes = false;

	for ( let i = 0; i < text.length; i++ ) {
		const char = text[ i ];

		if ( inQuotes ) {
			if ( char === '"' ) {
				if ( text[ i + 1 ] === '"' ) {
					field += '"';
					i++;
				} else {
					inQuotes = false;
				}
			} else {
				field += char;
			}
			continue;
		}

		if ( char === '"' ) {
			inQuotes = true;
			continue;
		}

		if ( char === delimiter ) {
			row.push( field );
			field = '';
			continue;
		}

		if ( char === '\n' || char === '\r' ) {
			if ( char === '\r' && text[ i + 1 ] === '\n' ) {
				i++;
			}
			row.push( field );
			records.push( row );
			row = [];
			field = '';
			continue;
		}

		field += char;
	}

	if ( field.length || row.length ) {
		row.push( field );
		records.push( row );
	}

	return records;
}

/**
 * Whether a parsed record is blank or a comment.
 *
 * @param {string[]} record Cells.
 * @return {boolean} True when the record should be ignored.
 */
function isSkippable( record ) {
	const cells = record.map( ( cell ) => cell.trim() );
	const hasValue = cells.some( ( cell ) => cell !== '' );

	if ( ! hasValue ) {
		return true;
	}

	return (
		cells[ 0 ].charAt( 0 ) === '#' &&
		cells.slice( 1 ).every( ( cell ) => cell === '' )
	);
}
