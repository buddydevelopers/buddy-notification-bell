<?php
/**
 * This file is the functionality loader.
 * It will include plugin setting file and call the proper files based on the setting.
 *
 * @since 2.0.0
 * @package BNB\Loader
 */

namespace BNB\Loader;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Load BNB functionality 
 * 
 * @package    BNB
 * @subpackage BNB/Loader
 * @author     buddydevelopers <buddydevelopers@gmail.com> 
 * @since 2.0.0
 */
class Loader {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->include();
	}
	
	/**
	 * Include all the required files. Ex. Plugin settings
	 */
	private function include() {
		// include others files here.
		// Load the create CPT file
		require_once BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . 'src/admin/class-admin-notification-settings.php';
		// require_once BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . 'src/admin/class-admin-broadcast-notification.php';
		require_once BUDDY_NOTIFICATION_BELL_PLUGINS_PATH . 'src/public/class-notification-bell-public.php';
	}
}
$instance = new Loader();
/**
 * setting page 
 * add notification bell menu in frontend
 * handle BuddyPress notification
 */
