=== Buddy Notification Bell ===
Contributors: 1naveengiri, buddydevelopers
Tags: buddypress,buddypress notifications, live, notifications, notifications bell, bell
Requires at least: WordPress 4.5
Tested up to: BuddyPress 2.8.1
Stable tag: 1.0.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Buddy Notification Bell convert buddypress notification to buddypress Notification bell. It show all notification with bell alert and anywhere you want with just one shortcode.

== Description ==

This plugin provide a shortcode 
=== [buddy_notification_bell] === 
to show notification bell.
This bell not only show real-time notification but also it will give a notification bell sound alert like we got in facebook and what\'sapp on new notification recive.

== Installation ==

This section describes how to install the plugin and get it working.


1. Download the zip file and extract
1. Upload `buddy-notification-bell` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the \'Plugins\' menu.
1. Alternatively you can use WordPress Plugin installer from Dashboard->Plugins->Add New to add this plugin
1. Use shortcode [buddy_notification_bell] where ever you want notification to be shown.
1. Enjoy

== Frequently Asked Questions ==

= Does This plugin works without BuddyPress =
No, It needs you to have BuddyPress installed and activated and the BuddyPress notifications component must be enabled

= Where Do I Ask for support? =
Right now I don\'t have any for support forum but if you have any query related to plugin you can email 1naveengiri@gmail.com.

== Screenshots ==

== Changelog ==

= 1.0.0=
1. Complete rewrite for better code and efficiency. 
2. Uses WordPress heartbeat api instead of long polling via the ajax. 
3. Allows theme authors to replace the inbuilt notification UI with notification bell . 

= 1.0.1=
1. Fix Fatal error when Notification Module is inactive
2. Code Improvement removed some debugging code.
3. Add Notice when BuddyPress is inactive
4. Add Unit Test for the Plug in

 == Upgrade Notice ==
Buddy Notification Bell 1.0.1, Minor release which fixes Fatal error when Notification Module is inactive