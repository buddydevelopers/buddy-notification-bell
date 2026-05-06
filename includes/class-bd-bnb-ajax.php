<?php
/**
 * Heartbeat handler and AJAX endpoints.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Ajax {

	public static function init() {
		add_filter( 'heartbeat_received', array( __CLASS__, 'process_heartbeat' ), 10, 2 );
		add_action( 'wp_ajax_bnb_mark_all_read', array( __CLASS__, 'mark_all_read' ) );
		add_action( 'wp_ajax_bnb_get_notifications', array( __CLASS__, 'get_notifications' ) );
		add_action( 'wp_ajax_bnb_get_count', array( __CLASS__, 'get_count' ) );
		add_action( 'wp_ajax_bnb_dismiss_notification', array( __CLASS__, 'dismiss_notification' ) );
	}

	/**
	 * Inject new notification data into the Heartbeat response.
	 *
	 * @param array $response
	 * @param array $data
	 * @return array
	 */
	public static function process_heartbeat( $response, $data ) {
		if ( ! isset( $data['bnb-data'] ) || ! is_user_logged_in() ) {
			return $response;
		}

		$request       = $data['bnb-data'];
		$last_id       = isset( $request['last_notified'] ) ? absint( $request['last_notified'] ) : 0;
		$user_id       = bp_loggedin_user_id();

		// Notifications — items newer than the last known ID.
		$notifications = BD_BNB_Manager::get_new_notifications( $user_id, $last_id );
		$formatted     = BD_BNB_Manager::format_notifications( $notifications );
		$ids           = wp_list_pluck( $notifications, 'id' );
		$ids[]         = $last_id;
		$last_id       = ! empty( $ids ) ? max( $ids ) : $last_id;

		// Always return the current unread notification count so the badge stays in sync.
		$total_count = BD_BNB_Manager::get_unread_count( $user_id );

		// Messages — JS handles "is new" detection; we just return the current count.
		$message_count = -1;
		if ( function_exists( 'messages_get_unread_count' ) && bp_is_active( 'messages' ) ) {
			$message_count = (int) messages_get_unread_count( $user_id );
		}

		$response['bnb-data'] = array(
			'messages'      => $formatted,
			'last_notified' => $last_id,
			'total_count'   => $total_count,
			'message_count' => $message_count,
		);

		return $response;
	}

	/**
	 * AJAX: return all unread notifications as a JSON array.
	 *
	 * @return void Sends JSON response.
	 */
	public static function get_notifications() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$raw   = BD_BNB_Manager::get_all_notifications( bp_loggedin_user_id() );
		$style = get_option( 'bnb_notification_style', 'individual' );

		if ( 'grouped' === $style ) {
			$formatted = BD_BNB_Manager::format_notifications_grouped( $raw );
		} else {
			$formatted = BD_BNB_Manager::format_notifications( $raw );
		}

		wp_send_json_success( $formatted );
	}

	/**
	 * AJAX: return the current unread count.
	 *
	 * @return void Sends JSON response.
	 */
	public static function get_count() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$user_id  = bp_loggedin_user_id();
		$response = array(
			'count' => BD_BNB_Manager::get_unread_count( $user_id ),
		);

		if ( function_exists( 'messages_get_unread_count' ) && bp_is_active( 'messages' ) ) {
			$response['message_count'] = (int) messages_get_unread_count( $user_id );
		}

		wp_send_json_success( $response );
	}

	/**
	 * AJAX: mark a single notification as read.
	 *
	 * @return void Sends JSON response.
	 */
	public static function dismiss_notification() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$notification_id = absint( $_POST['notification_id'] ?? 0 );
		$user_id         = bp_loggedin_user_id();

		if ( empty( $notification_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notification.', 'buddy-notification-bell' ) ) );
		}

		global $wpdb;

		// Verify the notification belongs to this user before updating.
		$table = buddypress()->notifications->table_name;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'is_new' => 0 ),
			array(
				'id'      => $notification_id,
				'user_id' => $user_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		wp_send_json_success();
	}

	/**
	 * AJAX: mark all notifications as read for the current user.
	 *
	 * @return void Sends JSON response.
	 */
	public static function mark_all_read() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'buddy-notification-bell' ) ) );
		}

		$user_id = bp_loggedin_user_id();
		if ( empty( $user_id ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$table = buddypress()->notifications->table_name;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'is_new' => 0 ),
			array( 'user_id' => $user_id, 'is_new' => 1 ),
			array( '%d' ),
			array( '%d', '%d' )
		);

		wp_send_json_success();
	}
}
