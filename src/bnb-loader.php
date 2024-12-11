<?php
/**
 * This file is the functionality loader.
 * It will include plugin setting file and call the proper files based on the setting.
 *
 * @since 2.0.0
 * @package BNB\Loader
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// load i18n. 
// handle file loading.
// add freemius code.
/**
 * The core plugin class that is used to handle admin page operations required for plugin
 * admin-specific hooks, and public-facing site hooks.
 */
require BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . '/src/admin/class-bnb-settings.php';
require BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . '/src/public/class-notification-bell-public.php';

/**
 *  Check if buddypress activate.
 */
function bnb_check_buddypress_actiavted_or_not() {
	if ( class_exists( 'BuddyPress' ) ) {
		require BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . '/src/public/class-bp-notification-bell.php';
	}
}
add_action( 'plugins_loaded', 'bnb_check_buddypress_actiavted_or_not' );

/**
 * 1. Store notifications in the DB
 * 2. handle WordPress notifications
 * 3. Load them when the bell icon is clicked
 * 3. update notification using heartbeat API
 */
