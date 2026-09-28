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

// PRO templates by category. Varied use cases first: the promo card on
// All shows the first few.
export const proTemplates = () => [
	{ name: __( 'Recipe', 'rsfv' ), category: 'food' },
	{ name: __( 'Property listing', 'rsfv' ), category: 'business' },
	{ name: __( 'Course', 'rsfv' ), category: 'business' },
	{ name: __( 'Countdown', 'rsfv' ), category: 'text' },
	{ name: __( 'Coupon code', 'rsfv' ), category: 'product' },
	{ name: __( 'Sale', 'rsfv' ), category: 'product' },
	{ name: __( 'Top list', 'rsfv' ), category: 'blog' },
	{ name: __( 'Podcast episode', 'rsfv' ), category: 'blog' },
	{ name: __( 'Menu board', 'rsfv' ), category: 'food' },
	{ name: __( 'Visit us', 'rsfv' ), category: 'business' },
	{ name: __( 'Destination', 'rsfv' ), category: 'images' },
	{ name: __( 'Before and after', 'rsfv' ), category: 'images' },
	{ name: __( 'We’re hiring', 'rsfv' ), category: 'business' },
	{ name: __( 'App feature', 'rsfv' ), category: 'business' },
	{ name: __( 'Portfolio grid', 'rsfv' ), category: 'images' },
	{ name: __( 'Event', 'rsfv' ), category: 'text' },
	{ name: __( 'Stats counter', 'rsfv' ), category: 'text' },
	{ name: __( 'Product showcase', 'rsfv' ), category: 'product' },
	{ name: __( 'Lower third', 'rsfv' ), category: 'images' },
	{ name: __( 'Logo reveal', 'rsfv' ), category: 'brand' },
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
