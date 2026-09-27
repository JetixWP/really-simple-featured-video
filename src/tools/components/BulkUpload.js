/* global localStorage */

/**
 * Bulk upload tab.
 *
 * Hand entry for a short list. CSV or TXT for a long list.
 * A row without a post is discarded and its file is never uploaded.
 *
 * @package RSFV
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import PostTypeFilter from './PostTypeFilter';
import {
	CELL_MAX,
	MAX_LOCAL_FILES,
	MAX_PASTE,
	MAX_ROWS,
	MAX_SHEET_BYTES,
	baseName,
	isRemoteUrl,
	parseSheet,
} from '../bulk/sheet';

const STORAGE_KEY_POST_TYPE = 'rsfv_tools_post_type';
const PREVIEW_BATCH = 100;
const EMBED_BATCH = 25;
const TABLE_LIMIT = 200;
const SAMPLE = [
	'post,source,value,replace',
	'42,embed,https://www.youtube.com/watch?v=VIDEO_ID,yes',
	'my-product,self,https://cdn.example.com/videos/my-product.mp4,yes',
	'77,self,intro.mp4,no',
].join( '\n' );

/**
 * Read the shared post type choice.
 *
 * @param {Array}  postTypes    Enabled post types.
 * @param {string} defaultValue Fallback slug.
 * @return {string} Post type slug.
 */
function storedPostType( postTypes, defaultValue ) {
	try {
		const stored = localStorage.getItem( STORAGE_KEY_POST_TYPE );
		if ( stored && postTypes.some( ( type ) => type.value === stored ) ) {
			return stored;
		}
	} catch {
		// Ignore storage failures.
	}

	return defaultValue;
}

/**
 * Remember the shared post type choice.
 *
 * @param {string} value Post type slug.
 */
function storePostType( value ) {
	try {
		localStorage.setItem( STORAGE_KEY_POST_TYPE, value );
	} catch {
		// Ignore storage failures.
	}
}

/**
 * Split a list into batches.
 *
 * @param {Array}  items Items.
 * @param {number} size  Batch size.
 * @return {Array[]} Batches.
 */
function chunk( items, size ) {
	const batches = [];

	for ( let index = 0; index < items.length; index += size ) {
		batches.push( items.slice( index, index + size ) );
	}

	return batches;
}

/**
 * Row fields sent to the server.
 *
 * @param {Object} row Queue row.
 * @return {Object} Fields sent to the server.
 */
function toPayload( row ) {
	return {
		id: row.id,
		post: row.post.trim(),
		source: row.source,
		value: row.value.trim(),
		replace: !! row.replace,
	};
}

/**
 * Local checks that do not need the server.
 *
 * @param {Object} row  Queue row.
 * @param {Set}    seen Post keys already used.
 * @return {Object} The row with a local status.
 */
function localCheck( row, seen ) {
	const post = row.post.trim();
	const fail = ( reason ) => ( {
		...row,
		status: 'discarded',
		reason,
		postId: 0,
		postTitle: '',
		hasVideo: false,
	} );

	if ( ! post ) {
		return fail( 'missing_post' );
	}

	const source =
		row.source === 'embed' || row.source === 'self' ? row.source : '';

	if ( ! source ) {
		return fail( 'invalid_source' );
	}

	const value = row.value.trim();

	if ( ! value ) {
		return fail( 'missing_value' );
	}

	if ( post.length > CELL_MAX || value.length > CELL_MAX ) {
		return fail( 'value_too_long' );
	}

	if ( source === 'self' && ! isRemoteUrl( value ) && ! row.file ) {
		return fail( 'missing_file' );
	}

	const key = post.toLowerCase();

	if ( seen.has( key ) ) {
		return fail( 'duplicate_post' );
	}

	seen.add( key );

	return {
		...row,
		status: 'pending',
		reason: '',
		postId: 0,
		hasVideo: false,
	};
}

/**
 * Copy a server result onto a row.
 *
 * @param {Object} row   Queue row.
 * @param {Object} found Server result.
 * @return {Object} Updated row.
 */
function applyServerResult( row, found ) {
	return {
		...row,
		status: found.status || row.status,
		reason: found.reason || '',
		postId: found.post_id || 0,
		postTitle: found.post_title || '',
		hasVideo: !! found.has_video,
		file: found.status === 'saved' ? null : row.file,
	};
}

/**
 * Merge server results into the queue.
 *
 * @param {Array} list    Queue.
 * @param {Array} results Server results.
 * @return {Array} Updated queue.
 */
function mergeResults( list, results ) {
	const byId = new Map(
		results.map( ( item ) => [ Number( item.id ), item ] )
	);

	return list.map( ( row ) => {
		const found = byId.get( row.id );
		return found ? applyServerResult( row, found ) : row;
	} );
}

/**
 * Keep the first ready row for each post id.
 *
 * @param {Array} list Queue.
 * @return {Array} Queue with later duplicates discarded.
 */
function dedupeByPostId( list ) {
	const seen = new Set();

	list.forEach( ( row ) => {
		if ( row.status === 'saved' && row.postId ) {
			seen.add( row.postId );
		}
	} );

	return list.map( ( row ) => {
		if ( row.status !== 'ready' && row.status !== 'replace' ) {
			return row;
		}

		if ( ! row.postId ) {
			return row;
		}

		if ( seen.has( row.postId ) ) {
			return {
				...row,
				status: 'discarded',
				reason: 'duplicate_post',
			};
		}

		seen.add( row.postId );
		return row;
	} );
}

