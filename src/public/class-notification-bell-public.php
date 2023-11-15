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
		add_filter( 'wp_nav_menu_items', array( $this, 'bnb_add_notification_bell_menu_item' ), 10, 2 );
		add_action( 'comment_post', array( $this, 'bnb_insert_new_commentdata' ), 10, 3 );
		add_action( 'transition_comment_status', array( $this, 'bnb_transition_comment_status' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'buddy-bnb-style', BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'src/public/css/style.css' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'buddy-bnb-notify-realtime-script', BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'src/public/js/notify-realtime.js', array( 'jquery', 'heartbeat' ) );
		wp_enqueue_script( 'buddy-bnb-script', BUDDY_NOTIFICATION_BELL_PLUGINS_URL . 'src/public/js/script.js', array( 'jquery' ) );
	}
	
	/**
	 * Add comment notification in table when a comment is marked as approved.
	 *
	 * @param  string $new_status New comment status
	 * @param  string $old_status Old comment status
	 * @param  array $commentdata All comment data
	 */
	public function bnb_transition_comment_status( $new_status, $old_status, $commentdata ) {
		if ( $old_status != $new_status ) {
			if ( $new_status == 'approved' ) {
				// Convert the comment object into an array
				$comment_array = array(
					'comment_ID'            => $commentdata->comment_ID,
					'comment_post_ID'       => $commentdata->comment_post_ID,
					'comment_author'        => $commentdata->comment_author,
					'comment_author_email'  => $commentdata->comment_author_email,
					'comment_author_url'    => $commentdata->comment_author_url,
					'comment_content'       => $commentdata->comment_content,
					'comment_type'          => $commentdata->comment_type,
					'comment_parent'        => $commentdata->comment_parent,
					'comment_approved'      => $commentdata->comment_approved,
					'comment_date'          => $commentdata->comment_date,
					'new_status'            => $new_status,
					'old_status'            => $old_status,
				);
				$comment_approved = 1;
				$this->bnb_insert_new_commentdata( $commentdata->comment_ID, $comment_approved, $comment_array );
			}
		}
	}
	/**
	 * Add notification when a new comment is added on a post
	 *
	 * @param  int $comment_ID Comment ID
	 * @param  int $comment_approved Comment approved status
	 * @param  array $commentdata All comment data
	 */
	function bnb_insert_new_commentdata( $comment_ID, $comment_approved, $commentdata ) {
		error_log( print_r($commentdata, true ));
		if ( 1 === $comment_approved ) {
			global $wpdb;

			$component_name   = $commentdata['comment_parent'] == 0 ? 'comment' : 'reply';
			$component_action = $component_name == 'comment' ? 'new_comment' : 'new_reply';
			$table            = $wpdb->prefix . 'bnb_notifications';
			if ( $commentdata['comment_parent'] == 0 ) {
				// get post author id  and consider it as notification user_id
				$comment_post = get_post( $commentdata['comment_post_ID'] );
				$author_id    = $comment_post->post_author;
			} else {
				// get author id by parent comment id.
				$comment   = get_comment( intval( $commentdata['comment_parent'] ) );
				$author_id = $comment->user_id;
			}

			$data = array(
				'user_id'          => $author_id,
				'item_id'          => $commentdata['comment_post_ID'],
				'secondary_item_id'=> $comment_ID,
				'component_name'   => $component_name,
				'component_action' => $component_action,
				'date_notified'    => $commentdata['comment_date'],
				'is_new'           => 1,
			);
			$format = array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
			);

			$wpdb->insert( $table, $data, $format );
		}
	}

	/**
	 * Function to add buddy bell as a menu item.
	 *
	 * @param  string $items menu items
	 * @param  array $args  menu arguments
	 *
	 * @return string $items menu items with buddy notifications bell.
	 */
	public function bnb_add_notification_bell_menu_item ( $items, $args ) {
		$disable_default_visible = get_option( 'make_default_visible', 1 );
		$theme_location = apply_filters( 'buddy_theme_location', 'primary' );
		if ( $args->theme_location == $theme_location && 'yes' !== $disable_default_visible ) {
			$items .= '<li class="notification-bell-menu">' . $this->bnb_notifications_lists_dropdown_menu() . '</li>';
		}
		return $items;
	}

	/**
	 * Function to show notification bell with notification count.
	 */
	public function bnb_notifications_lists_dropdown_menu() {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$notifications_count = $this->bnb_user_notification_count(); 
		$alert_class   = (int) $notifications_count > 0 ? 'bnb-pending-count bnb-alert' : 'bnb-count bnb-no-alert';
		$menu_title    = '<div class="bnb-pending-notifications ' . $alert_class . '">' . apply_filters( 'buddy_bell_icon', '<svg width="20" height="20" class="wnbell_icon" aria-hidden="true" focusable="false" data-prefix="far" data-icon="bell" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
			<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM67.53 368c21.22-27.97 44.42-74.33 44.53-159.42 0-.2-.06-.38-.06-.58 0-61.86 50.14-112 112-112s112 50.14 112 112c0 .2-.06.38-.06.58.11 85.1 23.31 131.46 44.53 159.42H67.53zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z">
			</path></svg>' ) . '<span>' . number_format_i18n( $notifications_count ) . '</span></div>';

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

	/**
	 * Return current user unread notification count
	 * 
	 * @return int $notification_count Notification count
	 */
	public function bnb_user_notification_count(){
		$user_id = get_current_user_id();
		global $wpdb;
		$table = $wpdb->prefix . 'bnb_notifications';
		$query = "SELECT Count(*) FROM {$table} WHERE user_id = %d AND is_new = %d";
		$query = $wpdb->prepare( $query, $user_id, 1 );
		return (int) $wpdb->get_var( $query );
	}
}

new Buddy_Notification_Bell_Public();
