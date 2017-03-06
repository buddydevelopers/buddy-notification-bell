<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://buddydevelopers.com
 * @since             1.0.0
 * @package           Buddy_Notification_Bell
 *
 * @wordpress-plugin
 * Plugin Name:     Buddy Notification Bell
 * Plugin URI:        http://buddydevelopers.com
 * Description:       This plugin convert buddypress notification to buddypress Notification bell. It show all notification with bell alert and anywhere you want with just one shortcode.
 * Version:           1.0.0
 * Author:            buddydevelopers
 * Author URI:        http://buddydevelopers.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       buddy-notification-bell
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



if ( ! defined( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL' ) ) {
	define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL',  plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH' ) ) {
	define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH',  plugin_dir_path( __FILE__ ) );
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-notification-bell-public.php';
$instance = Buddy_Notification_Bell_Public::get_instance();
