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



			var poster_frame;

			// Open media modal on “Set Poster Image”
			$(document).on('click', '.rsfv-set-poster', function(e){
				e.preventDefault();

				// reuse or create new frame
				if ( poster_frame ) {
				poster_frame.open();
				return;
				}

				poster_frame = wp.media({
				title: RSFV.uploader_title,          // reuse your localized strings
				button: { text: RSFV.uploader_btn_text },
				library: { type: 'image' },
				multiple: false
				});

				poster_frame.on('select', function(){
				var attachment = poster_frame.state().get('selection').first().toJSON();
				$('#'+ RSFV.meta_poster_key).val(attachment.id);        // you can localize this const too
				$('#rsfv-poster-preview')
					.attr('src', attachment.url)
					.show();
				$('.rsfv-remove-poster').show();
				});

				poster_frame.open();
			});

			// Remove poster
			$(document).on('click', '.rsfv-remove-poster', function(e){
				e.preventDefault();
				$('#'+ RSFV.meta_poster_key).val('');
				$('#rsfv-poster-preview').hide();
				$(this).hide();
			});


			// toggle poster UI on radio change.
			$('input[type=radio][name=rsfv_source]').on('change', function(){
				if ( 'self' === $(this).val() ) {
					$('.rsfv-poster').show();
				} else {
					$('.rsfv-poster').hide();
				}
			});

		}
	);
}( jQuery, RSFV ) );
