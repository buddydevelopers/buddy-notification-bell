<?php
/**
 * Core loader — includes all plugin classes and boots them.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Core {

	public static function init() {
		require_once BNB_PATH . 'includes/class-bd-bnb-manager.php';
		require_once BNB_PATH . 'includes/class-bd-bnb-ajax.php';
		require_once BNB_PATH . 'includes/class-bd-bnb-bell.php';
		require_once BNB_PATH . 'includes/class-bd-bnb-settings.php';

		BD_BNB_Ajax::init();
		BD_BNB_Bell::init();
		BD_BNB_Settings::init();
	}
}
