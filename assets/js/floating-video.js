/**
 * Floating Video – Frontend Script
 *
 * Renders a floating play button at the bottom-left corner and opens a
 * popup video player when clicked. Supports multiple videos with prev/next
 * navigation.
 *
 * @package RSFV
 */

( function () {
	'use strict';

	// Bail if the localized data is missing.
	if ( typeof RSFVFloatingVideo === 'undefined' ) {
		return;
	}

	var videos        = RSFVFloatingVideo.videos || [];
	var totalVideos   = videos.length;
	var currentIndex  = 0;
	var selfControls  = RSFVFloatingVideo.selfControls || { controls: true };
	var embedControls = RSFVFloatingVideo.embedControls || { controls: true };
	var aspectRatio   = RSFVFloatingVideo.aspectRatio || '16/9';

	/**
	 * Convert a standard YouTube/Vimeo URL into an embed URL with
	 * parameters derived from the embed control settings.
	 *
	 * @param {string} url The original video URL.
	 * @return {string} The embed-ready URL.
	 */
	function getEmbedUrl( url ) {
		var match;
		var baseUrl = '';

		// YouTube: standard, short, and embed URLs.
		match = url.match( /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{11})/ );
		if ( match ) {
			baseUrl = 'https://www.youtube.com/embed/' + match[ 1 ];
		}

		// Vimeo.
		if ( ! baseUrl ) {
			match = url.match( /vimeo\.com\/(?:video\/)?(\d+)/ );
			if ( match ) {
				baseUrl = 'https://player.vimeo.com/video/' + match[ 1 ];
			}
		}

		// Dailymotion.
		if ( ! baseUrl ) {
			match = url.match( /dailymotion\.com\/video\/([\w]+)/ );
			if ( match ) {
				baseUrl = 'https://www.dailymotion.com/embed/video/' + match[ 1 ];
			}
		}

		// Already an embed URL or unknown – use as-is.
		if ( ! baseUrl ) {
			return url;
		}

		// Build URL parameters from embed controls.
		var params = [];
		params.push( embedControls.autoplay ? 'autoplay=1' : 'autoplay=0' );
		params.push( embedControls.controls ? 'controls=1' : 'controls=0' );

		if ( embedControls.loop ) {
			params.push( 'loop=1' );
		}

		if ( embedControls.mute ) {
			params.push( 'mute=1' );
			params.push( 'muted=1' );
		}

		if ( embedControls.pip ) {
			params.push( 'picture-in-picture=1' );
		}

		params.push( 'rel=0' );

		return baseUrl + '?' + params.join( '&' );
	}

	/**
	 * Build the floating button element.
	 *
	 * @return {HTMLElement}
	 */
	function createButton() {
		var label = videos[ 0 ] ? videos[ 0 ].title : 'Play Video';
		var btn   = document.createElement( 'button' );
		btn.className = 'rsfv-floating-btn';
		btn.setAttribute( 'aria-label', label );
		btn.innerHTML =
			'<span class="rsfv-floating-btn__icon"></span>' +
			'<span class="rsfv-floating-btn__tooltip">' + label + '</span>';
		return btn;
	}

	/**
	 * Build the popup overlay and video container with navigation.
	 *
	 * @return {HTMLElement}
	 */
	function createOverlay() {
		var overlay = document.createElement( 'div' );
		overlay.className = 'rsfv-floating-overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-label', 'Video' );

		var popup = document.createElement( 'div' );
		popup.className = 'rsfv-floating-popup';

		// Close button.
		var close = document.createElement( 'button' );
		close.className = 'rsfv-floating-popup__close';
		close.setAttribute( 'aria-label', 'Close' );
		close.innerHTML = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

		// Video wrapper.
		var videoWrap = document.createElement( 'div' );
		videoWrap.className = 'rsfv-floating-popup__video';

		// Apply dynamic aspect ratio (e.g. '16/9' → padding-bottom: 56.25%).
		var ratioParts = aspectRatio.split( '/' );
		if ( ratioParts.length === 2 ) {
			var w = parseFloat( ratioParts[ 0 ] );
			var h = parseFloat( ratioParts[ 1 ] );
			if ( w > 0 && h > 0 ) {
				videoWrap.style.paddingBottom = ( ( h / w ) * 100 ).toFixed( 4 ) + '%';
			}
		}

		popup.appendChild( close );
		popup.appendChild( videoWrap );

		// Navigation bar (only if multiple videos).
		if ( totalVideos > 1 ) {
			var nav = document.createElement( 'div' );
			nav.className = 'rsfv-floating-popup__nav';

			// Prev button.
			var prevBtn = document.createElement( 'button' );
			prevBtn.className = 'rsfv-floating-popup__nav-btn rsfv-floating-popup__nav-prev';
			prevBtn.setAttribute( 'aria-label', 'Previous video' );
			prevBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16"><polyline points="15 18 9 12 15 6"/></svg>';

			// Title + counter.
			var info = document.createElement( 'div' );
			info.className = 'rsfv-floating-popup__nav-info';

			var title = document.createElement( 'span' );
			title.className = 'rsfv-floating-popup__nav-title';

			var counter = document.createElement( 'span' );
			counter.className = 'rsfv-floating-popup__nav-counter';

			info.appendChild( title );
			info.appendChild( counter );

			// Next button.
			var nextBtn = document.createElement( 'button' );
			nextBtn.className = 'rsfv-floating-popup__nav-btn rsfv-floating-popup__nav-next';
			nextBtn.setAttribute( 'aria-label', 'Next video' );
			nextBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>';

			nav.appendChild( prevBtn );
			nav.appendChild( info );
			nav.appendChild( nextBtn );
			popup.appendChild( nav );
		}

		overlay.appendChild( popup );

		return overlay;
	}

	/**
	 * Insert the video element for a given video config.
	 *
	 * @param {HTMLElement} container The .rsfv-floating-popup__video element.
	 * @param {Object}      videoData The video config object.
	 */
	function insertVideo( container, videoData ) {
		container.innerHTML = '';

		if ( videoData.videoSource === 'self' && videoData.videoUrl ) {
			var video = document.createElement( 'video' );
			video.src = videoData.videoUrl;
			video.playsInline = true;

			// Controls.
			if ( selfControls.controls ) {
				video.controls = true;
				if ( ! selfControls.download ) {
					video.setAttribute( 'controlsList', 'nodownload' );
				}
			}

			// Autoplay.
			if ( selfControls.autoplay ) {
				video.autoplay = true;
			}

			// Loop.
			if ( selfControls.loop ) {
				video.loop = true;
			}

			// Mute.
			if ( selfControls.mute ) {
				video.muted = true;
			}

			// Picture-in-Picture.
			if ( selfControls.pip ) {
				video.setAttribute( 'autopictureinpicture', '' );
			} else {
				video.setAttribute( 'disablepictureinpicture', '' );
			}

			container.appendChild( video );
		} else if ( videoData.videoSource === 'embed' && ( videoData.embedUrl || videoData.videoUrl ) ) {
			var iframe = document.createElement( 'iframe' );
			iframe.src = getEmbedUrl( videoData.embedUrl || videoData.videoUrl );

			// Build allow attribute from embed controls.
			var allowParts = [ 'fullscreen' ];
			if ( embedControls.autoplay ) {
				allowParts.push( 'autoplay' );
			}
			if ( embedControls.pip ) {
				allowParts.push( 'picture-in-picture' );
			}
			iframe.allow = allowParts.join( '; ' );
			iframe.allowFullscreen = true;

			container.appendChild( iframe );
		}
	}

	/**
	 * Remove the video element from the popup container.
	 *
	 * @param {HTMLElement} container The .rsfv-floating-popup__video element.
	 */
	function clearVideo( container ) {
		container.innerHTML = '';
	}

	/**
	 * Update the navigation bar state (title, counter, button disabled states).
	 *
	 * @param {HTMLElement} popup The .rsfv-floating-popup element.
	 */
	function updateNav( popup ) {
		var titleEl   = popup.querySelector( '.rsfv-floating-popup__nav-title' );
		var counterEl = popup.querySelector( '.rsfv-floating-popup__nav-counter' );
		var prevBtn   = popup.querySelector( '.rsfv-floating-popup__nav-prev' );
		var nextBtn   = popup.querySelector( '.rsfv-floating-popup__nav-next' );

		if ( ! titleEl ) {
			return;
		}

		var videoData = videos[ currentIndex ];

		titleEl.textContent   = videoData.title || 'Video';
		counterEl.textContent = ( currentIndex + 1 ) + ' / ' + totalVideos;

		prevBtn.disabled = ( currentIndex === 0 );
		nextBtn.disabled = ( currentIndex === totalVideos - 1 );
	}

	/**
	 * Load a video by index and update the nav.
	 *
	 * @param {HTMLElement} videoContainer The .rsfv-floating-popup__video element.
	 * @param {HTMLElement} popup          The .rsfv-floating-popup element.
	 */
	function loadVideo( videoContainer, popup ) {
		insertVideo( videoContainer, videos[ currentIndex ] );
		updateNav( popup );
	}

	/**
	 * Initialise the floating video feature.
	 */
	function init() {
		// Must have at least one video with a URL.
		var hasVideo = videos.some( function ( v ) {
			return v.videoUrl || v.embedUrl;
		} );

		if ( ! hasVideo ) {
			return;
		}

		var btn            = createButton();
		var overlay        = createOverlay();
		var popup          = overlay.querySelector( '.rsfv-floating-popup' );
		var videoContainer = overlay.querySelector( '.rsfv-floating-popup__video' );
		var closeBtn       = overlay.querySelector( '.rsfv-floating-popup__close' );
		var prevBtn        = overlay.querySelector( '.rsfv-floating-popup__nav-prev' );
		var nextBtn        = overlay.querySelector( '.rsfv-floating-popup__nav-next' );

		document.body.appendChild( btn );
		document.body.appendChild( overlay );

		/**
		 * Open the popup.
		 */
		function open() {
			currentIndex = 0;
			loadVideo( videoContainer, popup );
			overlay.classList.add( 'rsfv-floating-overlay--visible' );
			btn.style.opacity = '0.4';
			btn.style.pointerEvents = 'none';
		}

		/**
		 * Close the popup.
		 */
		function close() {
			overlay.classList.remove( 'rsfv-floating-overlay--visible' );
			btn.style.opacity = '';
			btn.style.pointerEvents = '';
			// Clear video after transition ends to stop playback.
			setTimeout( function () {
				clearVideo( videoContainer );
			}, 300 );
		}

		btn.addEventListener( 'click', open );
		closeBtn.addEventListener( 'click', close );

		// Prev / Next handlers.
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				if ( currentIndex > 0 ) {
					currentIndex--;
					loadVideo( videoContainer, popup );
				}
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				if ( currentIndex < totalVideos - 1 ) {
					currentIndex++;
					loadVideo( videoContainer, popup );
				}
			} );
		}

		// Close on overlay click (outside the popup).
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				close();
			}
		} );

		// Keyboard: Escape to close, Left/Right for prev/next.
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! overlay.classList.contains( 'rsfv-floating-overlay--visible' ) ) {
				return;
			}

			if ( e.key === 'Escape' ) {
				close();
			} else if ( e.key === 'ArrowLeft' && currentIndex > 0 ) {
				currentIndex--;
				loadVideo( videoContainer, popup );
			} else if ( e.key === 'ArrowRight' && currentIndex < totalVideos - 1 ) {
				currentIndex++;
				loadVideo( videoContainer, popup );
			}
		} );
	}

	// Run on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
