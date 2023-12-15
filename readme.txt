=== Buddy Notification Bell ===
Contributors: 1naveengiri, buddydevelopers, codex007
Donate link: https://www.paypal.me/1naveengiri/500
Tags: buddypress, buddypress notifications, notifications, notifications bell, facebook like notification
Requires at least: WordPress 4.5
Tested up to: WordPress 6.4.2
Stable tag: 1.0.4
Requires PHP: 7.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Buddy Notification Bell convert BuddyPress notification to BuddyPress Bell Notification. It shows all notification with bell alert and anywhere you want just with one shortcode.

== Description ==

Plugin shows all BuddyPress notification with Bell alert. You can place your Notifications Bell anywhere, just with a shortcode [buddy_notification_bell] to show notification bell.
This bell not only show real-time notification but also it gives a notification bell sound alert. 
Same like we get in facebook on new notification receive.

== Installation ==

This section describes how to install the plugin and get it working.


1. Download the zip file and extract
1. Upload `buddy-notification-bell` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu.
1. Alternatively you can use WordPress Plugin installer from Dashboard->Plugins->Add New to add this plugin
1. Use shortcode [buddy_notification_bell] where ever you want notification to be shown.
1. Enjoy

== Frequently Asked Questions ==

= Does This plugin works with bbPress =
Yes, it works with bbPress but since it is a BuddyPress addon. You must have the BuddyPress notification module active. 

= Where Do I Ask for support? =
If you have any query related to the plugin you can email buddydevelopers@gmail.com or 1naveengiri@gmail.com.

= How to change the bell icon? =
You can use this snippet. 
```
add_filter( 'buddy_bell_icon', function( $icon ){
      return '<i class="fas fa-bell fa-2x"></i>';
});
```

== Demo ==

https://www.youtube.com/watch?v=seMBJZB-vu8

== Screenshots ==

1. Notification Bell with Notification Drop down by BuddyDeveloper[http://buddydevelopers.com]
2. Short code to show the Bell Icon.
3. setting to enable disable notification bell in primary menu.

== Changelog ==

= 1.0.4 =
1. Add plugin translation files
2. Fix style issues for bell notification container

= 1.0.3 =
1. Test plugin with latest wp and bp.
2. Fix error for bbPress Notifications.
3. update readme

= 1.0.2 =
1. Tested with BuddyPress 5.1.2
2. Hide Notification count when no new notification
3. Set a default position of bell in primary menu on Plugin activation
4. Update Plugin readme issues

= 1.0.1 =
1. Fix Fatal error when Notification Module is inactive
2. Code Improvement removed some debugging code.
3. Add Notice when BuddyPress is inactive

= 1.0.0 =
1. Complete rewrite for better code and efficiency. 
2. Uses WordPress heartbeat api instead of long polling via the ajax. 
3. Allows theme authors to replace the inbuilt notification UI with notification bell . 


 == Upgrade Notice ==
Buddy Notification Bell 1.0.2, Minor release. Tested with new WordPress and BuddyPress versions. Also includes few fixes and plugin readme improvement.