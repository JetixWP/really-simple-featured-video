/**
 * Floating Video Form Component
 *
 * Handles add / edit of a single floating video with video source and display conditions.
 *
 * @package RSFV
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const FloatingVideoForm = ( { video, onSave, onCancel, saving } ) => {
	const isEditing = !! video;

	const [ title, setTitle ] = useState( video?.title || '' );
	const [ videoSource, setVideoSource ] = useState( video?.video_source || 'self' );
	const [ videoId, setVideoId ] = useState( video?.video_id || 0 );
	const [ videoUrl, setVideoUrl ] = useState( video?.video_url || '' );
	const [ embedUrl, setEmbedUrl ] = useState( video?.embed_url || '' );
	const [ displayType, setDisplayType ] = useState( video?.display_type || 'sitewide' );
	const [ pageIds, setPageIds ] = useState( video?.page_ids || [] );
	const [ targetPostTypes, setTargetPostTypes ] = useState( video?.target_post_types || [] );
	const [ targetTaxonomies, setTargetTaxonomies ] = useState( video?.target_taxonomies || [] );
	const [ status, setStatus ] = useState( video?.status || 'publish' );

	// Page search state.
	const [ pageSearch, setPageSearch ] = useState( '' );
	const [ pageResults, setPageResults ] = useState( [] );
	const [ searchingPages, setSearchingPages ] = useState( false );

	// Term search state.
	const [ termSearch, setTermSearch ] = useState( '' );
	const [ termResults, setTermResults ] = useState( [] );
	const [ searchingTerms, setSearchingTerms ] = useState( false );

	// Build a flat list of initially-selected terms for display labels.
	const buildInitialSelectedTerms = () => {
		if ( ! video?.target_taxonomies ) return [];
		const items = [];
		const allTax = window.rsfvTools?.taxonomies || [];
		video.target_taxonomies.forEach( ( td ) => {
			const taxInfo = allTax.find( ( t ) => t.name === td.taxonomy );
			( td.terms || [] ).forEach( ( termId ) => {
				const termInfo = taxInfo?.terms?.find( ( t ) => t.value === termId );
				const taxObj = taxInfo ? taxInfo.label : td.taxonomy;
				items.push( {
					value: termId,
					taxonomy: td.taxonomy,
					label: termInfo ? `${ termInfo.label } (${ taxObj })` : `#${ termId } (${ taxObj })`,
				} );
			} );
		} );
		return items;
	};

	const [ selectedTerms, setSelectedTerms ] = useState( buildInitialSelectedTerms );

	// Available data from localized script.
	const allPostTypes = window.rsfvTools?.postTypesAll || [];
	const allTaxonomies = window.rsfvTools?.taxonomies || [];
	const allPages = window.rsfvTools?.pages || [];

	/**
	 * Open the WP Media uploader.
	 */
	const openMediaUploader = () => {
		const frame = wp.media( {
			title: __( 'Select Video', 'rsfv' ),
			button: { text: __( 'Use this video', 'rsfv' ) },
			library: { type: 'video' },
			multiple: false,
		} );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			setVideoId( attachment.id );
			setVideoUrl( attachment.url );
		} );

		frame.open();
	};

	/**
	 * Remove the selected self-hosted video.
	 */
	const removeVideo = () => {
		setVideoId( 0 );
		setVideoUrl( '' );
	};

	/**
	 * Search for pages via REST API.
	 *
	 * @param {string} term Search term.
	 */
	const searchPages = useCallback(
		async ( term ) => {
			if ( ! term || term.length < 2 ) {
				setPageResults( [] );
				return;
			}

			setSearchingPages( true );
			try {
				const results = await apiFetch( {
					path: `/rsfv/v1/floating-videos/search-pages?search=${ encodeURIComponent( term ) }`,
				} );
				setPageResults( results );
			} catch {
				setPageResults( [] );
			} finally {
				setSearchingPages( false );
			}
		},
		[]
	);

	useEffect( () => {
		const timeout = setTimeout( () => searchPages( pageSearch ), 300 );
		return () => clearTimeout( timeout );
	}, [ pageSearch, searchPages ] );

	/**
	 * Add a page to the selected pages list.
	 *
	 * @param {number} id    Page ID.
	 * @param {string} label Page label.
	 */
	const addPage = ( id, label ) => {
		if ( ! pageIds.includes( id ) ) {
			setPageIds( [ ...pageIds, id ] );
		}
		setPageSearch( '' );
		setPageResults( [] );
	};

	/**
	 * Remove a page from the selected pages list.
	 *
	 * @param {number} id Page ID to remove.
	 */
	const removePage = ( id ) => {
		setPageIds( pageIds.filter( ( pid ) => pid !== id ) );
	};

	/**
	 * Search for taxonomy terms via REST API.
	 *
	 * @param {string} term Search term.
	 */
	const searchTerms = useCallback(
		async ( term ) => {
			if ( ! term || term.length < 2 ) {
				setTermResults( [] );
				return;
			}

			setSearchingTerms( true );
			try {
				const results = await apiFetch( {
					path: `/rsfv/v1/floating-videos/search-terms?search=${ encodeURIComponent( term ) }`,
				} );
				setTermResults( results );
			} catch {
				setTermResults( [] );
			} finally {
				setSearchingTerms( false );
			}
		},
		[]
	);

	useEffect( () => {
		const timeout = setTimeout( () => searchTerms( termSearch ), 300 );
		return () => clearTimeout( timeout );
	}, [ termSearch, searchTerms ] );

	/**
	 * Add a taxonomy term to the selection.
	 *
	 * @param {Object} termItem Term object with value, label, taxonomy.
	 */
	const addTerm = ( termItem ) => {
		// Check if already selected.
		const alreadySelected = selectedTerms.some(
			( t ) => t.value === termItem.value && t.taxonomy === termItem.taxonomy
		);

		if ( ! alreadySelected ) {
			setSelectedTerms( [ ...selectedTerms, termItem ] );

			// Update targetTaxonomies structure.
			setTargetTaxonomies( ( prev ) => {
				const existing = prev.find( ( t ) => t.taxonomy === termItem.taxonomy );
				if ( existing ) {
					return prev.map( ( t ) =>
						t.taxonomy === termItem.taxonomy
							? { ...t, terms: [ ...t.terms, termItem.value ] }
							: t
					);
				}
				return [ ...prev, { taxonomy: termItem.taxonomy, terms: [ termItem.value ] } ];
			} );
		}

		setTermSearch( '' );
		setTermResults( [] );
	};

	/**
	 * Remove a taxonomy term from the selection.
	 *
	 * @param {number} termId   Term ID.
	 * @param {string} taxonomy Taxonomy slug.
	 */
	const removeTerm = ( termId, taxonomy ) => {
		setSelectedTerms( ( prev ) =>
			prev.filter( ( t ) => ! ( t.value === termId && t.taxonomy === taxonomy ) )
		);

		setTargetTaxonomies( ( prev ) => {
			const updated = prev.map( ( t ) => {
				if ( t.taxonomy === taxonomy ) {
					const newTerms = t.terms.filter( ( id ) => id !== termId );
					return { ...t, terms: newTerms };
				}
				return t;
			} ).filter( ( t ) => t.terms.length > 0 );
			return updated;
		} );
	};

	/**
	 * Check if a taxonomy term is already selected.
	 *
	 * @param {number} termId   Term ID.
	 * @param {string} taxonomy Taxonomy slug.
	 * @return {boolean}
	 */
	const isTermAlreadySelected = ( termId, taxonomy ) => {
		return selectedTerms.some( ( t ) => t.value === termId && t.taxonomy === taxonomy );
	};

	/**
	 * Toggle a post type in the target list.
	 *
	 * @param {string} pt Post type slug.
	 */
	const togglePostType = ( pt ) => {
		setTargetPostTypes( ( prev ) =>
			prev.includes( pt ) ? prev.filter( ( p ) => p !== pt ) : [ ...prev, pt ]
		);
	};

	/**
	 * Get page label by ID.
	 *
	 * @param {number} id Page ID.
	 * @return {string} Label.
	 */
	const getPageLabel = ( id ) => {
		const page = allPages.find( ( p ) => p.value === id );
		return page ? page.label : `#${ id }`;
	};

	/**
	 * Handle form submit.
	 *
	 * @param {Event} e Form event.
	 */
	const handleSubmit = ( e ) => {
		e.preventDefault();

		const formData = {
			title,
			status,
			video_source: videoSource,
			video_id: videoSource === 'self' ? videoId : 0,
			embed_url: videoSource === 'embed' ? embedUrl : '',
			display_type: displayType,
			page_ids: displayType === 'specific_pages' ? pageIds : [],
			target_post_types: displayType === 'post_types' ? targetPostTypes : [],
			target_taxonomies: displayType === 'taxonomies' ? targetTaxonomies : [],
		};

		onSave( formData );
	};

	/**
	 * Check if the form is valid for submission.
	 *
	 * @return {boolean}
	 */
	const isValid = () => {
		if ( ! title.trim() ) return false;
		if ( videoSource === 'self' && ! videoId ) return false;
		if ( videoSource === 'embed' && ! embedUrl.trim() ) return false;
		return true;
	};

	return (
		<div className="rsfv-fv-form-wrap">
			<div className="rsfv-fv-form-header">
				<h2>
					{ isEditing
						? __( 'Edit Floating Video', 'rsfv' )
						: __( 'Add New Floating Video', 'rsfv' ) }
				</h2>
				<button className="button" onClick={ onCancel }>
					{ __( '← Back to List', 'rsfv' ) }
				</button>
			</div>

			<form className="rsfv-fv-form" onSubmit={ handleSubmit }>
				{/* Title */}
				<div className="rsfv-fv-field">
					<label htmlFor="rsfv-fv-title">{ __( 'Title', 'rsfv' ) }</label>
					<input
						id="rsfv-fv-title"
						type="text"
						className="regular-text"
						value={ title }
						onChange={ ( e ) => setTitle( e.target.value ) }
						placeholder={ __( 'Enter a name for this floating video', 'rsfv' ) }
						required
					/>
				</div>

				{/* Status */}
				<div className="rsfv-fv-field">
					<label htmlFor="rsfv-fv-status">{ __( 'Status', 'rsfv' ) }</label>
					<select
						id="rsfv-fv-status"
						value={ status }
						onChange={ ( e ) => setStatus( e.target.value ) }
					>
						<option value="publish">{ __( 'Active', 'rsfv' ) }</option>
						<option value="draft">{ __( 'Draft', 'rsfv' ) }</option>
					</select>
				</div>

				{/* Video Source */}
				<fieldset className="rsfv-fv-fieldset">
					<legend>{ __( 'Video', 'rsfv' ) }</legend>

					<div className="rsfv-fv-field">
						<label htmlFor="rsfv-fv-source">{ __( 'Video Source', 'rsfv' ) }</label>
						<select
							id="rsfv-fv-source"
							value={ videoSource }
							onChange={ ( e ) => setVideoSource( e.target.value ) }
						>
							<option value="self">{ __( 'Self-hosted (Upload)', 'rsfv' ) }</option>
							<option value="embed">{ __( 'Embed (URL)', 'rsfv' ) }</option>
						</select>
					</div>

					{ videoSource === 'self' && (
						<div className="rsfv-fv-field">
							<label>{ __( 'Video File', 'rsfv' ) }</label>
							{ videoId && videoUrl ? (
								<div className="rsfv-fv-video-preview">
									<video src={ videoUrl } style={ { maxWidth: '320px', maxHeight: '180px' } } controls />
									<div className="rsfv-fv-video-actions">
										<button type="button" className="button" onClick={ openMediaUploader }>
											{ __( 'Change Video', 'rsfv' ) }
										</button>
										<button type="button" className="button button-link-delete" onClick={ removeVideo }>
											{ __( 'Remove', 'rsfv' ) }
										</button>
									</div>
								</div>
							) : (
								<button type="button" className="button" onClick={ openMediaUploader }>
									{ __( 'Select Video', 'rsfv' ) }
								</button>
							) }
						</div>
					) }

					{ videoSource === 'embed' && (
						<div className="rsfv-fv-field">
							<label htmlFor="rsfv-fv-embed-url">{ __( 'Embed URL', 'rsfv' ) }</label>
							<input
								id="rsfv-fv-embed-url"
								type="url"
								className="regular-text"
								value={ embedUrl }
								onChange={ ( e ) => setEmbedUrl( e.target.value ) }
								placeholder="https://www.youtube.com/watch?v=..."
							/>
							<p className="description">
								{ __( 'Paste a YouTube, Vimeo, or other supported video URL.', 'rsfv' ) }
							</p>
						</div>
					) }
				</fieldset>

				{/* Display Conditions */}
				<fieldset className="rsfv-fv-fieldset">
					<legend>{ __( 'Display Conditions', 'rsfv' ) }</legend>

					<div className="rsfv-fv-field">
						<label htmlFor="rsfv-fv-display-type">{ __( 'Show On', 'rsfv' ) }</label>
						<select
							id="rsfv-fv-display-type"
							value={ displayType }
							onChange={ ( e ) => setDisplayType( e.target.value ) }
						>
							<option value="sitewide">{ __( 'Sitewide (All Pages)', 'rsfv' ) }</option>
							<option value="specific_pages">{ __( 'Specific Posts & Pages (All CPTs)', 'rsfv' ) }</option>
							<option value="post_types">{ __( 'Post Type (Singles & Archives)', 'rsfv' ) }</option>
							<option value="taxonomies">{ __( 'Taxonomy Terms', 'rsfv' ) }</option>
						</select>
					</div>

					{/* Specific Pages */}
					{ displayType === 'specific_pages' && (
						<div className="rsfv-fv-field">
							<label>{ __( 'Select Posts & Pages', 'rsfv' ) }</label>

							{ pageIds.length > 0 && (
								<div className="rsfv-fv-tags">
									{ pageIds.map( ( id ) => (
										<span key={ id } className="rsfv-fv-tag">
											{ getPageLabel( id ) }
											<button
												type="button"
												className="rsfv-fv-tag-remove"
												onClick={ () => removePage( id ) }
											>
												×
											</button>
										</span>
									) ) }
								</div>
							) }

							<div className="rsfv-fv-page-search">
								<input
									type="text"
									className="regular-text"
									value={ pageSearch }
									onChange={ ( e ) => setPageSearch( e.target.value ) }
										placeholder={ __( 'Search posts, pages & custom post types...', 'rsfv' ) }
								/>
								{ searchingPages && <span className="spinner is-active"></span> }

								{ pageResults.length > 0 && (
									<ul className="rsfv-fv-search-results">
										{ pageResults.map( ( result ) => (
											<li key={ result.value }>
												<button
													type="button"
													onClick={ () => addPage( result.value, result.label ) }
													disabled={ pageIds.includes( result.value ) }
												>
													{ result.label }
													{ pageIds.includes( result.value ) && ' ✓' }
												</button>
											</li>
										) ) }
									</ul>
								) }
							</div>
						</div>
					) }

					{/* Post Types */}
					{ displayType === 'post_types' && (
						<div className="rsfv-fv-field">
							<label>{ __( 'Select Post Types', 'rsfv' ) }</label>
							<div className="rsfv-fv-checkbox-group">
								{ allPostTypes.map( ( pt ) => (
									<label key={ pt.value } className="rsfv-fv-checkbox-label">
										<input
											type="checkbox"
											checked={ targetPostTypes.includes( pt.value ) }
											onChange={ () => togglePostType( pt.value ) }
										/>
										{ pt.label }
									</label>
								) ) }
							</div>
							<p className="description">
								{ __( 'The floating video will appear on single and archive pages of selected post types.', 'rsfv' ) }
							</p>
						</div>
					) }

					{/* Taxonomies */}
					{ displayType === 'taxonomies' && (
						<div className="rsfv-fv-field">
							<label>{ __( 'Select Taxonomy Terms', 'rsfv' ) }</label>

							{ selectedTerms.length > 0 && (
								<div className="rsfv-fv-tags">
									{ selectedTerms.map( ( term ) => (
										<span key={ `${ term.taxonomy }-${ term.value }` } className="rsfv-fv-tag">
											{ term.label }
											<button
												type="button"
												className="rsfv-fv-tag-remove"
												onClick={ () => removeTerm( term.value, term.taxonomy ) }
											>
												×
											</button>
										</span>
									) ) }
								</div>
							) }

							<div className="rsfv-fv-page-search">
								<input
									type="text"
									className="regular-text"
									value={ termSearch }
									onChange={ ( e ) => setTermSearch( e.target.value ) }
									placeholder={ __( 'Search taxonomy terms...', 'rsfv' ) }
								/>
								{ searchingTerms && <span className="spinner is-active"></span> }

								{ termResults.length > 0 && (
									<ul className="rsfv-fv-search-results">
										{ termResults.map( ( result ) => (
											<li key={ `${ result.taxonomy }-${ result.value }` }>
												<button
													type="button"
													onClick={ () => addTerm( result ) }
													disabled={ isTermAlreadySelected( result.value, result.taxonomy ) }
												>
													{ result.label }
													{ isTermAlreadySelected( result.value, result.taxonomy ) && ' ✓' }
												</button>
											</li>
										) ) }
									</ul>
								) }
							</div>

							<p className="description">
								{ __( 'The floating video will appear on archive pages and single posts belonging to the selected terms.', 'rsfv' ) }
							</p>
						</div>
					) }
				</fieldset>

				{/* Actions */}
				<div className="rsfv-fv-form-actions">
					<button
						type="submit"
						className="button button-primary button-large"
						disabled={ saving || ! isValid() }
					>
						{ saving
							? __( 'Saving...', 'rsfv' )
							: isEditing
								? __( 'Update Floating Video', 'rsfv' )
								: __( 'Create Floating Video', 'rsfv' ) }
					</button>
					<button type="button" className="button button-large" onClick={ onCancel }>
						{ __( 'Cancel', 'rsfv' ) }
					</button>
				</div>
			</form>
		</div>
	);
};

export default FloatingVideoForm;
