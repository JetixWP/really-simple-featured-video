/**
 * What Video Studio PRO adds, for short promos in the free plugin.
 * Only labels live here; the features themselves are in the PRO plugin.
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

export const proPresetOptions = () => [
	{
		value: 'pro-portrait',
		label: __( '9:16, Stories, Reels, Shorts (PRO)', 'rsfv' ),
		disabled: true,
	},
	{
		value: 'pro-feed',
		label: __( '4:5, feed (PRO)', 'rsfv' ),
		disabled: true,
	},
	{
		value: 'pro-60',
		label: __( '16:9, 1080p at 60 fps (PRO)', 'rsfv' ),
		disabled: true,
	},
	{ value: 'pro-4k', label: __( '16:9, 4K (PRO)', 'rsfv' ), disabled: true },
];

export const proTemplateNames = () => [
	__( 'Sale', 'rsfv' ),
	__( 'Stats counter', 'rsfv' ),
	__( 'Testimonial', 'rsfv' ),
	__( 'Logo reveal', 'rsfv' ),
	__( 'Lower third', 'rsfv' ),
	__( 'Kinetic type', 'rsfv' ),
	__( 'Event', 'rsfv' ),
	__( 'Product showcase', 'rsfv' ),
];

/**
 * Link to PRO with a campaign tag.
 *
 * @param {string} url      Upgrade URL.
 * @param {string} campaign Campaign.
 * @return {string} URL.
 */
export const proUrl = ( url, campaign ) =>
	`${ (
		url ||
		'https://jetixwp.com/plugins/really-simple-featured-video/#pricing'
	).replace(
		/#.*$/,
		''
	) }?utm_campaign=${ campaign }&utm_source=rsfv-plugin#pricing`;
