/* global bnbData, wp */
( function ( $ ) {
	'use strict';

	if ( typeof bnbData === 'undefined' ) {
		return;
	}

	var lastNotified    = parseInt( bnbData.lastNotified, 10 ) || 0;
	var lastMsgCount    = -1;    // -1 = baseline not yet established.
	var msgBaselineSet  = false; // true after first heartbeat tick sets the baseline.
	var lastKnownCount  = 0;     // tracks displayed notification count for tab-return check.
	var audio           = null;
	var audioUnlocked   = false; // Safari requires a user gesture before audio.play() works.
	var pollTimer       = null;

	// Tab alert state (title blink + favicon dot when tab is in background).
	var originalTitle   = document.title;
	var titleBlinkTimer = null;
	var tabAlertCount   = 0;
	var faviconEl       = document.querySelector( 'link[rel~="icon"]' );
	var originalFavicon = faviconEl ? faviconEl.href : '';
	// Orange dot favicon matching the plugin brand colour.
	var alertFavicon    = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="%23f0640c" stroke="white" stroke-width="1.5"/></svg>';

	/* ── Boot ───────────────────────────────────────────────────────────── */

	$( function () {
		if ( 'yes' === bnbData.soundEnabled ) {
			audio = new Audio( bnbData.soundUrl );
			audio.preload = 'auto';

			// Safari blocks audio.play() until the user has interacted with the page.
			// Play+pause silently on first click/touch to satisfy the gesture requirement.
			function unlockAudio() {
				if ( audioUnlocked || ! audio ) {
					return;
				}
				audio.play().then( function () {
					audio.pause();
					audio.currentTime = 0;
					audioUnlocked = true;
					document.removeEventListener( 'click', unlockAudio );
					document.removeEventListener( 'touchstart', unlockAudio );
				} ).catch( function () {} );
			}
			document.addEventListener( 'click', unlockAudio );
			document.addEventListener( 'touchstart', unlockAudio );
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
		// Bell click: in BuddyBoss mode delegate to BB panel; otherwise toggle own dropdown.
		$( document ).on( 'click', '.bnb-bell-button', function ( e ) {
			e.stopPropagation();

			if ( 'yes' === bnbData.buddybossMode ) {
				var $bbBtn = $( bnbData.buddybossSelector );
				if ( $bbBtn.length ) {
					$bbBtn.trigger( 'click' );
				}
				return;
			}

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

		// Notification item click: navigate only — do not mark as read.
		// BP shows it in the Unread tab; user marks read explicitly via × or Mark all read.
		$( document ).on( 'click', '.bnb-notification-item', function ( e ) {
			if ( $( e.target ).closest( '.bnb-dismiss' ).length ) {
				return; // Let dismiss handler handle it.
			}
			var href = $( this ).data( 'href' );
			if ( href && /^https?:\/\//i.test( href ) ) {
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
			var $btn     = $( this );
			var $wrapper = $btn.closest( '.bnb-bell-wrapper' );
			$btn.prop( 'disabled', true );
			$.post( bnbData.ajaxUrl, {
				action: 'bnb_mark_all_read',
				nonce:  bnbData.nonce,
			}, function ( res ) {
				$btn.prop( 'disabled', false );
				if ( res.success ) {
					$wrapper.find( '.bnb-notification-list' ).empty();
					$wrapper.find( '.bnb-empty' ).show();
					updateCount( 0 );
					setTimeout( function () { closeDropdown( $wrapper ); }, 400 );
				}
			} );
		} );

		// Heartbeat: send last notified ID.
		$( document ).on( 'heartbeat-send', function ( e, data ) {
			data['bnb-data'] = { last_notified: lastNotified };
		} );

		// Heartbeat: receive counts and new notification items.
		$( document ).on( 'heartbeat-tick', function ( e, data ) {
			if ( ! data.hasOwnProperty( 'bnb-data' ) ) {
				return;
			}
			var bnb      = data['bnb-data'];
			lastNotified = parseInt( bnb.last_notified, 10 ) || lastNotified;

			// Sync notification count badge on every tick (keeps badge in sync when
			// BB marks notifications read via its own panel).
			if ( typeof bnb.total_count !== 'undefined' ) {
				updateCount( bnb.total_count );
			}

			// Message count: JS owns "is new" detection to avoid the absint(-1) sentinel bug.
			if ( bnb.message_count >= 0 ) {
				updateBBMessageCount( bnb.message_count );
				if ( msgBaselineSet && bnb.message_count > lastMsgCount ) {
					ringBell();
					playSound();
					$( document ).trigger( 'bnb:new_messages', [ { count: bnb.message_count } ] );
					startTabAlert( ( bnb.total_count || 0 ) + bnb.message_count );
				}
				lastMsgCount   = bnb.message_count;
				msgBaselineSet = true;
			}

			// New notification items.
			var messages = bnb.messages || [];
			if ( messages.length ) {
				$( document ).trigger( 'bnb:new_notifications', [ { count: messages.length, messages: messages } ] );

				ringBell();
				playSound();
				startTabAlert( ( bnb.total_count || 0 ) + Math.max( 0, bnb.message_count || 0 ) );

				// If the dropdown is already open, refresh the list so the new item appears.
				var $openWrapper = $( '.bnb-bell-wrapper' ).filter( function () {
					return 'true' === $( this ).find( '.bnb-bell-button' ).attr( 'aria-expanded' );
				} );
				if ( $openWrapper.length ) {
					fetchNotifications( $openWrapper );
				}
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

		$list.empty().append( $( '<div>' ).addClass( 'bnb-loading' ).text( bnbData.i18n.loading ) );
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

		if ( notif.avatar_url && /^https?:\/\//i.test( notif.avatar_url ) ) {
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
		lastKnownCount = count;
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

	/* ── Tab visibility: catch up when user returns to this tab ─────────── */

	document.addEventListener( 'visibilitychange', function () {
		if ( ! document.hidden ) {
			checkOnTabReturn();
		}
	} );

	function checkOnTabReturn() {
		// Restore title and favicon immediately — don't wait for AJAX.
		stopTabAlert();

		$.post( bnbData.ajaxUrl, {
			action: 'bnb_get_count',
			nonce:  bnbData.nonce,
		}, function ( res ) {
			if ( ! res.success ) {
				return;
			}

			var newCount   = typeof res.data.count !== 'undefined' ? res.data.count : 0;
			var shouldRing = false;

			if ( newCount > lastKnownCount ) {
				shouldRing = true;
			}
			updateCount( newCount );

			// Check messages too if the server returned a message count.
			if ( typeof res.data.message_count !== 'undefined' && res.data.message_count >= 0 ) {
				var newMsgCount = res.data.message_count;
				updateBBMessageCount( newMsgCount );
				if ( msgBaselineSet && newMsgCount > lastMsgCount ) {
					shouldRing = true;
				}
				lastMsgCount = newMsgCount;
			}

			if ( shouldRing ) {
				ringBell();
				playSound();
			}
		} );
	}

	function startTabAlert( count ) {
		if ( ! document.hidden || count <= 0 ) {
			return;
		}
		tabAlertCount   = count;
		var alertTitle  = '(' + count + ') ' + originalTitle;
		var toggle      = false;

		// Switch favicon to orange dot.
		if ( faviconEl ) {
			faviconEl.href = alertFavicon;
		}

		// Blink title between count and original.
		clearInterval( titleBlinkTimer );
		document.title  = alertTitle;
		titleBlinkTimer = setInterval( function () {
			document.title = toggle ? originalTitle : alertTitle;
			toggle         = ! toggle;
		}, 1200 );
	}

	function stopTabAlert() {
		clearInterval( titleBlinkTimer );
		titleBlinkTimer = null;
		document.title  = originalTitle;
		tabAlertCount   = 0;
		if ( faviconEl && originalFavicon ) {
			faviconEl.href = originalFavicon;
		}
	}

	/* ── BuddyBoss message count sync ───────────────────────────────────── */

	function updateBBMessageCount( count ) {
		var $wrap  = $( bnbData.buddybossMessageSelector );
		if ( ! $wrap.length ) {
			return;
		}
		var $badge = $wrap.find( '.count' );
		if ( count > 0 ) {
			if ( $badge.length ) {
				$badge.text( count );
			} else {
				$wrap.append( $( '<span>' ).addClass( 'count' ).text( count ) );
			}
		} else {
			$badge.remove();
		}
	}

	/* ── Helpers ─────────────────────────────────────────────────────────── */

	function absInt( val ) {
		var n = parseInt( val, 10 );
		return isNaN( n ) || n < 0 ? 0 : n;
	}

}( jQuery ) );
