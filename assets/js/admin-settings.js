/**
 * Admin settings script.
 *
 * { global, rsfv_settings_data }
 *
 * @package RSFV
 */

( function( $, data ) {
	$(
		function() {
			// Edit prompt.
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

			// Select all/none.
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

			const collBtn      = document.getElementsByClassName( 'collapsible' );
			const collBtnCount = collBtn.length;
			let i;

			for ( i = 0; i < collBtnCount; i++ ) {
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

			$( 'body' ).on(
				'click',
				'.rsfv-upload-image-btn',
				function (e) {
					e.preventDefault();
					const button     = $( this ),
						customUploader = wp.media(
							{
								title: data.uploader_title,
								library: {
									type: 'image'
								},
								button: {
									text: data.uploader_btn_text // button label text.
								},
								multiple: false // for multiple image selection set to true.
							}
						).on(
							'select',
							function () {
								// it also has "open" and "close" events.
								const attachment       = customUploader.state().get( 'selection' ).first().toJSON();
								const image_element_id = $( button ).attr( 'data-element-id' );
								$( `#${image_element_id}` ).attr( 'src', attachment.url );
								$( button ).next().show();
								$( button ).next().next().val( attachment.id );
							}
						)
							.open();
				}
			);

			// Removing video.
			$( 'body' ).on(
				'click',
				'.rsfv-remove-image-btn',
				function () {
					const default_image = $( this ).attr( 'data-default-image' );
					$( this ).prev().prev().attr( 'src', default_image );
					$( this ).next().val( '' );
					$( this ).hide();
					return false;
				}
			);

			$( '.rsfv-theme-compatibility-select' ).select2();
		}
	);
}( jQuery, rsfv_settings_data ) );
