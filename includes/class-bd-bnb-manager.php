<?php
/**
 * Notification fetching and formatting.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Manager {

	/**
	 * Returns the highest notification ID currently unread for the given user.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function get_latest_notification_id( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		global $wpdb;

		$table      = buddypress()->notifications->table_name;
		$components = self::get_components_placeholders();

		if ( empty( $components['placeholders'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT MAX(id) FROM `{$table}` WHERE user_id = %d AND component_name IN ( {$components['placeholders']} ) AND is_new = %d",
			array_merge( array( $user_id ), $components['values'], array( 1 ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Returns all unread notifications newer than $last_id for the given user.
	 *
	 * @param int $user_id
	 * @param int $last_id
	 * @return array Raw notification objects from DB.
	 */
	public static function get_new_notifications( $user_id, $last_id ) {
		global $wpdb;

		$table      = buddypress()->notifications->table_name;
		$components = self::get_components_placeholders();

		if ( empty( $components['placeholders'] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE user_id = %d AND component_name IN ( {$components['placeholders']} ) AND id > %d AND is_new = %d",
			array_merge( array( $user_id ), $components['values'], array( absint( $last_id ), 1 ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query );
	}

	/**
	 * Returns the total unread notification count for the given user.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function get_unread_count( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		global $wpdb;

		$table      = buddypress()->notifications->table_name;
		$components = self::get_components_placeholders();

		if ( empty( $components['placeholders'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table}` WHERE user_id = %d AND component_name IN ( {$components['placeholders']} ) AND is_new = %d",
			array_merge( array( $user_id ), $components['values'], array( 1 ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Returns all current unread notifications for the given user as raw DB rows.
	 *
	 * @param int $user_id
	 * @return array Raw notification objects.
	 */
	public static function get_all_notifications( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		return self::get_raw_unread_notifications( $user_id );
	}

	/**
	 * Queries unread notification rows directly from the DB.
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @return array
	 */
	private static function get_raw_unread_notifications( $user_id, $limit = 20 ) {
		global $wpdb;

		$table      = buddypress()->notifications->table_name;
		$components = self::get_components_placeholders();

		if ( empty( $components['placeholders'] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE user_id = %d AND component_name IN ( {$components['placeholders']} ) AND is_new = %d ORDER BY date_notified DESC LIMIT %d",
			array_merge( array( $user_id ), $components['values'], array( 1, $limit ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (array) $wpdb->get_results( $query );
	}

	/**
	 * Formats raw notification objects into a structured array for JS.
	 *
	 * @param array $notifications
	 * @return array Each item: { id, text, href, time_diff, avatar_url, is_new }
	 */
	public static function format_notifications( $notifications ) {
		$formatted  = array();
		$notify_url = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		foreach ( (array) $notifications as $notification ) {
			$description = self::get_notification_description( $notification );
			$extracted   = self::extract_description( $description, $notify_url );
			$text        = $extracted['text'];
			$href        = $extracted['href'];

			if ( empty( trim( $text ) ) ) {
				$text = __( 'You have a new notification', 'buddy-notification-bell' );
			}

			$time_diff = sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'buddy-notification-bell' ),
				human_time_diff( strtotime( $notification->date_notified ), time() )
			);

			// Best-effort avatar: secondary_item_id is often the acting user in BP.
			$avatar_url = self::get_notification_avatar( $notification );

			$formatted[] = array(
				'id'         => (int) $notification->id,
				'text'       => wp_strip_all_tags( $text ),
				'href'       => esc_url_raw( $href ),
				'time_diff'  => $time_diff,
				'avatar_url' => $avatar_url,
				'is_new'     => (int) $notification->is_new,
			);
		}

		return $formatted;
	}

	/**
	 * Returns an avatar URL for a notification, using the acting user when possible.
	 *
	 * @param object $notification
	 * @return string
	 */
	private static function get_notification_avatar( $notification ) {
		$actor_id = ! empty( $notification->secondary_item_id ) ? (int) $notification->secondary_item_id : 0;

		// Verify it's actually a user before using as avatar source.
		if ( $actor_id && get_userdata( $actor_id ) ) {
			if ( function_exists( 'bp_core_fetch_avatar' ) ) {
				return bp_core_fetch_avatar( array(
					'item_id' => $actor_id,
					'object'  => 'user',
					'type'    => 'thumb',
					'html'    => false,
				) );
			}
			return get_avatar_url( $actor_id, array( 'size' => 48 ) );
		}

		// Fall back to the notification recipient's avatar.
		return get_avatar_url( (int) $notification->user_id, array( 'size' => 48 ) );
	}

	/**
	 * Groups raw notifications by component_action and returns one item per group.
	 * When a group has multiple items, the BP callback is called with the actual count
	 * so it returns plural text (e.g. "3 friendship requests").
	 *
	 * @param array $notifications Raw notification objects from DB.
	 * @return array Each item: { id, text, href, time_diff, avatar_url, is_new }
	 */
	public static function format_notifications_grouped( $notifications ) {
		$groups     = array();
		$notify_url = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		foreach ( (array) $notifications as $notification ) {
			$key = $notification->component_name . ':' . $notification->component_action;
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'latest' => $notification,
					'count'  => 0,
				);
			}
			$groups[ $key ]['count']++;
		}

		$formatted = array();
		foreach ( $groups as $group ) {
			$notification = $group['latest'];
			$count        = $group['count'];

			$description = self::get_notification_description( $notification, $count );
			$extracted   = self::extract_description( $description, $notify_url );
			$text        = $extracted['text'];
			$href        = $extracted['href'];

			if ( empty( trim( $text ) ) ) {
				$text = __( 'You have a new notification', 'buddy-notification-bell' );
			}

			$time_diff = sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'buddy-notification-bell' ),
				human_time_diff( strtotime( $notification->date_notified ), time() )
			);

			$formatted[] = array(
				'id'         => (int) $notification->id,
				'text'       => wp_strip_all_tags( $text ),
				'href'       => esc_url_raw( $href ),
				'time_diff'  => $time_diff,
				'avatar_url' => self::get_notification_avatar( $notification ),
				'is_new'     => 1,
			);
		}

		return $formatted;
	}

	/**
	 * Resolves a notification object into a human-readable description.
	 * Mirrors BuddyPress's own bp_get_the_notification_description().
	 *
	 * @param object $notification
	 * @param int    $count Total items in this group (>1 triggers plural text from BP callbacks).
	 * @return string|array
	 */
	public static function get_notification_description( $notification, $count = 1 ) {
		$bp = buddypress();

		if (
			isset( $bp->{ $notification->component_name }->notification_callback ) &&
			is_callable( $bp->{ $notification->component_name }->notification_callback )
		) {
			$description = call_user_func(
				$bp->{ $notification->component_name }->notification_callback,
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				$count
			);
		} elseif (
			isset( $bp->{ $notification->component_name }->format_notification_function ) &&
			function_exists( $bp->{ $notification->component_name }->format_notification_function )
		) {
			$description = call_user_func(
				$bp->{ $notification->component_name }->format_notification_function,
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				$count
			);
		} else {
			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			$description = apply_filters_ref_array(
				'bp_notifications_get_notifications_for_user',
				array(
					$notification->component_action,
					$notification->item_id,
					$notification->secondary_item_id,
					$count,
					'string',
					$notification->component_action,
					$notification->component_name,
					$notification->id,
				)
			);
		}

		/** This filter is documented in bp-notifications/bp-notifications-template.php */
		return apply_filters( 'bp_get_the_notification_description', $description, $notification );
	}

	/**
	 * Extracts text and a direct href from a BP notification description.
	 *
	 * BP components return descriptions in three formats:
	 *   - array  ['text' => '...', 'link' => 'url']
	 *   - object ->text / ->link
	 *   - HTML string  '<a href="url">text</a>'  (legacy callbacks)
	 *
	 * @param mixed  $description
	 * @param string $fallback_url Used when no direct link can be found.
	 * @return array { text: string, href: string }
	 */
	private static function extract_description( $description, $fallback_url ) {
		if ( is_array( $description ) ) {
			return array(
				'text' => isset( $description['text'] ) ? (string) $description['text'] : '',
				'href' => ! empty( $description['link'] ) ? $description['link'] : $fallback_url,
			);
		}

		if ( is_object( $description ) ) {
			return array(
				'text' => isset( $description->text ) ? (string) $description->text : '',
				'href' => ! empty( $description->link ) ? $description->link : $fallback_url,
			);
		}

		// HTML string — pull href out of the first anchor tag.
		$href = $fallback_url;
		if ( is_string( $description ) && false !== strpos( $description, 'href=' ) ) {
			preg_match( '/href=["\']([^"\']+)["\']/', $description, $matches );
			if ( ! empty( $matches[1] ) ) {
				$href = html_entity_decode( $matches[1] );
			}
		}

		return array(
			'text' => wp_strip_all_tags( (string) $description ),
			'href' => $href,
		);
	}

	/**
	 * Returns SQL placeholders and values for registered BP components.
	 *
	 * @return array { placeholders: string, values: array }
	 */
	private static function get_components_placeholders() {
		$components   = bp_notifications_get_registered_components();
		$placeholders = array();
		$values       = array();

		foreach ( $components as $component ) {
			$placeholders[] = '%s';
			$values[]       = $component;
		}

		return array(
			'placeholders' => implode( ', ', $placeholders ),
			'values'       => $values,
		);
	}
}
