<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes all plugin options from the database.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'make_default_visible' );
delete_option( 'bnb_sound_enabled' );
delete_option( 'bnb_bell_position' );
delete_option( 'bnb_show_count' );
delete_option( 'bnb_floating_bell' );
delete_option( 'bnb_notification_style' );
