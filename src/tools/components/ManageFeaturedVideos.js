/**
 * Manage Featured Videos Component
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import PostsTable from './PostsTable';
import Pagination from './Pagination';
import PostTypeFilter from './PostTypeFilter';

const ManageFeaturedVideos = () => {
	const [ postType, setPostType ] = useState( '' );
	const [ posts, setPosts ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalPosts, setTotalPosts ] = useState( 0 );
	const [ perPage, setPerPage ] = useState(
		window.rsfvTools?.perPage || 20
	);
	const [ search, setSearch ] = useState( '' );
	const [ searchInput, setSearchInput ] = useState( '' );
	const [ searchTimeout, setSearchTimeout ] = useState( null );

	const postTypes = window.rsfvTools?.postTypes || [];

	// Get current post type label.
	const currentPostTypeLabel = useMemo( () => {
		const found = postTypes.find( ( pt ) => pt.value === postType );
		return found ? found.label : __( 'posts', 'rsfv' );
	}, [ postTypes, postType ] );

	// Set initial post type.
	useEffect( () => {
		if ( postTypes.length > 0 && ! postType ) {
			setPostType( postTypes[ 0 ].value );
		}
	}, [ postTypes, postType ] );

	const fetchPosts = useCallback( async () => {
		if ( ! postType ) {
			return;
		}

		setLoading( true );

		try {
			let path = `/rsfv/v1/posts?post_type=${ postType }&page=${ page }&per_page=${ perPage }`;
			if ( search ) {
				path += `&search=${ encodeURIComponent( search ) }`;
			}

			const response = await apiFetch( {
				path,
				parse: false,
			} );

			const data = await response.json();
			const total = parseInt(
				response.headers.get( 'X-WP-Total' ),
				10
			);
			const pages = parseInt(
				response.headers.get( 'X-WP-TotalPages' ),
				10
			);

			setPosts( data );
			setTotalPosts( total );
			setTotalPages( pages );
		} catch ( error ) {
			console.error( 'Error fetching posts:', error );
			setPosts( [] );
		} finally {
			setLoading( false );
		}
	}, [ postType, page, perPage, search ] );

	useEffect( () => {
		fetchPosts();
	}, [ fetchPosts ] );

	const handlePostTypeChange = ( newPostType ) => {
		setPostType( newPostType );
		setPage( 1 );
		setSearch( '' );
		setSearchInput( '' );
	};

	const handlePageChange = ( newPage ) => {
		setPage( newPage );
	};

	const handlePerPageChange = ( newPerPage ) => {
		setPerPage( newPerPage );
		setPage( 1 );
	};

	const handleSearchInputChange = ( value ) => {
		setSearchInput( value );

		// Clear any existing timeout.
		if ( searchTimeout ) {
			clearTimeout( searchTimeout );
		}

		// If cleared or 3+ characters, trigger search with debounce.
		if ( value === '' ) {
			setSearch( '' );
			setPage( 1 );
		} else if ( value.length >= 3 ) {
			const timeout = setTimeout( () => {
				setSearch( value );
				setPage( 1 );
			}, 300 );
			setSearchTimeout( timeout );
		}
	};

	const handleSearchSubmit = ( e ) => {
		e.preventDefault();
		if ( searchTimeout ) {
			clearTimeout( searchTimeout );
		}
		setSearch( searchInput );
		setPage( 1 );
	};

	return (
		<div className="rsfv-manage-videos">
			<div className="rsfv-toolbar">
				<PostTypeFilter
					postTypes={ postTypes }
					selectedPostType={ postType }
					onChange={ handlePostTypeChange }
				/>

				<div className="rsfv-per-page">
					<label htmlFor="rsfv-per-page">
						{ __( 'Per page:', 'rsfv' ) }
					</label>
					<select
						id="rsfv-per-page"
						value={ perPage }
						onChange={ ( e ) =>
							handlePerPageChange( parseInt( e.target.value, 10 ) )
						}
					>
						<option value="10">10</option>
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
				</div>

				<form className="rsfv-search" onSubmit={ handleSearchSubmit }>
					{ loading && search && (
						<span className="rsfv-search-spinner spinner is-active"></span>
					) }
					<input
						type="search"
						className="rsfv-search-input"
						placeholder={ sprintf(
							/* translators: %s: post type name */
							__( 'Search %s...', 'rsfv' ),
							currentPostTypeLabel
						) }
						value={ searchInput }
						onChange={ ( e ) => handleSearchInputChange( e.target.value ) }
					/>
				</form>
			</div>

			{ loading ? (
				<div className="rsfv-loading">
					<span className="spinner is-active"></span>
					<span>{ __( 'Loading posts...', 'rsfv' ) }</span>
				</div>
			) : (
				<>
					<PostsTable
						posts={ posts }
						onRefresh={ fetchPosts }
					/>

					{ totalPages > 1 && (
						<Pagination
							currentPage={ page }
							totalPages={ totalPages }
							totalItems={ totalPosts }
							onPageChange={ handlePageChange }
						/>
					) }
				</>
			) }
		</div>
	);
};

export default ManageFeaturedVideos;