/**
 * Stop a spreadsheet from treating a cell as a formula.
 *
 * @param {string} value Cell text.
 * @return {string} Escaped cell.
 */
function csvEscape( value ) {
	let cell = String( value ?? '' );

	if ( /^[=+\-@]/.test( cell ) ) {
		cell = "'" + cell;
	}

	if ( /[",\n\r]/.test( cell ) ) {
		cell = '"' + cell.replace( /"/g, '""' ) + '"';
	}

	return cell;
}

/**
 * Download a text file.
 *
 * @param {string} filename File name.
 * @param {string} text     File text.
 */
function downloadText( filename, text ) {
	const blob = new Blob( [ text ], { type: 'text/csv;charset=utf-8' } );
	const url = URL.createObjectURL( blob );
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = filename;
	document.body.appendChild( link );
	link.click();
	link.remove();
	URL.revokeObjectURL( url );
}

/**
 * Whether a dropped file is a video.
 *
 * @param {File} file File.
 * @return {boolean} True when the file is a video.
 */
function isVideoFile( file ) {
	if ( file.type && file.type.indexOf( 'video/' ) === 0 ) {
		return true;
	}

	return /\.(mp4|m4v|webm|ogv|mov|qt)$/i.test( file.name );
}

/**
 * Whether a dropped file is a CSV or TXT sheet.
 *
 * @param {File} file File.
 * @return {boolean} True when the file is a CSV or TXT sheet.
 */
function isSheetFile( file ) {
	return /\.(csv|txt)$/i.test( file.name );
}

/**
 * Text for a reason code.
 *
 * @param {string} code Reason code.
 * @param {string} item Singular name of the chosen post type.
 * @return {string} Message for the code.
 */
function reasonText( code, item ) {
	switch ( code ) {
		case 'missing_post':
			/* translators: %s: singular post type name, such as Product */
			return sprintf( __( 'No %s was given.', 'rsfv' ), item );
		case 'invalid_post':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__( 'No matching %s was found.', 'rsfv' ),
				item
			);
		case 'invalid_status':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__( 'That %s cannot take a featured video.', 'rsfv' ),
				item
			);
		case 'invalid_source':
			return __( 'Source must be self or embed.', 'rsfv' );
		case 'missing_value':
			return __( 'The video value is empty.', 'rsfv' );
		case 'value_too_long':
			return __( 'A cell is too long.', 'rsfv' );
		case 'invalid_embed':
			return __( 'The embed link is not a valid URL.', 'rsfv' );
		case 'unsupported_embed':
			return __(
				'Embed links must be YouTube, Vimeo, or Dailymotion.',
				'rsfv'
			);
		case 'invalid_url':
			return __( 'The video link is not allowed.', 'rsfv' );
		case 'invalid_mime':
			return __( 'The file is not a video this site allows.', 'rsfv' );
		case 'missing_file':
			return __( 'The video file was not included.', 'rsfv' );
		case 'file_mismatch':
			return __( 'The video file name does not match the row.', 'rsfv' );
		case 'duplicate_post':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__( 'This %s is already used by an earlier row.', 'rsfv' ),
				item
			);
		case 'has_video':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__(
					'This %s already has a featured video. Set replace to yes to change it.',
					'rsfv'
				),
				item
			);
		case 'forbidden':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__( 'You cannot edit that %s.', 'rsfv' ),
				item
			);
		case 'upload_forbidden':
			return __( 'You cannot upload files.', 'rsfv' );
		case 'too_large':
			return __( 'The video is larger than this site allows.', 'rsfv' );
		case 'download_failed':
			return __( 'The video could not be downloaded.', 'rsfv' );
		case 'upload_failed':
			return __( 'The video could not be saved to the library.', 'rsfv' );
		case 'save_failed':
			return sprintf(
				/* translators: %s: singular post type name, such as Product */
				__( 'The video could not be set on the %s.', 'rsfv' ),
				item
			);
		case 'will_replace':
			return __(
				'This will replace the current featured video.',
				'rsfv'
			);
		default:
			return '';
	}
}

/**
 * Short status label.
 *
 * @param {string} status Row status.
 * @return {string} Short label.
 */
function statusLabel( status ) {
	switch ( status ) {
		case 'pending':
			return __( 'Not checked', 'rsfv' );
		case 'ready':
			return __( 'Ready', 'rsfv' );
		case 'replace':
			return __( 'Will replace', 'rsfv' );
		case 'discarded':
			return __( 'Discarded', 'rsfv' );
		case 'saved':
			return __( 'Saved', 'rsfv' );
		case 'uploading':
			return __( 'Working', 'rsfv' );
		default:
			return status;
	}
}

const postCache = new Map();

/**
 * Load posts of one type, optionally filtered by a search term.
 *
 * Results are cached per type and term, so every row shares one request.
 *
 * @param {string} postType Post type slug.
 * @param {string} term     Search term.
 * @return {Promise<Array>} Posts with id and title.
 */
function loadPosts( postType, term ) {
	const key = `${ postType }|${ term }`;

	if ( ! postCache.has( key ) ) {
		const search = term ? `&search=${ encodeURIComponent( term ) }` : '';
		const request = apiFetch( {
			path: `/rsfv/v1/posts?post_type=${ encodeURIComponent(
				postType
			) }&per_page=20${ search }`,
		} )
			.then( ( data ) => ( Array.isArray( data ) ? data : [] ) )
			.catch( ( err ) => {
				postCache.delete( key );
				throw err;
			} );

		postCache.set( key, request );
	}

	return postCache.get( key );
}

