/**
 * Image picker for image and images variables.
 *
 * @package RSFV
 */

import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BaseControl, Button } from '@wordpress/components';
import { getMedia, rememberMedia } from '../payload';

const MediaField = ( { label, help, value, multiple, max, onChange } ) => {
	const ids = ( multiple ? value || [] : [ value ] ).filter( Boolean );
	const [ items, setItems ] = useState( [] );

	useEffect( () => {
		let live = true;
		Promise.all( ids.map( getMedia ) ).then( ( list ) => {
			if ( live ) {
				setItems( list.filter( Boolean ) );
			}
		} );
		return () => {
			live = false;
		};
	}, [ JSON.stringify( ids ) ] );

	const open = () => {
		const frame = window.wp.media( {
			title: label,
			library: { type: 'image' },
			multiple: multiple ? 'add' : false,
			button: { text: __( 'Use these', 'rsfv' ) },
		} );
		frame.on( 'open', () => {
			const selection = frame.state().get( 'selection' );
			ids.forEach( ( id ) => {
				const attachment = window.wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		} );
		frame.on( 'select', () => {
			const picked = frame
				.state()
				.get( 'selection' )
				.toJSON()
				.map( ( a ) => {
					const media = {
						id: a.id,
						url: a.url,
						thumb:
							( a.sizes &&
								a.sizes.medium &&
								a.sizes.medium.url ) ||
							a.url,
					};
					rememberMedia( media );
					return a.id;
				} );
			if ( multiple ) {
				onChange( picked.slice( 0, max || 12 ) );
			} else {
				onChange( picked[ 0 ] || 0 );
			}
		} );
		frame.open();
	};

	const remove = ( id ) => {
		if ( multiple ) {
			onChange( ids.filter( ( item ) => item !== id ) );
		} else {
			onChange( 0 );
		}
	};

	return (
		<BaseControl label={ label } help={ help } __nextHasNoMarginBottom>
			<div className="rsfv-studio-media">
				{ items.map( ( item ) => (
					<div className="rsfv-studio-media__item" key={ item.id }>
						<img src={ item.thumb } alt="" />
						<Button
							className="rsfv-studio-media__remove"
							icon="no-alt"
							size="small"
							label={ __( 'Remove image', 'rsfv' ) }
							onClick={ () => remove( item.id ) }
						/>
					</div>
				) ) }
			</div>
			<Button variant="secondary" onClick={ open }>
				{ multiple &&
					( ids.length
						? __( 'Change images', 'rsfv' )
						: __( 'Choose images', 'rsfv' ) ) }
				{ ! multiple &&
					( ids.length
						? __( 'Change image', 'rsfv' )
						: __( 'Choose image', 'rsfv' ) ) }
			</Button>
			{ multiple && max ? (
				<p className="rsfv-studio-muted">
					{ sprintf(
						/* translators: %d: maximum number of images. */
						__( 'Up to %d images.', 'rsfv' ),
						max
					) }
				</p>
			) : null }
		</BaseControl>
	);
};

export default MediaField;
