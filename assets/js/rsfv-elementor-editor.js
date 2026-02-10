/**
 * RSFV Elementor Editor – populate widget controls on first add.
 *
 * When the user drags the RSFV Video widget into the editor for the first
 * time, it is created client-side with empty defaults. The server-side
 * `get_post_metadata` prefill only works for widgets already saved in
 * `_elementor_data`.
 *
 * This script hooks `panel/open_editor/widget/rsfv_video`. When controls
 * are empty it uses `setExternalChange()` which fires per-key
 * `change:external:<key>` events on the settings model — exactly what
 * Elementor control views listen for in their `onAfterExternalChange()`
 * method — then re-opens the panel editor to fully rebuild the control
 * views with updated conditions.
 *
 * @package RSFV
 * @since   0.73.0
 */

( function () {
	'use strict';

	// Track widget IDs we have already prefilled.
	var filled = {};

	/**
	 * Build settings from the localised RSFV meta.
	 */
	function buildDefaults( meta ) {
		if ( ! meta || ! meta.source ) {
			return null;
		}

		var d = { video_type: meta.source };

		if ( meta.source === 'self' && meta.video_id ) {
			d.self_video = {
				id:  parseInt( meta.video_id, 10 ),
				url: meta.video_url || '',
			};
			if ( meta.poster_id ) {
				d.poster_image = {
					id:  parseInt( meta.poster_id, 10 ),
					url: meta.poster_url || '',
				};
			}
		} else if ( meta.source === 'embed' && meta.embed_url ) {
			d.embed_url = { url: meta.embed_url };
		}

		return d;
	}

	/**
	 * Does the widget already have explicit video values?
	 */
	function hasValues( sm ) {
		var sv = sm.get( 'self_video' ) || {};
		var eu = sm.get( 'embed_url' )  || {};
		return !! ( sv.url || sv.id || eu.url );
	}

	// ── Panel open handler ───────────────────────────────────────────

	function onPanelOpen( panel, model, view ) {
		var meta     = ( typeof rsfvElementorMeta !== 'undefined' ) ? rsfvElementorMeta : null;
		var defaults = buildDefaults( meta );

		if ( ! defaults ) {
			return;
		}

		var id = model.get( 'id' ) || model.cid;

		if ( filled[ id ] ) {
			return;
		}

		var sm = model.get( 'settings' );

		var src = sm.get( 'video_source' );
		if ( src && src !== 'current_post' ) {
			return;
		}

		if ( hasValues( sm ) ) {
			filled[ id ] = true;
			return;
		}

		// Mark before async work.
		filled[ id ] = true;

		// Use setExternalChange to update the model. This fires
		// `change:external:<key>` events per key on the settings model,
		// which is exactly what Elementor's BaseData control views listen
		// for via `onAfterExternalChange()` → `applySavedValue()`.
		if ( typeof sm.setExternalChange === 'function' ) {
			sm.setExternalChange( defaults );
		} else {
			sm.set( defaults );
		}

		// The conditions for controls like `self_video` and `embed_url`
		// depend on the `video_type` value. When that just changed,
		// the panel needs to re-render so the correct conditional
		// controls become visible with their new values.
		// Re-opening the editor panel rebuilds all control views.
		// The `filled` guard prevents the re-triggered hook from looping.
		setTimeout( function () {
			try {
				$e.run( 'panel/editor/open', {
					model: model,
					view: view,
				} );
			} catch ( e ) {
				// Fallback: try manual panel refresh.
				try {
					var c = view.getContainer ? view.getContainer() : null;
					if ( c && c.panel ) {
						c.panel.refresh();
					}
				} catch ( e2 ) {
					// Swallow.
				}
			}
		}, 50 );
	}

	// ── Init ─────────────────────────────────────────────────────────

	jQuery( window ).on( 'elementor:init', function () {
		elementor.hooks.addAction(
			'panel/open_editor/widget/rsfv_video',
			onPanelOpen
		);
	} );
}() );
