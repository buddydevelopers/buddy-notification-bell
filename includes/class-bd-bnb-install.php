<?php
/**
 * Handles plugin activation and sets default options.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Install {

	/**
	 * Runs on plugin activation. Sets defaults without overwriting existing values.
	 */
	public static function activate() {
		$defaults = array(
			'bnb_sound_enabled'      => 'yes',
			'bnb_bell_position'      => 'right',
			'bnb_show_count'         => 'yes',
			'bnb_floating_bell'      => '',
			'bnb_notification_style' => 'individual',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
