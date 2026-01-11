/**
 * Posts Table Component
 *
 * @package RSFV
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import VideoTypeSelect from './VideoTypeSelect';
import VideoAction from './VideoAction';
import VideoPreview from './VideoPreview';
import ThumbnailCell from './ThumbnailCell';
import { applyFilters, doAction } from '../hooks';

const PostsTable = ( { posts: initialPosts, onRefresh } ) => {
	const [ posts, setPosts ] = useState( initialPosts );

	// Get columns from config, allowing extensions to add more.
	const columns = useMemo( () => {
		const baseColumns = window.rsfvTools?.columns || {};
		return applyFilters( 'rsfv_tools_columns', baseColumns );
	}, [] );

	// Update posts when initialPosts changes.
	if ( initialPosts !== posts && initialPosts.length !== posts.length ) {
		setPosts( initialPosts );
	}

	if ( ! posts || posts.length === 0 ) {
		return (
			<div className="rsfv-no-posts">
				<p>{ __( 'No posts found for this post type.', 'rsfv' ) }</p>
			</div>
		);
	}

	const getVideoStatusBadge = ( post ) => {
		if ( post.has_video ) {
			return (
				<span className="rsfv-badge rsfv-badge-success">
					{ __( 'Has Video', 'rsfv' ) }
				</span>
			);
		}
		return (
			<span className="rsfv-badge rsfv-badge-default">
				{ __( 'No Video', 'rsfv' ) }
			</span>
		);
	};

	const handlePostUpdate = ( postId, updates ) => {
		setPosts( ( currentPosts ) =>
			currentPosts.map( ( post ) =>
				post.id === postId ? { ...post, ...updates } : post
			)
		);

		// Trigger action for extensions to listen to.
		doAction( 'rsfv_tools_post_updated', postId, updates );
	};

	/**
	 * Render cell content based on column key.
	 *
	 * @param {string} columnKey Column key.
	 * @param {Object} post      Post data.
	 * @return {JSX.Element|string} Cell content.
	 */
	const renderCellContent = ( columnKey, post ) => {
		// Allow extensions to override cell content.
		const customContent = applyFilters(
			'rsfv_tools_cell_content',
			null,
			columnKey,
			post,
			handlePostUpdate
		);

		if ( customContent !== null ) {
			return customContent;
		}

		// Default cell renderers.
		switch ( columnKey ) {
			case 'thumbnail':
				return (
					<ThumbnailCell post={ post } onUpdate={ handlePostUpdate } />
				);

			case 'title':
				return (
					<>
						<strong>
							<a
								href={ post.edit_link }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ post.title || __( '(No title)', 'rsfv' ) }
							</a>
						</strong>
						<div className="row-actions">
							<span className="edit">
								<a
									href={ post.edit_link }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Edit', 'rsfv' ) }
								</a>
							</span>
							{ ' | ' }
							<span className="view">
								<a
									href={ post.permalink }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'View', 'rsfv' ) }
								</a>
							</span>
						</div>
					</>
				);

			case 'status_type':
				return (
					<div className="rsfv-status-type">
						{ getVideoStatusBadge( post ) }
						<VideoTypeSelect
							post={ post }
							onUpdate={ handlePostUpdate }
						/>
					</div>
				);

			case 'video_action':
				return (
					<VideoAction post={ post } onUpdate={ handlePostUpdate } />
				);

			case 'video_preview':
				return <VideoPreview post={ post } />;

			default:
				// For unknown columns, check if post has data for it.
				return post[ columnKey ] || '';
		}
	};

	return (
		<table className="rsfv-posts-table wp-list-table widefat fixed striped">
			<thead>
				<tr>
					{ Object.entries( columns ).map( ( [ key, column ] ) => (
						<th key={ key } className={ column.class || '' }>
							{ column.label }
						</th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ posts.map( ( post ) => (
					<tr key={ post.id }>
						{ Object.entries( columns ).map( ( [ key, column ] ) => (
							<td key={ key } className={ column.class || '' }>
								{ renderCellContent( key, post ) }
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
		</table>
	);
};

export default PostsTable;
