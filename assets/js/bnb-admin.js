/* Buddy Notification Bell — Admin JS */
( function ( $ ) {
	'use strict';

	var soundContext;

	$( function () {
		var soundFrame;

		// Copy shortcode to clipboard.
		$( document ).on( 'click', '.bnb-copy-btn', function () {
			var $btn  = $( this );
			var text  = $btn.data( 'clipboard' ) || $btn.closest( '.bnb-shortcode-box' ).find( 'code' ).text();

			if ( ! navigator.clipboard ) {
				return;
			}

			navigator.clipboard.writeText( text ).then( function () {
				var original = $btn.text();
				$btn.text( bnbAdminData.copied );
				setTimeout( function () {
					$btn.text( original );
				}, 2000 );
			} );
		} );

		// Select a notification sound from the WordPress Media Library.
		$( document ).on( 'click', '.bnb-select-sound', function () {
			if ( soundFrame ) {
				soundFrame.open();
				return;
			}

			soundFrame = wp.media( {
				title: bnbAdminData.chooseSound,
				button: {
					text: bnbAdminData.useSound
				},
				library: {
					type: 'audio'
				},
				multiple: false
			} );

			soundFrame.on( 'select', function () {
				var attachment = soundFrame.state().get( 'selection' ).first().toJSON();
				$( 'input[name="bnb_sound_preset"][value="custom"]' ).prop( 'checked', true ).trigger( 'change' );
				$( '#bnb_sound_url' ).val( attachment.url ).trigger( 'change' );
			} );

			soundFrame.open();
		} );

		// Keep the preview player in sync with the selected URL.
		$( document ).on( 'change input', '#bnb_sound_url', function () {
			var $input = $( this );
			var $picker = $input.closest( '.bnb-sound-picker' );
			var url = $input.val() || $picker.find( '.bnb-sound-preview' ).data( 'default-url' );

			$picker.find( '.bnb-sound-preview' ).attr( 'src', url );
			$picker.find( '.bnb-reset-sound' ).prop( 'hidden', ! $input.val() );
		} );

		// Show the Media Library controls only for the custom sound option.
		$( document ).on( 'change', 'input[name="bnb_sound_preset"]', function () {
			var isCustom = 'custom' === $( this ).val();
			$( '.bnb-custom-sound' ).prop( 'hidden', ! isCustom );
		} );

		// Preview a built-in preset or the selected custom audio file.
		$( document ).on( 'click', '.bnb-preview-preset', function ( event ) {
			var preset = $( this ).data( 'preset' );
			var $picker = $( this ).closest( '.bnb-sound-picker' );

			event.preventDefault();
			$( 'input[name="bnb_sound_preset"][value="' + preset + '"]' ).prop( 'checked', true ).trigger( 'change' );

			if ( 'default' === preset || 'custom' === preset ) {
				var audio = $picker.find( '.bnb-sound-preview' ).get( 0 );
				audio.currentTime = 0;
				audio.play().catch( function () {} );
				return;
			}

			playPreset( preset );
		} );

		// Clear the custom URL so the bundled sound is used.
		$( document ).on( 'click', '.bnb-reset-sound', function () {
			$( '#bnb_sound_url' ).val( '' ).trigger( 'change' );
		} );
	} );

	/**
	 * Play a generated notification sound from the built-in library.
	 *
	 * @param {string} preset Preset key.
	 */
	function playPreset( preset ) {
		var AudioContext = window.AudioContext || window.webkitAudioContext;
		var patterns = {
			chime:   { notes: [ 523.25, 659.25, 783.99 ], duration: 0.14, type: 'sine' },
			pop:     { notes: [ 740, 370 ], duration: 0.09, type: 'sine' },
			sparkle: { notes: [ 880, 1174.66, 1567.98 ], duration: 0.1, type: 'triangle' },
			soft:    { notes: [ 440, 554.37 ], duration: 0.22, type: 'sine' }
		};
		var pattern = patterns[ preset ];

		if ( ! AudioContext || ! pattern ) {
			return;
		}

		soundContext = soundContext || new AudioContext();
		soundContext.resume().catch( function () {} );

		pattern.notes.forEach( function ( frequency, index ) {
			var start = soundContext.currentTime + ( index * pattern.duration );
			var end = start + pattern.duration + 0.08;
			var oscillator = soundContext.createOscillator();
			var gain = soundContext.createGain();

			oscillator.type = pattern.type;
			oscillator.frequency.setValueAtTime( frequency, start );
			gain.gain.setValueAtTime( 0.0001, start );
			gain.gain.exponentialRampToValueAtTime( 0.16, start + 0.015 );
			gain.gain.exponentialRampToValueAtTime( 0.0001, end );
			oscillator.connect( gain );
			gain.connect( soundContext.destination );
			oscillator.start( start );
			oscillator.stop( end );
		} );
	}

}( jQuery ) );
