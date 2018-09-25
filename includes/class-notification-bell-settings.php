<?php

/**
 * Settings of the plugin.
 *
 * @link       http://buddydevelopers.com
 * @since      1.0.0
 *
 * @package    Buddy_Notification_Bell
 * @subpackage Buddy_Notification_Bell/Settings
 */

/**
 * Class used to create Settings of the plugin.
 *
 * @package    Buddy_Notification_Bell
 * @subpackage Buddy_Notification_Bell/settings
 * @author     buddydevelopers <buddydevelopers@gmail.com>
 */
class Buddy_Notification_Bell_Settings {


	/**
	 * Single ton pattern instance reuse.
	 *
	 * @access  private
	 *
	 * @var object  $_instance class instance.
	 */
	private static $_instance;

	/**
	 * GET Instance
	 *
	 * Function help to create class instance as per singleton pattern.
	 *
	 * @return object  $_instance
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'buddy_notification_plugin_menu' ) );
	}

	/**
	 * Add Buddy Notifications bell setting menu.
	 */
	public function buddy_notification_plugin_menu() {
		add_options_page( 
			'Buddy Notifications Bell',
			'Buddy Bell',
			'manage_options',
			'buddy-notifications.php',
			array( $this, 'buddy_notifications_setttings_menu' )
		);
	}

	/**
	 * Buddy Notifications Setting menu. 
	 */
	public function buddy_notifications_setttings_menu(){
		?>
		<div class="wrap">
			<h1>Buddy Notification Bell Settings </h1>
			<?php 
				if( isset( $_POST['save_settings'] ) ){
					$make_default_visible = ( isset( $_POST['make_default_visible'] ) && !empty( $_POST['make_default_visible'] ) )? $_POST['make_default_visible']: '';
					update_option( 'make_default_visible', $make_default_visible );

				}
				$make_default_visible = get_option( 'make_default_visible' );

			?>
			<form method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label>Disable Default Bell Menu</label>
							</th>
							<td>
								<input type="checkbox" name="make_default_visible" value="yes" <?php echo ( isset( $make_default_visible ) && !empty( $make_default_visible ) && 'yes' === $make_default_visible )? 'checked': ''; ?>>
							</td>
						</tr>
						<tr>
							<td>
								<p class="submit">
									<input type="submit" class='button button-primary' name="save_settings" value="Save Changes">
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<?php
	}
}