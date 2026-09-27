/**
 * Post Type Filter Component
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const PostTypeFilter = ( {
	postTypes,
	selectedPostType,
	onChange,
	disabled = false,
} ) => {
	if ( ! postTypes || postTypes.length === 0 ) {
		return null;
	}

	return (
		<div className="rsfv-post-type-filter">
			<label htmlFor="rsfv-post-type-select">
				{ __( 'Post Type:', 'rsfv' ) }
			</label>
			<select
				id="rsfv-post-type-select"
				value={ selectedPostType }
				disabled={ disabled }
				onChange={ ( e ) => onChange( e.target.value ) }
			>
				{ postTypes.map( ( type ) => (
					<option key={ type.value } value={ type.value }>
						{ type.label }
					</option>
				) ) }
			</select>
		</div>
	);
};

export default PostTypeFilter;
