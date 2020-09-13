/* global rsfv_settings_data */
( function( $, data ) {
	$(
		function() {
			// Edit prompt
			$(
				function() {
					let changed = false;

					$( 'input, textarea, select, checkbox' ).change(
						function() {
							changed = true;
						}
					);

					$( '.rsfv-nav-tab-wrapper a' ).click(
						function() {
							if ( changed ) {
								  window.onbeforeunload = function() {
									return data.i18n_nav_warning;
								  };
							} else {
								window.onbeforeunload = '';
							}
						}
					);

					$( '.submit :input' ).click(
						function() {
							window.onbeforeunload = '';
						}
					);
				}
			);

			// Select all/none
			$( '.rsfv' ).on(
				'click',
				'.select_all',
				function() {
					$( this )
					.closest( 'td' )
					.find( 'select option' )
					.attr( 'selected', 'selected' );
					$( this )
					.closest( 'td' )
					.find( 'select' )
					.trigger( 'change' );
					return false;
				}
			);

			$( '.rsfv' ).on(
				'click',
				'.select_none',
				function() {
					$( this )
					.closest( 'td' )
					.find( 'select option' )
					.removeAttr( 'selected' );
					$( this )
					.closest( 'td' )
					.find( 'select' )
					.trigger( 'change' );
					return false;
				}
			);

			const collBtn = document.getElementsByClassName( 'collapsible' );
			let i;

			for ( i = 0; i < collBtn.length; i++ ) {
				collBtn[ i ].addEventListener(
					'click',
					function( e ) {
						e.preventDefault();
						this.classList.toggle( 'active' );
						const content = this.nextElementSibling;
						if ( content.style.maxHeight ) {
							content.style.maxHeight = null;
						} else {
							content.style.maxHeight = content.scrollHeight + 'px';
						}
					}
				);
				if ( i === 0 ) {
					   $( collBtn[ i ] ).trigger( 'click' );
				}
			}
		}
	);
}( jQuery, rsfv_settings_data ) );
