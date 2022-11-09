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
 * Plugin Name: Buddy Notification Bell
 * Plugin URI:  http://buddydevelopers.com
 * Description: WordPress Notifications Management system. This plugins shows you all types of WordPress and WordPress plugins notifications like facebook/twitter does.
 * Version:     2.0.0
 * Author:      BuddyDevelopers
 * Author URI:  http://buddydevelopers.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: buddy-notification-bell
 * Domain Path: /languages
 */

/**
 * This file is the main file of plugin that define constant and call the loader.
 *
 * @since 2.0.0
 * @package bnb
 */
// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
if ( ! defined( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL' ) ) {
	define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL',  plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH' ) ) {
	define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH',  plugin_dir_path( __FILE__ ) );
}

// Load the plugin files
require_once BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . 'src/class-loader.php';

// add install and uninstall operation.
// add locel files rendering.