/**
 * Dropdown that lists posts of the chosen type, with search.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.postType Post type slug.
 * @param {string}   props.value    Current post cell (ID or slug).
 * @param {string}   props.title    Matched title.
 * @param {Object}   props.labels   Names for the post type: singular, search, notFound.
 * @param {boolean}  props.disabled Whether the field is locked.
 * @param {Function} props.onChange Called with the post ID and title.
 * @return {JSX.Element} Post picker.
 */
function PostPicker( { postType, value, title, labels, disabled, onChange } ) {
	const [ open, setOpen ] = useState( false );
	const [ term, setTerm ] = useState( '' );
	const [ query, setQuery ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ failed, setFailed ] = useState( false );
	const [ active, setActive ] = useState( 0 );
	const wrap = useRef( null );
	const search = useRef( null );
	const listId = useRef(
		`rsfv-bulk-picker-${ Math.random().toString( 36 ).slice( 2 ) }`
	);

	useEffect( () => {
		const handle = setTimeout( () => setQuery( term.trim() ), 250 );
		return () => clearTimeout( handle );
	}, [ term ] );

	useEffect( () => {
		if ( ! open ) {
			return undefined;
		}

		let live = true;
		setLoading( true );
		setFailed( false );

		loadPosts( postType, query )
			.then( ( data ) => {
				if ( live ) {
					setResults( data );
					setActive( 0 );
				}
			} )
			.catch( () => {
				if ( live ) {
					setResults( [] );
					setFailed( true );
				}
			} )
			.finally( () => {
				if ( live ) {
					setLoading( false );
				}
			} );

		return () => {
			live = false;
		};
	}, [ open, postType, query ] );

	useEffect( () => {
		if ( ! open ) {
			return undefined;
		}

		const close = ( event ) => {
			if ( wrap.current && ! wrap.current.contains( event.target ) ) {
				setOpen( false );
			}
		};

		document.addEventListener( 'mousedown', close );
		return () => document.removeEventListener( 'mousedown', close );
	}, [ open ] );

	useEffect( () => {
		if ( open && search.current ) {
			search.current.focus();
		}
	}, [ open ] );

	const choose = ( post ) => {
		onChange( String( post.id ), post.title || '' );
		setOpen( false );
		setTerm( '' );
	};

	const onKeyDown = ( event ) => {
		if ( event.key === 'Escape' ) {
			event.preventDefault();
			setOpen( false );
		} else if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			setActive( ( index ) =>
				Math.min( index + 1, Math.max( results.length - 1, 0 ) )
			);
		} else if ( event.key === 'ArrowUp' ) {
			event.preventDefault();
			setActive( ( index ) => Math.max( index - 1, 0 ) );
		} else if ( event.key === 'Enter' && results[ active ] ) {
			event.preventDefault();
			choose( results[ active ] );
		}
	};

	let label = sprintf(
		/* translators: %s: singular post type name, such as Product */
		__( 'Select %s', 'rsfv' ),
		labels.singular
	);

	if ( title ) {
		label = title;
	} else if ( value ) {
		label = value;
	}

	return (
		<div className="rsfv-bulk-picker" ref={ wrap }>
			<button
				type="button"
				className={ `rsfv-bulk-picker-toggle ${
					value ? '' : 'is-empty'
				}` }
				disabled={ disabled }
				aria-haspopup="listbox"
				aria-expanded={ open }
				onClick={ () => setOpen( ( current ) => ! current ) }
			>
				<span className="rsfv-bulk-picker-label">{ label }</span>
				{ value && /^\d+$/.test( value ) ? (
					<span className="rsfv-bulk-picker-id">
						{ sprintf(
							/* translators: %s: post ID */
							__( 'ID %s', 'rsfv' ),
							value
						) }
					</span>
				) : null }
			</button>

			{ open ? (
				<div className="rsfv-bulk-picker-pop">
					<input
						ref={ search }
						type="search"
						value={ term }
						placeholder={ labels.search }
						aria-label={ labels.search }
						aria-controls={ listId.current }
						onChange={ ( event ) => setTerm( event.target.value ) }
						onKeyDown={ onKeyDown }
					/>
					{ loading && ! results.length ? (
						<p className="rsfv-bulk-picker-msg">
							{ __( 'Loading…', 'rsfv' ) }
						</p>
					) : null }
					{ failed ? (
						<p className="rsfv-bulk-picker-msg">
							{ __(
								'The list could not be loaded. Close and open it to try again.',
								'rsfv'
							) }
						</p>
					) : null }
					{ ! loading && ! failed && ! results.length ? (
						<p className="rsfv-bulk-picker-msg">
							{ labels.notFound }
						</p>
					) : null }
					{ results.length ? (
						<ul
							id={ listId.current }
							role="listbox"
							className="rsfv-bulk-picker-list"
						>
							{ results.map( ( post, index ) => (
								<li
									key={ post.id }
									role="option"
									aria-selected={
										String( post.id ) === value
									}
									className={ `${
										index === active ? 'is-active' : ''
									} ${
										String( post.id ) === value
											? 'is-selected'
											: ''
									}` }
									onMouseEnter={ () => setActive( index ) }
									onMouseDown={ ( event ) => {
										event.preventDefault();
										choose( post );
									} }
								>
									<span>
										{ post.title ||
											__( '(no title)', 'rsfv' ) }
									</span>
									<small>
										{ sprintf(
											/* translators: %d: post ID */
											__( 'ID %d', 'rsfv' ),
											post.id
										) }
									</small>
								</li>
							) ) }
						</ul>
					) : null }
				</div>
			) : null }
		</div>
	);
}

