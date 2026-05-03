/* Buddy Notification Bell — Admin JS */
( function ( $ ) {
	'use strict';

	$( function () {
		// Copy shortcode to clipboard.
		$( document ).on( 'click', '.bnb-copy-btn', function () {
			var $btn  = $( this );
			var text  = $btn.data( 'clipboard' ) || $btn.closest( '.bnb-shortcode-box' ).find( 'code' ).text();

			if ( ! navigator.clipboard ) {
				return;
			}

			navigator.clipboard.writeText( text ).then( function () {
				var original = $btn.text();
				$btn.text( 'Copied!' );
				setTimeout( function () {
					$btn.text( original );
				}, 2000 );
			} );
		} );
	} );

}( jQuery ) );
