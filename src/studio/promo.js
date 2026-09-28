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

// Varied use cases first: the promo card shows the first few.
export const proTemplateNames = () => [
	__( 'News headline', 'rsfv' ),
	__( 'Recipe', 'rsfv' ),
	__( 'Property listing', 'rsfv' ),
	__( 'Course', 'rsfv' ),
	__( 'Countdown', 'rsfv' ),
	__( 'Coupon code', 'rsfv' ),
	__( 'Sale', 'rsfv' ),
	__( 'Testimonial', 'rsfv' ),
	__( 'Top list', 'rsfv' ),
	__( 'Podcast episode', 'rsfv' ),
	__( 'Menu board', 'rsfv' ),
	__( 'Visit us', 'rsfv' ),
	__( 'Destination', 'rsfv' ),
	__( 'Before and after', 'rsfv' ),
	__( 'We’re hiring', 'rsfv' ),
	__( 'App feature', 'rsfv' ),
	__( 'Portfolio grid', 'rsfv' ),
	__( 'Stats counter', 'rsfv' ),
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
