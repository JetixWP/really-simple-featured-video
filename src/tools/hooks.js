/**
 * RSFV Tools Hooks System
 *
 * Provides a WordPress-like hooks system for extending the Tools app.
 *
 * @package RSFV
 */

const hooks = {
	filters: {},
	actions: {},
};

/**
 * Add a filter callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback function.
 * @param {number}   priority Priority (default 10).
 */
export const addFilter = ( hookName, callback, priority = 10 ) => {
	if ( ! hooks.filters[ hookName ] ) {
		hooks.filters[ hookName ] = [];
	}

	hooks.filters[ hookName ].push( { callback, priority } );
	hooks.filters[ hookName ].sort( ( a, b ) => a.priority - b.priority );
};

/**
 * Apply filters to a value.
 *
 * @param {string} hookName Hook name.
 * @param {*}      value    Value to filter.
 * @param {...*}   args     Additional arguments.
 * @return {*} Filtered value.
 */
export const applyFilters = ( hookName, value, ...args ) => {
	if ( ! hooks.filters[ hookName ] ) {
		return value;
	}

	return hooks.filters[ hookName ].reduce(
		( acc, { callback } ) => callback( acc, ...args ),
		value
	);
};

/**
 * Add an action callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback function.
 * @param {number}   priority Priority (default 10).
 */
export const addAction = ( hookName, callback, priority = 10 ) => {
	if ( ! hooks.actions[ hookName ] ) {
		hooks.actions[ hookName ] = [];
	}

	hooks.actions[ hookName ].push( { callback, priority } );
	hooks.actions[ hookName ].sort( ( a, b ) => a.priority - b.priority );
};

/**
 * Execute action callbacks.
 *
 * @param {string} hookName Hook name.
 * @param {...*}   args     Arguments to pass to callbacks.
 */
export const doAction = ( hookName, ...args ) => {
	if ( ! hooks.actions[ hookName ] ) {
		return;
	}

	hooks.actions[ hookName ].forEach( ( { callback } ) => callback( ...args ) );
};

/**
 * Remove a filter callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback to remove.
 */
export const removeFilter = ( hookName, callback ) => {
	if ( ! hooks.filters[ hookName ] ) {
		return;
	}

	hooks.filters[ hookName ] = hooks.filters[ hookName ].filter(
		( item ) => item.callback !== callback
	);
};

/**
 * Remove an action callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback to remove.
 */
export const removeAction = ( hookName, callback ) => {
	if ( ! hooks.actions[ hookName ] ) {
		return;
	}

	hooks.actions[ hookName ] = hooks.actions[ hookName ].filter(
		( item ) => item.callback !== callback
	);
};

// Export as global for external extensions.
window.rsfvToolsHooks = {
	addFilter,
	applyFilters,
	addAction,
	doAction,
	removeFilter,
	removeAction,
};

export default {
	addFilter,
	applyFilters,
	addAction,
	doAction,
	removeFilter,
	removeAction,
};
