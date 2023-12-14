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
		add_shortcode( 'buddy_notification_bell', array( $this, 'jingle_bells_notifications_toolbar_menu' ) );
		
		add_action( 'wp_enqueue_scripts',array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts',array( $this, 'enqueue_scripts' ) );
		add_filter( 'heartbeat_received', array( $this, 'bnb_process_notification_request' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'add_js_global' ) );
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
	function place_buddy_notification_bell ( $items, $args ) {
		$disable_default_visible = get_option( 'make_default_visible' );
		$theme_location = apply_filters('buddy_theme_location', 'primary');
	    if ( $args->theme_location == $theme_location && 'yes' !== $disable_default_visible ) {
	        $items .= '<div class="notification-bell-menu">'. $this->jingle_bells_notifications_toolbar_menu() .'</div>';
	    }
	    return $items;
	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'buddy-style', BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'assets/css/style.css' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'buddy-script',BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'assets/js/script.js',array( 'jquery', 'heartbeat' ) );
	}

	public function get_js_settings() {

		return apply_filters( 'bnb_get_js_settings', array(
				'last_notified' => $this->bnb_get_latest_notification_id(),//please do not change last_notified as we use it to filter the new notifications
		));
	}


	/**
	 * Add global bpln object
	 */
	public function add_js_global() {
		?>
		<script type="text/javascript">
			var bnb = <?php echo json_encode( $this->get_js_settings() );?>;
		</script>
		<audio id="buzzer" src="<?php echo BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'assets/sounds/Pling-bell.mp3';?>" type="audio/mp3"></audio>
	<?php
	}

	/**
	 * Get the last notification id for the user
	 *
	 * @global wpdb $wpdb
	 * @param int $user_id
	 * @return int notification_id
	 */
	function bnb_get_latest_notification_id( $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;

		$bp = buddypress();

		$table = $bp->notifications->table_name;

		$registered_components = bp_notifications_get_registered_components();

		$components_list = array();

		foreach ( $registered_components as $component ) {
			$components_list[] = $wpdb->prepare( '%s', $component );
		}

		$components_list = implode( ',', $components_list );

		$query = "SELECT MAX(id) FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND is_new = %d ";

		$query = $wpdb->prepare( $query, $user_id, 1 );

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Function to show notification bell with notification count.
	 */
	public  function jingle_bells_notifications_toolbar_menu() {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
		// var_dump($notifications);
		$count         = ! empty( $notifications ) ? count( $notifications ) : 0;
		$alert_class   = (int) $count > 0 ? 'bnb-pending-count bnb-alert' : 'bnb-count bnb-no-alert';
		$hide_count = (int) $count <= 0 ? 'style="display:none"': '';
		$menu_title    = '<div class="bnb-pending-notifications ' . $alert_class . '">' . apply_filters( 'buddy_bell_icon', '<svg width="30" height="30" class="wnbell_icon" aria-hidden="true" focusable="false" data-prefix="far" data-icon="bell" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
			<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM67.53 368c21.22-27.97 44.42-74.33 44.53-159.42 0-.2-.06-.38-.06-.58 0-61.86 50.14-112 112-112s112 50.14 112 112c0 .2-.06.38-.06.58.11 85.1 23.31 131.46 44.53 159.42H67.53zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z">
			</path></svg>' ) . '<span ' . $hide_count . '>' . number_format_i18n( $count ) . '</span></div>';
		$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		$output = apply_filters( 'buddy_notification_output', '', $notifications );
		if( $output ) return $output;

		ob_start();?>
        <div class='bell_notification_container'>
            <div class='notification_bell'><?php echo $menu_title;?></div>
            <div class='notifications_lists_container'>
                <div class='notifications_lists'>
					<?php if ( ! empty( $notifications ) ) {?>
						<?php foreach ( (array) $notifications as $notification ) { ?>
                            <div>
                                <a href='<?php echo $notification->href ;?>' class='bnb-notification-text'><?php echo $notification->content;?></a>
                            </div>
						<?php }?>
					<?php } else {?>
                        <div class="no-new-notifications">
                            <a href='<?php echo $menu_link;?>' class='bnb-notification-text'><?php echo __('No new notifications', 'buddy-notification-bell'); ?></a>
                        </div>
					<?php }?>
                </div>
            </div>
        </div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Filter on the heartbeat recieved data and inject the new notifications data
	 *
	 * @param array $response
	 * @param array $data
	 * @param int $screen_id
	 * @return array response
	 */
	function bnb_process_notification_request( $response, $data, $screen_id ) {
        
		if ( isset( $data['bnb-data'] ) ) {

			$notifications = array();
			$notification_ids = array();

			$request = $data['bnb-data'];

			$last_notified_id = absint( $request['last_notified'] );

			if ( ! empty( $request ) ) {

				$notifications = $this->bnb_get_new_notifications( get_current_user_id(),  $last_notified_id );

				$notification_ids = wp_list_pluck( $notifications, 'id' );

				$notifications = $this->bnb_get_notification_messages( $notifications );

			}
			//include our last notified id to the list
			$notification_ids[] = $last_notified_id;
			//find the max id that we are sending with this request
			$last_notified_id = max( $notification_ids );

			$response['bnb-data'] = array( 'messages' => $notifications, 'last_notified' => $last_notified_id );

	    }
	    return $response;
	}


	/**
	 * Get all new notifications after a given time for the current user
	 *
	 * @global array $wpdb
	 * @param int $user_id
	 * @param int $last_notified
	 * @return array notification data array
	 */

	function bnb_get_new_notifications( $user_id, $last_notified ) {

		global $wpdb;

		$bp = buddypress();

		$table = $bp->notifications->table_name;

		$registered_components = bp_notifications_get_registered_components();

		$components_list = array();

		foreach ( $registered_components as $component ) {
			$components_list[] = $wpdb->prepare( '%s', $component );
		}

		$components_list = implode( ',', $components_list );

		$query = "SELECT * FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND id > %d AND is_new = %d ";

		$query = $wpdb->prepare( $query, $user_id, $last_notified, 1 );

		return $wpdb->get_results( $query );
	}

	/**
	 * Get a list of processed messages
	 *
	 */
	function bnb_get_notification_messages( $notifications ) {

		$messages = array();

		if ( empty( $notifications ) ) {
			return $messages;
		}

		$total_notifications = count( $notifications );

		for ( $i = 0; $i < $total_notifications; $i++ ) {

			$notification = $notifications[ $i ];

			$messages[] = $this->bnb_get_the_notification_description( $notification );

		}

		return $messages;
	}

	/**
	 * A copy of bp_get_the_notification_description to server our purpose of parsing notification to extract the message
	 *
	 * @see bp_get_the_notification_description
	 * @param type $notification
	 * @return type
	 */

	function bnb_get_the_notification_description( $notification ) {

		$bp = buddypress();

		// Callback function exists
		if ( isset( $bp->{ $notification->component_name }->notification_callback ) && is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		} elseif ( isset( $bp->{ $notification->component_name }->format_notification_function ) && function_exists( $bp->{ $notification->component_name }->format_notification_function ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->format_notification_function, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

			// Allow non BuddyPress components to hook in
		} else {
			
			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			 $description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array( $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->component_action, $notification->component_name, $notification->id ) );
		}

		/**
		 * Filters the full-text description for a specific notification.
		 *
		 * @since BuddyPress (1.9.0)
		 *
		 * @param string $description Full-text description for a specific notification.
		 */
		return apply_filters( 'bp_get_the_notification_description', $description );
	}

}
