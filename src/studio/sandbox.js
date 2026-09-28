/**
 * Sandboxed iframe that runs the Video Studio runtime.
 *
 * The iframe has sandbox="allow-scripts" and no allow-same-origin, so scene
 * code can't reach the admin (cookies, REST nonce, DOM). Scripts are inlined
 * into srcdoc and all media is passed in as Blobs.
 *
 * @package RSFV
 */

const codeCache = new Map();

/**
 * Fetch a script's source once.
 *
 * @param {string} url Script URL (same site).
 * @return {Promise<string>} Source, or '' if it can't be loaded.
 */
export function loadScript( url ) {
	if ( ! codeCache.has( url ) ) {
		codeCache.set(
			url,
			window
				.fetch( url, { credentials: 'same-origin' } )
				.then( ( response ) => ( response.ok ? response.text() : '' ) )
				.catch( () => '' )
		);
	}
	return codeCache.get( url );
}

/**
 * Make source safe to place inside an inline <script>.
 *
 * @param {string} code Source.
 * @return {string} Source.
 */
const inline = ( code ) => code.replace( /<\/script/gi, '<\\/script' );

export default class Sandbox {
	/**
	 * @param {Object}   options            Options.
	 * @param {string}   options.runtimeUrl Runtime script URL.
	 * @param {string[]} options.scripts    Template and extension script URLs.
	 * @param {string}   options.title      Accessible iframe title.
	 */
	constructor( { runtimeUrl, scripts = [], title = '' } ) {
		this.runtimeUrl = runtimeUrl;
		this.scripts = scripts;
		this.title = title;
		this.iframe = null;
		this.seq = 0;
		this.pending = new Map();
		this.listeners = {};
		this.onMessage = this.onMessage.bind( this );
		this.booted = null;
	}

	/**
	 * Create the iframe inside a container.
	 *
	 * @param {HTMLElement} container Container.
	 * @return {Promise<Object>} Boot event (lists registered templates).
	 */
	async mount( container ) {
		const [ runtime, ...scripts ] = await Promise.all( [
			loadScript( this.runtimeUrl ),
			...this.scripts.map( loadScript ),
		] );
		if ( ! runtime ) {
			throw new Error( 'runtime' );
		}

		const html =
			'<!doctype html><html><head><meta charset="utf-8">' +
			'<style>html,body{margin:0;height:100%;overflow:hidden;background:transparent}' +
			'#rsfv-viewport{position:absolute;inset:0;overflow:hidden}' +
			'#rsfv-stage{position:absolute;left:50%;top:50%;transform-origin:0 0}' +
			'</style></head><body><div id="rsfv-viewport"><div id="rsfv-stage"></div></div>' +
			`<script>${ inline( runtime ) }</script>` +
			scripts
				.filter( Boolean )
				.map( ( code ) => `<script>${ inline( code ) }</script>` )
				.join( '' ) +
			'<script>window.RSFVStudio && window.RSFVStudio.boot();</script>' +
			'</body></html>';

		this.booted = new Promise( ( resolve ) => {
			this.once( 'boot', resolve );
		} );

		window.addEventListener( 'message', this.onMessage );

		const iframe = document.createElement( 'iframe' );
		iframe.setAttribute( 'sandbox', 'allow-scripts' );
		iframe.setAttribute( 'title', this.title );
		iframe.className = 'rsfv-studio-frame';
		iframe.srcdoc = html;
		container.appendChild( iframe );
		this.iframe = iframe;

		return this.booted;
	}

	/**
	 * Handle a message from the iframe.
	 *
	 * @param {MessageEvent} event Event.
	 */
	onMessage( event ) {
		if ( ! this.iframe || event.source !== this.iframe.contentWindow ) {
			return;
		}
		const data = event.data || {};
		if ( data.event ) {
			( this.listeners[ data.event ] || [] )
				.slice()
				.forEach( ( cb ) => cb( data ) );
			return;
		}
		const pending = this.pending.get( data.id );
		if ( ! pending ) {
			return;
		}
		this.pending.delete( data.id );
		if ( data.ok ) {
			pending.resolve( data.result );
		} else {
			pending.reject( new Error( data.error || 'error' ) );
		}
	}

	/**
	 * Call the runtime.
	 *
	 * @param {string} type    Handler.
	 * @param {Object} payload Payload.
	 * @return {Promise<*>} Result.
	 */
	call( type, payload = {} ) {
		if ( ! this.iframe || ! this.iframe.contentWindow ) {
			return Promise.reject( new Error( 'not mounted' ) );
		}
		const id = ++this.seq;
		return new Promise( ( resolve, reject ) => {
			this.pending.set( id, { resolve, reject } );
			// The frame has an opaque origin, so '*' is the only target that
			// works. Nothing secret is ever sent to it.
			this.iframe.contentWindow.postMessage( { id, type, payload }, '*' );
		} );
	}

	/**
	 * Listen to runtime events (time, progress, boot).
	 *
	 * @param {string}   event Event.
	 * @param {Function} cb    Callback.
	 * @return {Function} Unsubscribe.
	 */
	on( event, cb ) {
		this.listeners[ event ] = ( this.listeners[ event ] || [] ).concat(
			cb
		);
		return () => {
			this.listeners[ event ] = ( this.listeners[ event ] || [] ).filter(
				( fn ) => fn !== cb
			);
		};
	}

	/**
	 * Listen once.
	 *
	 * @param {string}   event Event.
	 * @param {Function} cb    Callback.
	 */
	once( event, cb ) {
		const off = this.on( event, ( data ) => {
			off();
			cb( data );
		} );
	}

	/**
	 * Remove the iframe.
	 */
	destroy() {
		window.removeEventListener( 'message', this.onMessage );
		this.pending.forEach( ( p ) => p.reject( new Error( 'destroyed' ) ) );
		this.pending.clear();
		if ( this.iframe ) {
			this.iframe.remove();
		}
		this.iframe = null;
	}
}
