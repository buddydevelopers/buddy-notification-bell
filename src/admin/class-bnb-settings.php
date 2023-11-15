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
class BNB_Settings {
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
		$this->add_tabs();
		$this->load_assets();
	}

	/**
	 * Add settings tabs
	 */
	public function add_tabs() {
		add_action( 'buddy_bnb_notifications_tab', array( $this, 'notifications_tab' ), 10, 1 );
		add_action( 'buddy_bnb_general_tab', array( $this, 'general_tab' ), 10, 1 );
	}

	/**
	 * Load needed assets
	 */
	public function load_assets() {
		add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_style' ) );
	}

	/**
	 * Enqueue style
	 */
	public function enqueue_style(){
		wp_enqueue_style( 'buddy-admin-style', BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'src/admin/css/style.css' );
	}

	/**
	 * Notification tab callback
	 */
	public function notifications_tab( $tab ) {
		$section  = isset( $_GET['section'] ) ? $_GET['section'] : '';
		$active_class = ( $key === $section || ( empty( $section ) && 'general' === $key ) ) ? 'nav-tab-active' : '';
		$sub_tabs = array(
			''  => __( 'General', "buddy-notification-bell" ),
			// 'buddypress'     => __( 'BuddyPress', "buddy-notification-bell" ),
		);
		?>
		<div class="clearfix d-flex align-content-center flex-wrap">
			<ul class="subsubsub m-0 p-0">
				<?php
				foreach( $sub_tabs as $key => $value ) {
					$active_class = ( $key === $section || ( empty( $section ) && 'general' === $key ) ) ? 'current' : '';
					echo '<li><a href="?page=bnb_notification&tab=' . $tab . '&section=' . $key . '" class="' . $active_class . '">' . $value . '</a> | </li>';
				}
				?>
			</ul>
			<div class="buddy-bnb-sub-tab-content">
				<?php
				do_action( 'buddy_bnb_' . $section . '_sub_tab', $tab );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Notification tab callback
	 */
	public function general_tab( $tab ) {
		echo __( 'General', 'buddy-notification-bell' ); // Put your HTML here
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
			__( 'Buddy Notification Bell', 'buddy-notification-bell' ),
			__( 'Notification Bell', 'buddy-notification-bell' ),
			'manage_options',
			'bnb_notification',
			array( $this, 'add_settings' ),
			'dashicons-bell',
			20
		);
	}

	/**
	 *  Add Notification menu callback
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
				submit_button( __( 'Save Settings', 'buddy-notification-bell' ) );
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
			<?php
			// check user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Get the active tab from the $_GET param
			$default_tab = 'general';
			$tab         = isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;

			?>
			<!-- Here are our tabs -->
			<?php
			$tabs = array(
				'general'  => __( 'General', "buddy-notification-bell" ),
				'notifications'     => __( 'Notifications', "buddy-notification-bell" ),
			);
			?>
			<nav class="nav-tab-wrapper">
				<?php
				foreach( $tabs as $key => $value ) {
					$active_class = ( $key === $tab || ( empty( $tab ) && 'general' === $key ) ) ? 'nav-tab-active' : '';
					echo '<a href="?page=bnb_notification&tab=' . esc_attr( $key ) . '" class="nav-tab ' . $active_class . '">' . esc_attr( $value ) . '</a>';
				}
				?>
			</nav>
			<form method="post" id="buddy-bnb-settings" action="">
				<div class="buddy-bnb-tab-content">
					<?php
					do_action( 'buddy_bnb_' . $tab . '_tab', $tab );
					?>
				</div>
			</form>
		</div>
		<?php
	}
}
$instance = new BNB_Settings();
$instance->init();
