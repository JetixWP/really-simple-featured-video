/**
 * Progress bar. WordPress has one from 6.6; older versions get a plain bar,
 * so Video Studio keeps working there.
 *
 * @package RSFV
 */

import { ProgressBar } from '@wordpress/components';

const Fallback = ( { value = 0, className = '' } ) => (
	<div
		className={ `rsfv-progress ${ className }` }
		role="progressbar"
		aria-valuemin={ 0 }
		aria-valuemax={ 100 }
		aria-valuenow={ Math.round( value ) }
	>
		<div
			className="rsfv-progress__fill"
			style={ { width: `${ Math.max( 0, Math.min( 100, value ) ) }%` } }
		/>
	</div>
);

const Progress = ProgressBar || Fallback;

export default Progress;
