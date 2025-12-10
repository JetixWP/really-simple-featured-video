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
			const { addQueryArgs } = wp.url;

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

		// Process Plugin Rollback.
		function processPluginRollback( e ) {
			if ( e.preventDefault ) {
				e.preventDefault();
			}

			const version = $( '#rsfv_rollback_version_select_option' ).val();
			const rollbackUrl = addQueryArgs( data.rollback_url, { version: version } );

			window.location.href = rollbackUrl;
			return false;
		}
		$( '#rsfv_rollback_version_button' ).on( 'click', processPluginRollback );


		function submitDiscountRequest( e ) {
			e.preventDefault();

			const email = $( this ).find( 'input[name="email"]' ).val();
			const fname = $( this ).find( 'input[name="first_name"]' ).val();
			const lname = $( this ).find( 'input[name="last_name"]' ).val();

			const elSubmitBtn = $( this ).find( 'input[type=submit]' );
			const messageEl = $( this ).find( '.rsfv-pro-discount-response span' );
			const defaultLabel = elSubmitBtn.data( 'default-label' );
			messageEl.text( '' );
			elSubmitBtn.val( 'Sending...' );

			$.post(
				'https://jetixwp.com/?jwp-api=rsfv_pro_discount_code',
				{
					email: email,
					first_name: JSON.stringify( fname ),
					last_name: JSON.stringify( lname ),
				}
			).done( function( res ) {
				messageEl.text( res?.message );
				elSubmitBtn.val( defaultLabel );
				elSubmitBtn.attr( 'disabled', 'disabled' );
			} ).fail( function(res) {
				messageEl.text( 'Failed to send, please try again or mail us support@jetixwp.com' );
				elSubmitBtn.attr( 'disabled', 'disabled' );
				setTimeout( function() {
					elSubmitBtn.val( defaultLabel );
					elSubmitBtn.removeAttr( 'disabled' );
				}, 2000 );
			} );
		}

		$( '#js-rsfv-pro-request-discount' ).on( 'submit', submitDiscountRequest );

		/**
		 * AJAX getter for Compatibility Engine Status.
		 */
		function updateCompatibilityEngineStatus() {
			const engineStatusEl = $( '#theme-engine-status' );

			engineStatusEl.attr( 'class', 'loading' );

			$.post(
				data.ajax_url,
				{
					action: 'rsfv_current_theme_compat',
					_wpnonce: data.nonce,
				}
			).done( function( res ) {
				const data = res?.data;

				if ( data?.status && data?.engine ) {
					engineStatusEl.attr( 'class', data.status );
					engineStatusEl.text( data.engine );
				}
			} ).fail( function(res) {
				console.log( res );

				engineStatusEl.removeClass( 'loading' );
			} );
		}

		updateCompatibilityEngineStatus();

		}
	);
}( jQuery, rsfv_settings_data ) );
