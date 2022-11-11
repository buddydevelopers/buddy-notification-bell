<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://buddydevelopers.com
 * @since      1.0.0
 *
 * @package    Buddy_Notification_Bell
 * @subpackage Buddy_Notification_Bell/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Buddy_Notification_Bell
 * @subpackage Buddy_Notification_Bell/public
 * @author     buddydevelopers <buddydevelopers@gmail.com>
 */
class Buddy_Notification_Bell_Public {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Function to initialse the class work.
	 */
	public function init() {
		add_filter( 'wp_nav_menu_items', array( $this, 'place_buddy_notification_bell' ), 10, 2 );
	}

	/**
	 * Function to add buddy bell as a menu item.
	 *
	 * @param  string $items menu items
	 * @param  array $args  menu arguments
	 *
	 * @return string $items menu items with buddy notifications bell.
	 */
	public function place_buddy_notification_bell ( $items, $args ) {
		$disable_default_visible = get_option( 'make_default_visible', 1 );
		$theme_location = apply_filters( 'buddy_theme_location', 'primary' );
	    if ( $args->theme_location == $theme_location && 'yes' !== $disable_default_visible ) {
	        $items .= '<li class="notification-bell-menu">' . $this->jingle_bells_notifications_toolbar_menu() . '</li>';
	    }
	    return $items;
	}

	/**
	 * Function to show notification bell with notification count.
	 */
	public function jingle_bells_notifications_toolbar_menu() {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		// $notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
		// var_dump($notifications);
		$count         = ! empty( $notifications ) ? count( $notifications ) : 0;
		$alert_class   = (int) $count > 0 ? 'bnb-pending-count bnb-alert' : 'bnb-count bnb-no-alert';
		$hide_count = (int) $count <= 0 ? 'style="display:none"': '';
		$menu_title    = '<div class="bnb-pending-notifications ' . $alert_class . '">' . apply_filters( 'buddy_bell_icon', '<svg width="20" height="20" class="wnbell_icon" aria-hidden="true" focusable="false" data-prefix="far" data-icon="bell" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
			<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM67.53 368c21.22-27.97 44.42-74.33 44.53-159.42 0-.2-.06-.38-.06-.58 0-61.86 50.14-112 112-112s112 50.14 112 112c0 .2-.06.38-.06.58.11 85.1 23.31 131.46 44.53 159.42H67.53zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z">
			</path></svg>' ) . '<span ' . $hide_count . '>' . number_format_i18n( $count ) . '</span></div>';
		$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		$output = apply_filters( 'buddy_notification_output', '', $notifications );
		if ( $output ) { return $output; }

		ob_start();?>
    	<div class='bell_notification_container'>
            <div class='notification_bell'><?php echo $menu_title;?></div>
            <div class='notifications_lists_container'>
                <div class='notifications_lists'>
					<!-- Notification lists appears here -->
                </div>
            </div>
        </div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}

new Buddy_Notification_Bell_Public();