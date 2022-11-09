<?php
/**
 * File to create Notification settings page.
 *
 * @since 2.0.0
 * @package BNB\SETTINGS
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;


/**
 * Create Notifications settings page.
 *
 * @package    BNB
 * @subpackage BNB/SETTINGS
 * @author     buddydevelopers <buddydevelopers@gmail.com> 
 * @since 2.0.0
 */
class Admin_Notification_Settings {
    /**
	 * Class constructor
	 */
	public function __construct() {
	}

	/**
	 * Initialize plugin settings related functions
	 */
	public function init() {
		// Add the top level menu with name "WP Notifications".
		$this->add_menu();
	}

	/**
	 * Function to add "WP Notifications" menu
	 */
	public function add_menu() {
		add_action( 'admin_menu', array( $this, 'add_menu_callback' ) );
	}

	/**
	 * Function to add notification top level menu.
	 */
	public function add_menu_callback() {
		add_menu_page(
			__( 'WP Notifications', 'buddy-notification-bell' ),
			__( 'WP Notifications', 'buddy-notification-bell' ),
			'manage_options',
			'bnb_notification',
			array( $this, 'buddy_notification_menu_callback' ),
			'dashicons-bell',
			20
		);
		add_submenu_page(
			'bnb_notification',
			__( 'All Broadcast Notifications', 'buddy-notification-bell' ),
			__( 'All Broadcast', 'buddy-notification-bell' ),
			'manage_options',
			'bnb_notification',
			array( $this, 'broadcast_notifications' )
		);
		add_submenu_page(
			'bnb_notification',
			__( 'Add New', 'buddy-notification-bell' ),
			__( 'Add New', 'buddy-notification-bell' ),
			'manage_options',
			'add_bnb_notification',
			array( $this, 'add_notifications' )
		);
		add_submenu_page(
			'bnb_notification',
			__( 'Settings', 'buddy-notification-bell' ),
			__( 'Settings', 'buddy-notification-bell' ),
			'manage_options',
			'settings_bnb_notification',
			array( $this, 'add_settings' )
		);
	}

	/**
	 *	All Notification menu callback
	 */
	public function broadcast_notifications() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		</div>
		<?php
	}
	/**
	 *	Add Notification menu callback
	 */
	public function add_notifications() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post">
				<?php
				// output security fields for the registered setting "wporg_options"
				settings_fields( 'wporg_options' );
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections( 'wporg' );
				// output save settings button
				submit_button( __( 'Save Settings', 'textdomain' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add BNB settings
	 */
	public function add_settings() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		</div>
		<?php
	}
}
$instance = new Admin_Notification_Settings();
$instance->init();