/**
 * Bulk upload screen.
 *
 * @return {JSX.Element} Bulk upload screen.
 */
const BulkUpload = () => {
	const postTypes = window.rsfvTools?.postTypes || [];
	const nextId = useRef( 1 );
	const fileInput = useRef( null );
	const busy = useRef( false );

	const [ postType, setPostType ] = useState( () =>
		storedPostType( postTypes, postTypes[ 0 ] ? postTypes[ 0 ].value : '' )
	);
	const [ rows, setRows ] = useState( [] );
	const [ running, setRunning ] = useState( false );
	const [ progress, setProgress ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ paste, setPaste ] = useState( '' );
	const [ dragging, setDragging ] = useState( false );
	const [ showConfirm, setShowConfirm ] = useState( false );
	const [ replaceConfirmed, setReplaceConfirmed ] = useState( false );
	const [ method, setMethod ] = useState( 'list' );
	const [ uploadDone, setUploadDone ] = useState( false );
	const typeInfo =
		postTypes.find( ( type ) => type.value === postType ) || {};
	const labels = {
		singular: typeInfo.singular || __( 'Post', 'rsfv' ),
		search: typeInfo.search || __( 'Search', 'rsfv' ),
		notFound: typeInfo.notFound || __( 'Nothing found.', 'rsfv' ),
	};

	useEffect( () => {
		if ( ! running ) {
			return undefined;
		}

		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ running ] );

	const makeRow = ( partial ) => ( {
		id: nextId.current++,
		post: '',
		source: 'self',
		value: '',
		replace: false,
		file: null,
		status: 'pending',
		reason: '',
		postId: 0,
		postTitle: '',
		hasVideo: false,
		...partial,
	} );

	const resetConfirm = () => {
		setShowConfirm( false );
		setReplaceConfirmed( false );
	};

	const changeType = ( value ) => {
		setPostType( value );
		storePostType( value );
		setRows( [] );
		setError( '' );
		setNotice( '' );
		setProgress( '' );
		setUploadDone( false );
		resetConfirm();
	};

	const clearList = () => {
		if ( running ) {
			return;
		}

		setRows( [] );
		setError( '' );
		setNotice( '' );
		setProgress( '' );
		setPaste( '' );
		setUploadDone( false );
		postCache.clear();
		resetConfirm();
	};

	const patchRow = ( id, fields ) => {
		resetConfirm();
		setRows( ( current ) =>
			current.map( ( row ) => {
				if ( row.id !== id || row.status === 'saved' ) {
					return row;
				}

				return {
					...row,
					...fields,
					status: 'pending',
					reason: '',
					postId: 0,
					hasVideo: false,
				};
			} )
		);
	};

	const removeRow = ( id ) => {
		if ( running ) {
			return;
		}

		resetConfirm();
		setRows( ( current ) => current.filter( ( row ) => row.id !== id ) );
	};

	const addRows = ( incoming ) => {
		if ( ! incoming.length ) {
			return false;
		}

		if ( rows.length + incoming.length > MAX_ROWS ) {
			setError( __( 'The list is limited to 10,000 rows.', 'rsfv' ) );
			return false;
		}

		resetConfirm();
		setRows( ( current ) => current.concat( incoming ) );
		return true;
	};

	const addLinks = () => {
		const lines = paste
			.split( /\r?\n/ )
			.map( ( line ) => line.trim() )
			.filter( ( line ) => line && line.charAt( 0 ) !== '#' );

		if ( ! lines.length ) {
			return;
		}

		if ( lines.length > MAX_PASTE ) {
			setError(
				__( 'Paste up to 200 links. For more, use a CSV file.', 'rsfv' )
			);
			return;
		}

		const incoming = lines.map( ( line ) =>
			makeRow( {
				source: 'embed',
				value: line,
			} )
		);

		if ( addRows( incoming ) ) {
			setPaste( '' );
			setError( '' );
			setNotice( '' );
		}
	};

	const ingestFiles = async ( fileList ) => {
		if ( running ) {
			return;
		}

		const files = Array.from( fileList || [] );

		if ( method === 'list' ) {
			const videos = files.filter( isVideoFile );

			if ( ! videos.length ) {
				setError(
					__(
						'Only video files go here. For a CSV or TXT, switch to Import CSV or TXT.',
						'rsfv'
					)
				);
				return;
			}

			if ( videos.length > MAX_LOCAL_FILES ) {
				setError(
					__(
						'Drop up to 200 video files at a time. For more, use a CSV with https links.',
						'rsfv'
					)
				);
				return;
			}

			const incoming = videos.map( ( file ) =>
				makeRow( {
					source: 'self',
					value: file.name,
					file,
				} )
			);

			setError( '' );
			setNotice( '' );

			if ( addRows( incoming ) && files.length !== videos.length ) {
				setNotice( __( 'Only video files were added.', 'rsfv' ) );
			}
			return;
		}

		const sheets = files.filter( isSheetFile );

		if ( ! sheets.length ) {
			setError( __( 'Drop a CSV or TXT file.', 'rsfv' ) );
			return;
		}

		const videos = files.filter( isVideoFile );

		if ( videos.length > MAX_LOCAL_FILES ) {
			setError(
				__(
					'Drop up to 200 video files at a time. For more, put https links in the CSV.',
					'rsfv'
				)
			);
			return;
		}

		setError( '' );
		setNotice( '' );

		let parsed = [];

		for ( let index = 0; index < sheets.length; index++ ) {
			const sheet = sheets[ index ];

			if ( sheet.size > MAX_SHEET_BYTES ) {
				setError(
					__(
						'Each CSV or TXT file must be 2 MB or smaller.',
						'rsfv'
					)
				);
				return;
			}

			const text = await sheet.text();
			const result = parseSheet( text );

			if ( result.error === 'bad_header' ) {
				setError(
					__(
						'The first line must be post,source,value,replace.',
						'rsfv'
					)
				);
				return;
			}

			if ( result.error === 'too_many' ) {
				setError( __( 'A file can hold up to 10,000 rows.', 'rsfv' ) );
				return;
			}

			if ( result.error === 'empty' ) {
				setError( __( 'The file has no rows.', 'rsfv' ) );
				return;
			}

			parsed = parsed.concat( result.rows );
		}

		const pool = new Map();

		videos.forEach( ( file ) => {
			const key = file.name.trim().toLowerCase();
			const list = pool.get( key ) || [];
			list.push( file );
			pool.set( key, list );
		} );

		const incoming = parsed.map( ( raw ) => {
			let file = null;

			if ( raw.source === 'self' && ! isRemoteUrl( raw.value ) ) {
				const list = pool.get( baseName( raw.value ) );

				if ( list && list.length ) {
					file = list.shift();
				}
			}

			return makeRow( {
				post: raw.post,
				source: raw.source === 'embed' ? 'embed' : 'self',
				value: raw.value,
				replace: !! raw.replace,
				file,
			} );
		} );

		let unused = 0;
		pool.forEach( ( list ) => {
			unused += list.length;
		} );

		if ( addRows( incoming ) && unused ) {
			setNotice(
				__(
					'Some video files did not match a row, so they were left out.',
					'rsfv'
				)
			);
		}
	};

	const onDrop = ( event ) => {
		event.preventDefault();
		setDragging( false );
		ingestFiles( event.dataTransfer.files );
	};

	const checkRows = async () => {
		if ( busy.current || ! postType ) {
			return;
		}

		busy.current = true;
		setRunning( true );
		setError( '' );
		resetConfirm();

		const seen = new Set();

		rows.forEach( ( row ) => {
			if ( row.status === 'saved' && row.post.trim() ) {
				seen.add( row.post.trim().toLowerCase() );
			}
		} );

		let working = rows.map( ( row ) => {
			if ( row.status === 'saved' ) {
				return row;
			}

			return localCheck( row, seen );
		} );
		setRows( working );

		const pending = working.filter( ( row ) => row.status === 'pending' );
		const batches = chunk( pending, PREVIEW_BATCH );

		try {
			for ( let index = 0; index < batches.length; index++ ) {
				const done = Math.min(
					( index + 1 ) * PREVIEW_BATCH,
					pending.length
				);
				setProgress(
					sprintf(
						/* translators: 1: rows checked so far, 2: rows to check */
						__( 'Checking %1$d of %2$d', 'rsfv' ),
						done,
						pending.length
					)
				);

				const response = await apiFetch( {
					path: '/rsfv/v1/bulk/preview',
					method: 'POST',
					data: {
						post_type: postType,
						rows: batches[ index ].map( toPayload ),
					},
				} );

				working = mergeResults( working, response?.results || [] );
				setRows( working );
			}

			working = dedupeByPostId( working );
			setRows( working );
		} catch ( err ) {
			setError( err?.message || __( 'The check failed.', 'rsfv' ) );
		} finally {
			busy.current = false;
			setRunning( false );
			setProgress( '' );
		}
	};

	const applyRows = async () => {
		if ( busy.current || ! postType ) {
			return;
		}

		busy.current = true;
		setRunning( true );
		setError( '' );
		setShowConfirm( false );

		let working = rows;
		const queue = working.filter(
			( row ) => row.status === 'ready' || row.status === 'replace'
		);
		let embeds = [];

		const flushEmbeds = async () => {
			if ( ! embeds.length ) {
				return;
			}

			const batch = embeds;
			embeds = [];

			const response = await apiFetch( {
				path: '/rsfv/v1/bulk/apply',
				method: 'POST',
				data: {
					post_type: postType,
					rows: batch.map( toPayload ),
				},
			} );

			working = mergeResults( working, response?.results || [] );
			setRows( working );
		};

		try {
			for ( let index = 0; index < queue.length; index++ ) {
				const row = queue[ index ];
				setProgress(
					sprintf(
						/* translators: 1: current row, 2: rows to save */
						__( 'Saving %1$d of %2$d', 'rsfv' ),
						index + 1,
						queue.length
					)
				);

				if ( row.source === 'embed' ) {
					embeds.push( row );

					if ( embeds.length === EMBED_BATCH ) {
						await flushEmbeds();
					}
					continue;
				}

				await flushEmbeds();

				const previous = row.status;
				working = working.map( ( item ) =>
					item.id === row.id ? { ...item, status: 'uploading' } : item
				);
				setRows( working );

				try {
					let response;

					if ( row.file ) {
						const form = new FormData();
						form.append( 'post_type', postType );
						form.append(
							'rows',
							JSON.stringify( [ toPayload( row ) ] )
						);
						form.append( 'file', row.file, row.file.name );
						response = await apiFetch( {
							path: '/rsfv/v1/bulk/apply',
							method: 'POST',
							body: form,
						} );
					} else {
						response = await apiFetch( {
							path: '/rsfv/v1/bulk/apply',
							method: 'POST',
							data: {
								post_type: postType,
								rows: [ toPayload( row ) ],
							},
						} );
					}

					working = mergeResults( working, response?.results || [] );
					setRows( working );
				} catch ( err ) {
					working = working.map( ( item ) =>
						item.id === row.id
							? { ...item, status: previous }
							: item
					);
					setRows( working );
					throw err;
				}
			}

			await flushEmbeds();
		} catch ( err ) {
			setError(
				err?.message ||
					__(
						'The upload stopped. Saved rows are kept. Press Continue to retry the rest.',
						'rsfv'
					)
			);
		} finally {
			busy.current = false;
			setRunning( false );
			setProgress( '' );
			setUploadDone( true );
		}
	};

	const onPrimary = () => {
		if ( running ) {
			return;
		}

		const pending = rows.some( ( row ) => row.status === 'pending' );

		if ( pending ) {
			checkRows();
			return;
		}

		const ready = rows.filter(
			( row ) => row.status === 'ready' || row.status === 'replace'
		);

		if ( ! ready.length ) {
			return;
		}

		const replaces = ready.filter(
			( row ) => row.status === 'replace'
		).length;

		if ( replaces > 0 && ! replaceConfirmed ) {
			setShowConfirm( true );
			return;
		}

		applyRows();
	};

	const confirmReplace = () => {
		setReplaceConfirmed( true );
		setShowConfirm( false );
		applyRows();
	};

	const downloadReport = () => {
		const lines = [ 'post,source,value,status,message' ];

		rows.forEach( ( row ) => {
			const message = row.reason
				? reasonText( row.reason, labels.singular )
				: statusLabel( row.status );
			lines.push(
				[
					csvEscape( row.post ),
					csvEscape( row.source ),
					csvEscape( row.value ),
					csvEscape( row.status ),
					csvEscape( message ),
				].join( ',' )
			);
		} );

		downloadText( 'rsfv-bulk-report.csv', lines.join( '\n' ) );
	};

	if ( ! postTypes.length ) {
		return (
			<div className="rsfv-bulk">
				<p>
					{ __(
						'Turn on a post type in the plugin settings before uploading videos.',
						'rsfv'
					) }
				</p>
			</div>
		);
	}

	const counts = rows.reduce( ( total, row ) => {
		total[ row.status ] = ( total[ row.status ] || 0 ) + 1;
		return total;
	}, {} );
	const pendingCount = counts.pending || 0;
	const readyCount = ( counts.ready || 0 ) + ( counts.replace || 0 );
	const savedCount = counts.saved || 0;
	const discarded = rows.filter( ( row ) => row.status === 'discarded' );
	const showTable = rows.length > 0 && rows.length <= TABLE_LIMIT;
	let primaryLabel = __( 'Check rows', 'rsfv' );

	if ( ! pendingCount && readyCount && savedCount ) {
		primaryLabel = __( 'Continue', 'rsfv' );
	} else if ( ! pendingCount && readyCount ) {
		primaryLabel = __( 'Upload and set videos', 'rsfv' );
	}

	const primaryDisabled =
		running || ! rows.length || ( ! pendingCount && ! readyCount );
	const switchLocked = running || rows.length > 0;
	const methods = [
		{ id: 'list', label: __( 'Add by hand', 'rsfv' ) },
		{ id: 'file', label: __( 'Import CSV or TXT', 'rsfv' ) },
	];

	const pickMethod = ( next ) => {
		if ( switchLocked || next === method ) {
			return;
		}

		setMethod( next );
		setError( '' );
		setNotice( '' );
		setPaste( '' );
	};

	const dropProps = {
		onDragEnter: ( event ) => {
			event.preventDefault();
			setDragging( true );
		},
		onDragOver: ( event ) => {
			event.preventDefault();
			setDragging( true );
		},
		onDragLeave: () => setDragging( false ),
		onDrop,
	};

	return (
		<div className="rsfv-bulk">
			<header className="rsfv-bulk-header">
				<div>
					<h2>{ __( 'Bulk upload', 'rsfv' ) }</h2>
					<p>
						{ sprintf(
							/* translators: %s: singular post type name, such as Product */
							__(
								'Every video must go to a %s. A row without one is thrown away, and its file is never uploaded.',
								'rsfv'
							),
							labels.singular
						) }
					</p>
				</div>
				<PostTypeFilter
					postTypes={ postTypes }
					selectedPostType={ postType }
					onChange={ changeType }
					disabled={ switchLocked }
				/>
			</header>

			<div
				className="rsfv-bulk-switch"
				role="group"
				aria-label={ __( 'How to add videos', 'rsfv' ) }
			>
				{ methods.map( ( item ) => (
					<button
						key={ item.id }
						type="button"
						className={ method === item.id ? 'is-active' : '' }
						aria-pressed={ method === item.id }
						disabled={ switchLocked && method !== item.id }
						onClick={ () => pickMethod( item.id ) }
					>
						{ item.label }
					</button>
				) ) }
			</div>
			{ rows.length ? (
				<p className="rsfv-bulk-note">
					{ __(
						'To switch method or post type, press Start over first.',
						'rsfv'
					) }
				</p>
			) : null }

			{ method === 'list' ? (
				<div className="rsfv-bulk-panel">
					<div
						className={ `rsfv-bulk-drop ${
							dragging ? 'is-dragging' : ''
						}` }
						{ ...dropProps }
					>
						<p className="rsfv-bulk-drop-title">
							{ __( 'Drop video files here', 'rsfv' ) }
						</p>
						<p className="rsfv-bulk-drop-hint">
							{ sprintf(
								/* translators: %s: singular post type name, such as Product */
								__(
									'You pick the %s for each one in the next step.',
									'rsfv'
								),
								labels.singular
							) }
						</p>
						<button
							type="button"
							className="button"
							disabled={ running }
							onClick={ () =>
								fileInput.current && fileInput.current.click()
							}
						>
							{ __( 'Choose videos', 'rsfv' ) }
						</button>
					</div>

					<div className="rsfv-bulk-or">
						<span>{ __( 'or', 'rsfv' ) }</span>
					</div>

					<div className="rsfv-bulk-paste">
						<label htmlFor="rsfv-bulk-links">
							{ __(
								'Paste YouTube, Vimeo, or Dailymotion links, one per line',
								'rsfv'
							) }
						</label>
						<textarea
							id="rsfv-bulk-links"
							rows="3"
							value={ paste }
							disabled={ running }
							placeholder="https://www.youtube.com/watch?v=…"
							onChange={ ( event ) =>
								setPaste( event.target.value )
							}
						/>
						<div className="rsfv-bulk-paste-actions">
							<button
								type="button"
								className="button"
								disabled={ running || ! paste.trim() }
								onClick={ addLinks }
							>
								{ __( 'Add links', 'rsfv' ) }
							</button>
						</div>
					</div>
				</div>
			) : null }

			{ method === 'file' ? (
				<div className="rsfv-bulk-panel">
					<div
						className={ `rsfv-bulk-drop ${
							dragging ? 'is-dragging' : ''
						}` }
						{ ...dropProps }
					>
						<p className="rsfv-bulk-drop-title">
							{ __( 'Drop a CSV or TXT file here', 'rsfv' ) }
						</p>
						<p className="rsfv-bulk-drop-hint">
							{ __(
								'If a row names a video file, drop that file at the same time.',
								'rsfv'
							) }
						</p>
						<button
							type="button"
							className="button"
							disabled={ running }
							onClick={ () =>
								fileInput.current && fileInput.current.click()
							}
						>
							{ __( 'Choose file', 'rsfv' ) }
						</button>
					</div>

					{ ! rows.length ? (
						<section className="rsfv-bulk-format">
							<div className="rsfv-bulk-format-head">
								<h3>{ __( 'File format', 'rsfv' ) }</h3>
								<button
									type="button"
									className="button button-small"
									onClick={ () =>
										downloadText(
											'rsfv-bulk-sample.csv',
											SAMPLE
										)
									}
								>
									{ __( 'Download sample CSV', 'rsfv' ) }
								</button>
							</div>
							<dl className="rsfv-bulk-columns">
								<dt>
									<code>post</code>
								</dt>
								<dd>
									{ sprintf(
										/* translators: %s: singular post type name, such as Product */
										__( '%s ID or slug.', 'rsfv' ),
										labels.singular
									) }
								</dd>
								<dt>
									<code>source</code>
								</dt>
								<dd>
									{ __(
										'self for a video file, embed for a link.',
										'rsfv'
									) }
								</dd>
								<dt>
									<code>value</code>
								</dt>
								<dd>
									{ __(
										'A YouTube, Vimeo, or Dailymotion link. Or a file name like intro.mp4. Or an https link ending in .mp4.',
										'rsfv'
									) }
								</dd>
								<dt>
									<code>replace</code>
								</dt>
								<dd>
									{ sprintf(
										/* translators: %s: singular post type name, such as Product */
										__(
											'yes to replace a video the %s already has. Empty means no.',
											'rsfv'
										),
										labels.singular
									) }
								</dd>
							</dl>
							<pre className="rsfv-bulk-sample">{ SAMPLE }</pre>
							<p className="rsfv-bulk-note">
								{ __(
									'TXT files can use tabs. Blank lines and lines starting with # are skipped. Up to 10,000 rows.',
									'rsfv'
								) }
							</p>
						</section>
					) : null }
				</div>
			) : null }

			<input
				ref={ fileInput }
				className="rsfv-bulk-file"
				type="file"
				multiple
				accept={
					method === 'list'
						? 'video/*,.mp4,.m4v,.webm,.ogv,.mov'
						: '.csv,.txt,video/*,.mp4,.m4v,.webm,.ogv,.mov'
				}
				onChange={ ( event ) => {
					ingestFiles( event.target.files );
					event.target.value = '';
				} }
			/>

			{ error ? (
				<p className="rsfv-bulk-error" role="alert">
					{ error }
				</p>
			) : null }
			{ notice ? <p className="rsfv-bulk-note">{ notice }</p> : null }
			{ progress ? (
				<p className="rsfv-bulk-progress" role="status">
					{ progress }
				</p>
			) : null }

			{ rows.length ? (
				<div className="rsfv-bulk-counts">
					<span>
						{ sprintf(
							/* translators: %d: row count */
							__( 'Not checked: %d', 'rsfv' ),
							pendingCount
						) }
					</span>
					<span>
						{ sprintf(
							/* translators: %d: row count */
							__( 'Ready: %d', 'rsfv' ),
							counts.ready || 0
						) }
					</span>
					<span>
						{ sprintf(
							/* translators: %d: row count */
							__( 'Will replace: %d', 'rsfv' ),
							counts.replace || 0
						) }
					</span>
					<span>
						{ sprintf(
							/* translators: %d: row count */
							__( 'Discarded: %d', 'rsfv' ),
							discarded.length
						) }
					</span>
					<span>
						{ sprintf(
							/* translators: %d: row count */
							__( 'Saved: %d', 'rsfv' ),
							savedCount
						) }
					</span>
				</div>
			) : null }

			{ rows.length > TABLE_LIMIT ? (
				<p className="rsfv-bulk-note">
					{ sprintf(
						/* translators: %d: number of rows */
						__(
							'%d rows are loaded. The list is too long to edit here. Check the counts, then run it.',
							'rsfv'
						),
						rows.length
					) }
				</p>
			) : null }

			{ showTable ? (
				<table className="rsfv-bulk-table">
					<colgroup>
						<col className="rsfv-bulk-col-video" />
						<col className="rsfv-bulk-col-post" />
						<col className="rsfv-bulk-col-replace" />
						<col className="rsfv-bulk-col-status" />
						<col className="rsfv-bulk-col-actions" />
					</colgroup>
					<thead>
						<tr>
							<th>{ __( 'Video', 'rsfv' ) }</th>
							<th>{ labels.singular }</th>
							<th>{ __( 'Replace', 'rsfv' ) }</th>
							<th>{ __( 'Status', 'rsfv' ) }</th>
							<th>
								<span className="screen-reader-text">
									{ __( 'Actions', 'rsfv' ) }
								</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{ rows.map( ( row ) => (
							<tr
								key={ row.id }
								className={ `is-${ row.status }` }
							>
								<td>
									<div
										className="rsfv-bulk-value"
										title={ row.value }
									>
										{ row.source === 'embed'
											? __( 'Embed', 'rsfv' )
											: __( 'File', 'rsfv' ) }
										<span>{ row.value }</span>
									</div>
								</td>
								<td>
									<PostPicker
										postType={ postType }
										labels={ labels }
										value={ row.post }
										title={ row.postTitle }
										disabled={
											running || row.status === 'saved'
										}
										onChange={ ( nextPost, nextTitle ) =>
											patchRow( row.id, {
												post: nextPost,
												postTitle: nextTitle,
											} )
										}
									/>
								</td>
								<td>
									<input
										type="checkbox"
										checked={ !! row.replace }
										disabled={
											running || row.status === 'saved'
										}
										aria-label={ __(
											'Replace the current featured video',
											'rsfv'
										) }
										onChange={ ( event ) =>
											patchRow( row.id, {
												replace: event.target.checked,
											} )
										}
									/>
								</td>
								<td>
									<span
										className={ `rsfv-bulk-status is-${ row.status }` }
									>
										{ statusLabel( row.status ) }
									</span>
									{ row.reason &&
									row.status === 'discarded' ? (
										<small>
											{ reasonText(
												row.reason,
												labels.singular
											) }
										</small>
									) : null }
								</td>
								<td>
									{ row.status === 'saved' ? null : (
										<button
											type="button"
											className="button button-small button-link-delete"
											disabled={ running }
											onClick={ () =>
												removeRow( row.id )
											}
										>
											{ __( 'Remove', 'rsfv' ) }
										</button>
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) : null }

			{ discarded.length && ! showTable ? (
				<div className="rsfv-bulk-discarded">
					<h3>{ __( 'Discarded rows', 'rsfv' ) }</h3>
					<ul>
						{ discarded.slice( 0, 50 ).map( ( row ) => (
							<li key={ row.id }>
								{ sprintf(
									/* translators: 1: post id or slug, 2: reason */
									__( '%1$s — %2$s', 'rsfv' ),
									row.post ||
										sprintf(
											/* translators: %s: singular post type name, such as Product */
											__( '(no %s)', 'rsfv' ),
											labels.singular
										),
									reasonText( row.reason, labels.singular )
								) }
							</li>
						) ) }
					</ul>
					{ discarded.length > 50 ? (
						<p className="rsfv-bulk-note">
							{ uploadDone
								? __(
										'Download the report to see every discarded row.',
										'rsfv'
								  )
								: sprintf(
										/* translators: %d: number of discarded rows not listed */
										__(
											'%d more are not listed. The full list is in the report after the upload.',
											'rsfv'
										),
										discarded.length - 50
								  ) }
						</p>
					) : null }
				</div>
			) : null }

			{ showConfirm ? (
				<div className="rsfv-bulk-confirm" role="alert">
					<p>
						{ sprintf(
							/* translators: %d: rows that will replace an existing video */
							__(
								'%d rows will replace an existing featured video.',
								'rsfv'
							),
							counts.replace || 0
						) }
					</p>
					<button
						type="button"
						className="button button-primary"
						onClick={ confirmReplace }
					>
						{ __( 'Replace and continue', 'rsfv' ) }
					</button>
					<button
						type="button"
						className="button"
						onClick={ () => setShowConfirm( false ) }
					>
						{ __( 'Cancel', 'rsfv' ) }
					</button>
				</div>
			) : null }

			{ rows.length ? (
				<div className="rsfv-bulk-actions">
					<button
						type="button"
						className="button button-primary"
						disabled={ primaryDisabled }
						onClick={ onPrimary }
					>
						{ primaryLabel }
					</button>
					{ uploadDone && ! running ? (
						<button
							type="button"
							className="button"
							onClick={ downloadReport }
						>
							{ __( 'Download report', 'rsfv' ) }
						</button>
					) : null }
					<button
						type="button"
						className="button"
						onClick={ clearList }
					>
						{ __( 'Start over', 'rsfv' ) }
					</button>
				</div>
			) : null }
		</div>
	);
};

export default BulkUpload;
