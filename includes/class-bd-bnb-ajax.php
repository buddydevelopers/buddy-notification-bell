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

		$request         = $data['bnb-data'];
		$last_id         = isset( $request['last_notified'] ) ? absint( $request['last_notified'] ) : 0;
		$user_id         = bp_loggedin_user_id();
		$notifications   = BD_BNB_Manager::get_new_notifications( $user_id, $last_id );
		$formatted       = BD_BNB_Manager::format_notifications( $notifications );
		$ids             = wp_list_pluck( $notifications, 'id' );
		$ids[]           = $last_id;
		$last_id         = ! empty( $ids ) ? max( $ids ) : $last_id;

		$response['bnb-data'] = array(
			'messages'      => $formatted,
			'last_notified' => $last_id,
		);

		return $response;
	}

	/**
	 * AJAX: return all unread notifications as a JSON array.
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
	 */
	public static function get_count() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		wp_send_json_success( array(
			'count' => BD_BNB_Manager::get_unread_count( bp_loggedin_user_id() ),
		) );
	}

	/**
	 * AJAX: mark a single notification as read.
	 */
	public static function dismiss_notification() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$notification_id = absint( $_POST['notification_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$user_id         = bp_loggedin_user_id();

		if ( empty( $notification_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notification.', 'buddy-notification-bell' ) ) );
		}

		global $wpdb;

		// Verify the notification belongs to this user before updating.
		$table = buddypress()->notifications->table_name;
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
	 */
	public static function mark_all_read() {
		check_ajax_referer( 'bnb_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'buddy-notification-bell' ) ) );
		}

		bp_notifications_mark_all_for_user( bp_loggedin_user_id() );

		wp_send_json_success();
	}
}
