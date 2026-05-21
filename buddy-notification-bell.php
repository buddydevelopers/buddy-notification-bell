<?php
/**
 * Plugin Name:     Buddy Notification Bell
 * Plugin URI:      https://buddydevelopers.com
 * Description:     Converts BuddyPress notifications into a Facebook-style real-time notification bell with sound alerts.
 * Version:         2.0.2
 * Requires at least: 5.5
 * Tested up to:    7.0
 * Requires PHP:    7.4
 * Author:          buddydevelopers
 * Author URI:      https://buddydevelopers.com
 * License:         GPL-2.0+
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     buddy-notification-bell
 * Domain Path:     /languages
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BNB_VERSION', '2.0.2' );
define( 'BNB_FILE', __FILE__ );
define( 'BNB_PATH', plugin_dir_path( __FILE__ ) );
define( 'BNB_URL', plugin_dir_url( __FILE__ ) );

// Keep old constants for backward compat (themes may reference these).
define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_URL', BNB_URL );
define( 'BUDDY_NOTIFICATION_BELL_PLUGINS_PATH', BNB_PATH );

require_once BNB_PATH . 'includes/class-bd-bnb-install.php';
register_activation_hook( BNB_FILE, array( 'BD_BNB_Install', 'activate' ) );

/**
 * Boot the plugin once BuddyPress is loaded.
 */
function bd_bnb_init() {
	if ( ! function_exists( 'buddypress' ) ) {
		return;
	}

	$bp = buddypress();
	if ( ! isset( $bp->notifications ) || empty( $bp->notifications ) ) {
		return;
	}

	require_once BNB_PATH . 'includes/class-bd-bnb-core.php';
	BD_BNB_Core::init();
}
add_action( 'bp_include', 'bd_bnb_init' );

/**
 * Show a dismissible admin notice when neither BuddyPress nor BuddyBoss
 * Platform is active. BuddyBoss Platform is a BP fork with its own loader
 * path, so both are checked explicitly.
 */
function bd_bnb_admin_notice() {
	if ( get_user_meta( get_current_user_id(), 'bnb_platform_notice_dismissed', true ) ) {
		return;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( 'buddypress/bp-loader.php' ) || is_plugin_active( 'buddyboss-platform/bp-loader.php' ) ) {
		return;
	}

	$dismiss_url = wp_nonce_url(
		add_query_arg( 'bnb_dismiss_notice', '1' ),
		'bnb_dismiss_notice'
	);
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Buddy Notification Bell', 'buddy-notification-bell' ); ?></strong>
			&mdash;
			<?php esc_html_e( 'No community platform detected. Install BuddyPress or BuddyBoss Platform to enable community notification features.', 'buddy-notification-bell' ); ?>
			&nbsp;<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( "Don't show again", 'buddy-notification-bell' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'bd_bnb_admin_notice' );

/**
 * Persist the notice dismissal in user meta when the "Don't show again" link
 * is clicked.
 */
function bd_bnb_handle_notice_dismiss() {
	if ( ! isset( $_GET['bnb_dismiss_notice'] ) ) {
		return;
	}
	check_admin_referer( 'bnb_dismiss_notice' );
	update_user_meta( get_current_user_id(), 'bnb_platform_notice_dismissed', 1 );
	wp_safe_redirect( remove_query_arg( array( 'bnb_dismiss_notice', '_wpnonce' ) ) );
	exit;
}
add_action( 'admin_init', 'bd_bnb_handle_notice_dismiss' );

/**
 * Add Settings and Hire Us links on the plugins list page.
 */
function bd_bnb_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=buddy-notification-bell' ) ) . '">' . esc_html__( 'Settings', 'buddy-notification-bell' ) . '</a>';
	$links[] = '<a href="' . esc_url( 'https://buddydevelopers.com' ) . '" target="_blank">' . esc_html__( 'Hire Us', 'buddy-notification-bell' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bd_bnb_plugin_action_links' );

