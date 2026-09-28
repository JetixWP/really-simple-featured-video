/**
 * Template list.
 *
 * @package RSFV
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink } from '@wordpress/components';
import { proTemplateNames, proUrl } from '../promo';

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
				<div className="rsfv-studio-promo rsfv-studio-template--pro">
					<p>
						<strong>{ __( '8 more templates', 'rsfv' ) }</strong>
						<span className="rsfv-pro-tag">
							{ __( 'PRO', 'rsfv' ) }
						</span>
					</p>
					<div className="rsfv-studio-promo__chips">
						{ proTemplateNames().map( ( name ) => (
							<span key={ name }>{ name }</span>
						) ) }
					</div>
					<p>
						{ __(
							'Also vertical, square and 4K sizes, music and a brand kit.',
							'rsfv'
						) }
					</p>
					<span className="rsfv-studio-template__links">
						<ExternalLink
							href={ proUrl( upgradeUrl, 'studio-templates' ) }
						>
							{ __( 'Get PRO', 'rsfv' ) }
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
