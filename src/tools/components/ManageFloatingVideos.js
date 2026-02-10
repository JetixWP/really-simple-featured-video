/**
 * Manage Floating Videos Component
 *
 * Provides full CRUD for floating videos with display conditions.
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import FloatingVideoForm from './FloatingVideoForm';

const ManageFloatingVideos = () => {
	const [ videos, setVideos ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ editing, setEditing ] = useState( null ); // null = list, 'new' = add, {object} = edit.
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	/**
	 * Fetch all floating videos from the REST API.
	 */
	const fetchVideos = useCallback( async () => {
		setLoading( true );
		try {
			const data = await apiFetch( { path: '/rsfv/v1/floating-videos' } );
			setVideos( data );
		} catch ( error ) {
			console.error( 'Error fetching floating videos:', error );
			setVideos( [] );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchVideos();
	}, [ fetchVideos ] );

	/**
	 * Show a temporary notice.
	 *
	 * @param {string} message Notice message.
	 * @param {string} type    Notice type: success or error.
	 */
	const showNotice = ( message, type = 'success' ) => {
		setNotice( { message, type } );
		setTimeout( () => setNotice( null ), 4000 );
	};

	/**
	 * Handle save (create or update).
	 *
	 * @param {Object} formData The floating video form data.
	 */
	const handleSave = async ( formData ) => {
		setSaving( true );
		try {
			if ( editing === 'new' ) {
				await apiFetch( {
					path: '/rsfv/v1/floating-videos',
					method: 'POST',
					data: formData,
				} );
				showNotice( __( 'Floating video created successfully.', 'rsfv' ) );
			} else {
				await apiFetch( {
					path: `/rsfv/v1/floating-videos/${ editing.id }`,
					method: 'PUT',
					data: formData,
				} );
				showNotice( __( 'Floating video updated successfully.', 'rsfv' ) );
			}

			setEditing( null );
			fetchVideos();
		} catch ( error ) {
			console.error( 'Save error:', error );
			showNotice( __( 'Error saving floating video.', 'rsfv' ), 'error' );
		} finally {
			setSaving( false );
		}
	};

	/**
	 * Handle delete.
	 *
	 * @param {number} id The floating video ID.
	 */
	const handleDelete = async ( id ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this floating video?', 'rsfv' ) ) ) {
			return;
		}

		try {
			await apiFetch( {
				path: `/rsfv/v1/floating-videos/${ id }`,
				method: 'DELETE',
			} );
			showNotice( __( 'Floating video deleted.', 'rsfv' ) );
			fetchVideos();
		} catch ( error ) {
			console.error( 'Delete error:', error );
			showNotice( __( 'Error deleting floating video.', 'rsfv' ), 'error' );
		}
	};

	/**
	 * Toggle status between publish and draft.
	 *
	 * @param {Object} video The floating video object.
	 */
	const handleToggleStatus = async ( video ) => {
		const newStatus = video.status === 'publish' ? 'draft' : 'publish';
		try {
			await apiFetch( {
				path: `/rsfv/v1/floating-videos/${ video.id }`,
				method: 'PUT',
				data: {
					...video,
					status: newStatus,
				},
			} );
			showNotice(
				newStatus === 'publish'
					? __( 'Floating video activated.', 'rsfv' )
					: __( 'Floating video deactivated.', 'rsfv' )
			);
			fetchVideos();
		} catch ( error ) {
			console.error( 'Toggle error:', error );
			showNotice( __( 'Error updating status.', 'rsfv' ), 'error' );
		}
	};

	/**
	 * Get human-readable display condition label.
	 *
	 * @param {Object} video The floating video object.
	 * @return {string} Label text.
	 */
	const getDisplayLabel = ( video ) => {
		switch ( video.display_type ) {
			case 'sitewide':
				return __( 'Sitewide', 'rsfv' );
			case 'specific_pages':
				const count = video.page_ids?.length || 0;
				return count === 1
					? __( '1 specific post/page', 'rsfv' )
					: `${ count } ${ __( 'specific posts/pages', 'rsfv' ) }`;
			case 'post_types':
				return ( video.target_post_types || [] ).join( ', ' ) || __( 'Post Types', 'rsfv' );
			case 'taxonomies':
				return __( 'Taxonomy terms', 'rsfv' );
			default:
				return '—';
		}
	};

	/**
	 * Get video source label.
	 *
	 * @param {Object} video The floating video object.
	 * @return {string} Label text.
	 */
	const getSourceLabel = ( video ) => {
		return video.video_source === 'embed'
			? __( 'Embed', 'rsfv' )
			: __( 'Self-hosted', 'rsfv' );
	};

	// If editing, show the form.
	if ( editing !== null ) {
		return (
			<div className="rsfv-floating-videos">
				{ notice && (
					<div className={ `notice notice-${ notice.type } is-dismissible rsfv-fv-notice` }>
						<p>{ notice.message }</p>
					</div>
				) }
				<FloatingVideoForm
					video={ editing === 'new' ? null : editing }
					onSave={ handleSave }
					onCancel={ () => setEditing( null ) }
					saving={ saving }
				/>
			</div>
		);
	}

	return (
		<div className="rsfv-floating-videos">
			{ notice && (
				<div className={ `notice notice-${ notice.type } is-dismissible rsfv-fv-notice` }>
					<p>{ notice.message }</p>
				</div>
			) }

			<div className="rsfv-fv-header">
				<h2>{ __( 'Floating Videos', 'rsfv' ) }</h2>
				<button
					className="button button-primary"
					onClick={ () => setEditing( 'new' ) }
				>
					{ __( '+ Add New Floating Video', 'rsfv' ) }
				</button>
			</div>

			{ loading ? (
				<div className="rsfv-loading">
					<span className="spinner is-active"></span>
					<span>{ __( 'Loading floating videos...', 'rsfv' ) }</span>
				</div>
			) : videos.length === 0 ? (
				<div className="rsfv-fv-empty">
					<div className="rsfv-fv-empty-icon">🎬</div>
					<h3>{ __( 'No floating videos yet', 'rsfv' ) }</h3>
					<p>{ __( 'Add a floating video to display a play button on your site that opens a video popup when clicked.', 'rsfv' ) }</p>
					<button
						className="button button-primary"
						onClick={ () => setEditing( 'new' ) }
					>
						{ __( 'Create Your First Floating Video', 'rsfv' ) }
					</button>
				</div>
			) : (
				<table className="wp-list-table widefat fixed striped rsfv-fv-table">
					<thead>
						<tr>
							<th className="column-title">{ __( 'Title', 'rsfv' ) }</th>
							<th className="column-source">{ __( 'Video Source', 'rsfv' ) }</th>
							<th className="column-display">{ __( 'Display On', 'rsfv' ) }</th>
							<th className="column-status">{ __( 'Status', 'rsfv' ) }</th>
							<th className="column-actions">{ __( 'Actions', 'rsfv' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ videos.map( ( video ) => (
							<tr key={ video.id }>
								<td className="column-title">
									<strong>
										<a
											href="#"
											onClick={ ( e ) => {
												e.preventDefault();
												setEditing( video );
											} }
										>
											{ video.title || __( '(no title)', 'rsfv' ) }
										</a>
									</strong>
								</td>
								<td className="column-source">
									{ getSourceLabel( video ) }
								</td>
								<td className="column-display">
									{ getDisplayLabel( video ) }
								</td>
								<td className="column-status">
									<span className={ `rsfv-fv-status rsfv-fv-status--${ video.status }` }>
										{ video.status === 'publish' ? __( 'Active', 'rsfv' ) : __( 'Draft', 'rsfv' ) }
									</span>
								</td>
								<td className="column-actions">
									<button
										className="button button-small"
										onClick={ () => setEditing( video ) }
									>
										{ __( 'Edit', 'rsfv' ) }
									</button>
									{ ' ' }
									<button
										className="button button-small"
										onClick={ () => handleToggleStatus( video ) }
									>
										{ video.status === 'publish' ? __( 'Deactivate', 'rsfv' ) : __( 'Activate', 'rsfv' ) }
									</button>
									{ ' ' }
									<button
										className="button button-small button-link-delete"
										onClick={ () => handleDelete( video.id ) }
									>
										{ __( 'Delete', 'rsfv' ) }
									</button>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default ManageFloatingVideos;
