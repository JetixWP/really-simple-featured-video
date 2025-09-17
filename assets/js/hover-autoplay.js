/**
 * Really Simple Featured Video - Mobile Iframe Fix
 * Enhanced handling for embed videos on mobile/tablet devices
 */

class RSFVHoverAutoplay {
    constructor(options = {}) {
        this.options = {
            mobileBreakpoint: 768,
            hoverDelay: 100,
            enableOnMobile: true,
            enableOnDesktop: true,
            respectUserPreferences: true,
            debugMode: false,
            ...options
        };
        
        this.isMobile = false;
        this.isTablet = false;
        this.reducedMotion = false;
        this.activeVideos = new Map();
        this.playingVideos = new Set();
        this.touchStartTime = 0;
        this.touchMoved = false;
        
        this.init();
    }
    
    init() {
        this.checkReducedMotion();
        this.checkDeviceType();
        this.initializeVideos();
        
        // Enhanced resize listener
        window.addEventListener('resize', this.debounce(() => {
            this.checkDeviceType();
            this.refreshVideoHandlers();
        }, 250));
        
        // Add orientation change listener for tablets
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.checkDeviceType();
                this.refreshVideoHandlers();
            }, 100);
        });
        
        // Listen for iframe API ready events
        this.initializeIframeAPIs();
        
        this.log('RSFV Hover Autoplay initialized with enhanced mobile support');
    }
    
    checkReducedMotion() {
        if (this.options.respectUserPreferences && window.matchMedia) {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
    }
    
    checkDeviceType() {
        const width = window.innerWidth;
        const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const userAgent = navigator.userAgent.toLowerCase();
        const isMobileUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
        
        // Enhanced device detection
        this.isMobile = width <= 480 || (hasTouch && width <= 600 && isMobileUA);
        this.isTablet = (width > 480 && width <= this.options.mobileBreakpoint) || 
                       (hasTouch && width <= 1024 && /ipad|tablet/i.test(userAgent));
        
        this.log(`Device: Mobile=${this.isMobile}, Tablet=${this.isTablet}, Width=${width}, Touch=${hasTouch}`);
    }
    
    shouldEnableHover() {
        if (this.reducedMotion) {
            this.log('Hover disabled: reduced motion');
            return false;
        }
        
        if ((this.isMobile || this.isTablet) && !this.options.enableOnMobile) {
            this.log('Hover disabled: mobile/tablet disabled in settings');
            return false;
        }
        
        if (!this.isMobile && !this.isTablet && !this.options.enableOnDesktop) {
            this.log('Hover disabled: desktop disabled in settings');
            return false;
        }
        
        return true;
    }
    
    initializeIframeAPIs() {
        // YouTube API
        if (!window.onYouTubeIframeAPIReady) {
            window.onYouTubeIframeAPIReady = () => {
                this.log('YouTube API ready');
                this.refreshVideoHandlers();
            };
        }
        
        // Load YouTube API if not already loaded
        if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
        
        // Vimeo API (if needed)
        window.addEventListener('message', (event) => {
            if (event.origin !== 'https://player.vimeo.com') return;
            this.handleVimeoMessage(event.data);
        });
    }
    
    handleVimeoMessage(data) {
        try {
            const message = typeof data === 'string' ? JSON.parse(data) : data;
            this.log('Vimeo message:', message);
        } catch (e) {
            // Ignore parsing errors
        }
    }
    
    initializeVideos() {
        const videoContainers = document.querySelectorAll(
					'.rsfv-video-container, .rsfv-featured-video, [data-rsfv-video]'
				);

        videoContainers.forEach((container, index) => {
            this.setupVideoContainer(container, index);
        });
    }
    
    setupVideoContainer(container, index) {
        if (container.dataset.rsfvHoverInit) return;
        
        const video = this.findVideoElement(container);

        if (!video) {
            this.log(`No video element found in container ${index}`);
            return;
        }

        container.dataset.rsfvHoverInit = 'true';
        
        const videoType = this.getVideoType(video);

        this.log(`Setting up ${videoType} video ${index} for ${this.isMobile ? 'mobile' : this.isTablet ? 'tablet' : 'desktop'}`);
        
        if (videoType === 'html5') {
            this.setupHTML5Video(container, video, index);
        } else {
            this.setupIframeVideo(container, video, videoType, index);
        }
    }
    
    findVideoElement(container) {
        return container.querySelector('video') || 
               container.querySelector('iframe[src*="youtube"]') ||
               container.querySelector('iframe[src*="vimeo"]') ||
               container.querySelector('iframe[src*="dailymotion"]') ||
               container.querySelector('iframe.rsfv-video');
    }
    
    getVideoType(element) {
        if (element.tagName === 'VIDEO') {
            return 'html5';
        }
        
        if (element.tagName === 'IFRAME') {
            const src = element.src || '';
            if (src.includes('youtube')) return 'youtube';
            if (src.includes('vimeo')) return 'vimeo';
            if (src.includes('dailymotion')) return 'dailymotion';
            return 'iframe';
        }
        
        return 'unknown';
    }
    
    setupHTML5Video(container, video, index) {
        if (!this.shouldEnableHover()) return;
        
        let hoverTimeout;
        let isPlaying = false;
        const videoId = `html5-${index}`;
        
        const playVideo = () => {
            if (this.playingVideos.has(videoId)) return;
            
            if (hoverTimeout) clearTimeout(hoverTimeout);
            
            hoverTimeout = setTimeout(() => {
                this.playHTML5Video(video);
                isPlaying = true;
                this.playingVideos.add(videoId);
                container.setAttribute('data-state', 'playing');
                this.log(`HTML5 video ${index} started playing`);
            }, this.options.hoverDelay);
        };
        
        const pauseVideo = () => {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
            
            if (isPlaying) {
                this.pauseHTML5Video(video);
                isPlaying = false;
                this.playingVideos.delete(videoId);
                container.removeAttribute('data-state');
                this.log(`HTML5 video ${index} paused`);
            }
        };

        // Desktop events
        if (!this.isMobile && !this.isTablet) {
            container.addEventListener('mouseenter', playVideo);
            container.addEventListener('mouseleave', pauseVideo);
            video.addEventListener('focus', playVideo);
            video.addEventListener('blur', pauseVideo);
        }
        
        // Mobile/Tablet events
        if (this.isMobile || this.isTablet) {
            this.setupTouchEvents(container, playVideo, pauseVideo, `HTML5-${index}`);
        }
        
        // Click to play normally (all devices)
        video.addEventListener('click', (e) => {
            e.stopPropagation();
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        });
        
        // Store cleanup function
        this.activeVideos.set(video, () => {
            container.removeEventListener('mouseenter', playVideo);
            container.removeEventListener('mouseleave', pauseVideo);
            video.removeEventListener('focus', playVideo);
            video.removeEventListener('blur', pauseVideo);
            this.cleanupTouchEvents(container);
            if (hoverTimeout) clearTimeout(hoverTimeout);
        });
    }
    
    setupIframeVideo(container, iframe, videoType, index) {
        if (!this.shouldEnableHover()) return;
        
        this.ensureIframeAPI(iframe, videoType);
        
        let hoverTimeout;
        let isPlaying = false;
        const videoId = `${videoType}-${index}`;
        
        const playVideo = () => {
            if (this.playingVideos.has(videoId)) return;
            
            if (hoverTimeout) clearTimeout(hoverTimeout);
            
            hoverTimeout = setTimeout(() => {
                this.playIframeVideo(iframe, videoType);
                isPlaying = true;
                this.playingVideos.add(videoId);
                container.setAttribute('data-state', 'playing');
                this.log(`${videoType} video ${index} started playing`);
            }, this.options.hoverDelay);
        };
        
        const pauseVideo = () => {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
            
            if (isPlaying) {
                this.pauseIframeVideo(iframe, videoType);
                isPlaying = false;
                this.playingVideos.delete(videoId);
                container.removeAttribute('data-state');
                this.log(`${videoType} video ${index} paused`);
            }
        };
        
        // Desktop events
        if (!this.isMobile && !this.isTablet) {
            container.addEventListener('mouseenter', playVideo);
            container.addEventListener('mouseleave', pauseVideo);
        }
        
        // Mobile/Tablet events - ENHANCED for iframes
        if (this.isMobile || this.isTablet) {
            this.setupTouchEvents(container, playVideo, pauseVideo, `${videoType}-${index}`);
            
            // Add click overlay for iframe videos on mobile
            this.addMobileClickOverlay(container, iframe, playVideo, pauseVideo, index);
        }
        
        // Store cleanup function
        this.activeVideos.set(iframe, () => {
            container.removeEventListener('mouseenter', playVideo);
            container.removeEventListener('mouseleave', pauseVideo);
            this.cleanupTouchEvents(container);
            this.removeMobileClickOverlay(container);
            if (hoverTimeout) clearTimeout(hoverTimeout);
        });
    }
    
    addMobileClickOverlay(container, iframe, playCallback, pauseCallback, index) {
        // Create a transparent overlay for better mobile interaction
        let overlay = container.querySelector('.rsfv-mobile-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'rsfv-mobile-overlay';
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10;
                background: transparent;
                cursor: pointer;
            `;
            
            // Position container relatively if not already
            if (getComputedStyle(container).position === 'static') {
                container.style.position = 'relative';
            }
            
            container.appendChild(overlay);
        }
        
        // Enhanced touch handling for overlay
        let touchStarted = false;
        
        const handleOverlayTouch = (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!touchStarted) {
                touchStarted = true;
                playCallback();
                this.log(`Mobile overlay triggered for iframe ${index}`);
                
                // Hide overlay after interaction to allow normal video controls
                setTimeout(() => {
                    if (overlay && overlay.parentNode) {
                        overlay.style.display = 'none';
                    }
                }, 1000);
            }
        };
        
        overlay.addEventListener('touchstart', handleOverlayTouch, { passive: false });
        overlay.addEventListener('click', handleOverlayTouch);
        
        // Store overlay reference for cleanup
        container._rsfvMobileOverlay = overlay;
    }
    
    removeMobileClickOverlay(container) {
        const overlay = container.querySelector('.rsfv-mobile-overlay');
        if (overlay) {
            overlay.remove();
        }
        delete container._rsfvMobileOverlay;
    }
    
    setupTouchEvents(container, playCallback, pauseCallback, videoId) {
        let touchStartTime = 0;
        let touchMoved = false;
        let hasInteracted = false;
        
        const handleTouchStart = (e) => {
            touchStartTime = Date.now();
            touchMoved = false;
            
            // Start playing immediately on touch for mobile
            if (!hasInteracted) {
                playCallback();
                hasInteracted = true;
                this.log(`Touch start: ${videoId}`);
            }
        };
        
        const handleTouchMove = (e) => {
            touchMoved = true;
        };
        
        const handleTouchEnd = (e) => {
            const touchDuration = Date.now() - touchStartTime;
            
            // Quick tap = continue playing (normal video interaction)
            if (!touchMoved && touchDuration < 300) {
                this.log(`Quick tap on ${videoId} - keeping video playing`);
                return;
            }
            
            // Long press or swipe = pause (like hover out)
            if (touchDuration > 500 || touchMoved) {
                pauseCallback();
                hasInteracted = false;
                this.log(`Long touch/swipe on ${videoId} - pausing`);
            }
        };
        
        const handleTouchCancel = (e) => {
            pauseCallback();
            hasInteracted = false;
        };
        
        // Use passive listeners for better performance
        container.addEventListener('touchstart', handleTouchStart, { passive: true });
        container.addEventListener('touchmove', handleTouchMove, { passive: true });
        container.addEventListener('touchend', handleTouchEnd, { passive: true });
        container.addEventListener('touchcancel', handleTouchCancel, { passive: true });
        
        // Store cleanup function
        container._rsfvTouchCleanup = () => {
            container.removeEventListener('touchstart', handleTouchStart);
            container.removeEventListener('touchmove', handleTouchMove);
            container.removeEventListener('touchend', handleTouchEnd);
            container.removeEventListener('touchcancel', handleTouchCancel);
        };
    }
    
    cleanupTouchEvents(container) {
        if (container._rsfvTouchCleanup) {
            container._rsfvTouchCleanup();
            delete container._rsfvTouchCleanup;
        }
    }
    
    ensureIframeAPI(iframe, videoType) {
        const currentSrc = iframe.src;
        let newSrc = currentSrc;
        
        switch (videoType) {
            case 'youtube':
                if (!currentSrc.includes('enablejsapi=1')) {
                    newSrc += (currentSrc.includes('?') ? '&' : '?') + 'enablejsapi=1';
                }
                if (!currentSrc.includes('origin=')) {
                    newSrc += '&origin=' + encodeURIComponent(window.location.origin);
                }
                break;
                
            case 'vimeo':
                if (!currentSrc.includes('api=1')) {
                    newSrc += (currentSrc.includes('?') ? '&' : '?') + 'api=1';
                }
                break;
                
            case 'dailymotion':
                if (!currentSrc.includes('api=postMessage')) {
                    newSrc += (currentSrc.includes('?') ? '&' : '?') + 'api=postMessage';
                }
                break;
        }
        
        if (newSrc !== currentSrc) {
            iframe.src = newSrc;
            this.log(`Enhanced iframe src for ${videoType}:`, newSrc);
        }
    }
    
    playHTML5Video(video) {
        try {
            if (!video.dataset.rsfvOriginalMuted) {
                video.dataset.rsfvOriginalMuted = video.muted;
            }
            
            video.muted = true;
            
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    this.log('HTML5 video playing successfully');
                }).catch(error => {
                    this.log('HTML5 video play failed:', error);
                    // Try with user interaction
                    setTimeout(() => {
                        video.play().catch(e => this.log('Retry failed:', e));
                    }, 100);
                });
            }
        } catch (error) {
            this.log('Error playing HTML5 video:', error);
        }
    }
    
    pauseHTML5Video(video) {
        try {
            video.pause();
            
            if (video.dataset.rsfvOriginalMuted !== undefined) {
                video.muted = video.dataset.rsfvOriginalMuted === 'true';
            }
            
            this.log('HTML5 video paused successfully');
        } catch (error) {
            this.log('Error pausing HTML5 video:', error);
        }
    }
    
    playIframeVideo(iframe, videoType) {
        try {
            const message = this.getPlayMessage(videoType);
            if (message) {
                iframe.contentWindow.postMessage(message, '*');
                this.log(`Sent play command to ${videoType} iframe`);
            }
        } catch (error) {
            this.log(`Error playing ${videoType} video:`, error);
        }
    }
    
    pauseIframeVideo(iframe, videoType) {
        try {
            const message = this.getPauseMessage(videoType);
            if (message) {
                iframe.contentWindow.postMessage(message, '*');
                this.log(`Sent pause command to ${videoType} iframe`);
            }
        } catch (error) {
            this.log(`Error pausing ${videoType} video:`, error);
        }
    }
    
    getPlayMessage(videoType) {
        switch (videoType) {
            case 'youtube':
                return JSON.stringify({event: 'command', func: 'playVideo', args: ''});
            case 'vimeo':
                return JSON.stringify({method: 'play'});
            case 'dailymotion':
                return JSON.stringify({command: 'play'});
            default:
                return null;
        }
    }
    
    getPauseMessage(videoType) {
        switch (videoType) {
            case 'youtube':
                return JSON.stringify({event: 'command', func: 'pauseVideo', args: ''});
            case 'vimeo':
                return JSON.stringify({method: 'pause'});
            case 'dailymotion':
                return JSON.stringify({command: 'pause'});
            default:
                return null;
        }
    }
    
    refreshVideoHandlers() {
        this.activeVideos.forEach((cleanup) => cleanup());
        this.activeVideos.clear();
        this.playingVideos.clear();
        this.initializeVideos();
        this.log('Video handlers refreshed');
    }
    
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.refreshVideoHandlers();
    }
    
    destroy() {
        this.activeVideos.forEach((cleanup) => cleanup());
        this.activeVideos.clear();
        this.playingVideos.clear();
        this.log('RSFV Hover Autoplay destroyed');
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    log(...args) {
        if (this.options.debugMode) {
            console.log('[RSFV Mobile Fix]', ...args);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get options from WordPress localized script or use defaults
    const rsfvOptions = window.RSFVHoverAutoplaySettings || {};
    
    // Initialize the hover autoplay functionality
    window.rsfvHoverAutoplay = new RSFVHoverAutoplay({
        mobileBreakpoint: rsfvOptions.mobileBreakpoint || 768,
        hoverDelay: rsfvOptions.hoverDelay || 100,
        enableOnMobile: rsfvOptions.enableOnMobile === "1", // default true
        enableOnDesktop: rsfvOptions.enableOnDesktop === "1", // default true
        respectUserPreferences: rsfvOptions.respectUserPreferences === "1", // default true
        debugMode: rsfvOptions.debugMode || false,
    });
});

// Re-initialize for dynamically loaded content
document.addEventListener('rsfvContentLoaded', function() {
    if (window.rsfvHoverAutoplay) {
        window.rsfvHoverAutoplay.initializeVideos();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RSFVHoverAutoplay;
}