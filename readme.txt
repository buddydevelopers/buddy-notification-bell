=== Buddy Notification Bell ===
Contributors: 1naveengiri, buddydevelopers, codex007
Donate link: https://www.paypal.me/1naveengiri/500
Tags: buddypress, buddyboss, notifications, notification bell, real-time notifications
Requires at least: WordPress 5.5
Tested up to: 6.9
Stable tag: 2.0.1
Requires PHP: 7.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Real-time notification bell for BuddyPress & BuddyBoss with sound alerts, LinkedIn-style dropdown, and floating bell shortcode.

== Description ==

Buddy Notification Bell adds a real-time notification bell to your BuddyPress or BuddyBoss site. New notifications are detected automatically using the WordPress Heartbeat API — no page reload needed.

**Key features:**

* Real-time detection of new notifications via WordPress Heartbeat API
* Sound alert on new notification (enable/disable in settings)
* LinkedIn-style dropdown panel with avatar, text, and time
* Unread count badge on the bell icon
* Floating bell button option (great for block/FSE themes)
* Place the bell anywhere with the `[buddy_notification_bell]` shortcode
* Auto-injects into the primary navigation menu (classic themes)
* Mark all notifications as read with one click
* Individual or grouped notification display style
* Full BuddyBoss Platform compatibility — including an option to trigger BuddyBoss's own notification panel on bell click
* Secure: all AJAX endpoints nonce-verified, all output properly escaped

== Installation ==

This section describes how to install the plugin and get it working.


1. Download the zip file and extract
1. Upload `buddy-notification-bell` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu.
1. Alternatively you can use WordPress Plugin installer from Dashboard->Plugins->Add New to add this plugin
1. Use shortcode [buddy_notification_bell] where ever you want notification to be shown.
1. Enjoy

== Frequently Asked Questions ==

= Does this plugin work with BuddyBoss Platform? =
Yes. Full BuddyBoss Platform support is included. All BuddyBoss notification types (connections, follow, groups, messages, etc.) display correctly in the bell dropdown. You can also enable the "Use BuddyBoss Notification Panel" option so the bell triggers BuddyBoss's native panel instead of the built-in dropdown.

= Does this plugin work with bbPress? =
Yes, it works with bbPress. You must have the BuddyPress Notifications module active.

= Where do I ask for support? =
Email buddydevelopers@gmail.com or 1naveengiri@gmail.com.

= How do I change the bell icon? =
Use this filter in your theme's functions.php:
```
add_filter( 'buddy_bell_icon', function( $icon ) {
    return '<i class="fas fa-bell fa-2x"></i>';
} );
```

= How do I place the bell in a custom location? =
Use the shortcode `[buddy_notification_bell]` on any page, widget, or template. You can also call it from PHP: `echo do_shortcode( '[buddy_notification_bell]' );`

= Can I show a floating bell button? =
Yes. Go to Notification → Display and enable "Show Floating Bell". It appears fixed at the bottom-right corner — useful for block/FSE themes.

== Demo ==

https://www.youtube.com/watch?v=seMBJZB-vu8

== Screenshots ==

1. Notification Bell with Notification Drop down by BuddyDeveloper[http://buddydevelopers.com]
2. Short code to show the Bell Icon.
3. setting to enable disable notification bell in primary menu.

== Changelog ==

= 2.0.0 =
* Complete rewrite with modern BD_BNB_ architecture and LinkedIn-style bell UI
* Real-time notifications via WordPress Heartbeat API
* Sound alert on new notification (configurable)
* LinkedIn-style dropdown with avatar, notification text, and relative time
* Floating bell option for block/FSE themes
* Individual and grouped notification display styles
* Mark all read with one click
* Full BuddyBoss Platform integration — all notification types (connections, follow, groups, etc.) display correctly
* Option to use BuddyBoss's own notification panel on bell click
* Background tab alerts and real-time unread count badge updates
* Security hardening: nonce verification on all AJAX endpoints, sanitised input, escaped output
* Suppressed third-party admin notices on the plugin settings page
* Fixed mark-all-read using direct DB update for reliability
* Fixed BuddyBoss new-style notification actions (bb_connections_new_request, bb_following_new, etc.) correctly resolving notification text

= 1.0.4 =
* Add plugin translation files
* Fix style issues for bell notification container

= 1.0.3 =
* Test plugin with latest WordPress and BuddyPress versions
* Fix error for bbPress Notifications
* Update readme

= 1.0.2 =
* Tested with BuddyPress 5.1.2
* Hide notification count when no new notifications
* Set a default position of bell in primary menu on plugin activation
* Update plugin readme

= 1.0.1 =
* Fix fatal error when Notification Module is inactive
* Code improvement — removed debugging code
* Add notice when BuddyPress is inactive

= 1.0.0 =
* Initial release
* Uses WordPress Heartbeat API instead of long polling via AJAX
* Allows theme authors to replace the built-in notification UI via filter

== Upgrade Notice ==

= 2.0.0 =
Major release — complete rewrite. Includes BuddyBoss Platform support, a new LinkedIn-style dropdown UI, floating bell option, grouped notifications, sound alerts, and significant security improvements. Recommended for all users.