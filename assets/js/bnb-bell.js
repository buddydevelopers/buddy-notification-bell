/* global bnbData, wp */
( function ( $ ) {
	'use strict';

	if ( typeof bnbData === 'undefined' ) {
		return;
	}

	var lastNotified = parseInt( bnbData.lastNotified, 10 ) || 0;
	var audio        = null;
	var pollTimer    = null;

	/* ── Boot ───────────────────────────────────────────────────────────── */

	$( function () {
		if ( 'yes' === bnbData.soundEnabled ) {
			audio = new Audio( bnbData.soundUrl );
			audio.preload = 'auto';
		}

		// Initial count fetch.
		fetchCount();

		// Poll every N seconds for updated count.
		var interval = parseInt( bnbData.pollInterval, 10 ) || 30;
		pollTimer = setInterval( fetchCount, interval * 1000 );

		// Keep WP Heartbeat running for real-time push.
		if ( typeof wp !== 'undefined' && wp.heartbeat ) {
			wp.heartbeat.interval( 'fast' );
		}

		bindEvents();
	} );

	/* ── Events ─────────────────────────────────────────────────────────── */

	function bindEvents() {
		// Bell click: toggle dropdown, fetch notifications on open.
		$( document ).on( 'click', '.bnb-bell-button', function ( e ) {
			e.stopPropagation();
			var $wrapper = $( this ).closest( '.bnb-bell-wrapper' );
			var isOpen   = 'true' === $( this ).attr( 'aria-expanded' );

			if ( isOpen ) {
				closeDropdown( $wrapper );
			} else {
				openDropdown( $wrapper );
				fetchNotifications( $wrapper );
			}
		} );

		// Close on outside click.
		document.addEventListener( 'click', function ( e ) {
			$( '.bnb-bell-wrapper' ).each( function () {
				var $wrapper = $( this );
				if ( ! $wrapper[0].contains( e.target ) ) {
					closeDropdown( $wrapper );
				}
			} );
		} );

		// Close on Escape.
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				$( '.bnb-bell-wrapper' ).each( function () {
					closeDropdown( $( this ) );
				} );
			}
		} );

		// Notification item click: mark as read, then navigate.
		$( document ).on( 'click', '.bnb-notification-item', function ( e ) {
			if ( $( e.target ).closest( '.bnb-dismiss' ).length ) {
				return; // Let dismiss handler handle it.
			}
			var id   = absInt( $( this ).data( 'id' ) );
			var href = $( this ).data( 'href' );
			dismissNotification( id, $( this ) );
			if ( href ) {
				window.location.href = href;
			}
		} );

		// Dismiss button: mark as read, remove from DOM.
		$( document ).on( 'click', '.bnb-dismiss', function ( e ) {
			e.stopPropagation();
			var $item = $( this ).closest( '.bnb-notification-item' );
			var id    = absInt( $item.data( 'id' ) );
			dismissNotification( id, $item );
		} );

		// Mark all read.
		$( document ).on( 'click', '.bnb-mark-all-read', function () {
			var $wrapper = $( this ).closest( '.bnb-bell-wrapper' );
			$.post( bnbData.ajaxUrl, {
				action: 'bnb_mark_all_read',
				nonce:  bnbData.nonce,
			}, function ( res ) {
				if ( res.success ) {
					$wrapper.find( '.bnb-notification-item' ).removeClass( 'bnb-unread' );
					updateCount( 0 );
				}
			} );
		} );

		// Heartbeat: send last notified ID.
		$( document ).on( 'heartbeat-send', function ( e, data ) {
			data['bnb-data'] = { last_notified: lastNotified };
		} );

		// Heartbeat: receive new notifications (real-time push).
		$( document ).on( 'heartbeat-tick', function ( e, data ) {
			if ( ! data.hasOwnProperty( 'bnb-data' ) ) {
				return;
			}
			var bnb      = data['bnb-data'];
			lastNotified = parseInt( bnb.last_notified, 10 ) || lastNotified;

			var messages = bnb.messages || [];
			if ( ! messages.length ) {
				return;
			}

			// Fire backward-compat event.
			$( document ).trigger( 'bnb:new_notifications', [ { count: messages.length, messages: messages } ] );

			ringBell();
			playSound();

			// Get accurate count from server (avoids race with polling).
			fetchCount();

			// If the dropdown is already open, refresh the list so the new item appears.
			var $openWrapper = $( '.bnb-bell-wrapper' ).filter( function () {
				return 'true' === $( this ).find( '.bnb-bell-button' ).attr( 'aria-expanded' );
			} );
			if ( $openWrapper.length ) {
				fetchNotifications( $openWrapper );
			}
		} );
	}

	/* ── Dropdown open / close ──────────────────────────────────────────── */

	function openDropdown( $wrapper ) {
		$wrapper.find( '.bnb-bell-button' ).attr( 'aria-expanded', 'true' );
		$wrapper.find( '.bnb-dropdown' ).removeAttr( 'hidden' );
	}

	function closeDropdown( $wrapper ) {
		$wrapper.find( '.bnb-bell-button' ).attr( 'aria-expanded', 'false' );
		$wrapper.find( '.bnb-dropdown' ).attr( 'hidden', '' );
	}

	/* ── AJAX: fetch notifications ──────────────────────────────────────── */

	function fetchNotifications( $wrapper ) {
		var $list  = $wrapper.find( '.bnb-notification-list' );
		var $empty = $wrapper.find( '.bnb-empty' );

		$list.html( '<div class="bnb-loading">' + bnbData.i18n.loading + '</div>' );
		$empty.hide();

		$.post( bnbData.ajaxUrl, {
			action: 'bnb_get_notifications',
			nonce:  bnbData.nonce,
		}, function ( res ) {
			$list.empty();

			if ( ! res.success || ! res.data || ! res.data.length ) {
				$empty.show();
				return;
			}

			res.data.forEach( function ( notif ) {
				$list.append( buildItem( notif ) );
			} );

			// Sync badge to actual server count after rendering.
			fetchCount();
		} );
	}

	/* ── AJAX: fetch count (polling) ────────────────────────────────────── */

	function fetchCount() {
		$.post( bnbData.ajaxUrl, {
			action: 'bnb_get_count',
			nonce:  bnbData.nonce,
		}, function ( res ) {
			if ( res.success && typeof res.data.count !== 'undefined' ) {
				updateCount( res.data.count );
			}
		} );
	}

	/* ── AJAX: dismiss single notification ──────────────────────────────── */

	function dismissNotification( id, $item ) {
		if ( ! id ) {
			return;
		}
		$.post( bnbData.ajaxUrl, {
			action:          'bnb_dismiss_notification',
			nonce:           bnbData.nonce,
			notification_id: id,
		}, function ( res ) {
			if ( res.success ) {
				$item.remove();
				fetchCount();

				// Show empty state if list is now empty.
				var $list = $( '.bnb-notification-list' );
				if ( ! $list.find( '.bnb-notification-item' ).length ) {
					$list.closest( '.bnb-dropdown' ).find( '.bnb-empty' ).show();
				}
			}
		} );
	}

	/* ── Build notification item DOM ────────────────────────────────────── */

	function buildItem( notif ) {
		var $item = $( '<div>' )
			.addClass( 'bnb-notification-item' + ( notif.is_new ? ' bnb-unread' : '' ) )
			.attr( 'data-id', notif.id )
			.attr( 'data-href', notif.href );

		if ( notif.avatar_url ) {
			$item.append(
				$( '<img>' ).addClass( 'bnb-avatar' ).attr( 'src', notif.avatar_url ).attr( 'alt', '' )
			);
		}

		var $content = $( '<div>' ).addClass( 'bnb-content' );
		$content.append( $( '<p>' ).addClass( 'bnb-message' ).text( notif.text ) );
		$content.append( $( '<span>' ).addClass( 'bnb-time' ).text( notif.time_diff ) );
		$item.append( $content );

		$item.append(
			$( '<button>' )
				.addClass( 'bnb-dismiss' )
				.attr( 'type', 'button' )
				.attr( 'aria-label', bnbData.i18n.dismiss )
				.text( '×' ) // ×
		);

		return $item;
	}

	/* ── Count badge ─────────────────────────────────────────────────────── */

	function updateCount( count ) {
		if ( 'yes' !== bnbData.showCount ) {
			return;
		}
		$( '.bnb-count' ).each( function () {
			if ( count > 0 ) {
				$( this ).text( count ).show();
			} else {
				$( this ).hide();
			}
		} );
	}

	/* ── Bell ring animation ─────────────────────────────────────────────── */

	function ringBell() {
		$( '.bnb-bell-wrapper' ).addClass( 'bnb-bell-ringing' );
		setTimeout( function () {
			$( '.bnb-bell-wrapper' ).removeClass( 'bnb-bell-ringing' );
		}, 900 );
	}

	/* ── Sound ───────────────────────────────────────────────────────────── */

	function playSound() {
		if ( 'yes' !== bnbData.soundEnabled || ! audio ) {
			return;
		}
		audio.currentTime = 0;
		audio.play().catch( function () {} );
	}

	/* ── Helpers ─────────────────────────────────────────────────────────── */

	function absInt( val ) {
		var n = parseInt( val, 10 );
		return isNaN( n ) || n < 0 ? 0 : n;
	}

}( jQuery ) );
