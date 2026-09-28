/**
 * Template list.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink } from '@wordpress/components';

const DISMISS_KEY = 'rsfvStudioProCardDismissed';

const isDismissed = () => {
	try {
		return '1' === window.localStorage.getItem( DISMISS_KEY );
	} catch ( e ) {
		return false;
	}
};

const TemplatePicker = ( {
	templates,
	value,
	onChange,
	disabled,
	isPro,
	upgradeUrl,
} ) => {
	const [ hidden, setHidden ] = useState( isDismissed );

	const dismiss = () => {
		setHidden( true );
		try {
			window.localStorage.setItem( DISMISS_KEY, '1' );
		} catch ( e ) {
			// Private mode: just hide it for now.
		}
	};

	return (
		<div className="rsfv-studio-templates" role="radiogroup">
			{ templates.map( ( template ) => (
				<button
					type="button"
					role="radio"
					aria-checked={ value === template.id }
					key={ template.id }
					className={ `rsfv-studio-template${
						value === template.id ? ' is-selected' : ''
					}` }
					onClick={ () => onChange( template.id ) }
					disabled={ disabled }
				>
					<span className="rsfv-studio-template__title">
						{ template.title }
					</span>
					<span className="rsfv-studio-template__desc">
						{ template.description }
					</span>
				</button>
			) ) }

			{ ! isPro && ! hidden && (
				<div className="rsfv-studio-template rsfv-studio-template--pro">
					<span className="rsfv-studio-template__title">
						{ __( 'More in PRO', 'rsfv' ) }
					</span>
					<span className="rsfv-studio-template__desc">
						{ __(
							'More templates, vertical and square sizes, 4K, music and making videos for many products at once.',
							'rsfv'
						) }
					</span>
					<span className="rsfv-studio-template__links">
						<ExternalLink href={ upgradeUrl }>
							{ __( 'See PRO', 'rsfv' ) }
						</ExternalLink>
						<Button variant="link" onClick={ dismiss }>
							{ __( 'Hide', 'rsfv' ) }
						</Button>
					</span>
				</div>
			) }
		</div>
	);
};

export default TemplatePicker;
