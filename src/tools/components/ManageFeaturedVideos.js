/**
 * Manage Featured Videos Component
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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

	const postTypes = window.rsfvTools?.postTypes || [];

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
			const response = await apiFetch( {
				path: `/rsfv/v1/posts?post_type=${ postType }&page=${ page }&per_page=${ perPage }`,
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
	}, [ postType, page, perPage ] );

	useEffect( () => {
		fetchPosts();
	}, [ fetchPosts ] );

	const handlePostTypeChange = ( newPostType ) => {
		setPostType( newPostType );
		setPage( 1 );
	};

	const handlePageChange = ( newPage ) => {
		setPage( newPage );
	};

	const handlePerPageChange = ( newPerPage ) => {
		setPerPage( newPerPage );
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
						{ __( 'Posts per page:', 'rsfv' ) }
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
