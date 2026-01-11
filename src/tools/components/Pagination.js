/**
 * Pagination Component
 *
 * @package RSFV
 */

import { __ } from '@wordpress/i18n';

const Pagination = ( { currentPage, totalPages, totalItems, onPageChange } ) => {
	const handlePrevious = () => {
		if ( currentPage > 1 ) {
			onPageChange( currentPage - 1 );
		}
	};

	const handleNext = () => {
		if ( currentPage < totalPages ) {
			onPageChange( currentPage + 1 );
		}
	};

	const handleFirst = () => {
		onPageChange( 1 );
	};

	const handleLast = () => {
		onPageChange( totalPages );
	};

	return (
		<div className="rsfv-pagination tablenav bottom">
			<div className="tablenav-pages">
				<span className="displaying-num">
					{ totalItems } { __( 'items', 'rsfv' ) }
				</span>
				<span className="pagination-links">
					<button
						className="first-page button"
						onClick={ handleFirst }
						disabled={ currentPage === 1 }
						aria-label={ __( 'First page', 'rsfv' ) }
					>
						«
					</button>
					<button
						className="prev-page button"
						onClick={ handlePrevious }
						disabled={ currentPage === 1 }
						aria-label={ __( 'Previous page', 'rsfv' ) }
					>
						‹
					</button>
					<span className="paging-input">
						<span className="tablenav-paging-text">
							{ currentPage } { __( 'of', 'rsfv' ) }{ ' ' }
							<span className="total-pages">{ totalPages }</span>
						</span>
					</span>
					<button
						className="next-page button"
						onClick={ handleNext }
						disabled={ currentPage === totalPages }
						aria-label={ __( 'Next page', 'rsfv' ) }
					>
						›
					</button>
					<button
						className="last-page button"
						onClick={ handleLast }
						disabled={ currentPage === totalPages }
						aria-label={ __( 'Last page', 'rsfv' ) }
					>
						»
					</button>
				</span>
			</div>
		</div>
	);
};

export default Pagination;
