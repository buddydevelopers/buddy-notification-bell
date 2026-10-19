/* Buddy Notification Bell — Admin JS */
( function ( $ ) {
	'use strict';

	$( function () {
		$( document ).on( 'click', '.bnb-copy-btn', function ( event ) {
			event.preventDefault();

			var $btn = $( this );
			var text = $btn.attr( 'data-clipboard' );

			if ( ! text || ! navigator.clipboard ) {
				return;
			}

			navigator.clipboard.writeText( text ).then( function () {
				var original = $btn.data( 'label' ) || $btn.text();
				var copied   = ( window.bnbAdmin && bnbAdmin.copied ) ? bnbAdmin.copied : 'Copied';

				$btn.data( 'label', original );
				$btn.addClass( 'is-copied' ).text( copied );

				window.setTimeout( function () {
					$btn.removeClass( 'is-copied' ).text( original );
				}, 1600 );
			} );
		} );
	} );

}( jQuery ) );
