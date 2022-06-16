/**
 * File rsfv-media.js.
 *
 * Plugin media script.
 *
 * @package RSFV
 */

(function($, RSFV ){
	$(
		function() {
			// Selecting video.
			$( 'body' ).on(
				'click',
				'.rsfv-upload-video-btn',
				function (e) {
					e.preventDefault();
					var button     = $( this ),
					customUploader = wp.media(
						{
							title: RSFV.uploader_title,
							library: {
								type: 'video'
							},
							button: {
								text: RSFV.uploader_btn_text // button label text.
							},
							multiple: false // for multiple image selection set to true.
						}
					).on(
						'select',
						function () { // it also has "open" and "close" events.
							var attachment = customUploader.state().get( 'selection' ).first().toJSON();
							$( button ).removeClass( 'button' ).html( '<video controls="" src="' + attachment.url + '"></video>' ).next().val( attachment.id ).next().show();
						}
					)
					.open();
				}
			);

			// Removing video.
			$( 'body' ).on(
				'click',
				'.remove-video',
				function () {
					$( this ).hide().prev().val( '' ).prev().addClass( 'button' ).html( 'Upload Video' );
					return false;
				}
			);

			// Toggles video input source.
			function toggleVideoInput( val ) {
				console.log( val, typeof val );
				if ( 'self' === val ) {
					$( '.rsfv-self' ).show();
					$( '.rsfv-embed' ).hide();
				} else {
					$( '.rsfv-embed' ).show();
					$( '.rsfv-self' ).hide();
				}
			}

			toggleVideoInput( $( 'input[type=radio][name=rsfv_source]:checked' ).val() );
			$( 'input[type=radio][name=rsfv_source]' ).on(
				'change',
				function() {
					toggleVideoInput( $( this ).val() );
				}
			);

		}
	);
}( jQuery, RSFV ) );
