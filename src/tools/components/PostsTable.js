/**
 * Posts Table Component
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import VideoActionCell from './VideoActionCell';

const PostsTable = ( { posts: initialPosts, onRefresh } ) => {
	const [ posts, setPosts ] = useState( initialPosts );

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
	};

	return (
		<table className="rsfv-posts-table wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th className="column-thumbnail">
						{ __( 'Thumbnail', 'rsfv' ) }
					</th>
					<th className="column-title">{ __( 'Title', 'rsfv' ) }</th>
					<th className="column-status">
						{ __( 'Video Status', 'rsfv' ) }
					</th>
					<th className="column-video-action">
						{ __( 'Video Type & Action', 'rsfv' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ posts.map( ( post ) => (
					<tr key={ post.id }>
						<td className="column-thumbnail">
							{ post.thumbnail ? (
								<img
									src={ post.thumbnail }
									alt={ post.title }
									className="rsfv-thumbnail"
								/>
							) : (
								<div className="rsfv-no-thumbnail">
									<span className="dashicons dashicons-format-image"></span>
								</div>
							) }
						</td>
						<td className="column-title">
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
						</td>
						<td className="column-status">
							{ getVideoStatusBadge( post ) }
						</td>
						<td className="column-video-action">
							<VideoActionCell
								post={ post }
								onUpdate={ handlePostUpdate }
							/>
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
};

export default PostsTable;
