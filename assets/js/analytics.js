/**
 * Really Simple Featured Video — anonymous view and play counts.
 *
 * Views: player is at least half on screen, once per tab session.
 * Plays: a real click or keypress. Hover autoplay does not count.
 *
 * @package RSFV
 */
( function () {
	'use strict';

	if ( typeof rsfvAnalytics === 'undefined' || ! rsfvAnalytics.endpoint ) {
		return;
	}

	var endpoint = rsfvAnalytics.endpoint;
	var queue = [];
	var timer = null;

	/**
	 * Session key for one video, event, and surface.
	 *
	 * @param {number} videoId Registry id.
	 * @param {string} eventName view or play.
	 * @param {string} surface Surface key.
	 * @return {string}
	 */
	function storageKey( videoId, eventName, surface ) {
		return 'rsfv:' + videoId + ':' + eventName + ':' + surface;
	}

	/**
	 * Whether this tab already counted the event.
	 *
	 * @param {number} videoId Registry id.
	 * @param {string} eventName view or play.
	 * @param {string} surface Surface key.
	 * @return {boolean}
	 */
	function already( videoId, eventName, surface ) {
		try {
			return window.sessionStorage.getItem( storageKey( videoId, eventName, surface ) ) === '1';
		} catch ( error ) {
			return false;
		}
	}

	/**
	 * Remember a counted event for this tab session.
	 *
	 * @param {number} videoId Registry id.
	 * @param {string} eventName view or play.
	 * @param {string} surface Surface key.
	 */
	function remember( videoId, eventName, surface ) {
		try {
			window.sessionStorage.setItem( storageKey( videoId, eventName, surface ), '1' );
		} catch ( error ) {
			// Private browsing can block storage. The event can still be sent once.
		}
	}

	/**
	 * Send the queued events. The server adds 1 per event.
	 */
	function flush() {
		if ( ! queue.length || ! navigator.sendBeacon ) {
			return;
		}

		var batch = queue.splice( 0, 20 );
		var body = JSON.stringify( { events: batch } );
		var blob = new Blob( [ body ], { type: 'application/json' } );

		navigator.sendBeacon( endpoint, blob );
	}

	/**
	 * Send soon, and again if more events arrive.
	 */
	function schedule() {
		if ( timer ) {
			window.clearTimeout( timer );
		}

		timer = window.setTimeout( flush, 800 );
	}

	/**
	 * Record one event, once per session.
	 *
	 * @param {number|string} videoId Registry id.
	 * @param {string}        eventName view or play.
	 * @param {string}        surface Surface key.
	 */
	function referrerHost() {
		try {
			if ( ! document.referrer ) {
				return '';
			}

			var url = new URL( document.referrer );

			if ( url.host === window.location.host ) {
				return '';
			}

			return url.host.replace( /^www\./, '' ).slice( 0, 191 );
		} catch ( error ) {
			return '';
		}
	}

	var progressEvents = {
		progress_25: true,
		progress_50: true,
		progress_75: true,
		complete: true,
	};

	function track( videoId, eventName, surface, seconds ) {
		var id = parseInt( videoId, 10 );
		var known = eventName === 'view' || eventName === 'play' || eventName === 'watch' || progressEvents[ eventName ];

		if ( ! id || ! known || ! surface ) {
			return;
		}

		if ( eventName !== 'watch' && already( id, eventName, surface ) ) {
			return;
		}

		if ( eventName !== 'watch' ) {
			remember( id, eventName, surface );
		}

		var item = {
			video_id: id,
			event: eventName,
			surface: surface,
			seconds: Math.max( 0, Math.round( seconds || 0 ) ),
		};

		if ( eventName === 'view' && rsfvAnalytics.audience ) {
			item.referrer = referrerHost();
		}

		queue.push( item );
		schedule();
	}

	window.rsfvTrack = track;

	/**
	 * Watch one stamped player.
	 *
	 * @param {HTMLElement} node Wrapper with data-rsfv-analytics.
	 */
	function bind( node ) {
		if ( node.getAttribute( 'data-rsfv-bound' ) === '1' ) {
			return;
		}

		node.setAttribute( 'data-rsfv-bound', '1' );

		var videoId = node.getAttribute( 'data-rsfv-video-id' );
		var surface = node.getAttribute( 'data-rsfv-surface' ) || 'shortcode';

		if ( surface === 'sticky' ) {
			return;
		}

		if ( 'IntersectionObserver' in window ) {
			var observer = new IntersectionObserver(
				function ( entries ) {
					entries.forEach(
						function ( entry ) {
							if ( entry.isIntersecting && entry.intersectionRatio >= 0.5 ) {
								track( videoId, 'view', surface );
								observer.disconnect();
							}
						}
					);
				},
				{ threshold: [ 0.5 ] }
			);

			observer.observe( node );
		}

		node.addEventListener(
			'play',
			function ( event ) {
				var target = event.target;

				if ( ! target || target.tagName !== 'VIDEO' || ! event.isTrusted ) {
					return;
				}

				track( videoId, 'play', surface );
			},
			true
		);

		var video = node.querySelector( 'video.rsfv-video, video' );
		var iframe = node.querySelector( 'iframe.rsfv-video, iframe' );

		if ( video ) {
			watchHtml5( video, videoId, surface );
		}

		if ( iframe ) {
			watchEmbed( iframe, videoId, surface );
		}
	}

	/**
	 * Count progress and watched seconds on a self-hosted video.
	 *
	 * @param {HTMLVideoElement} video Element.
	 * @param {string}           videoId Registry id.
	 * @param {string}           surface Surface key.
	 */
	function watchHtml5( video, videoId, surface ) {
		var marks = {};
		var last = 0;
		var watched = 0;

		function flush() {
			var seconds = Math.round( watched );

			if ( seconds > 0 ) {
				track( videoId, 'watch', surface, seconds );
				watched = 0;
			}
		}

		video.addEventListener( 'timeupdate', function () {
			if ( ! video.duration ) {
				return;
			}

			var percent = ( video.currentTime / video.duration ) * 100;

			[ 25, 50, 75 ].forEach( function ( mark ) {
				if ( ! marks[ mark ] && percent >= mark ) {
					marks[ mark ] = true;
					track( videoId, 'progress_' + mark, surface );
				}
			} );

			if ( video.currentTime > last && ( video.currentTime - last ) < 1.5 ) {
				watched += video.currentTime - last;
			}

			last = video.currentTime;
		} );

		video.addEventListener( 'ended', function () {
			track( videoId, 'complete', surface );
			flush();
		} );
		video.addEventListener( 'pause', flush );
		window.addEventListener( 'pagehide', flush );
	}

	/**
	 * Ask a provider iframe for playback time.
	 *
	 * @param {HTMLIFrameElement} iframe Frame.
	 * @param {string}            videoId Registry id.
	 * @param {string}            surface Surface key.
	 */
	function watchEmbed( iframe, videoId, surface ) {
		var src = iframe.getAttribute( 'src' ) || '';
		var kind = '';

		if ( src.indexOf( 'youtube' ) !== -1 || src.indexOf( 'youtu.be' ) !== -1 ) {
			kind = 'youtube';
		} else if ( src.indexOf( 'vimeo' ) !== -1 ) {
			kind = 'vimeo';
		} else if ( src.indexOf( 'dailymotion' ) !== -1 || src.indexOf( 'dai.ly' ) !== -1 ) {
			kind = 'dailymotion';
		}

		if ( ! kind ) {
			return;
		}

		var marks = {};
		var watched = 0;
		var lastTime = 0;

		function note( current, duration ) {
			if ( ! duration ) {
				return;
			}

			var percent = ( current / duration ) * 100;

			[ 25, 50, 75 ].forEach( function ( mark ) {
				if ( ! marks[ mark ] && percent >= mark ) {
					marks[ mark ] = true;
					track( videoId, 'progress_' + mark, surface );
				}
			} );

			if ( current > lastTime && ( current - lastTime ) < 2 ) {
				watched += current - lastTime;
			}

			lastTime = current;
		}

		function flush() {
			var seconds = Math.round( watched );

			if ( seconds > 0 ) {
				track( videoId, 'watch', surface, seconds );
				watched = 0;
			}
		}

		function handshake() {
			if ( ! iframe.contentWindow ) {
				return;
			}

			if ( kind === 'youtube' ) {
				iframe.contentWindow.postMessage( JSON.stringify( { event: 'listening', id: videoId, channel: 'widget' } ), '*' );
				iframe.contentWindow.postMessage( JSON.stringify( { event: 'command', func: 'addEventListener', args: [ 'onStateChange' ], id: videoId, channel: 'widget' } ), '*' );
			}

			if ( kind === 'vimeo' ) {
				iframe.contentWindow.postMessage( JSON.stringify( { method: 'addEventListener', value: 'timeupdate' } ), '*' );
				iframe.contentWindow.postMessage( JSON.stringify( { method: 'addEventListener', value: 'ended' } ), '*' );
			}
		}

		iframe.addEventListener( 'load', handshake );
		window.setTimeout( handshake, 500 );

		window.addEventListener( 'message', function ( event ) {
			if ( ! event || ! event.source || event.source !== iframe.contentWindow ) {
				return;
			}

			var data = event.data;

			if ( typeof data === 'string' ) {
				try {
					data = JSON.parse( data );
				} catch ( error ) {
					data = null;
				}
			}

			if ( ! data ) {
				return;
			}

			if ( kind === 'youtube' && data.info && typeof data.info === 'object' && typeof data.info.currentTime === 'number' ) {
				note( data.info.currentTime, data.info.duration || 0 );
			}

			if ( kind === 'youtube' && data.event === 'onStateChange' ) {
				var state = typeof data.info === 'number' ? data.info : 0;

				if ( state === 0 ) {
					track( videoId, 'complete', surface );
					flush();
				}

				if ( state === 2 ) {
					flush();
				}
			}

			if ( kind === 'vimeo' && data.event === 'timeupdate' && data.data ) {
				note( data.data.seconds || 0, data.data.duration || 0 );
			}

			if ( kind === 'vimeo' && data.event === 'ended' ) {
				track( videoId, 'complete', surface );
				flush();
			}

			if ( kind === 'dailymotion' && data.event === 'timeupdate' ) {
				note( data.time || 0, data.duration || 0 );
			}

			if ( kind === 'dailymotion' && data.event === 'video_end' ) {
				track( videoId, 'complete', surface );
				flush();
			}
		} );

		window.addEventListener( 'pagehide', flush );
	}

	window.rsfvWatchVideo = watchHtml5;
	window.rsfvWatchEmbed = watchEmbed;

	var lastPointer = 0;

	document.addEventListener(
		'pointerdown',
		function ( event ) {
			if ( event.isTrusted ) {
				lastPointer = Date.now();
			}
		},
		true
	);

	document.addEventListener(
		'keydown',
		function ( event ) {
			if ( event.isTrusted && ( event.key === 'Enter' || event.key === ' ' ) ) {
				lastPointer = Date.now();
			}
		},
		true
	);

	window.addEventListener(
		'blur',
		function () {
			if ( Date.now() - lastPointer > 700 ) {
				return;
			}

			var active = document.activeElement;

			if ( ! active || active.tagName !== 'IFRAME' || ! active.closest ) {
				return;
			}

			var node = active.closest( '[data-rsfv-analytics="1"]' );

			if ( ! node ) {
				return;
			}

			track(
				node.getAttribute( 'data-rsfv-video-id' ),
				'play',
				node.getAttribute( 'data-rsfv-surface' ) || 'shortcode'
			);
		}
	);

	/**
	 * Bind every stamped player currently in the document.
	 */
	function scan() {
		var nodes = document.querySelectorAll( '[data-rsfv-analytics="1"]' );

		Array.prototype.forEach.call( nodes, bind );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', scan );
	} else {
		scan();
	}

	window.addEventListener( 'pagehide', flush );
} )();
