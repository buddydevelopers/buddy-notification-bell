<?php
/**
 * Plugin Name:     Buddy Notification Bell
 * Plugin URI:      https://buddydevelopers.com
 * Description:     Converts BuddyPress notifications into a Facebook-style real-time notification bell with sound alerts.
 * Version:         2.0.0
 * Author:          buddydevelopers
 * Author URI:      https://buddydevelopers.com
 * License:         GPL-2.0+
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     buddy-notification-bell
 * Domain Path:     /languages
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BNB_VERSION', '2.0.0' );
define( 'BNB_FILE', __FILE__ );
define( 'BNB_PATH', plugin_dir_path( __FILE__ ) );
define( 'BNB_URL', plugin_dir_url( __FILE__ ) );

// Keep old constants for backward compat (themes may reference these).
define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL', BNB_URL );
define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH', BNB_PATH );

require_once BNB_PATH . 'includes/class-bd-bnb-install.php';
register_activation_hook( BNB_FILE, array( 'BD_BNB_Install', 'activate' ) );

/**
 * Boot the plugin once BuddyPress is loaded.
 */
function bd_bnb_init() {
	if ( ! function_exists( 'buddypress' ) ) {
		return;
	}

	$bp = buddypress();
	if ( ! isset( $bp->notifications ) || empty( $bp->notifications ) ) {
		return;
	}

	require_once BNB_PATH . 'includes/class-bd-bnb-core.php';
	BD_BNB_Core::init();
}
add_action( 'bp_include', 'bd_bnb_init' );

/**
 * Show admin notice when BuddyPress is not active.
 */
function bnb_admin_notice() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' .
			esc_html__( 'Buddy Notification Bell requires BuddyPress to be activated.', 'buddy-notification-bell' ) .
			'</p></div>';
	}
}
add_action( 'admin_notices', 'bnb_admin_notice' );

/**
 * Add Settings and Hire Us links on the plugins list page.
 */
function bd_bnb_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=buddy-notification-bell' ) ) . '">' . esc_html__( 'Settings', 'buddy-notification-bell' ) . '</a>';
	$links[] = '<a href="' . esc_url( 'https://buddydevelopers.com' ) . '" target="_blank">' . esc_html__( 'Hire Us', 'buddy-notification-bell' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bd_bnb_plugin_action_links' );

/**
 * Load plugin translations.
 */
function bd_bnb_load_textdomain() {
	load_plugin_textdomain( 'buddy-notification-bell', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'bd_bnb_load_textdomain' );
